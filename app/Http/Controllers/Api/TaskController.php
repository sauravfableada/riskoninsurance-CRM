<?php

namespace App\Http\Controllers\Api;

use App\Models\ModuleStatusHistory;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskController extends ApiBaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $search = $request->get('search');

        $status = $request->get('status');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));

        $tasks = $this->scopeOwnedRecords(
            Task::query()->with(['assignedUser', 'customer', 'project.customer'])
        )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($projectQuery) use ($search) {
                            $projectQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($fromDate, function ($query) use ($fromDate) {
                $query->where('due_date', '>=', $fromDate . ' 00:00:00');
            })
            ->when($toDate, function ($query) use ($toDate) {
                $query->where('due_date', '<=', $toDate . ' 23:59:59');
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully',
            'data' => $tasks,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('create', Task::class);

        $request->merge([
            'priority' => $request->input('priority') ?: 'medium',
            'status' => $request->input('status') ?: 'in_progress',
        ]);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['related_id']);
        $this->ensureVisibleProject((int) $data['project_id']);
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        $data['related_type'] = !empty($data['related_id']) ? 'customer' : null;
        if (Task::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId($data['assigned_user_id'] ?? null, $user->id);
        }

        $task = Task::create($data);
        $historyEntry = $this->recordStatusHistory($task, $data['status'] ?? null, $data['status_comment'] ?? null);
        app(\App\Services\UserLogService::class)->created($task);

        if ($request->has('custom_fields')) {
            $task->saveCustomFields($request->get('custom_fields'));
        }

        // WhatsApp notification to assigned staff
        try {
            $task->load(['assignedUser', 'customer', 'project.customer']);

            $projectName = $task->project?->name ?: '--';
            $customer = $task->customer ?: $task->project?->customer;
            $customerName = $customer?->name ?: 'Customer';
            $dueDateText = $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('d M Y') : '';
            $priorityText = $task->priority ? ucfirst(str_replace('_', ' ', $task->priority)) : '';
            $descText = $task->description ?: '';

            $staffPhone = $task->assignedUser?->whatsapp ?: $task->assignedUser?->phone;
            if ($staffPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_assigned_staff',
                    $staffPhone,
                    [
                        $task->assignedUser->name ?? 'Staff',
                        $projectName,
                        $task->title ?? 'Task',
                        $customerName,
                        $dueDateText,
                        $priorityText,
                        $descText,
                    ],
                    $task->id
                );
            }

            $customerPhone = $customer?->whatsapp ?: $customer?->phone;
            if ($customerPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_created_customer',
                    $customerPhone,
                    [
                        $customerName,
                        $projectName,
                        $task->title ?? 'Task',
                        $task->assignedUser?->name ?? 'Staff',
                        $dueDateText,
                        $descText,
                    ],
                    $task->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Task create WhatsApp block failed', [
                'task_id' => $task->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $task->fresh(['assignedUser', 'customer', 'project.customer']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('tasks.index'),
        ], 201);
    }

    public function show(string $id)
    {
        $task = Task::with(['assignedUser', 'customer', 'project.customer', 'project.creator'])->findOrFail($id);
        $this->authorize('view', $task);

        return response()->json([
            'success' => true,
            'message' => 'Task retrieved successfully.',
            'data' => $task,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $originalStatus = $task->status;
        $validator = Validator::make($request->all(), $this->rules(true), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['related_id']);
        $this->ensureVisibleProject((int) $data['project_id']);
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        $data['related_type'] = !empty($data['related_id']) ? 'customer' : null;
        if (Task::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId(
                $data['assigned_user_id'] ?? $task->assigned_user_id,
                $task->created_by ?? $task->user_id ?? $user->id
            );
        }

        $task->update($data);
        $historyEntry = null;
        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($task, $data['status'] ?? $task->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($task);

        if ($request->has('custom_fields')) {
            $task->saveCustomFields($request->get('custom_fields'));
        }

        try {
            $task->load(['assignedUser', 'customer', 'project.customer']);

            $projectName = $task->project?->name ?: '--';
            $customer = $task->customer ?: $task->project?->customer;
            $customerName = $customer?->name ?: 'Customer';
            $customerPhone = $customer?->whatsapp ?: $customer?->phone;
            $staffName = $task->assignedUser?->name ?? 'Staff';
            $staffPhone = $task->assignedUser?->whatsapp ?: $task->assignedUser?->phone;
            $dueDateText = $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('d M Y') : '';
            $priorityText = $task->priority ? ucfirst(str_replace('_', ' ', $task->priority)) : '';
            $statusText = $task->status ? ucwords(str_replace('_', ' ', $task->status)) : '';
            $descText = $task->description ?: '';

            if ($staffPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_updated_staff',
                    $staffPhone,
                    [
                        $staffName,
                        $projectName,
                        $task->title ?? 'Task',
                        $customerName,
                        $dueDateText,
                        $priorityText,
                        $statusText,
                        $descText,
                    ],
                    $task->id
                );
            }

            if ($customerPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'task_updated_customer',
                    $customerPhone,
                    [
                        $customerName,
                        $projectName,
                        $task->title ?? 'Task',
                        $staffName,
                        $dueDateText,
                        $statusText,
                        $descText,
                    ],
                    $task->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Task update WhatsApp block failed', [
                'task_id' => $task->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $task->fresh(['assignedUser', 'customer', 'project.customer']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('tasks.index', $task),
        ]);
    }

    public function destroy(string $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('delete', $task);
        app(\App\Services\UserLogService::class)->deleted($task);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,in_progress,completed'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $originalStatus = $task->status;
        $task->update(['status' => $data['status']]);

        $historyEntry = null;
        if ($data['status'] !== $originalStatus || filled($data['status_comment'] ?? null)) {
            $historyEntry = $this->recordStatusHistory($task, $data['status'], $data['status_comment'] ?? null);
        }
        
        app(\App\Services\UserLogService::class)->updated($task);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully.',
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
        ]);
    }

    public function updatePriority(Request $request, string $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);

        $validator = Validator::make($request->all(), [
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $task->update(['priority' => $validator->validated()['priority']]);
        app(\App\Services\UserLogService::class)->updated($task);

        return response()->json([
            'success' => true,
            'message' => 'Task priority updated successfully.',
        ]);
    }

    private function rules(bool $updating = false): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'related_id' => ['required', 'exists:customers,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:pending,in_progress,completed'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function messages(): array
    {
        return [
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Please select a valid project.',
            'related_id.required' => 'Please select a customer.',
            'related_id.exists' => 'Please select a valid customer.',
            'title.required' => 'Task title is required.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'due_date.date' => 'Please enter a valid due date.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Please select a valid priority.',
            'status.required' => 'Status is required.',
            'status.in' => 'Please select a valid status.',
            'assigned_user_id.exists' => 'Please select a valid staff user.',
        ];
    }

    private function recordStatusHistory(Task $task, ?string $status, ?string $comment): ?ModuleStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return ModuleStatusHistory::create([
                'historable_type' => Task::class,
                'historable_id' => $task->id,
                'status' => $status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Task status history save skipped.', [
                'task_id' => $task->id,
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

        $history->loadMissing('updater');

        return [
            'status' => $history->status,
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
        ];
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function ensureVisibleProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('view', $project);
    }

    private function ensureAssignableUser(?int $assignedUserId, $user): void
    {
        if (!$assignedUserId || $user->isAdmin()) {
            return;
        }

        abort_unless((int) $assignedUserId === (int) $user->id, 403, 'You can only assign records to yourself.');
    }
}
