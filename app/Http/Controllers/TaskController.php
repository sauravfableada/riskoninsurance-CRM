<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
        $customers = $this->visibleCustomers();
        return view('crm.tasks.create', compact('users', 'projects', 'customers'));
    }

    public function edit(string $id)
    {
        $task = Task::with(['statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $task);
        $users = $this->selectableUsers();
        $projects = $this->scopeOwnedRecords(Project::query())->orderBy('name')->get();
        $customers = $this->visibleCustomers();
        return view('crm.tasks.edit', compact('task', 'users', 'projects', 'customers'));
    }

    public function show(string $id)
    {
        $task = Task::with(['assignedUser', 'project.customer', 'project.creator', 'statusHistories.updater'])->findOrFail($id);
        $this->authorize('view', $task);

        $customer = null;
        if ($task->related_type === 'customer' && $task->related_id) {
            $customer = $this->visibleCustomers()->firstWhere('id', $task->related_id);
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
            Task::with(['assignedUser', 'project.customer'])
        )
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('project', fn($project) => $project->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
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

        $columns = ['No', 'Title', 'Status', 'Priority', 'Due Date', 'Assigned To', 'Customer', 'Project', 'Created At'];

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
                    $task->project?->customer?->name ?? '',
                    $task->project ? $task->project->name : '',
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
