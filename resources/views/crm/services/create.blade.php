@extends('layouts.app')

@section('page_title', 'Services - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden service-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Service</h1>
                        <p class="text-muted small mb-0">Create a new service for an existing product.</p>
                    </div>
                    <a href="{{ route('services.index') }}" class="btn btn-dark-blue service-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/services" id="serviceForm" class="needs-validation" novalidate>
                    @csrf

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
                            <input type="text" name="service_name" id="service_name" value="{{ old('service_name') }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="service_name-error"></div>
                        </div>


                        {{-- Price --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Service Price <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="service_price" id="service_price" value="{{ old('service_price') }}"
                                class="form-control" step="0.01" required>

                            <div class="invalid-feedback" id="service_price-error"></div>
                        </div>


                        {{-- Status --}}
                        <div class="col-md-6">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>

                            <select name="status" id="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active" @selected(old('status') == 'active')>
                                    Active
                                </option>

                                <option value="inactive" @selected(old('status') == 'inactive')>
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
                                placeholder="Enter service details..." required>{{ old('description') }}</textarea>

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
                            <span id="btnText">Submit</span>
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>


    <!-- Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1050" id="toastContainer">
    </div>

@endsection

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/service.js' : '/js/service.js') }}"></script>
@endpush
