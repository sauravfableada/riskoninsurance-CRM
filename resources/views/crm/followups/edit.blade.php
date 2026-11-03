@extends('layouts.app')

@section('page_title', 'Follow Ups - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden followup-form-card">
            <div class="card-body p-0">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Edit Follow Up</h1>
                            <p class="text-muted small mb-0">Update follow up details.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            @can('followups.view')
                                <a href="{{ route('followups.show', $followUp) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            @endcan
                            <a href="{{ route('followups.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                @php
                    if (!function_exists('field_label') && file_exists(app_path('Helpers/FieldCustomizationHelper.php'))) {
                        require_once app_path('Helpers/FieldCustomizationHelper.php');
                    }
                    $customService = app(\App\Services\FieldCustomizationService::class);
                    $fieldLabel = function(string $field, string $default) use ($customService) {
                        return $customService->getFieldLabel('followups', $field, $default);
                    };
                    $isRequired = function(string $field, bool $default = false) use ($customService) {
                        return $customService->isFieldRequired('followups', $field);
                    };
                    $isVisible = function(string $field, bool $default = true) use ($customService) {
                        return $customService->isFieldVisible('followups', $field);
                    };
                @endphp
                <form method="POST" action="/api/follow-ups/{{ $followUp->id }}"
                    class="needs-validation ajax-followup-form js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        @if($isVisible('lead_id', true))
                        <div class="col-md-6">
                            <label class="form-label">{{ $fieldLabel('lead_id', 'Lead Name') }} @if($isRequired('lead_id', false))<span class="text-danger">*</span>@endif</label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="lead_id" id="lead_id" class="form-select select2" {{ $isRequired('lead_id', false) ? 'required' : '' }}>
                                        <option value="">Select Lead</option>
                                        @foreach ($leads as $lead)
                                            <option value="{{ $lead->id }}" @selected(old('lead_id', $followUp->lead_id) == $lead->id)>
                                                {{ $lead->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback d-block" id="lead_id-error"></div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddLead" title="Add New Lead"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($isVisible('assigned_user_id', true))
                        <div class="col-md-6">
                            @include('crm.partials.assigned-user-field', [
                                'users' => $users, 
                                'selected' => $followUp->assigned_user_id ?? auth()->id(), 
                                'label' => $fieldLabel('assigned_user_id', 'Assigned Staff / Agent'),
                                'required' => $isRequired('assigned_user_id', false),
                                'searchPlaceholder' => 'Select Staff'
                            ])
                        </div>
                        @endif

                        @if($isVisible('purpose', true))
                        <div class="col-md-6">
                            <label class="form-label">{{ $fieldLabel('purpose', 'Purpose') }} @if($isRequired('purpose', false))<span class="text-danger">*</span>@endif</label>
                            <input name="purpose" id="purpose" value="{{ old('purpose', $followUp->purpose) }}"
                                class="form-control" placeholder="{{ $fieldLabel('purpose', 'Purpose') }}" {{ $isRequired('purpose', false) ? 'required' : '' }}>
                            <div class="invalid-feedback d-block" id="purpose-error"></div>
                        </div>
                        @endif

                        @if($isVisible('follow_up_at', true))
                        <div class="col-md-6">
                            <label class="form-label">{{ $fieldLabel('follow_up_at', 'Follow Up Date & Time') }} @if($isRequired('follow_up_at', true))<span class="text-danger">*</span>@endif</label>
                            <input type="datetime-local" name="follow_up_at" id="follow_up_at"
                                value="{{ old('follow_up_at', \Illuminate\Support\Carbon::parse($followUp->follow_up_at)->format('Y-m-d\\TH:i')) }}"
                                class="form-control" {{ $isRequired('follow_up_at', true) ? 'required' : '' }}>
                            <div class="invalid-feedback d-block" id="follow_up_at-error"></div>
                        </div>
                        @endif

                        @if($isVisible('priority', true))
                        <div class="col-md-6">
                            <label class="form-label">{{ $fieldLabel('priority', 'Priority') }} @if($isRequired('priority', true))<span class="text-danger">*</span>@endif</label>
                            <select name="priority" id="priority" class="form-select" {{ $isRequired('priority', true) ? 'required' : '' }}>
                                <option value="low" @selected(old('priority', $followUp->priority) == 'low')>Low</option>
                                <option value="medium" @selected(old('priority', $followUp->priority) == 'medium')>Medium
                                </option>
                                <option value="high" @selected(old('priority', $followUp->priority) == 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error"></div>
                        </div>
                        @endif

                        @if($isVisible('status', true))
                        <div class="col-md-6">
                            <label class="form-label">{{ $fieldLabel('status', 'Status') }} @if($isRequired('status', true))<span class="text-danger">*</span>@endif</label>
                            <select name="status" id="status" class="form-select js-status-comment-trigger" {{ $isRequired('status', true) ? 'required' : '' }}>
                                <option value="pending" @selected(old('status', $followUp->status) == 'pending')>Pending
                                </option>
                                <option value="resheduled" @selected(old('status', $followUp->status) == 'resheduled')>
                                    Rescheduled</option>
                                <option value="completed" @selected(old('status', $followUp->status) == 'completed')>Completed
                                </option>
                                <option value="cancelled" @selected(old('status', $followUp->status) == 'cancelled')>Cancelled
                                </option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error"></div>
                        </div>
                        @endif

                        @if($isVisible('comment', true))
                        <div class="col-12">
                            <label class="form-label">{{ $fieldLabel('comment', 'Comment') }} @if($isRequired('comment', false))<span class="text-danger">*</span>@endif</label>
                            <textarea name="comment" id="comment" rows="2"
                                class="form-control" placeholder="{{ $fieldLabel('comment', 'Comment') }}" {{ $isRequired('comment', false) ? 'required' : '' }}>{{ old('comment', $followUp->comment) }}</textarea>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 followup-form-actions">
                        <a href="{{ route('followups.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">update</button>
                    </div>
                </form>

                @include('crm.partials.status-history-table', ['histories' => $followUp->statusHistories])
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
                            <input type="text" class="form-control" name="name" placeholder="Full name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" list="quickLeadCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone number" maxlength="15" inputmode="numeric">
                            </div>
                            <datalist id="quickLeadCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address" placeholder="Address">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select">
                                    <option value="new">New</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="working">Working</option>
                                    <option value="ready_to_close">Ready to Close</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Source <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="source" placeholder="Source">
                            </div>
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
    
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.5.2/dist/select2-bootstrap-5-theme.min.css"
            rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
        <style>
            @media (max-width: 767.98px) {
                .followup-form-actions .btn,
                .followup-back-btn {
                    width: 100%;
                }
            
                .followup-form-actions {
                    padding-top: 1rem !important;
                    margin-top: 1.5rem !important;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/followup.js' : '/js/followup.js') }}"></script>
        <script>
            $(document).ready(function () {

                $.ajax({
                    type: "GET",
                    url: "/api/follow-ups/{{ $followUp->id }}",
                    success: function (res) {

                        let data = res.data;

                        // Set input values
                        $('input[name="purpose"]').val(data.purpose);
                        $('textarea[name="comment"]').val(data.comment);

                        // Select dropdowns
                        $('select[name="assigned_user_id"]').val(data.assigned_user_id).trigger('change');
                        $('select[name="lead_id"]').val(data.lead_id).trigger('change');
                        $('select[name="priority"]').val(data.priority).trigger('change');
                        $('select[name="status"]').val(data.status).trigger('change');

                        // Format datetime-local
                        if (data.follow_up_at) {
                            let dt = data.follow_up_at.replace(' ', 'T').slice(0, 16);
                            $('input[name="follow_up_at"]').val(dt);
                        }
                    },
                    error: function (err) {
                        // console.log(err);
                    }
                });

            });
        </script>
    @endpush
@endsection
