@extends('layouts.app')

@section('page_title', 'Products')

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <style>
        .master-header-btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 gap-3">
                    <div class="pe-3">
                        <h4 class="fw-bold mb-0">Manage Products</h4>
                        <p class="text-muted small mb-0">Track product pricing and categories in the CRM.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-dark-blue master-header-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                        @can('products.create')
                            <button type="button" class="btn btn-outline-dark-blue master-header-btn" onclick="showImportDialog()">
                                <i class="fa-solid fa-upload me-1"></i>Import CSV
                            </button>
                        @endcan
                        @can('products.view')
                            <a href="{{ route('products.export') }}" class="btn btn-outline-dark-blue master-header-btn">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('products.create')
                            <a href="{{ route('products.create') }}" class="btn btn-dark-blue master-header-btn">
                                <i class="fa-solid fa-plus me-1"></i>Add Product
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Active Products</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search products..."
                            id="productsSearch" value="{{ request('search') }}">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="productsTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Product Info</th>
                                <th class="d-none d-md-table-cell">Price</th>
                                <th class="d-none d-md-table-cell">Category</th>
                                <th class="d-none d-md-table-cell">Created At</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="card-footer border-top-0 py-4 px-4" id="productsPagination"></div>
            </div>
        </div>

        <form id="productsImportForm" class="d-none" enctype="multipart/form-data">
            @csrf
            <input type="file" name="import_file" id="productsImportFile" accept=".csv,text/csv">
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        window.crmUserPermissions = {
            products: {
                view: @json(auth()->user()?->hasMatrixPermission('view_products')),
                create: @json(auth()->user()?->hasMatrixPermission('create_products')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_products')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_products')),
            }
        };
    </script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/product.js' : '/js/product.js') }}"></script>
@endpush
