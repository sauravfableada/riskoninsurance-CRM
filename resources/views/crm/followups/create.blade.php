@extends('layouts.app')

@section('page_title', 'Follow Ups - Create')

@section('content')
    <div class="container-fluid p-0">

        <div class="card shadow-sm border-0 followup-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Schedule Follow Up</h1>
                        <p class="text-muted small mb-0">Create a new follow up record for a lead or customer.</p>
                    </div>
                    <a href="{{ route('followups.index') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-angle-left"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/follow-ups"
                    class="needs-validation ajax-followup-form followup-create-form" novalidate>
                    @csrf

                    <div class="row g-3 g-md-4">

                        <!-- Lead Name -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-medium">Lead Name <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="lead_id" id="lead_id" class="form-select select2" required>
                                        <option value="">Select Lead</option>
                                        @foreach ($leads as $lead)
                                            <option value="{{ $lead->id }}" @selected(old('lead_id') == $lead->id)>
                                                {{ $lead->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback d-block" id="lead_id-error"></div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddLead" title="Add New Lead"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned To -->
                        <div class="col-12 col-md-6">
                            @include('crm.partials.assigned-user-field', ['users' => $users, 'selected' => auth()->id(), 'searchPlaceholder' => 'Select Staff'])
                        </div>

                        <!-- Purpose -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-medium">Purpose <span class="text-danger">*</span></label>
                            <input name="purpose" id="purpose" value="{{ old('purpose') }}" class="form-control"
                                placeholder="Enter purpose of follow up" required>
                            <div class="invalid-feedback d-block" id="purpose-error"></div>
                        </div>

                        <!-- Follow Up Date -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-medium">Follow Up Date & Time <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local" name="follow_up_at" id="follow_up_at"
                                value="{{ old('follow_up_at') }}" class="form-control" required>
                            <div class="invalid-feedback d-block" id="follow_up_at-error"></div>
                        </div>
                        
                        <!-- Priority -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-medium">Priority <span class="text-danger">*</span></label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="low" @selected(old('priority') == 'low')>Low</option>
                                <option value="medium" @selected(old('priority', 'medium') == 'medium')>Medium</option>
                                <option value="high" @selected(old('priority') == 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error"></div>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="pending" @selected(old('status', 'pending') == 'pending')>Pending</option>
                                <option value="resheduled" @selected(old('status') == 'resheduled')>Rescheduled</option>
                                <option value="completed" @selected(old('status') == 'completed')>Completed</option>
                                <option value="cancelled" @selected(old('status') == 'cancelled')>Cancelled</option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error"></div>
                        </div>

                        <!-- Comment -->
                        <div class="col-12">
                            <label class="form-label fw-medium">Comment</label>
                            <textarea name="comment" id="comment" rows="3" class="form-control" placeholder="Add any additional notes...">{{ old('comment') }}</textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 followup-form-actions">
                        <a href="{{ route('followups.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">Submit</button>
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
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.5.2/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>

        .followup-create-form .select2-container,
        .followup-create-form .ts-wrapper {
            width: 100% !important;
        }

        @media (max-width: 767.98px) {
            .followup-form-card {
                border-radius: 18px;
                overflow: hidden;
            }

            .followup-create-form .row {
                --bs-gutter-x: 0.85rem;
                --bs-gutter-y: 1rem;
            }

            .followup-form-actions .btn {
                width: 100%;
            }

            .followup-form-actions {
                padding-top: 1rem !important;
                margin-top: 1.5rem !important;
            }
        }

        @media (max-width: 575.98px) {

            .followup-form-card .card-header,
            .followup-form-card .card-body {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/followup.js' : '/js/followup.js') }}"></script>
@endpush
