@extends('layouts.app')

@section('page_title', 'Edit Team Member')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-form.css' : '/css/users-form.css') }}?v={{ filemtime(public_path('css/users-form.css')) }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Edit Team Member</h1>
                    <p class="text-muted small mb-0">Update team profile, role, and module permissions.</p>
                </div>
                <a href="{{ route('team-members.index') }}" class="btn btn-dark-blue">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            @php
                $isSuperAdmin = (int) ($user['role'] ?? 5) === 3;
                $profileImage = !empty($user['profile']) ? asset('assets/img/profile/' . $user['profile']) : null;
            @endphp

            <form id="teamMemberForm" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="role" id="memberRole" value="5">

                {{-- Team Members always use the Staff role.
                <div class="mb-4">
                    <div class="d-flex flex-wrap gap-2" id="roleTabs">
                        <button type="button" class="btn btn-outline-dark-blue {{ $isSuperAdmin ? 'active' : '' }}" data-role="3" id="super-admin-tab">Super Admin</button>
                        <button type="button" class="btn btn-outline-dark-blue {{ !$isSuperAdmin ? 'active' : '' }}" data-role="5" id="sub-admin-tab">Sub Admin</button>
                    </div>
                </div> --}}

                <div class="border-bottom mb-4 pb-3">
                    <h5 class="mb-1">Basic Details</h5>
                    <p class="text-muted small mb-0">Update name, contact, and account status.</p>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="form-control" value="{{ $user['first_name'] }}" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" value="{{ $user['last_name'] }}">
                    </div>
                    {{-- Username is intentionally disabled for team members.
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" id="username" class="form-control" value="{{ $user['username'] }}">
                    </div> --}}
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ $user['email'] }}" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <div class="input-group designation-input-group">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fa fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Mobile</label>
                        @php
                            $teamCountryCode = $user['country_code'] ?? '+91';
                            $teamStoredMobile = $user['mobile'] === '--' ? '' : $user['mobile'];
                            $teamLocalMobile = str_starts_with($teamStoredMobile, $teamCountryCode) ? substr($teamStoredMobile, strlen($teamCountryCode)) : $teamStoredMobile;
                        @endphp
                        <div class="input-group">
                            <input type="text" name="country_code" class="form-control flex-grow-0" value="{{ $teamCountryCode }}" list="teamCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                            <input type="tel" name="mobile" id="mobile" class="form-control" value="{{ $teamLocalMobile }}" maxlength="15" inputmode="numeric">
                        </div>
                        <datalist id="teamCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Active" {{ $user['status'] === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $user['status'] === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold"><i class="fa-solid fa-briefcase me-1 text-muted"></i>Designation</label>
                        <select name="designation" id="designation" class="form-select team-designation-select"><option value=""></option>@foreach($designations as $designation)<option value="{{ $designation->name }}" {{ $user['designation'] === $designation->name ? 'selected' : '' }}>{{ $designation->name }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Profile Image</label>
                        <input type="file" name="profile_image" id="profile_image" class="form-control" accept=".jpg,.jpeg,.png">
                        @if($profileImage)
                            <div class="mt-2" id="currentImagePreview">
                                <img src="{{ $profileImage }}" class="img-thumbnail" style="max-width:150px;max-height:150px;">
                            </div>
                        @endif
                        <div id="imagePreview" class="mt-2"></div>
                    </div>
                </div>

                <div id="permissionsSection">
                    @include('crm.users.partials.permission', [
                        'permissionMatrix' => $permissionMatrix,
                        'permissionActions' => $permissionActions,
                        'selectedPermissions' => old('permissions', $selectedPermissions),
                    ])
                </div>

                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('team-members.index') }}" class="btn btn-outline-dark-blue px-4">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('TeamMember.partials.designation-modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/users.js' : '/js/users.js') }}?v={{ filemtime(public_path('js/users.js')) }}"></script>
<script>
$(function() {
    const updateUrl = '{{ route('team-members.update', $user['id']) }}';
    const indexUrl = '{{ route('team-members.index') }}';
    initDesignationSelector();

    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const eyeIcon = $('#eyeIcon');
        const isPassword = passwordField.attr('type') === 'password';
        passwordField.attr('type', isPassword ? 'text' : 'password');
        eyeIcon.toggleClass('fa-eye fa-eye-slash');
    });

    $('#profile_image').on('change', function() {
        const file = this.files[0];
        if (!file) {
            $('#imagePreview').empty();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            $('#currentImagePreview').remove();
            $('#imagePreview').html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-width:150px;max-height:150px;">');
        };
        reader.readAsDataURL(file);
    });

    $(document).on('change', '.permission-all', function() {
        const checked = $(this).is(':checked');
        const $row = $(this).closest('tr');
        $row.find('.permission-delete, .permission-dashboard').prop('checked', checked);
    });

    $(document).on('change', '.permission-delete, .permission-dashboard', function() {
        const $row = $(this).closest('tr');
        const allChecked = $row.find('.permission-delete').is(':checked') && $row.find('.permission-dashboard').is(':checked');
        $row.find('.permission-all').prop('checked', allChecked);
    });

    $('#teamMemberForm').on('submit', function(e) {
        e.preventDefault();
        clearValidationErrors();

        const formData = new FormData(this);
        const role = $('#memberRole').val();
        formData.set('role', role);

        const $btn = $(this).find('button[type="submit"]');
        const original = $btn.html();

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            beforeSend: function() {
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
            },
            success: function(res) {
                const ok = res && (res.status === true || res.success === true);
                if (!ok) {
                    window.showAlert('error', res.message || 'Unable to update team member');
                    return;
                }

                window.showAlert('success', res.message || 'Team member updated successfully', '', indexUrl);
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    renderValidationErrors(xhr.responseJSON.errors);
                    return;
                }

                const message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                window.showAlert('error', message);
            },
            complete: function() {
                $btn.prop('disabled', false).html(original);
            }
        });
    });

    $('#teamMemberForm').on('input change', 'input, select, textarea', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.tm-field-error').remove();
        $(this).closest('.input-group').next('.tm-field-error').remove();
    });

    function clearValidationErrors() {
        const $summary = $('#teamMemberValidationErrors');
        $summary.addClass('d-none').empty();
        $('#teamMemberForm .is-invalid').removeClass('is-invalid');
        $('#teamMemberForm .tm-field-error').remove();
    }

    function renderValidationErrors(errors) {
        const $summary = $('#teamMemberValidationErrors');
        const messages = [];

        Object.keys(errors).forEach(function(field) {
            const fieldMessages = errors[field] || [];
            if (!fieldMessages.length) {
                return;
            }

            const $field = $('[name="' + field + '"]');
            if ($field.length) {
                $field.addClass('is-invalid');
                const firstMessage = fieldMessages[0];
                const errorHtml = '<div class="invalid-feedback d-block tm-field-error">' + escapeHtml(firstMessage) + '</div>';

                if ($field.closest('.input-group').length) {
                    $field.closest('.input-group').after(errorHtml);
                } else {
                    $field.after(errorHtml);
                }
            }

            // Red box summary removed
        });
        
        // Scroll to the first error field
        const firstErrorField = $('#teamMemberForm .is-invalid').first();
        if (firstErrorField.length) {
            window.scrollTo({ top: firstErrorField.offset().top - 150, behavior: 'smooth' });
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
