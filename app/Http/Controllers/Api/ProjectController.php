<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\ModuleStatusHistory;
use App\Models\Project;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProjectController extends ApiBaseController
{
    /**
     * Display a listing of the projects.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $query = $this->scopeOwnedRecords(
            Project::with(['customer', 'assignedUser', 'creator', 'updater'])
        );

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        // Filter by customer
        if ($request->has('customer_id') && !empty($request->customer_id)) {
            $query->where('customer_id', $request->customer_id);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
        $projects = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $projects,
            'message' => 'Projects retrieved successfully'
        ]);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('create', Project::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'description' => 'required|string|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:pending,ongoing,completed,canceled',
            'status_comment' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'The name field is required.',
            'customer_id.required' => 'The customer id field is required.',
            'status.required' => 'The status field is required.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'Please enter a valid start date.',
            'end_date.required' => 'The end date field is required.',
            'end_date.date' => 'Please enter a valid end date.',
            'end_date.after_or_equal' => 'The end date must be a date after or equal to start date.',
            'description.required' => 'The description field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        if (Schema::hasColumn('projects', 'user_id')) {
            $data['user_id'] = $this->resolveOwnedUserId($data['assigned_user_id'] ?? null, $user->id);
        }
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        $project = Project::create($data);
        $historyEntry = $this->recordStatusHistory($project, $data['status'] ?? null, $data['status_comment'] ?? null);
        $project->load(['customer', 'assignedUser', 'creator']);
        app(\App\Services\UserLogService::class)->created($project);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project created successfully',
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('projects.index'),
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('view', $project);
        $project->load(['customer', 'assignedUser', 'creator', 'updater', 'deleter']);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project retrieved successfully'
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('update', $project);
        $originalStatus = $project->status;
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'description' => 'required|string|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|required|in:pending,ongoing,completed,canceled',
            'status_comment' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'The name field is required.',
            'customer_id.required' => 'The customer id field is required.',
            'status.required' => 'The status field is required.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'Please enter a valid start date.',
            'end_date.required' => 'The end date field is required.',
            'end_date.date' => 'Please enter a valid end date.',
            'end_date.after_or_equal' => 'The end date must be a date after or equal to start date.',
            'description.required' => 'The description field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $data = $validator->validated();
        if (array_key_exists('customer_id', $data)) {
            $this->ensureVisibleCustomer((int) $data['customer_id']);
        }
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        if (Schema::hasColumn('projects', 'user_id')) {
            $data['user_id'] = $this->resolveOwnedUserId(
                $data['assigned_user_id'] ?? $project->assigned_user_id,
                $project->created_by ?? $project->user_id ?? $user->id
            );
        }
        $data['updated_by'] = $user->id;

        $project->update($data);
        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($project, $data['status'] ?? $project->status, $data['status_comment'] ?? null);
        }
        $project->load(['customer', 'assignedUser', 'updater']);
        app(\App\Services\UserLogService::class)->updated($project);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project updated successfully',
            'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
            'redirect' => route('projects.index', $project),
        ]);
    }


    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('delete', $project);
        $project->deleted_by = $user->id;
        $project->save();
        app(\App\Services\UserLogService::class)->deleted($project);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }

    /**
     * Get project statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Project::count(),
            'pending' => Project::where('status', 'pending')->count(),
            'ongoing' => Project::where('status', 'ongoing')->count(),
            'completed' => Project::where('status', 'completed')->count(),
            'canceled' => Project::where('status', 'canceled')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Statistics retrieved successfully'
        ]);
    }

    /**
     * Get projects by customer
     */
    public function byCustomer(Customer $customer)
    {
        $projects = $customer->projects()->with(['assignedUser'])->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
            'message' => 'Customer projects retrieved successfully'
        ]);
    }

    /**
     * Get projects assigned to user
     */
    public function assignedToMe()
    {
        $projects = Project::with(['customer'])
            ->where('assigned_user_id', Auth::id())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
            'message' => 'Assigned projects retrieved successfully'
        ]);
    }

    private function recordStatusHistory(Project $project, ?string $status, ?string $comment): ?ModuleStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return ModuleStatusHistory::create([
                'historable_type' => Project::class,
                'historable_id' => $project->id,
                'status' => $status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Project status history save skipped.', [
                'project_id' => $project->id,
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

        return [
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
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

    private function ensureAssignableUser(?int $assignedUserId, $user): void
    {
        if (!$assignedUserId || $user->isAdmin()) {
            return;
        }

        abort_unless((int) $assignedUserId === (int) $user->id, 403, 'You can only assign records to yourself.');
    }
}
