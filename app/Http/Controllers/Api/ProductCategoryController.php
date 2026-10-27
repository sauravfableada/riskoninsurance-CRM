<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends ApiBaseController
{
    private function ensurePermission(string $permission): void
    {
        abort_unless(auth()->user()?->hasMatrixPermission($permission), 403);
    }

    public function index(Request $request)
    {
        $this->ensurePermission('view_products');

        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $categories = ProductCategory::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Product categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensurePermission('create_products');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:product_categories,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = ProductCategory::create([
            'name' => $validator->validated()['name'],
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function show(string $id)
    {
        $this->ensurePermission('view_products');

        $category = ProductCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product category retrieved successfully',
            'data' => $category,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $this->ensurePermission('edit_products');

        $category = ProductCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:product_categories,name,' . $id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update([
            'name' => $validator->validated()['name'],
            'modified_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $this->ensurePermission('delete_products');

        $category = ProductCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product category deleted successfully.',
        ]);
    }

    public function toggleStatus(Request $request, ProductCategory $productCategory)
    {
        $this->ensurePermission('edit_products');

        $validator = Validator::make($request->all(), [
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $productCategory->update([
            'is_active' => $request->boolean('is_active'),
            'modified_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => $productCategory->only(['id', 'is_active']),
        ]);
    }
}
