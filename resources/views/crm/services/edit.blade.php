@extends('layouts.app')

@section('page_title', 'Services - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden service-form-card">
            <div class="card-body p-0">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Edit Service</h1>
                            <p class="text-muted small mb-0">Update service details for the selected product.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            @can('services.view')
                                <a href="{{ route('services.show', $service->id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            @endcan
                            <a href="{{ route('services.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                <form method="POST" action="/api/services/{{ $service->id }}" id="serviceupdate" class="needs-validation js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')
                    
                    <!-- Add this hidden input -->
                    <input type="hidden" id="current_product_id" value="{{ $service->product_id }}">

                    <div class="row g-3">
                        {{-- Product --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Product <span class="text-danger">*</span>
                            </label>

                            <select name="product_id" id="product_id" class="form-select" required>
                                <option value="">Loading products...</option>
                            </select>

                            <div class="invalid-feedback" id="product_id-error"></div>
                        </div>

                        {{-- Service Name --}}
                        <div class="col-md-6">
                            <label class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="service_name" id="service_name" value="{{ $service->service_name }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="service_name-error"></div>
                        </div>

                        {{-- Price --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Service Price <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="service_price" id="service_price" value="{{ $service->service_price }}"
                                class="form-control" step="0.01" required>

                            <div class="invalid-feedback" id="service_price-error"></div>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>

                            <select name="status" id="status" class="form-select js-status-comment-trigger" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ $service->status == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="inactive" {{ $service->status == 'inactive' ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>

                            <div class="invalid-feedback" id="status-error"></div>
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label class="form-label">
                                Description
                            </label>

                            <textarea name="description" id="description" rows="2" class="form-control"
                                placeholder="Enter service details..." required>{{ $service->description }}</textarea>

                            <div class="invalid-feedback" id="description-error"></div>

                            <small class="text-muted">
                                Maximum 2000 characters
                            </small>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('services.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $service->statusHistories])
    </div>

    <!-- Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1050" id="toastContainer"></div>
@endsection

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/service.js' : '/js/service.js') }}"></script>
@endpush
