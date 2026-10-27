<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $products = Product::with(['category', 'creator'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages(true));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $data['created_by'] = Auth::id();

        $product = Product::create($data);

        if ($request->has('custom_fields')) {
            $product->saveCustomFields($request->get('custom_fields'));
        }
        app(\App\Services\UserLogService::class)->created($product);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product->fresh(['category', 'creator']),
            'redirect' => route('products.index'),
        ], 201);
    }

    public function show(string $id)
    {
        $product = Product::with(['category', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => $product,
        ], 200);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => ['required', 'file', 'mimes:csv'],
        ], [
            'import_file.required' => 'Please select an import file.',
            'import_file.file' => 'Please upload a valid import file.',
            'import_file.mimes' => 'Please upload a CSV file.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('import_file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read the import file.',
            ], 422);
        }

        $headers = fgetcsv($handle);

        if (!$headers) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'Import file is empty.',
            ], 422);
        }

        $normalizedHeaders = array_map(function ($header) {
            return Str::of((string) $header)->lower()->replace([' ', '-', '.'], '_')->toString();
        }, $headers);

        $rowNumber = 1;
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowData = $this->mapImportRow($normalizedHeaders, $row);
            $name = trim((string) ($rowData['name'] ?? ''));
            $categoryName = trim((string) ($rowData['category_name'] ?? ''));
            $priceValue = trim((string) ($rowData['price'] ?? ''));

            if ($name === '' || $categoryName === '' || $priceValue === '') {
                $skipped++;
                $errors[] = "Row {$rowNumber}: product name, category, and price are required.";
                continue;
            }

            if (!is_numeric($priceValue)) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: price must be a valid number.";
                continue;
            }

            $category = ProductCategory::firstOrCreate(
                ['name' => $categoryName],
                [
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'modified_by' => Auth::id(),
                ]
            );

            if (!$category->is_active) {
                $category->update([
                    'is_active' => true,
                    'modified_by' => Auth::id(),
                ]);
            }

            $sku = trim((string) ($rowData['sku'] ?? ''));
            $product = $sku !== ''
                ? Product::where('sku', $sku)->first()
                : Product::where('name', $name)
                    ->where('product_category_id', $category->id)
                    ->first();

            $payload = [
                'name' => $name,
                'price' => (float) $priceValue,
                'description' => $this->nullableString($rowData['description'] ?? null),
                'stock_quantity' => $this->nullableInteger($rowData['stock_quantity'] ?? null) ?? 0,
                'sku' => $sku !== '' ? $sku : null,
                'product_category_id' => $category->id,
                'modified_by' => Auth::id(),
            ];

            if ($product) {
                $product->update($payload);
                $updated++;
                continue;
            }

            $payload['created_by'] = Auth::id();
            Product::create($payload);
            $imported++;
        }

        fclose($handle);

        $message = "{$imported} product(s) imported successfully.";
        if ($updated > 0) {
            $message .= " {$updated} existing product(s) updated.";
        }
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'summary' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
            'redirect' => route('products.index'),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $validator = Validator::make($request->all(), $this->rules($product), $this->messages(false));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $data['modified_by'] = Auth::id();

        $product->update($data);

        if ($request->has('custom_fields')) {
            $product->saveCustomFields($request->get('custom_fields'));
        }
        app(\App\Services\UserLogService::class)->updated($product);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product->fresh(['category', 'creator']),
            'redirect' => route('products.index'),
        ]);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->deleted_by = Auth::id();
        $product->save();
        app(\App\Services\UserLogService::class)->deleted($product);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function rules(?Product $product = null): array
    {
        $productId = $product?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku,' . $productId],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'image' => [$product ? 'nullable' : 'required', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
        ];
    }

    private function messages(bool $isCreate): array
    {
        return [
            'name.required' => 'Please fill out the product name!',
            'price.required' => 'Please enter a valid price!',
            'price.numeric' => 'Please enter a valid price!',
            'price.min' => 'Please enter a valid price!',
            'product_category_id.required' => 'Please select a valid category!',
            'product_category_id.exists' => 'Please select a valid category!',
            'image.required' => $isCreate ? 'Please select an image!' : 'Please select a valid image!',
            'image.mimes' => 'Please select a valid image! Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB!',
        ];
    }

    private function mapImportRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            $value = $row[$index] ?? null;

            $key = match ($header) {
                'product_name', 'name' => 'name',
                'category', 'category_name', 'product_category' => 'category_name',
                'price', 'product_price' => 'price',
                'description', 'product_description' => 'description',
                'sku' => 'sku',
                'stock_quantity', 'stock', 'quantity' => 'stock_quantity',
                default => $header,
            };

            $mapped[$key] = is_string($value) ? trim($value) : $value;
        }

        return $mapped;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
