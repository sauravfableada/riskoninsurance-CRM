<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function index()
    {
        return view('crm.pipeline.index');
    }

    public function create()
    {
        $customers = $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();

        return view('crm.pipeline.create', compact('customers', 'stages'));
    }

    public function edit(Pipeline $pipeline)
    {
        $pipeline->load(['statusHistories.updater']);
        $customers = $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();

        return view('crm.pipeline.edit', compact('pipeline', 'customers', 'stages'));
    }

    public function show(Pipeline $pipeline)
    {
        $pipeline->load(['customer', 'stage', 'creator', 'statusHistories.updater']);

        return view('crm.pipeline.show', compact('pipeline'));
    }

    public function export(Request $request)
    {
        $fileName = 'pipelines_' . date('Y-m-d_H-i-s') . '.csv';
        $query = Pipeline::with(['customer', 'stage', 'creator'])
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('pipeline_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stage', fn ($stage) => $stage->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->stage_id, fn ($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $pipelines = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Customer Name', 'Pipeline Name', 'Stage Name', 'Status', 'Created By', 'Created At'];

        $callback = function () use ($pipelines, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($pipelines as $pipeline) {
                fputcsv($file, [
                    $i++,
                    $pipeline->customer?->name ?? '--',
                    $pipeline->pipeline_name ?? '--',
                    $pipeline->stage?->name ?? '--',
                    $pipeline->status ? ucfirst(str_replace('_', ' ', $pipeline->status)) : '--',
                    $pipeline->creator?->name ?? '--',
                    $pipeline->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
