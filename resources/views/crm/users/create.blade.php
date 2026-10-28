@extends('layouts.app')

@section('page_title', 'Add Sub Agent')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-form.css' : '/css/users-form.css') }}?v={{ filemtime(public_path('css/users-form.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden staff-form-card">
            
            <!-- Card Header -->
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Sub Agent</h1>
                        <p class="text-muted small mb-0">Create a new sub-agent account and assign module access.</p>
                    </div>
                    <a href="{{ route('users.index') }}" class="btn btn-dark-blue staff-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-3 p-md-4">
                <form action="{{ url('/api/users') }}" method="POST" enctype="multipart/form-data" id="staffCreateForm" class="ajax-user-form" autocomplete="off">

                    @csrf

                    <!-- Hidden fake inputs to prevent browser autofill -->
                    <input type="text" name="fake_username_remember" style="position:absolute; top:-9999px; left:-9999px; width:1px; height:1px;" tabindex="-1" autocomplete="username">
                    <input type="password" name="fake_password_remember" style="position:absolute; top:-9999px; left:-9999px; width:1px; height:1px;" tabindex="-1" autocomplete="current-password">

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-2" for="name">
                                <i class="bi bi-person-fill"></i> Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" autocomplete="off">
                            @error('name')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="email">
                                <i class="bi bi-envelope-fill"></i> Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="new-password">
                            @error('email')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="password">
                                <i class="bi bi-key-fill"></i> Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" autocomplete="new-password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fa fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Role -->
                        <input type="hidden" name="role" value="staff">
                        {{-- Role selection is disabled in the Sub Agent module; all users are Staff.
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="role">
                                <i class="bi bi-shield-lock-fill"></i> Role <span class="text-danger">*</span>
                            </label>
                            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role', 'staff') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div> --}}

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="phone">
                                <i class="bi bi-telephone-fill"></i> Phone no. <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" id="country_code" name="country_code" class="form-control flex-grow-0 @error('country_code') is-invalid @enderror" value="{{ old('country_code', '+91') }}" list="staffCountryCodes" maxlength="5" aria-label="Country code" style="width: 95px;">
                                <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" inputmode="numeric" maxlength="15" placeholder="Phone number">
                            </div>
                            <datalist id="staffCountryCodes">
                                <option value="+91">India</option><option value="+1">USA / Canada</option><option value="+44">United Kingdom</option>
                                <option value="+61">Australia</option><option value="+971">UAE</option><option value="+966">Saudi Arabia</option>
                                <option value="+65">Singapore</option><option value="+60">Malaysia</option><option value="+974">Qatar</option>
                            </datalist>
                            @error('country_code')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                            @error('phone')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- WhatsApp -->
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="whatsapp">
                                <i class="bi bi-whatsapp"></i> WhatsApp no.
                            </label>
                            <div class="input-group">
                                <input type="text" id="whatsapp_country_code" name="whatsapp_country_code" class="form-control flex-grow-0 @error('whatsapp_country_code') is-invalid @enderror" value="{{ old('whatsapp_country_code', '+91') }}" list="staffCountryCodes" maxlength="5" aria-label="WhatsApp country code" style="width: 95px;">
                                <input type="tel" id="whatsapp" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp') }}" inputmode="numeric" maxlength="15" placeholder="WhatsApp number">
                            </div>
                            @error('whatsapp_country_code')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                            @error('whatsapp')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Image -->
                        <div class="col-md-12">
                            <label class="form-label d-flex align-items-center gap-2" for="image">
                                <i class="bi bi-person-circle"></i> Profile Image
                            </label>
                            <input type="file" id="image" name="image" class="form-control @error('image') is-invalid @enderror" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg">
                            @error('image')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                            
                            <div class="staff-avatar-preview mt-2" id="createImagePreviewWrap" style="display: none;">
                                <img src="" alt="Sub-agent preview" id="createImagePreview" class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-2" for="address">
                                <i class="bi bi-geo-alt-fill"></i> Address
                            </label>
                            <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">Submit</button>
                    </div>

                    <!-- Permissions Section -->
                    @include('crm.users.partials.permission', [
                        'permissionMatrix' => $permissionMatrix ?? [],
                        'permissionActions' => $permissionActions ?? [],
                        'selectedPermissions' => old('permissions', []),
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/users.js' : '/js/users.js') }}?v={{ filemtime(public_path('js/users.js')) }}"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('staffCreateForm');
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        const imageInput = document.getElementById('image');
        const imagePreviewWrap = document.getElementById('createImagePreviewWrap');
        const imagePreview = document.getElementById('createImagePreview');

        // Real-time field validation
        function validateField(field) {
            if (!field) return;

            const value = field.value.trim();
            let isValid = true;

            switch (field.name) {
                case 'name':
                case 'email':
                    isValid = value.length > 0;
                    break;
                case 'password':
                    isValid = value.length >= 8;
                    break;
                case 'phone':
                    isValid = /^\d{4,15}$/.test(value);
                    break;
                case 'country_code':
                    isValid = /^\+\d{1,4}$/.test(value);
                    break;
                case 'whatsapp':
                    isValid = value === '' || /^\d{4,15}$/.test(value);
                    break;
                case 'whatsapp_country_code':
                    isValid = /^\+\d{1,4}$/.test(value);
                    break;
            }

            field.classList.toggle('is-invalid', !isValid);

            // Hide/show custom error message
            const errorDiv = field.parentElement.querySelector('.staff-validation');
            if (errorDiv) {
                errorDiv.style.display = isValid ? 'none' : 'block';
            }
        }

        if (passwordToggle && passwordInput && eyeIcon) {
            passwordToggle.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                eyeIcon.classList.toggle('fa-eye', !isPassword);
                eyeIcon.classList.toggle('fa-eye-slash', isPassword);
            });
        }

        // Image Preview
        if (imageInput && imagePreview && imagePreviewWrap) {
            imageInput.addEventListener('change', function () {
                const file = this.files[0];
                
                if (file) {
                    imagePreview.src = URL.createObjectURL(file);
                    imagePreviewWrap.style.display = 'block';
                } else {
                    imagePreview.src = '';
                    imagePreviewWrap.style.display = 'none';
                }
            });
        }

        // Attach validation to all inputs and textarea
        if (form) {
            const fields = form.querySelectorAll('input, textarea');
            
            fields.forEach(field => {
                field.addEventListener('input', () => validateField(field));
                field.addEventListener('blur', () => validateField(field));
            });
        }
        // Clear any aggressive browser auto-fill on creation
        setTimeout(function() {
            const emailField = document.getElementById('email');
            const passField = document.getElementById('password');
            if (emailField && (emailField.value === 'admin@insurance.com' || emailField.value === 'admin@example.com')) {
                emailField.value = '';
                if (passField) passField.value = '';
            }
        }, 100);
    });
    </script>
@endpush
