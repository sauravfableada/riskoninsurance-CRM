@extends('layouts.app')

@section('page_title', 'Upload Life Receipt')

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
                    <h1 class="h4 mb-1 fw-semibold">Upload Life Receipt</h1>
                    <p class="text-muted small mb-0">Select a family group and policy holder to upload a receipt.</p>
                </div>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="card-body p-3 p-md-4">
            <form id="receiptFilterForm" novalidate>
                <div class="row g-3 align-items-end">

                    {{-- Family Group --}}
                    <div class="col-md-4">
                        <label class="form-label d-flex align-items-center gap-2" for="familyGroupSelect">
                            <i class="bi bi-people-fill"></i> Family Group <span class="text-danger ms-1">*</span>
                        </label>
                        <select class="form-select" id="familyGroupSelect" name="family_group">
                            <option value="">Select Family Group</option>
                            @foreach($familyGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                            @endforeach
                        </select>
                        <div class="staff-validation" id="familyGroupSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Policy Holder Name --}}
                    <div class="col-md-4">
                        <label class="form-label d-flex align-items-center gap-2" for="policyHolderSelect">
                            <i class="bi bi-person-fill"></i> Policy Holder Name <span class="text-danger ms-1">*</span>
                        </label>
                        <select class="form-select" id="policyHolderSelect" name="policy_holder" disabled>
                            <option value="">Select Family Group First</option>
                        </select>
                        <div class="staff-validation" id="policyHolderSelect_error" style="display:none;"></div>
                    </div>

                    {{-- Way of Receipt Upload --}}
                    <div class="col-md-3">
                        <label class="form-label d-flex align-items-center gap-2" for="uploadType">
                            <i class="bi bi-upload"></i> Way of Receipt Upload
                        </label>
                        <select class="form-select" id="uploadType" name="upload_type">
                            <option value="single">One policy one receipt</option>
                            <option value="multiple">Multiple policy one receipt</option>
                        </select>
                    </div>

                    {{-- Clear Button --}}
                    <div class="col-md-1 d-grid">
                        <button type="button" id="clearBtn" class="btn btn-outline-dark-blue">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </button>
                    </div>

                </div>

                {{-- Form Actions --}}
                <div class="mt-4 pt-4 border-top d-flex justify-content-end gap-2 form-actions">
                    <button type="submit" id="submitBtn" class="btn btn-dark-blue">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>

            {{-- Results Area --}}
            <div id="resultsArea" class="mt-4" style="display:none;">
                <div class="border-top pt-4">
                    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-receipt"></i> Results
                    </h6>
                    <div id="resultsContent"></div>
                </div>
            </div>

            {{-- Empty State (default) --}}
            <div id="emptyState" class="text-center py-5 text-muted mt-2">
                <i class="bi bi-file-earmark-text display-1 opacity-25"></i>
                <p class="mt-3 mb-0">Select a Family Group and Policy Holder to view records.</p>
            </div>

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

    // ── Family Group → Policy Holder ──────────────────────────────────────────

    $('#familyGroupSelect').on('change', function () {
        const familyGroupId = $(this).val();
        const $ph = $('#policyHolderSelect');

        clearError('familyGroupSelect');
        $ph.html('<option value="">Loading...</option>').prop('disabled', true);

        if (!familyGroupId) {
            $ph.html('<option value="">Select Family Group First</option>').prop('disabled', true);
            return;
        }

        $.ajax({
            url: "{{ route('upload-document.get-family-group-clients') }}",
            type: 'GET',
            data: { family_group_id: familyGroupId },
            dataType: 'json',
            success: function (res) {
                $ph.html('<option value="">Select Policy Holder</option>').prop('disabled', false);
                if (res.status && res.data.length > 0) {
                    $.each(res.data, function (i, client) {
                        $ph.append(`<option value="${client.ref_id}" data-type="${client.type}">${client.name}</option>`);
                    });
                } else {
                    $ph.html('<option value="">No holders found for this group</option>').prop('disabled', false);
                }
            },
            error: function () {
                $ph.html('<option value="">Error loading holders</option>').prop('disabled', false);
            }
        });
    });

    $('#policyHolderSelect').on('change', function () { clearError('policyHolderSelect'); });

    // ── Clear ─────────────────────────────────────────────────────────────────

    $('#clearBtn').on('click', function () {
        $('#familyGroupSelect').val('');
        $('#policyHolderSelect').html('<option value="">Select Family Group First</option>').prop('disabled', true);
        $('#uploadType').val('single');
        clearError('familyGroupSelect');
        clearError('policyHolderSelect');
        $('#resultsArea').hide();
        $('#emptyState').show();
    });

    // ── Submit ────────────────────────────────────────────────────────────────

    $('#receiptFilterForm').on('submit', function (e) {
        e.preventDefault();

        let valid = true;

        if (!$('#familyGroupSelect').val()) {
            setError('familyGroupSelect', 'Please select a Family Group.'); valid = false;
        } else { clearError('familyGroupSelect'); }

        if (!$('#policyHolderSelect').val()) {
            setError('policyHolderSelect', 'Please select a Policy Holder.'); valid = false;
        } else { clearError('policyHolderSelect'); }

        if (!valid) return;

        // Show results placeholder — extend this section with actual data loading
        $('#emptyState').hide();
        $('#resultsArea').show();
        $('#resultsContent').html(`
            <div class="text-center py-4 text-muted">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="mb-0">Loading records...</p>
            </div>
        `);

        // TODO: Replace with actual AJAX call to load receipts
        setTimeout(function () {
            $('#resultsContent').html(`
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox display-4 opacity-25"></i>
                    <p class="mt-3 mb-0">No receipts found for the selected criteria.</p>
                </div>
            `);
        }, 800);
    });

});
</script>
@endpush
