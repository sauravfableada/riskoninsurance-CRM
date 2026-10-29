<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InsurancePolicyType;
use App\Models\InsurancePolicySubType;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return view('crm.tasks.index');
    }

    public function create()
    {
        $users = $this->selectableUsers();
        $projects = $this->scopeOwnedRecords(Project::query())->orderBy('name')->get();
        $policyTypes = InsurancePolicyType::orderBy('name')->get();
        $customers = $this->visibleCustomers();
        return view('crm.tasks.create', compact('users', 'projects', 'policyTypes', 'customers'));
    }

    public function edit(string $id)
    {
        $task = Task::with(['statusHistories.updater', 'policyType', 'policySubType'])->findOrFail($id);
        $this->authorize('update', $task);
        $users = $this->selectableUsers();
        $projects = $this->scopeOwnedRecords(Project::query())->orderBy('name')->get();
        $policyTypes = InsurancePolicyType::orderBy('name')->get();
        $policySubTypes = $task->policy_type_id
            ? InsurancePolicySubType::where('insurance_policy_types_id', $task->policy_type_id)->orderBy('name')->get()
            : collect();
        $customers = $this->visibleCustomers();
        return view('crm.tasks.edit', compact('task', 'users', 'projects', 'policyTypes', 'policySubTypes', 'customers'));
    }

    public function show(string $id)
    {
        $task = Task::with(['assignedUser', 'customer', 'project.customer', 'project.creator', 'policyType', 'policySubType', 'statusHistories.updater'])->findOrFail($id);
        $this->authorize('view', $task);

        $customer = null;
        if ($task->related_type === 'customer' && $task->related_id) {
            $customer = $this->visibleCustomers()->firstWhere('id', $task->related_id);
        }
        if (!$customer && $task->customer) {
            $customer = $task->customer;
        }
        if (!$customer && $task->project?->customer) {
            $customer = $task->project->customer;
        }

        return view('crm.tasks.show', compact('task', 'customer'));
    }

    public function export(Request $request)
    {
        $fileName = 'tasks_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Task::with(['assignedUser', 'customer', 'project.customer', 'policyType', 'policySubType'])
        )
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('policyType', fn($pt) => $pt->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('policySubType', fn($pst) => $pst->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn($project) => $project->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->policy_type_id, fn($q) => $q->where('policy_type_id', $request->policy_type_id))
            ->when($request->policy_sub_type_id, fn($q) => $q->where('policy_sub_type_id', $request->policy_sub_type_id))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->assigned_user_id, fn($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $tasks = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['No', 'Title', 'Status', 'Priority', 'Due Date', 'Assigned To', 'Customer', 'Policy Type', 'Policy Sub Type', 'Created At'];

        $callback = function () use ($tasks, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($tasks as $task) {
                fputcsv($file, [
                    $i++,
                    $task->title,
                    ucfirst(str_replace('_', ' ', $task->status)),
                    ucfirst($task->priority),
                    $task->due_date ? $task->due_date->format('Y-m-d') : '',
                    $task->assignedUser ? $task->assignedUser->name : '',
                    $task->customer?->name ?? ($task->project?->customer?->name ?? ''),
                    $task->policyType?->name ?? ($task->project?->name ?? ''),
                    $task->policySubType?->name ?? '',
                    $task->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function selectableUsers()
    {
        if (auth()->user()?->isAdmin()) {
            $users = User::role(['manager', 'staff', 'admin'])->orderBy('name')->get();
            if (auth()->check() && !$users->contains('id', auth()->id())) {
                $users->prepend(auth()->user());
            }
            return $users;
        }

        return User::where('id', auth()->id())->orderBy('name')->get();
    }

    private function visibleCustomers()
    {
        return $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
    }
}
