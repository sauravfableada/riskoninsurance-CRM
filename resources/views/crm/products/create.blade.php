@extends('layouts.app')

@section('page_title', 'Products - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden product-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Product</h1>
                        <p class="text-muted small mb-0">Create a product entry based on the legacy CRM setup.</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-dark-blue product-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/products" enctype="multipart/form-data"
                    class="needs-validation ajax-product-form" novalidate id="productCreateForm">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                class="form-control" placeholder="Product Name"
                                required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price') }}"
                                class="form-control" placeholder="0.00" required>
                            <div class="invalid-feedback" id="price-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 product-category-inline">
                                <select name="product_category_id" id="product_category_id"
                                    class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            @selected(old('product_category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn crm-action-btn stage-plus-btn" id="add-category-btn"
                                    title="Add Category">+</button>
                            </div>
                            <div class="invalid-feedback d-block" id="product_category_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Image <span class="text-danger">*</span></label>
                            <input type="file" name="image" id="image"
                                class="form-control"
                                accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml"
                                required>
                            <div class="form-text">Required. Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG. Max 2MB.
                            </div>
                            <div class="invalid-feedback d-block" id="image-error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="description"
                                class="form-control" rows="2"
                                placeholder="Product description">{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                    </div>

                    @include('partials.custom_fields', ['module' => 'Product'])

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="add-category-form" novalidate>
                        <div class="mb-3">
                            <label for="new-category-name" class="form-label fw-semibold">Category Name</label>
                            <input type="text" class="form-control" id="new-category-name" placeholder="Enter category name">
                            <div class="invalid-feedback" id="new-category-name-error"></div>
                        </div>
                        <div class="mb-0">
                            <label for="new-category-description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="new-category-description" rows="3" placeholder="Optional description"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue px-4" id="save-category-btn">Add Category</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/product-form.css' : '/css/product-form.css') }}?v={{ filemtime(public_path('css/product-form.css')) }}">
@endpush

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/product.js' : '/js/product.js') }}"></script>
@endpush
