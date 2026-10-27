<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\ModuleStatusHistory;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DealController extends ApiBaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $query = $this->scopeOwnedRecords(
            Deal::query()->with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])
        );

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $deals = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Deals retrieved successfully',
            'data' => $deals,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('create', Deal::class);

        $rules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
            'status_id' => [
                'required',
                'integer',
                Rule::exists('statuses', 'id')->where('type', 'deal'),
            ],
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
        ];

        if (Schema::hasColumn('deals', 'probability')) {
            $rules['probability'] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        if (Schema::hasColumn('deals', 'stage_id')) {
            $rules['stage_id'] = ['required', 'integer', 'exists:stages,id'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'customer_id.required' => 'Customer is required.',
            'assigned_user_id.required' => 'Staff is required.',
            'title.required' => 'Deal name is required.',
            'amount.required' => 'Deal value is required.',
            'amount.max' => 'Deal value is too large.',
            'probability.required' => 'Probability is required.',
            'stage_id.required' => 'Stage is required.',
            'status_id.required' => 'Deal status is required.',
        ]);

        $this->validateCloseDate($validator, $request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the following errors:',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser((int) $data['assigned_user_id'], $user);
        if (Deal::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId($data['assigned_user_id'] ?? null, $user->id);
        }

        if (empty($data['currency_id'])) {
            $defaultCurrency = Currency::where('is_default', true)->first() ?? Currency::orderBy('id')->first();
            if ($defaultCurrency) {
                $data['currency_id'] = $defaultCurrency->id;
            }
        }

        if (Schema::hasColumn('deals', 'created_by')) {
            $data['created_by'] = $user->id;
        }

        try {
            $deal = Deal::create($data);
            $historyEntry = $this->recordStatusHistory($deal, $data['status_id'] ?? null, $data['status_comment'] ?? null);
            app(\App\Services\UserLogService::class)->created($deal);
            $this->ensureCustomerForWonDeal($deal);
            $this->createAutoTaskForDeal($deal);
            $this->createOrUpdateProjectFromDeal($deal);

            return response()->json([
                'success' => true,
                'message' => 'Deal created successfully!',
                'data' => $deal->fresh(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator']),
                'history_entry' => $this->serializeHistoryEntry($historyEntry),
                'redirect' => route('deals.index'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deal. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $deal = Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator'])
            ->findOrFail($id);
        $this->authorize('view', $deal);

        return response()->json([
            'success' => true,
            'message' => 'Deal retrieved successfully',
            'data' => $deal,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $deal = Deal::findOrFail($id);
        $this->authorize('update', $deal);
        $originalStatusId = $deal->status_id;
        $rules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
            'status_id' => [
                'required',
                'integer',
                Rule::exists('statuses', 'id')->where('type', 'deal'),
            ],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
        ];

        if (Schema::hasColumn('deals', 'probability')) {
            $rules['probability'] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        if (Schema::hasColumn('deals', 'stage_id')) {
            $rules['stage_id'] = ['required', 'integer', 'exists:stages,id'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'customer_id.required' => 'Customer is required.',
            'assigned_user_id.required' => 'Staff is required.',
            'title.required' => 'Deal name is required.',
            'amount.required' => 'Deal value is required.',
            'amount.max' => 'Deal value is too large.',
            'probability.required' => 'Probability is required.',
            'stage_id.required' => 'Stage is required.',
            'status_id.required' => 'Deal status is required.',
        ]);

        $this->validateCloseDate($validator, $request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the following errors:',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser((int) $data['assigned_user_id'], $user);
        if (Deal::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId(
                $data['assigned_user_id'] ?? $deal->assigned_user_id,
                $deal->created_by ?? $deal->user_id ?? $user->id
            );
        }

        if (empty($data['currency_id'])) {
            $data['currency_id'] = $deal->currency_id;
            if (!$data['currency_id']) {
                $defaultCurrency = Currency::where('is_default', true)->first() ?? Currency::orderBy('id')->first();
                if ($defaultCurrency) {
                    $data['currency_id'] = $defaultCurrency->id;
                }
            }
        }

        try {
            $deal->update($data);
            if (
                (array_key_exists('status_id', $data) && (int) $data['status_id'] !== (int) $originalStatusId)
                || filled($data['status_comment'] ?? null)
            ) {
                $historyEntry = $this->recordStatusHistory($deal, $data['status_id'] ?? $deal->status_id, $data['status_comment'] ?? null);
            }
            app(\App\Services\UserLogService::class)->updated($deal);
            $this->ensureCustomerForWonDeal($deal);
            $this->createOrUpdateProjectFromDeal($deal);

            return response()->json([
                'success' => true,
                'message' => 'Deal updated successfully!',
                'data' => $deal->fresh(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator']),
                'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
                'redirect' => route('deals.index', $deal),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update deal. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $deal = Deal::findOrFail($id);
        $this->authorize('delete', $deal);
        app(\App\Services\UserLogService::class)->deleted($deal);
        $deal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deal deleted successfully!',
        ]);
    }

    public function updateStatus(Request $request, Deal $deal)
    {
        $this->authorize('update', $deal);

        $data = $request->validate([
            'status_id' => [
                'required',
                'integer',
                Rule::exists('statuses', 'id')->where('type', 'deal'),
            ],
        ]);

        $deal->update([
            'status_id' => $data['status_id'],
        ]);
        $this->ensureCustomerForWonDeal($deal);
        $this->createOrUpdateProjectFromDeal($deal);

        return response()->json([
            'success' => true,
            'message' => 'Deal status updated.',
        ]);
    }

    private function ensureCustomerForWonDeal(Deal $deal): void
    {
        $status = Status::find($deal->status_id);
        if (!$status || strtolower($status->name) !== 'won') {
            return;
        }

        $customer = $deal->customer_id ? Customer::find($deal->customer_id) : null;
        if ($customer) {
            return;
        }

        $customerData = [
            'name' => 'Customer - ' . $deal->title,
            'email' => null,
            'phone' => null,
            'is_active' => true,
        ];

        if (Schema::hasColumn('customers', 'created_by')) {
            $customerData['created_by'] = Auth::id();
        }

        if (Schema::hasColumn('customers', 'updated_by')) {
            $customerData['updated_by'] = Auth::id();
        }

        if (Schema::hasColumn('customers', 'user_id')) {
            $customerData['user_id'] = $deal->assigned_user_id
                ?? $deal->user_id
                ?? Auth::id();
        }

        $customer = Customer::create($customerData);

        $deal->update([
            'customer_id' => $customer->id,
        ]);
    }

    private function createAutoTaskForDeal(Deal $deal): void
    {
        Task::create([
            'title' => 'Send proposal',
            'description' => 'Auto task for new deal: ' . $deal->title,
            'related_type' => 'deal',
            'related_id' => $deal->id,
            'assigned_user_id' => $deal->assigned_user_id,
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'status' => 'pending',
        ]);
    }

    private function createOrUpdateProjectFromDeal(Deal $deal): void
    {
        $status = Status::find($deal->status_id);
        if (!$status || strtolower($status->name) !== 'won') {
            return;
        }

        if ($this->hasBookingProjectConflict($deal)) {
            return;
        }

        $projectQuery = Project::query();
        if (Schema::hasColumn('projects', 'deal_id')) {
            $projectQuery->where('deal_id', $deal->id);
        }

        $project = $projectQuery->first();
        if (!$project) {
            $createData = [
                'name' => $this->projectNameFromDeal($deal),
                'customer_id' => $deal->customer_id,
                'assigned_user_id' => $deal->assigned_user_id,
                'status' => 'planning',
            ];

            if (Schema::hasColumn('projects', 'project_code')) {
                $createData['project_code'] = $this->generateProjectCode();
            }

            if (Schema::hasColumn('projects', 'deal_id')) {
                $createData['deal_id'] = $deal->id;
            }

            Project::create($createData);
        } else {
            $updateData = [
                'name' => $this->projectNameFromDeal($deal),
                'customer_id' => $deal->customer_id,
                'assigned_user_id' => $deal->assigned_user_id,
            ];

            if (Schema::hasColumn('projects', 'deal_id')) {
                $updateData['deal_id'] = $deal->id;
            }

            $project->update($updateData);
        }
    }

    private function projectNameFromDeal(Deal $deal): string
    {
        $customerName = $deal->customer?->name ?? 'Customer';
        return $deal->title . ' - ' . $customerName;
    }

    private function generateProjectCode(): string
    {
        do {
            $code = 'PRJ-' . now()->format('Ymd') . '-' . strtoupper(str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT));
        } while (Project::where('project_code', $code)->exists());

        return $code;
    }

    private function hasBookingProjectConflict(Deal $deal): bool
    {
        if (!Schema::hasColumn('projects', 'booking_id')) {
            return false;
        }
        if (!Schema::hasColumn('projects', 'customer_id')) {
            return false;
        }

        $hasStartDate = Schema::hasColumn('projects', 'start_date');
        $hasEndDate = Schema::hasColumn('projects', 'end_date');
        if (!$hasStartDate || !$hasEndDate) {
            return false;
        }

        $query = Project::query()
            ->whereNotNull('booking_id')
            ->where('customer_id', $deal->customer_id);

        if ($deal->expected_close_date) {
            $expected = Carbon::parse($deal->expected_close_date)->startOfDay();
            $query->whereDate('start_date', '<=', $expected)
                ->whereDate('end_date', '>=', $expected);
        } else {
            $query->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '>=', Carbon::today());
            });
        }

        return $query->exists();
    }

    private function validateCloseDate($validator, Request $request): void
    {
        $expected = $request->input('expected_close_date');
        $statusId = $request->input('status_id');
        $customerId = $request->input('customer_id');

        if (!$expected || !$statusId) {
            return;
        }

        $status = Status::find($statusId);
        if (!$status) {
            return;
        }

        $statusName = strtolower((string) $status->name);
        if (in_array($statusName, ['won', 'lost'], true)) {
            $this->validateAgainstLeadCreated($validator, $expected, $customerId);
            return;
        }

        try {
            $expectedDate = Carbon::parse($expected)->startOfDay();
        } catch (\Exception $e) {
            return;
        }

        if ($expectedDate->lt(Carbon::today())) {
            $validator->errors()->add('expected_close_date', 'Close date cannot be in the past unless the deal is Won or Lost.');
        }

        $this->validateAgainstLeadCreated($validator, $expected, $customerId);
    }

    private function validateAgainstLeadCreated($validator, string $expected, ?string $customerId): void
    {
        if (!$customerId) {
            return;
        }

        $lead = Lead::query()->where('converted_customer_id', $customerId)
            ->orderBy('created_at')
            ->first();

        if (!$lead || !$lead->created_at) {
            return;
        }

        try {
            $expectedDate = Carbon::parse($expected)->startOfDay();
        } catch (\Exception $e) {
            return;
        }

        if ($expectedDate->lt($lead->created_at->startOfDay())) {
            $validator->errors()->add('expected_close_date', 'Close date cannot be before the lead creation date.');
        }
    }

    private function recordStatusHistory(Deal $deal, $statusId, ?string $comment): ?ModuleStatusHistory
    {
        $statusName = null;

        if ($statusId) {
            $statusName = Status::find($statusId)?->name;
        }

        if (!$statusName && !filled($comment)) {
            return null;
        }

        try {
            return ModuleStatusHistory::create([
                'historable_type' => Deal::class,
                'historable_id' => $deal->id,
                'status' => $statusName,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Deal status history save skipped.', [
                'deal_id' => $deal->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?ModuleStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        $status = strtolower((string) $history->status);

        return [
            'status_label' => match ($status) {
                'ready_to_close' => 'Ready to Close',
                'won' => 'Closed Won',
                'lost' => 'Closed Lost',
                default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            },
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? Auth::user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function ensureAssignableUser(int $assignedUserId, $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        abort_unless($assignedUserId === (int) $user->id, 403, 'You can only assign records to yourself.');
    }
}
