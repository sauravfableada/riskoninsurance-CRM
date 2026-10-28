@extends('layouts.app')

@section('page_title', 'Meetings - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden meeting-form-card">
            <div class="card-body p-0">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Edit Meeting</h1>
                            <p class="text-muted small mb-0">Update meeting details.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            <a href="{{ route('meetings.show', $meeting->id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            <a href="{{ route('meetings.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                <form method="POST" action="/api/meetings/{{ $meeting->id }}" id="meetingupdate"
                    class="needs-validation js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="meeting_id" value="{{ $meeting->id }}">

                    <div class="row g-3">
                        <!-- Meeting Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" value="{{ old('title', $meeting->title) }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>

                        <!-- Customer (Loaded via API) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="customer_id" id="customer_id" class="form-select"
                                        data-search-url="{{ route('api.customers.search') }}" data-search-type="customer"
                                        data-search-placeholder="-- Search Customer --" required>
                                        <option value="">-- Search Customer --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                                data-phone="{{ $customer->phone }}"
                                                {{ old('customer_id', $meeting->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="customer_id-error"></div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddCustomer" title="Add New Customer"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Staff (Loaded via API) -->
                        <div class="col-md-6">
                            @include('crm.partials.assigned-user-field', ['users' => $users, 'label' => 'Assigned Staff', 'selected' => $meeting->assigned_user_id])
                        </div>

                        <!-- Scheduled At -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Scheduled On <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                value="{{ old('scheduled_at', \Carbon\Carbon::parse($meeting->scheduled_at)->format('Y-m-d\TH:i')) }}"
                                class="form-control" required>
                            <div class="invalid-feedback" id="scheduled_at-error"></div>
                        </div>

                        <!-- Meeting Type -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Type</label>
                            <select name="meeting_type" id="meeting_type" class="form-select">
                                <option value="">Select Meeting Type</option>
                                <option value="online" {{ $meeting->meeting_type == 'online' ? 'selected' : '' }}>Online
                                </option>
                                <option value="offline" {{ $meeting->meeting_type == 'offline' ? 'selected' : '' }}>Offline
                                </option>
                                <option value="phone" {{ $meeting->meeting_type == 'phone' ? 'selected' : '' }}>Phone Call
                                </option>
                                <option value="video" {{ $meeting->meeting_type == 'video' ? 'selected' : '' }}>Video
                                    Conference</option>
                            </select>
                            <div class="invalid-feedback" id="meeting_type-error"></div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select js-status-comment-trigger" required>
                                <option value="scheduled" {{ $meeting->status == 'scheduled' ? 'selected' : '' }}>Scheduled
                                </option>
                                <option value="completed" {{ $meeting->status == 'completed' ? 'selected' : '' }}>Completed
                                </option>
                                <option value="cancelled" {{ $meeting->status == 'cancelled' ? 'selected' : '' }}>Cancelled
                                </option>  
                            </select>  
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>  

                        <!-- Agenda (Optional) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Agenda</label>
                            <textarea name="agenda" id="agenda" rows="4" class="form-control"
                                placeholder="Enter meeting agenda (Optional)">{{ old('agenda', $meeting->agenda) }}</textarea>
                            <div class="invalid-feedback" id="agenda-error"></div>
                        </div>

                        <!-- Address (Optional) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" id="address" rows="4" class="form-control"
                                placeholder="Enter meeting address/location (Optional)">{{ old('address', $meeting->address) }}</textarea>
                            <div class="invalid-feedback" id="address-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('meetings.index') }}" class="btn btn-outline-dark-blue px-4">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-dark-blue px-4">Update</button>
                    </div>
                </form>
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $meeting->statusHistories])
    </div>

    <!-- Modal: Add Customer -->
    <div class="modal fade" id="modalAddCustomer" tabindex="-1" aria-labelledby="modalAddCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#0a2540;">
                    <h5 class="modal-title text-white fw-bold" id="modalAddCustomerLabel">Add Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAddCustomer" action="/api/customers" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Full name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" list="quickCustomerCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone number" maxlength="15" inputmode="numeric">
                            </div>
                            <datalist id="quickCustomerCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span class="btn-text">Save</span>
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
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/meeting.js' : '/js/meeting.js') }}"></script>
@endpush
