@extends('layouts.app')

@section('page_title', 'Add Meeting')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden meeting-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Meeting</h1>
                        <p class="text-muted small mb-0">Schedule a new meeting with a customer.</p>
                    </div>
                    <a href="{{ route('meetings.index') }}" class="btn btn-dark-blue meeting-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                @php
                    if (!function_exists('field_label') && file_exists(app_path('Helpers/FieldCustomizationHelper.php'))) {
                        require_once app_path('Helpers/FieldCustomizationHelper.php');
                    }
                    $customService = app(\App\Services\FieldCustomizationService::class);
                    $fieldLabel = function(string $field, string $default) use ($customService) {
                        return $customService->getFieldLabel('meetings', $field, $default);
                    };
                    $isRequired = function(string $field, bool $default = false) use ($customService) {
                        return $customService->isFieldRequired('meetings', $field);
                    };
                    $isVisible = function(string $field, bool $default = true) use ($customService) {
                        return $customService->isFieldVisible('meetings', $field);
                    };
                @endphp

                <form method="POST" action="/api/meetings" id="meetingForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="row g-4">
                        <!-- Customer / Lead Selection -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0" id="meetingParticipantLabel">
                                    {{ $fieldLabel('customer_id', 'Customer') }} <span class="text-danger">*</span>
                                </label>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Meeting Target Type">
                                    <input type="radio" class="btn-check" name="participant_type" id="participant_customer" value="customer" checked autocomplete="off">
                                    <label class="btn btn-outline-dark-blue btn-sm py-0 px-2 fw-medium" for="participant_customer">
                                        <i class="bi bi-person me-1"></i>{{ $fieldLabel('customer_id', 'Customer') }}
                                    </label>

                                    <input type="radio" class="btn-check" name="participant_type" id="participant_lead" value="lead" autocomplete="off">
                                    <label class="btn btn-outline-dark-blue btn-sm py-0 px-2 fw-medium" for="participant_lead">
                                        <i class="bi bi-person-lines-fill me-1"></i>{{ $fieldLabel('lead_id', 'Lead') }}
                                    </label>
                                </div>
                            </div>

                            <!-- Customer Select Container -->
                            <div id="customerSelectContainer">
                                <div class="d-flex gap-2">
                                    <div class="flex-grow-1">
                                        <select name="customer_id" id="customer_id" class="form-select"
                                            data-search-url="{{ route('api.customers.search') }}" data-search-type="customer"
                                            data-search-placeholder="-- {{ $fieldLabel('customer_id', 'Search Customer') }} --">
                                            <option value="">-- {{ $fieldLabel('customer_id', 'Search Customer') }} --</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                                    data-phone="{{ $customer->phone }}">
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

                            <!-- Lead Select Container -->
                            <div id="leadSelectContainer" class="d-none">
                                <div class="d-flex gap-2">
                                    <div class="flex-grow-1">
                                        <select name="lead_id" id="lead_id" class="form-select"
                                            data-search-url="{{ route('api.leads.search') }}" data-search-type="lead"
                                            data-search-placeholder="-- {{ $fieldLabel('lead_id', 'Search Lead') }} --">
                                            <option value="">-- {{ $fieldLabel('lead_id', 'Search Lead') }} --</option>
                                            @foreach ($leads as $lead)
                                                <option value="{{ $lead->id }}" data-email="{{ $lead->email }}"
                                                    data-phone="{{ $lead->phone }}">
                                                    {{ $lead->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="lead_id-error"></div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddLead" title="Add New Lead"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned To -->
                        @if($isVisible('assigned_user_id', true))
                        <div class="col-md-6">
                            @include('crm.partials.assigned-user-field', [
                                'users' => $users,
                                'label' => $fieldLabel('assigned_user_id', 'Assigned To'),
                                'required' => $isRequired('assigned_user_id', false)
                            ])
                        </div>
                        @endif

                        <!-- Meeting Title -->
                        @if($isVisible('title', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('title', 'Meeting Title') }} @if($isRequired('title', true))<span class="text-danger">*</span>@endif</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="{{ $fieldLabel('title', 'Meeting Title') }}" {{ $isRequired('title', true) ? 'required' : '' }}>
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>
                        @endif

                        <!-- Scheduled On -->
                        @if($isVisible('scheduled_at', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('scheduled_at', 'Scheduled On') }} @if($isRequired('scheduled_at', true))<span class="text-danger">*</span>@endif</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                                value="{{ old('scheduled_at') }}" {{ $isRequired('scheduled_at', true) ? 'required' : '' }}>
                            <div class="invalid-feedback" id="scheduled_at-error"></div>
                        </div>
                        @endif

                        <!-- Meeting Type -->
                        @if($isVisible('meeting_type', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('meeting_type', 'Meeting Type') }} @if($isRequired('meeting_type', false))<span class="text-danger">*</span>@endif</label>
                            <select name="meeting_type" id="meeting_type" class="form-select" {{ $isRequired('meeting_type', false) ? 'required' : '' }}>
                                <option value="">Select Meeting Type</option>
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                                <option value="phone">Phone Call</option>
                                <option value="video">Video Conference</option>
                            </select>
                            <div class="invalid-feedback" id="meeting_type-error"></div>
                        </div>
                        @endif

                        <!-- Status -->
                        @if($isVisible('status', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('status', 'Status') }} @if($isRequired('status', true))<span class="text-danger">*</span>@endif</label>
                            <select name="status" id="status" class="form-select" {{ $isRequired('status', true) ? 'required' : '' }}>
                                <option value="scheduled" selected>Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>
                        @endif

                        <!-- Agenda (Optional) -->
                        @if($isVisible('agenda', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('agenda', 'Agenda') }} @if($isRequired('agenda', false))<span class="text-danger">*</span>@endif</label>
                            <textarea name="agenda" id="agenda" rows="4" class="form-control"
                                placeholder="{{ $fieldLabel('agenda', 'Enter meeting agenda (Optional)') }}" {{ $isRequired('agenda', false) ? 'required' : '' }}></textarea>
                            <div class="invalid-feedback" id="agenda-error"></div>
                        </div>
                        @endif

                        <!-- Address (Optional) -->
                        @if($isVisible('address', true))
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $fieldLabel('address', 'Address') }} @if($isRequired('address', false))<span class="text-danger">*</span>@endif</label>
                            <textarea name="address" id="address" rows="4" class="form-control"
                                placeholder="{{ $fieldLabel('address', 'Enter meeting address/location (Optional)') }}" {{ $isRequired('address', false) ? 'required' : '' }}></textarea>
                            <div class="invalid-feedback" id="address-error"></div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('meetings.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-dark-blue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
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

    <!-- Modal: Add Lead -->
    <div class="modal fade" id="modalAddLead" tabindex="-1" aria-labelledby="modalAddLeadLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#0a2540;">
                    <h5 class="modal-title text-white fw-bold" id="modalAddLeadLabel">Add Lead</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAddLead" action="/api/leads" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Full name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" list="quickLeadMeetingCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone number" maxlength="15" inputmode="numeric" required>
                            </div>
                            <datalist id="quickLeadMeetingCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
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
