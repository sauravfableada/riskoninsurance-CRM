<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DealController extends Controller
{
    public function pipeline()
    {
        $statuses = Status::where('type', 'deal')->where('is_active', true)->orderBy('name')->get();
        $deals = $this->scopeOwnedRecords(
            Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage'])
        )
            ->latest()
            ->get();

        $dealsByStatus = $deals->groupBy('status_id');

        return view('crm.pipeline.index', compact('statuses', 'dealsByStatus'));
    }

    public function pipelineCreate()
    {
        $customers = $this->visibleCustomers()->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();
        $statuses = Status::where('type', 'deal')->where('is_active', true)->orderBy('name')->get();

        return view('crm.pipeline.create', compact('customers', 'stages', 'statuses'));
    }

    public function index()
    {
        return view('crm.deals.index');
    }

    public function create()
    {
        $this->ensureDealStatuses();
        $customers = $this->visibleCustomers()->get();
        $statuses = Status::where('type', 'deal')->where('is_active', true)->orderBy('name')->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();
        $users = $this->selectableUsers();

        return view('crm.deals.create', compact('customers', 'statuses', 'stages', 'users'));
    }

    public function edit(string $id)
    {
        $deal = Deal::with(['statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $deal);
        $this->ensureDealStatuses();
        $customers = $this->visibleCustomers()->get();
        $statuses = Status::where('type', 'deal')->where('is_active', true)->orderBy('name')->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();
        $users = $this->selectableUsers();
        return view('crm.deals.edit', compact('deal', 'customers', 'statuses', 'stages', 'users'));
    }

    public function show(string $id)
    {
        $deal = Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator', 'statusHistories.updater'])
            ->findOrFail($id);
        $this->authorize('view', $deal);

        return view('crm.deals.show', compact('deal'));
    }

    public function export(Request $request)
    {
        $fileName = 'deals_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Deal::with(['customer', 'currency', 'status', 'assignedUser', 'creator', 'stage'])
        )
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('status', fn($status) => $status->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stage', fn($stage) => $stage->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status_id, fn($q) => $q->where('status_id', $request->status_id))
            ->when($request->stage_id, fn($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $deals = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['No', 'Deal Name', 'Customer', 'Created By', 'Assigned To', 'Stage', 'Status', 'Deal Value', 'Expected Close Date', 'Created At'];

        $callback = function () use ($deals, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($deals as $deal) {
                fputcsv($file, [
                    $i++,
                    $deal->title,
                    $deal->customer?->name ?? '--',
                    $deal->creator?->name ?? '--',
                    $deal->assignedUser?->name ?? '--',
                    $deal->stage?->name ?? '--',
                    $deal->status?->name ?? '--',
                    ($deal->currency?->symbol ?? $deal->currency?->code ?? '') . number_format((float) $deal->amount, 2, '.', ''),
                    $deal->expected_close_date?->format('Y-m-d') ?? '--',
                    $deal->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function ensureDealStatuses(): void
    {
        $existing = Status::where('type', 'deal')->count();
        if ($existing > 0) {
            return;
        }

        $defaults = [
            ['name' => 'Pending', 'color' => '#64748B'],
            ['name' => 'In-Process', 'color' => '#0EA5E9'],
            ['name' => 'Paused', 'color' => '#F59E0B'],
            ['name' => 'Lost', 'color' => '#EF4444'],
            ['name' => 'Won', 'color' => '#22C55E'],
        ];

        foreach ($defaults as $item) {
            Status::firstOrCreate(
                ['type' => 'deal', 'name' => $item['name']],
                ['color' => $item['color'], 'is_active' => true]
            );
        }
    }

    private function selectableUsers()
    {
        if (auth()->user()?->isAdmin()) {
            return User::orderBy('name')->get();
        }

        return User::where('id', auth()->id())->orderBy('name')->get();
    }

    private function visibleCustomers()
    {
        return $this->scopeOwnedRecords(Customer::query())->orderBy('name');
    }
}
