<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Product;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return view('crm.services.index');
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        return view('crm.services.create', compact('products'));
    }

    public function show(Service $service)
    {
        $service->load(['product', 'creator', 'updater', 'deleter', 'statusHistories.updater']);

        return view('crm.services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $service->load(['statusHistories.updater']);
        $products = Product::orderBy('name')->get();

        return view('crm.services.edit', compact('service', 'products'));
    }

    public function export(Request $request)
    {
        $fileName = 'services_' . date('Y-m-d_H-i-s') . '.csv';

        $query = Service::with(['product', 'creator'])
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('service_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $services = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Service Name', 'Product Name', 'Service Price', 'Service Status', 'Created At'];

        $callback = function () use ($services, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($services as $service) {
                fputcsv($file, [
                    $i++,
                    $service->service_name ?? '--',
                    $service->product?->name ?? '--',
                    number_format((float) ($service->service_price ?? 0), 2, '.', ''),
                    strtoupper($service->status ?? 'inactive'),
                    $service->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
