@extends('layouts.app')

@section('page_title', 'Raise Ticket')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden ticket-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Raise Ticket</h1>
                        <p class="text-muted small mb-0">Create a new support request.</p>
                    </div>
                    <a href="{{ route('tickets.index') }}" class="btn btn-dark-blue ticket-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">

            <form id="ticketForm" action="/api/tickets" method="POST" class="needs-validation ajax-ticket-form" data-redirect="{{ route('tickets.index') }}" novalidate>
                @csrf
                <div id="formErrors" class="alert alert-danger d-none"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select" data-search-url="{{ route('api.customers.search') }}" data-search-type="customer" data-search-placeholder="-- Search Customer --" required>
                            <option value="">-- Search Customer --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->email }})
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="customer_id-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="ticket_name" class="form-label fw-semibold">Ticket Name <span class="text-danger">*</span></label>
                        <input type="text" name="ticket_name" id="ticket_name" class="form-control" value="{{ old('ticket_name') }}" required>
                        <div class="invalid-feedback" id="ticket_name-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="">Select Status</option>
                            @foreach(['Open', 'In Progress', 'Resolved', 'Closed'] as $status)
                                <option value="{{ $status }}" {{ old('status') == $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="status-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="priority" class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                        <select name="priority" id="priority" class="form-select" required>
                            <option value="">Select Priority</option>
                            <option value="Low" {{ old('priority') == 'Low' ? 'selected' : '' }}>Low</option>
                            <option value="Medium" {{ old('priority', 'Medium') == 'Medium' ? 'selected' : '' }}>Medium</option>
                            <option value="High" {{ old('priority') == 'High' ? 'selected' : '' }}>High</option>
                            <option value="Urgent" {{ old('priority') == 'Urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        <div class="invalid-feedback" id="priority-error"></div>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="2" class="form-control" required>{{ old('description') }}</textarea>
                        <div class="invalid-feedback" id="description-error"></div>
                        <small class="text-muted">Maximum 2000 characters</small>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                        <span id="btnText">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/tickets-api.js' : '/js/tickets-api.js') }}"></script>
@endpush
