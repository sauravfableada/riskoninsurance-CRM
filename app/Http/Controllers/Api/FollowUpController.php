<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\FollowUp;
use App\Models\FollowUpStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FollowUpController extends ApiBaseController
{
    private const FOLLOW_UP_RULES = [
        'lead_id' => ['required', 'integer', 'exists:leads,id'],
        'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        'purpose' => ['required', 'string', 'max:255'],
        'comment' => ['nullable', 'string', 'max:2000'],
        'status_comment' => ['nullable', 'string', 'max:2000'],
        'priority' => ['required', 'in:low,medium,high'],
        'status' => ['required', 'in:pending,resheduled,completed,cancelled'],
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $query = $this->scopeOwnedRecords(
            FollowUp::with(['lead', 'assignedUser', 'creator'])
        );

        if ($request->has('lead_id') && $request->lead_id) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_user_id') && $request->assigned_user_id) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        if ($request->has('created_by') && $request->created_by) {
            $query->where('created_by', $request->created_by);
        }

        if ($request->has('updated_by') && $request->updated_by) {
            $query->where('updated_by', $request->updated_by);
        }

        // ✅ ADVANCED SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('priority', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedUser', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from_date')) {
            $query->where('follow_up_at', '>=', $request->from_date . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('follow_up_at', '<=', $request->to_date . ' 23:59:59');
        }
        if (!$request->filled('from_date') && !$request->filled('to_date') && $request->has('follow_up_at') && $request->follow_up_at) {
            $query->whereDate('follow_up_at', $request->follow_up_at);
        }

        $perPage = (int) $request->get('per_page', 10);
        $followUps = $query->latest('follow_up_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Follow-ups retrieved successfully',
            'data' => $followUps,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('create', FollowUp::class);

        $validator = $this->makeValidator($request, true);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleLead((int) $data['lead_id']);
        $data['assigned_user_id'] = $data['assigned_user_id'] ?? $user->id;
        $this->ensureAssignableUser((int) $data['assigned_user_id'], $user);
        if (FollowUp::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId($data['assigned_user_id'] ?? null, $user->id);
        }
        $data['created_by'] = $user->id;
        $data['updated_by'] = auth()->id();

        $followUp = FollowUp::create($data);
        $historyEntry = $this->recordStatusHistory($followUp, $data['status'] ?? null, $data['status_comment'] ?? null);
        app(\App\Services\UserLogService::class)->created($followUp, 'Created a Follow Up ' . ($followUp->purpose ?: ('ID ' . $followUp->id)));

        return response()->json([
            'success' => true,
            'data' => $followUp,
            'message' => 'Follow-up created successfully',
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('followups.index'),
        ], 201);
    }

    public function show($id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $followUp = FollowUp::with(['lead', 'customer', 'assignedUser', 'creator'])->find($id);

        if (!$followUp) {
            return $this->error('Follow-up not found', 404);
        }

        $this->authorize('view', $followUp);

        return response()->json([
            'success' => true,
            'data' => $followUp,
            'message' => 'Follow-up retrieved successfully'
        ]);
    }


    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $followUp = FollowUp::find($id);

        if (!$followUp) {
            return $this->error('Follow-up not found', 404);
        }

        $this->authorize('update', $followUp);

        $originalStatus = $followUp->status;
        $validator = $this->makeValidator($request, false);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleLead((int) $data['lead_id'], $followUp);
        $data['assigned_user_id'] = $data['assigned_user_id'] ?? $followUp->assigned_user_id ?? $user->id;
        $this->ensureAssignableUser((int) $data['assigned_user_id'], $user);
        if (FollowUp::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId(
                $data['assigned_user_id'] ?? $followUp->assigned_user_id,
                $followUp->created_by ?? $followUp->user_id ?? $user->id
            );
        }
        $data['updated_by'] = $user->id;
        $followUp->update($data);
        $historyEntry = null;

        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($followUp, $data['status'] ?? $followUp->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($followUp, 'Updated a Follow Up ' . ($followUp->purpose ?: ('ID ' . $followUp->id)));

        return response()->json([
            'success' => true,
            'data' => $followUp,
            'message' => 'Follow-up updated successfully',
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('followups.index')
        ]);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $followUp = FollowUp::find($id);

        if (!$followUp) {
            return $this->error('Follow-up not found', 404);
        }

        $this->authorize('delete', $followUp);

        $followUp->update(['deleted_by' => $user->id]);
        app(\App\Services\UserLogService::class)->deleted($followUp, 'Deleted a Follow Up ' . ($followUp->purpose ?: ('ID ' . $followUp->id)));
        $followUp->delete();

        return response()->json([
            'success' => true,
            'message' => 'Follow-up deleted successfully'
        ]);
    }

    public function apiByLead($id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $followUps = $this->scopeOwnedRecords(
            FollowUp::with(['lead', 'assignedUser', 'creator'])
        )
            ->where('lead_id', $id)
            ->latest('follow_up_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $followUps,
            'message' => 'Follow-ups retrieved successfully'
        ]);
    }

    public function updateStatus(Request $request, FollowUp $followup)
    {
        $this->authorize('update', $followup);

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,resheduled,completed,cancelled'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $request->input('status');
        $comment = $request->input('status_comment');

        if ($followup->status !== $status) {
            $followup->status = $status;
            $followup->save();

            $this->recordStatusHistory($followup, $status, $comment ?: 'Status updated from inline edit');
            
            if (class_exists(\App\Services\UserLogService::class)) {
                app(\App\Services\UserLogService::class)->updated($followup, 'Updated Follow Up Status');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $status
        ], 200);
    }

    public function updatePriority(Request $request, FollowUp $followup)
    {
        $this->authorize('update', $followup);

        $validator = Validator::make($request->all(), [
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid priority provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        $priority = $request->input('priority');

        if ($followup->priority !== $priority) {
            $followup->priority = $priority;
            $followup->save();
            
            if (class_exists(\App\Services\UserLogService::class)) {
                app(\App\Services\UserLogService::class)->updated($followup, 'Updated Follow Up Priority');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully.',
            'priority' => $priority
        ], 200);
    }

    private function recordStatusHistory(FollowUp $followUp, ?string $status, ?string $comment): ?FollowUpStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return FollowUpStatusHistory::create([
                'follow_up_id' => $followUp->id,
                'status' => $status ?? $followUp->status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Follow-up status history save skipped.', [
                'follow_up_id' => $followUp->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?FollowUpStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        $history->loadMissing('updater');

        return [
            'status' => $history->status,
            'status_label' => \Illuminate\Support\Str::of($history->status)->replace('_', ' ')->title()->toString(),
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
        ];
    }

    private function makeValidator(Request $request, bool $enforceFutureDate): \Illuminate\Contracts\Validation\Validator
    {
        $rules = self::FOLLOW_UP_RULES + [
            'follow_up_at' => $enforceFutureDate
                ? ['required', 'date', 'after_or_equal:today']
                : ['required', 'date'],
        ];

        return Validator::make($request->all(), $rules, $this->validationMessages());
    }

    private function validationMessages(): array
    {
        return [
            'lead_id.required' => 'Lead is required!',
            'lead_id.exists' => 'Please select a valid lead.',
            'assigned_user_id.exists' => 'Please select a valid staff member.',
            'purpose.required' => 'Purpose is required!',
            'priority.required' => 'Priority is required!',
            'priority.in' => 'Please select a valid priority.',
            'status.required' => 'Status is required!',
            'status.in' => 'Please select a valid status.',
            'follow_up_at.required' => 'Date/Time is required!',
            'follow_up_at.date' => 'Please enter a valid date/time.',
            'follow_up_at.after_or_equal' => 'Date/Time must be today or later.',
        ];
    }

    private function ensureVisibleLead(int $leadId, ?FollowUp $followUp = null): void
    {
        if ($followUp && (int) $followUp->lead_id === $leadId) {
            return;
        }

        $lead = \App\Models\Lead::findOrFail($leadId);
        $this->authorize('view', $lead);
    }

    private function ensureAssignableUser(int $assignedUserId, $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        abort_unless($assignedUserId === (int) $user->id, 403, 'You can only assign records to yourself.');
    }
}
