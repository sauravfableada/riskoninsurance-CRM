@extends('layouts.app')

@section('page_title', 'Projects - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden project-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Project</h1>
                        <p class="text-muted small mb-0">Create a new project for your customer.</p>
                    </div>
                    <a href="{{ route('projects.index') }}" class="btn btn-dark-blue project-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/projects" id="projectForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                            <input name="name" id="name" value="{{ old('name') }}" class="form-control" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select"
                                data-search-url="{{ route('api.customers.search') }}" data-search-type="customer"
                                data-search-placeholder="-- Search Customer --" required>
                                <option value="">-- Search Customer --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                        data-phone="{{ $customer->phone }}" @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            @include('crm.partials.assigned-user-field', ['users' => $users])
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="">Select Status</option>
                                @foreach (['pending' => 'Pending', 'ongoing' => 'Active', 'completed' => 'Completed', 'canceled' => 'Cancelled'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="start_date-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="end_date-error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="description" rows="2" class="form-control"
                                placeholder="Enter project details..." required>{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/project.js' : '/js/project.js') }}"></script>
    @endpush
@endsection
