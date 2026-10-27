@extends('layouts.app')

@section('page_title', 'Projects - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden project-form-card">
            <div class="card-body p-0">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Edit Project</h1>
                            <p class="text-muted small mb-0">Update project information.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            @can('projects.view')
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            @endcan
                            <a href="{{ route('projects.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                <form method="POST" action="/api/projects/{{ $project->id }}" id="projectForm"
                    class="needs-validation js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                            <input name="name" id="name" value="{{ old('name', $project->name) }}" class="form-control"
                                required>
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
                                        data-phone="{{ $customer->phone }}" @selected(old('customer_id', $project->customer_id) == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            @include('crm.partials.assigned-user-field', ['users' => $users, 'selected' => $project->assigned_user_id])
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select js-status-comment-trigger" required>
                                <option value="">Select Status</option>
                                @foreach (['pending' => 'Pending', 'ongoing' => 'Active', 'completed' => 'Completed', 'canceled' => 'Cancelled'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status', $project->status) === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date"
                                value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="form-control"
                                required>
                            <div class="invalid-feedback" id="start_date-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date"
                                value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}" class="form-control"
                                required>
                            <div class="invalid-feedback" id="end_date-error"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="description" rows="2" class="form-control"
                                placeholder="Enter project details..."
                                required>{{ old('description', $project->description) }}</textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $project->statusHistories])
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/project.js' : '/js/project.js') }}"></script>
    @endpush
@endsection
