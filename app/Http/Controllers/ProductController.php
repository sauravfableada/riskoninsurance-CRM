<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return view('crm.products.index');
    }

    public function create()
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('crm.products.create', compact('categories'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'creator']);

        return view('crm.products.view', compact('product'));
    }

    public function image(Product $product)
    {
        if (!$product->image_path || !Storage::disk('public')->exists($product->image_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($product->image_path));
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('crm.products.edit', compact('product', 'categories'));
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'products_' . date('Y-m-d_H-i-s') . '.csv';

        $query = Product::with(['category', 'creator'])
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = trim((string) $request->search);

                $builder->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $products = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

       $columns = ['No', 'Product Name', 'Price', 'Description', 'Category', 'Created By', 'Created At'];

        $callback = function () use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $index => $product) {
                fputcsv($file, [
                    $index + 1,
                    $product->name,
                    number_format((float) $product->price, 2, '.', ''),
                    $product->description ?? '--',
                    optional($product->category)->name ?? '--',
                    optional($product->creator)->name ?? '--',
                    optional($product->created_at)?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
