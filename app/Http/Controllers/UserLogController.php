<?php

namespace App\Http\Controllers;

use App\Models\UserLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserLogController extends Controller
{
    public function allNotifications(Request $request): View
    {
        $notifications = UserLog::query()
            ->with('actor:id,name')
            ->latest()
            ->paginate(10);

        return view('user_log.allNotifications', [
            'notifications' => $notifications,
        ]);
    }
    public function index(Request $request): View
    {
        [$notifications, $search, $perPage] = $this->buildLogsPaginator($request);

        $userLogs = $this->transformLogs($notifications);

        return view('user_log.index', [
            'notifications' => $userLogs,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        [$notifications, $search, $perPage] = $this->buildLogsPaginator($request);

        return response()->json([
            'success' => true,
            'data' => $this->transformLogs($notifications)->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $perPage,
                'total' => $notifications->total(),
                'from' => $notifications->firstItem() ?? 0,
                'to' => $notifications->lastItem() ?? 0,
                'search' => $search,
            ],
        ]);
    }

    public function apiShow(UserLog $notification): JsonResponse
    {
        $notification->loadMissing('actor:id,name');

        return response()->json([
            'success' => true,
            'data' => $this->transformLogDetail($notification),
        ]);
    }

    public function destroy(UserLog $notification): RedirectResponse
    {
        $notification->delete();

        return redirect()
            ->back()
            ->with('success', 'User log cleared successfully.');
    }

    public function apiDestroy(UserLog $notification): JsonResponse
    {
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'User log cleared successfully.',
        ]);
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        UserLog::query()->delete();

        return redirect()
            ->back()
            ->with('success', 'All user logs cleared successfully.');
    }

    public function apiDestroyAll(Request $request): JsonResponse
    {
        UserLog::query()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All user logs cleared successfully.',
        ]);
    }

    private function buildLogsPaginator(Request $request): array
    {
        $perPage = (int) $request->integer('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->query('q', ''));

        $notifications = UserLog::query()
            ->with('actor:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('actor', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return [$notifications, $search, $perPage];
    }

    private function transformLogs(LengthAwarePaginator $notifications): LengthAwarePaginator
    {
        return $notifications->through(function (UserLog $history) {
            $details = is_array($history->details) ? $history->details : [];
            $changes = collect($details['changes'] ?? []);

            return [
                'id' => $history->id,
                'actioned_by' => $history->actor?->name ?: '--',
                'taken_action' => strtoupper((string) $history->action),
                'module' => $this->resolveModuleName($history),
                'message' => $history->message,
                'created_at' => optional($history->created_at)->format('d M Y, h:i A') ?: '--',
                'changes_count' => $changes->count(),
                'summary' => $this->buildCompactSummary($changes),
            ];
        });
    }

    private function transformLogDetail(UserLog $history): array
    {
        $details = is_array($history->details) ? $history->details : [];
        $changes = collect($details['changes'] ?? []);

        return [
            'id' => $history->id,
            'actioned_by' => $history->actor?->name ?: '--',
            'taken_action' => strtoupper((string) $history->action),
            'module' => $this->resolveModuleName($history),
            'record_name' => $details['record_name'] ?? null,
            'message' => $history->message,
            'created_at' => optional($history->created_at)->format('d M Y, h:i A') ?: '--',
            'summary' => $this->buildCompactSummary($changes),
            'groups' => [
                'added' => $this->formatGroup($changes, 'added', 'new'),
                'updated' => $this->formatGroup($changes, 'updated', 'new'),
                'deleted' => $this->formatGroup($changes, 'deleted', 'old'),
            ],
            'has_structured_changes' => $changes->isNotEmpty(),
        ];
    }

    private function resolveModuleName(UserLog $history): string
    {
        return $history->subject_type
            ? Str::headline(class_basename($history->subject_type))
            : 'Activity';
    }

    private function buildCompactSummary($changes): string
    {
        $changes = collect($changes);

        if ($changes->isEmpty()) {
            return 'Detailed change summary is not available for this log.';
        }

        $parts = [];

        foreach (['added', 'updated', 'deleted'] as $type) {
            $count = $changes->where('type', $type)->count();
            if ($count > 0) {
                $parts[] = $count . ' ' . $type;
            }
        }

        return ucfirst(implode(', ', $parts));
    }

    private function formatGroup($changes, string $type, string $valueKey): array
    {
        return collect($changes)
            ->where('type', $type)
            ->map(function (array $item) use ($valueKey) {
                return [
                    'field' => $item['field'] ?? '',
                    'label' => $item['label'] ?? Str::headline((string) ($item['field'] ?? 'Field')),
                    'value' => $this->stringifyValue($item[$valueKey] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Not available';
        }

        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->map(fn($item) => is_scalar($item) ? (string) $item : json_encode($item))
                ->implode(', ');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
