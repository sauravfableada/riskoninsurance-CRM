@extends('layouts.app')

@section('page_title', 'Products - Categories')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/stages.css' : '/css/stages.css') }}">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/product-categories.css' : '/css/product-categories.css') }}?v={{ filemtime(public_path('css/product-categories.css')) }}">
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm overflow-hidden categories-card">
        <div class="card-header bg-white border-bottom-0 px-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Categories</h4>
                    <p class="text-muted small mb-0">Organize product categories and their active status.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-dark-blue addCategoryBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add Category
                    </button>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center categories-toolbar gap-3">
                <h6 class="fw-bold mb-0">Active Categories</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search categories..." id="categorySearch">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productCategoriesTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Category name</th>
                            <th class="d-none d-md-table-cell" style="width: 180px;">Status</th>
                            <th class="d-none d-md-table-cell" style="width: 180px;">Created At</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="productCategoriesPagination" class="card-footer border-top-0 py-4 px-4"></div>
        </div>
    </div>

    <div class="modal fade" id="productCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title" id="productCategoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('products.categories.index') }}" id="productCategoryForm" novalidate>
                        @csrf
                        <input type="hidden" name="_method" id="productCategoryFormMethod">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="productCategoryName" class="form-control" required>
                            <div class="invalid-feedback" id="product-category-name-error"></div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-dark-blue" id="productCategorySubmitBtn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.productCategoriesConfig = {
        indexUrl: @json(url('/api/products/categories')),
        storeUrl: @json(url('/api/products/categories')),
        updateUrlTemplate: @json(url('/api/products/categories/__ID__')),
        destroyUrlTemplate: @json(url('/api/products/categories/__ID__')),
        toggleUrlTemplate: @json(url('/api/products/categories/__ID__/toggle-status'))
    };
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/product-categories.js' : '/js/product-categories.js') }}?v={{ filemtime(public_path('js/product-categories.js')) }}"></script>
@endpush
