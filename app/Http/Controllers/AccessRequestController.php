<?php

namespace App\Http\Controllers;

use App\Models\StaffAccessRequest;
use App\Models\StaffPermissionGrant;
use App\Services\AccessRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessRequestController extends Controller
{
    public function __construct(private readonly AccessRequestService $accessRequestService)
    {
    }

    public function myRequests(Request $request): View
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $requests = StaffAccessRequest::query()
            ->with(['reviewer:id,name'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(15);

        $activeGrants = StaffPermissionGrant::query()
            ->with(['granter:id,name'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->latest('id')
            ->get();

        return view('crm.access-requests.my-requests', [
            'requests' => $requests,
            'activeGrants' => $activeGrants,
            'modules' => config('crm_permissions.modules', []),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'module' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'action' => ['required_without:actions', 'nullable', 'string', 'max:50', Rule::in(AccessRequestService::SUPPORTED_ACTIONS)],
            'actions' => ['required_without:action', 'nullable', 'array', 'min:1'],
            'actions.*' => ['string', 'max:50', Rule::in(AccessRequestService::SUPPORTED_ACTIONS)],
            'permission' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'record_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $actions = !empty($validated['actions'])
            ? array_values(array_unique($validated['actions']))
            : [$validated['action']];

        $created = [];
        $errors = [];

        foreach ($actions as $act) {
            try {
                $created[] = $this->accessRequestService->createRequest($user, [
                    'module' => $validated['module'],
                    'action' => $act,
                    'permission' => count($actions) === 1 ? ($validated['permission'] ?? null) : null,
                    'record_id' => $validated['record_id'] ?? null,
                    'reason' => $validated['reason'],
                ]);
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($created)) {
            return response()->json([
                'success' => false,
                'message' => $errors[0] ?? 'Unable to submit access request.',
            ], 422);
        }

        $formatted = array_map(fn (StaffAccessRequest $item) => $this->formatRequest($item), $created);

        return response()->json([
            'success' => true,
            'message' => count($created) > 1
                ? count($created) . ' access requests submitted and awaiting administrator approval.'
                : 'Access request submitted and awaiting administrator approval.',
            'data' => count($formatted) === 1 ? $formatted[0] : $formatted,
            'items' => $formatted,
        ], 201);
    }

    public function myStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $effective = $this->accessRequestService->getEffectivePermissionsForUser($user);

        $requests = StaffAccessRequest::query()
            ->with(['reviewer:id,name'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (StaffAccessRequest $item) => $this->formatRequest($item))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'is_admin' => $effective['is_admin'],
                'permissions' => $effective['matrix'],
                'flat_permissions' => $effective['flat'],
                'requests' => $requests,
                'pending_admin_count' => $user->isAdmin()
                    ? StaffAccessRequest::query()->where('status', StaffAccessRequest::STATUS_PENDING)->count()
                    : 0,
            ],
        ]);
    }

    private function formatRequest(StaffAccessRequest $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'module' => $item->module,
            'action' => $item->action,
            'permission' => $item->permission,
            'permission_label' => $this->accessRequestService->formatPermissionLabel($item->module, $item->action),
            'record_id' => $item->record_id,
            'reason' => $item->reason,
            'status' => $item->status,
            'admin_comment' => $item->admin_comment,
            'reviewed_by' => $item->reviewer?->name,
            'requested_at' => optional($item->requested_at ?? $item->created_at)->toIso8601String(),
            'requested_at_formatted' => optional($item->requested_at ?? $item->created_at)->format('d M Y, h:i A'),
            'reviewed_at' => optional($item->reviewed_at)->toIso8601String(),
            'reviewed_at_formatted' => optional($item->reviewed_at)->format('d M Y, h:i A'),
        ];
    }
}
