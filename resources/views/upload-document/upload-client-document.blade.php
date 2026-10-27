@extends('layouts.app')

@section('page_title', 'Upload Client Document')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-form.css' : '/css/users-form.css') }}?v={{ filemtime(public_path('css/users-form.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden staff-form-card">

        {{-- Header --}}
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Upload Client Document</h1>
                    <p class="text-muted small mb-0">Upload and attach documents to a client's family group.</p>
                </div>
                <a href="{{ route('upload-document.list') }}" class="btn btn-dark-blue">
                    <i class="fa-solid fa-angle-left pe-1"></i> Back
                </a>
            </div>
        </div>

        {{-- Body --}}
        <div class="card-body p-3 p-md-4">
            <form id="clientDocumentForm" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="row g-3">

                    {{-- Family Group --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="familyGroupSelect">
                            <i class="bi bi-people-fill"></i> Family Group <span class="text-danger ms-1">*</span>
                        </label>
                        <select name="family_group_id" id="familyGroupSelect" class="form-select">
                            <option value="">Select Family Group</option>
                            @foreach($familyGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                            @endforeach
                        </select>
                        <div class="staff-validation" id="familyGroupSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Client --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="clientSelect">
                            <i class="bi bi-person-fill"></i> Client <span class="text-danger ms-1">*</span>
                        </label>
                        <select name="client_id" id="clientSelect" class="form-select">
                            <option value="">Select Client</option>
                        </select>
                        <div class="staff-validation" id="clientSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Document Type --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="documentTypeSelect">
                            <i class="bi bi-folder-fill"></i> Document Type <span class="text-danger ms-1">*</span>
                        </label>
                        <select name="document_type_id" id="documentTypeSelect" class="form-select">
                            <option value="">Select Document Type</option>
                            @foreach($document_type as $type)
                                <option value="{{ $type->id }}">{{ $type->document_type }}</option>
                            @endforeach
                        </select>
                        <div class="staff-validation" id="documentTypeSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Document Name --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="DocumentSelect">
                            <i class="bi bi-file-earmark-text-fill"></i> Document Name <span class="text-danger ms-1">*</span>
                        </label>
                        <select name="Document_id" id="DocumentSelect" class="form-select">
                            <option value="">Select Document</option>
                        </select>
                        <div class="staff-validation" id="DocumentSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Upload Document --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="documentInput">
                            <i class="bi bi-upload"></i> Upload Document <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="file" id="documentInput" name="document[]" class="form-control"
                               multiple accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Accepted: JPG, PNG, PDF — max 5MB each</small>
                        <div class="staff-validation" id="documentInput_error" style="display:none;"></div>
                    </div>

                    {{-- Due Date --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="due_date">
                            <i class="bi bi-calendar-event-fill"></i> Due Date
                        </label>
                        <input type="date" id="due_date" name="due_date" class="form-control" min="{{ date('Y-m-d') }}">
                    </div>

                    {{-- Document Number --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="document_number">
                            <i class="bi bi-hash"></i> Document Number
                        </label>
                        <input type="text" id="document_number" name="document_number" class="form-control">
                    </div>

                    {{-- Remarks --}}
                    <div class="col-12">
                        <label class="form-label d-flex align-items-center gap-2" for="remarks">
                            <i class="bi bi-chat-left-text-fill"></i> Remarks
                        </label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3"></textarea>
                    </div>

                </div>

                {{-- Form Actions --}}
                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('upload-document.list') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-dark-blue">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ── Helpers ───────────────────────────────────────────────────────────────

    function setError(fieldId, message) {
        $('#' + fieldId).addClass('is-invalid');
        $('#' + fieldId + '_error').text(message).show();
    }

    function clearError(fieldId) {
        $('#' + fieldId).removeClass('is-invalid');
        $('#' + fieldId + '_error').text('').hide();
    }

    function validateForm() {
        let valid = true;

        if (!$('#familyGroupSelect').val()) {
            setError('familyGroupSelect', 'Family Group is required.'); valid = false;
        } else { clearError('familyGroupSelect'); }

        if (!$('#clientSelect').val()) {
            setError('clientSelect', 'Client is required.'); valid = false;
        } else { clearError('clientSelect'); }

        if (!$('#documentTypeSelect').val()) {
            setError('documentTypeSelect', 'Document Type is required.'); valid = false;
        } else { clearError('documentTypeSelect'); }

        if (!$('#DocumentSelect').val()) {
            setError('DocumentSelect', 'Document Name is required.'); valid = false;
        } else { clearError('DocumentSelect'); }

        if ($('#documentInput')[0].files.length === 0) {
            setError('documentInput', 'Please upload at least one document.'); valid = false;
        } else { clearError('documentInput'); }

        return valid;
    }

    // Clear on change
    $('#familyGroupSelect').on('change', function () { clearError('familyGroupSelect'); });
    $('#clientSelect').on('change', function ()      { clearError('clientSelect'); });
    $('#documentTypeSelect').on('change', function () { clearError('documentTypeSelect'); });
    $('#DocumentSelect').on('change', function ()    { clearError('DocumentSelect'); });
    $('#documentInput').on('change', function ()     { clearError('documentInput'); });

    // ── Family Group → Clients ────────────────────────────────────────────────

    $('#familyGroupSelect').on('change', function () {
        const familyGroupId = $(this).val();
        const $cs = $('#clientSelect');

        $cs.html('<option value="">Loading...</option>').prop('disabled', true);
        $('#DocumentSelect').html('<option value="">Select Document</option>');

        if (!familyGroupId) {
            $cs.html('<option value="">Select Client</option>').prop('disabled', false);
            return;
        }

        $.ajax({
            url: "{{ route('upload-document.get-family-group-clients') }}",
            type: 'GET',
            data: { family_group_id: familyGroupId },
            dataType: 'json',
            success: function (res) {
                $cs.html('<option value="">Select Client</option>').prop('disabled', false);
                if (res.status && res.data.length > 0) {
                    $.each(res.data, function (i, client) {
                        $cs.append(`<option value="${client.ref_id}" data-type="${client.type}">${client.name}</option>`);
                    });
                } else {
                    $cs.html('<option value="">No clients found</option>').prop('disabled', false);
                }
            },
            error: function () {
                $cs.html('<option value="">Error loading clients</option>').prop('disabled', false);
            }
        });
    });

    // ── Document Type → Document Names ───────────────────────────────────────

    $('#documentTypeSelect').on('change', function () {
        const typeId = $(this).val();
        const $ds = $('#DocumentSelect');

        $ds.html('<option value="">Select Document</option>');
        if (!typeId) return;

        $ds.html('<option value="">Loading...</option>').prop('disabled', true);

        $.ajax({
            url: "{{ route('upload-document.get-documents-by-type') }}",
            type: 'GET',
            data: { document_type_id: typeId },
            dataType: 'json',
            success: function (res) {
                $ds.html('<option value="">Select Document</option>').prop('disabled', false);
                if (res.status && res.data.length > 0) {
                    $.each(res.data, function (i, doc) {
                        $ds.append(`<option value="${doc.id}">${doc.document_name}</option>`);
                    });
                } else {
                    $ds.html('<option value="">No documents found</option>').prop('disabled', false);
                }
            },
            error: function () {
                $ds.html('<option value="">Error loading documents</option>').prop('disabled', false);
            }
        });
    });

    // ── Submit ────────────────────────────────────────────────────────────────

    $('#clientDocumentForm').on('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) {
            $('html, body').animate({ scrollTop: $('.is-invalid').first().offset().top - 100 }, 300);
            return;
        }

        const $btn = $('#submitBtn');

        $.ajax({
            url: "{{ route('upload-document.store') }}",
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
            },
            success: function (res) {
                if (res.status === true) {
                    Swal.fire({ icon: 'success', title: 'Success!', text: res.message, timer: 2000, showConfirmButton: false })
                        .then(() => { window.location.href = "{{ route('upload-document.list') }}"; });
                } else if (res.errors) {
                    const map = {
                        'family_group_id':  'familyGroupSelect',
                        'client_id':        'clientSelect',
                        'document_type_id': 'documentTypeSelect',
                        'Document_id':      'DocumentSelect',
                        'document':         'documentInput'
                    };
                    $.each(res.errors, function (field, messages) {
                        setError(map[field] || field, messages[0]);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error!', text: res.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Server Error!', text: 'An error occurred. Please try again.' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save');
            }
        });
    });

});
</script>
@endpush
