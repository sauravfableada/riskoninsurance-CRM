@extends('layouts.app')

@section('page_title', 'Edit Client Document')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-form.css' : '/css/users-form.css') }}?v={{ filemtime(public_path('css/users-form.css')) }}">
    <style>
        .file-card { transition: box-shadow .2s; }
        .file-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .file-thumb { height: 100px; width: 100%; object-fit: cover; border-radius: 6px; }
        .delete-file-btn { width:24px; height:24px; padding:0; font-size:11px; line-height:1; }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden staff-edit-card">

        {{-- Header --}}
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Edit Client Document</h1>
                    <p class="text-muted small mb-0">Update document details and files.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('upload-document.list') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-angle-left pe-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- Form Body --}}
        <div class="card-body p-3 p-md-4">
            <form id="clientDocumentForm" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="id" value="{{ $document->id }}">

                <div class="row g-3">

                    {{-- Family Group --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="familyGroupSelect">
                            <i class="bi bi-people-fill"></i> Family Group <span class="text-danger ms-1">*</span>
                        </label>
                        <select name="family_group_id" id="familyGroupSelect" class="form-select">
                            <option value="">Select Family Group</option>
                            @foreach($familyGroups as $group)
                                <option value="{{ $group->id }}" {{ $document->family_group_id == $group->id ? 'selected' : '' }}>
                                    {{ $group->group_name }}
                                </option>
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
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ $document->client_id == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
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
                                <option value="{{ $type->id }}" {{ $document->document_type_id == $type->id ? 'selected' : '' }}>
                                    {{ $type->document_type }}
                                </option>
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
                            @foreach($documents as $doc)
                                <option value="{{ $doc->id }}" {{ $document->document_id == $doc->id ? 'selected' : '' }}>
                                    {{ $doc->document_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="staff-validation" id="DocumentSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Due Date --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="due_date">
                            <i class="bi bi-calendar-event-fill"></i> Due Date
                        </label>
                        <input type="date" id="due_date" name="due_date" class="form-control"
                            value="{{ $document->due_date ? \Carbon\Carbon::parse($document->due_date)->format('Y-m-d') : '' }}" min="{{ date('Y-m-d') }}">
                    </div>

                    {{-- Document Number --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="document_number">
                            <i class="bi bi-hash"></i> Document Number
                        </label>
                        <input type="text" id="document_number" name="document_number" class="form-control"
                            value="{{ old('document_number', $document->document_number) }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="status">
                            <i class="bi bi-toggle-on"></i> Status
                        </label>
                        <select name="status" id="status" class="form-select">
                            <option value="1" {{ $document->status == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $document->status == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    {{-- Upload Additional Documents --}}
                    <div class="col-md-6">
                        <label class="form-label d-flex align-items-center gap-2" for="documentUpload">
                            <i class="bi bi-upload"></i> Upload Additional Documents
                        </label>
                        <input type="file" id="documentUpload" class="form-control" name="document[]"
                               multiple accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Accepted: JPG, PNG, PDF — max 5MB each</small>
                    </div>

                    {{-- Remarks --}}
                    <div class="col-12">
                        <label class="form-label d-flex align-items-center gap-2" for="remarks">
                            <i class="bi bi-chat-left-text-fill"></i> Remarks
                        </label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3">{{ old('remarks', $document->remarks) }}</textarea>
                    </div>

                    {{-- Existing Files --}}
                    @if($files->isNotEmpty())
                    <div class="col-12">
                        <label class="form-label d-flex align-items-center gap-2">
                            <i class="bi bi-paperclip"></i> Existing Files
                        </label>
                        <div class="row g-3" id="existingFilesContainer">
                            @foreach($files as $file)
                                @php $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)); @endphp
                                <div class="col-6 col-md-3 col-lg-2" id="file-card-{{ $file->id }}">
                                    <div class="border rounded-3 p-2 text-center position-relative file-card h-100 bg-light">
                                        <button type="button"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-file-btn"
                                                data-file-id="{{ $file->id }}" title="Delete File">
                                            <i class="fa fa-times"></i>
                                        </button>

                                        @if(in_array($ext, ['jpg','jpeg','png']))
                                            <img src="{{ asset($file->file_path) }}"
                                                 alt="{{ $file->file_name }}"
                                                 class="file-thumb mb-2"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="d-none align-items-center justify-content-center mb-2" style="height:100px;">
                                                <i class="bi bi-image text-secondary" style="font-size:2.5rem;"></i>
                                            </div>
                                        @elseif($ext === 'pdf')
                                            <div class="d-flex align-items-center justify-content-center mb-2" style="height:100px;">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size:2.5rem;"></i>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center mb-2" style="height:100px;">
                                                <i class="bi bi-file-earmark-fill text-secondary" style="font-size:2.5rem;"></i>
                                            </div>
                                        @endif

                                        <p class="mb-2 small text-truncate text-muted" title="{{ $file->file_name }}">
                                            {{ $file->file_name }}
                                        </p>
                                        <a href="{{ asset($file->file_path) }}" target="_blank"
                                           class="btn btn-sm btn-outline-dark-blue w-100">
                                            <i class="bi bi-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>

                {{-- Form Actions --}}
                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('upload-document.list') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-dark-blue">
                        <i class="fas fa-save me-1"></i> Update
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

    const documentId      = "{{ $document->id }}";
    const currentClientId = "{{ $document->client_id }}";
    const currentDocId    = "{{ $document->document_id }}";

    // ── Validation ────────────────────────────────────────────────────────────
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
        if (!$('#familyGroupSelect').val())  { setError('familyGroupSelect',  'Family Group is required.');  valid = false; } else { clearError('familyGroupSelect'); }
        if (!$('#clientSelect').val())       { setError('clientSelect',       'Client is required.');        valid = false; } else { clearError('clientSelect'); }
        if (!$('#documentTypeSelect').val()) { setError('documentTypeSelect', 'Document Type is required.'); valid = false; } else { clearError('documentTypeSelect'); }
        if (!$('#DocumentSelect').val())     { setError('DocumentSelect',     'Document Name is required.'); valid = false; } else { clearError('DocumentSelect'); }
        return valid;
    }

    $('#familyGroupSelect, #clientSelect, #documentTypeSelect, #DocumentSelect').on('change', function () {
        clearError(this.id);
    });

    // ── Family Group → Clients ────────────────────────────────────────────────
    $('#familyGroupSelect').on('change', function () {
        const familyGroupId = $(this).val();
        const $cs = $('#clientSelect');

        $cs.html('<option value="">Loading...</option>').prop('disabled', true);
        if (!familyGroupId) { $cs.html('<option value="">Select Client</option>').prop('disabled', false); return; }

        $.ajax({
            url: "{{ route('upload-document.get-family-group-clients') }}",
            type: 'GET',
            data: { family_group_id: familyGroupId },
            dataType: 'json',
            success: function (res) {
                $cs.html('<option value="">Select Client</option>').prop('disabled', false);
                if (res.status && res.data.length) {
                    $.each(res.data, function (i, c) {
                        $cs.append(`<option value="${c.ref_id}" ${c.ref_id == currentClientId ? 'selected' : ''}>${c.name}</option>`);
                    });
                }
            },
            error: function () { $cs.html('<option value="">Error loading clients</option>').prop('disabled', false); }
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
                if (res.status && res.data.length) {
                    $.each(res.data, function (i, d) {
                        $ds.append(`<option value="${d.id}" ${d.id == currentDocId ? 'selected' : ''}>${d.document_name}</option>`);
                    });
                }
            },
            error: function () { $ds.html('<option value="">Error loading documents</option>').prop('disabled', false); }
        });
    });

    // ── Delete file ───────────────────────────────────────────────────────────
    $(document).on('click', '.delete-file-btn', function () {
        const fileId = $(this).data('file-id');

        Swal.fire({
            title: 'Delete this file?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('upload-document/delete-file') }}/" + fileId,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                dataType: 'json',
                success: function (res) {
                    if (res.status === true) {
                        $('#file-card-' + fileId).fadeOut(300, function () { $(this).remove(); });
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error!', text: res.message });
                    }
                },
                error: function () { Swal.fire({ icon: 'error', title: 'Error!', text: 'Error deleting file.' }); }
            });
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
            url: "{{ url('upload-document/update') }}/" + documentId,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');
            },
            success: function (res) {
                if (res.status === true) {
                    Swal.fire({ icon: 'success', title: 'Updated!', text: res.message, timer: 2000, showConfirmButton: false })
                        .then(() => { window.location.href = "{{ route('upload-document.list') }}"; });
                } else if (res.errors) {
                    const map = { family_group_id: 'familyGroupSelect', client_id: 'clientSelect', document_type_id: 'documentTypeSelect', Document_id: 'DocumentSelect' };
                    $.each(res.errors, function (f, msgs) { setError(map[f] || f, msgs[0]); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error!', text: res.message });
                }
            },
            error: function () { Swal.fire({ icon: 'error', title: 'Server Error!', text: 'An error occurred. Please try again.' }); },
            complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Update'); }
        });
    });

});
</script>
@endpush
