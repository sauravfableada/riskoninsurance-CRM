<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return view('crm.projects.index');
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        $customers = $this->visibleCustomers();
        $users = $this->selectableUsers();

        return view('crm.projects.create', compact('customers', 'users'));
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);
        $project->load(['customer', 'assignedUser', 'creator', 'updater', 'statusHistories.updater']);

        return view('crm.projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        $project->load(['statusHistories.updater']);
        $customers = $this->visibleCustomers();
        $users = $this->selectableUsers();

        return view('crm.projects.edit', compact('project', 'customers', 'users'));
    }

    public function export(Request $request)
    {
        $fileName = 'projects_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Project::with(['customer', 'creator'])
        )
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $projects = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        $columns = ['No', 'Created By', 'Customer Name', 'Project Name', 'Project Status', 'Created At'];

        $callback = function () use ($projects, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($projects as $project) {
                fputcsv($file, [
                    $i++,
                    $project->creator?->name ?? '--',
                    $project->customer?->name ?? '--',
                    $project->name ?? '--',
                    $project->status ? ucfirst($project->status) : '--',
                    $project->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function selectableUsers()
    {
        if (auth()->user()?->isAdmin()) {
            return User::role(['admin', 'manager', 'staff'])->orderBy('name')->get();
        }

        return User::where('id', auth()->id())->orderBy('name')->get();
    }

    private function visibleCustomers()
    {
        return $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
    }
}
