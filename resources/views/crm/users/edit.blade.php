@extends('layouts.app')

@section('page_title', 'Edit Sub Agent')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-form.css' : '/css/users-form.css') }}?v={{ filemtime(public_path('css/users-form.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden staff-form-card">

            <!-- Header -->
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                    <div class="flex-grow-1 w-100">
                        <h1 class="h4 mb-1 fw-semibold">Edit Sub Agent</h1>
                        <p class="text-muted small mb-0">Update sub-agent details and module access.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form Body -->
            <div class="card-body p-3 p-md-4">
                @php
                    if (!function_exists('field_label') && file_exists(app_path('Helpers/FieldCustomizationHelper.php'))) {
                        require_once app_path('Helpers/FieldCustomizationHelper.php');
                    }
                    $customService = app(\App\Services\FieldCustomizationService::class);
                    $fieldLabel = function(string $field, string $default) use ($customService) {
                        return $customService->getFieldLabel('users', $field, $default);
                    };
                    $isRequired = function(string $field, bool $default = false) use ($customService) {
                        return $customService->isFieldRequired('users', $field);
                    };
                    $isVisible = function(string $field, bool $default = true) use ($customService) {
                        return $customService->isFieldVisible('users', $field);
                    };
                @endphp
                <form action="{{ url('/api/users/' . $user->id) }}" method="POST" enctype="multipart/form-data" id="staffEditForm" class="ajax-user-form">

                    @csrf
                    @method('PUT')

                    <!-- Anti password manager tricks -->
                    <input type="text" name="fake_username" autocomplete="username" class="d-none" tabindex="-1">
                    <input type="password" name="fake_password" autocomplete="new-password" class="d-none" tabindex="-1">

                    <div class="row g-3">
                        <!-- Name -->
                        @if($isVisible('name', true))
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-2" for="name">
                                <i class="bi bi-person-fill"></i> {{ $fieldLabel('name', 'Name') }} @if($isRequired('name', true))<span class="text-danger">*</span>@endif
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" placeholder="{{ $fieldLabel('name', 'Full Name') }}" {{ $isRequired('name', true) ? 'required' : '' }}>
                            @error('name')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <!-- Email (Readonly) -->
                        @if($isVisible('email', true))
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="email">
                                <i class="bi bi-envelope-fill"></i> {{ $fieldLabel('email', 'Email') }} @if($isRequired('email', true))<span class="text-danger">*</span>@endif
                            </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" readonly>
                            @error('email')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="password">
                                <i class="bi bi-key-fill"></i> Password
                                <small class="text-muted">(Leave blank to keep current)</small>
                            </label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" autocomplete="new-password">
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

                        <!-- Phone -->
                        @if($isVisible('phone', true))
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="phone">
                                <i class="bi bi-telephone-fill"></i> {{ $fieldLabel('phone', 'Phone no.') }} @if($isRequired('phone', false))<span class="text-danger">*</span>@endif
                            </label>
                            @php
                                $staffCountryCode = old('country_code', $user->country_code ?: '+91');
                                $storedStaffPhone = (string) $user->phone;
                                $staffLocalPhone = str_starts_with($storedStaffPhone, $staffCountryCode)
                                    ? substr($storedStaffPhone, strlen($staffCountryCode))
                                    : $storedStaffPhone;
                            @endphp
                            <div class="input-group">
                                <input type="text" id="country_code" name="country_code" class="form-control flex-grow-0 @error('country_code') is-invalid @enderror" value="{{ $staffCountryCode }}" list="staffCountryCodes" maxlength="5" aria-label="Country code" style="width: 95px;">
                                <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $staffLocalPhone) }}" inputmode="numeric" maxlength="15" placeholder="{{ $fieldLabel('phone', 'Phone number') }}" {{ $isRequired('phone', false) ? 'required' : '' }}>
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
                        @endif

                        <!-- WhatsApp -->
                        @if($isVisible('whatsapp', true))
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2" for="whatsapp">
                                <i class="bi bi-whatsapp"></i> {{ $fieldLabel('whatsapp', 'WhatsApp no.') }} @if($isRequired('whatsapp', false))<span class="text-danger">*</span>@endif
                            </label>
                            @php
                                $staffWhatsappCountryCode = old('whatsapp_country_code', $user->whatsapp_country_code ?: '+91');
                                $storedStaffWhatsapp = (string) $user->whatsapp;
                                $staffLocalWhatsapp = str_starts_with($storedStaffWhatsapp, $staffWhatsappCountryCode)
                                    ? substr($storedStaffWhatsapp, strlen($staffWhatsappCountryCode))
                                    : $storedStaffWhatsapp;
                            @endphp
                            <div class="input-group">
                                <input type="text" id="whatsapp_country_code" name="whatsapp_country_code" class="form-control flex-grow-0 @error('whatsapp_country_code') is-invalid @enderror" value="{{ $staffWhatsappCountryCode }}" list="staffCountryCodes" maxlength="5" aria-label="WhatsApp country code" style="width: 95px;">
                                <input type="tel" id="whatsapp" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $staffLocalWhatsapp) }}" inputmode="numeric" maxlength="15" placeholder="{{ $fieldLabel('whatsapp', 'WhatsApp number') }}" {{ $isRequired('whatsapp', false) ? 'required' : '' }}>
                            </div>
                            @error('whatsapp_country_code')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                            @error('whatsapp')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <!-- Image -->
                        @if($isVisible('image', true))
                        <div class="col-md-12">
                            <label class="form-label d-flex align-items-center gap-2" for="image">
                                <i class="bi bi-person-circle"></i> {{ $fieldLabel('image', 'Profile Image') }} @if($isRequired('image', false))<span class="text-danger">*</span>@endif
                            </label>
                            <input type="file" id="image" name="image" class="form-control @error('image') is-invalid @enderror" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg" {{ $isRequired('image', false) ? 'required' : '' }}>
                            @error('image')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                            
                            <div class="staff-avatar-preview mt-2">
                                @if($user->image)
                                    <img src="{{ asset('storage/' . $user->image) }}" alt="{{ $user->name }}" class="img-thumbnail" id="staffAvatarPreview" style="max-width: 120px; max-height: 120px;">
                                @else
                                    <img src="" alt="Sub-agent preview" class="img-thumbnail" id="staffAvatarPreview" style="max-width: 120px; max-height: 120px; display: none;">
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Address -->
                        @if($isVisible('address', true))
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-2" for="address">
                                <i class="bi bi-geo-alt-fill"></i> {{ $fieldLabel('address', 'Address') }} @if($isRequired('address', false))<span class="text-danger">*</span>@endif
                            </label>
                            <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" placeholder="{{ $fieldLabel('address', 'Address') }}" {{ $isRequired('address', false) ? 'required' : '' }}>{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    </div>
                            @error('image')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror

                            <!-- Current Image Preview -->
                            <div class="staff-avatar-preview mt-2" id="editImagePreviewWrap" style="display: inline-flex;">
                                @php
                                    $hasValidAvatar = $user->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path);
                                @endphp

                                @if($hasValidAvatar)
                                    <img src="{{ route('users.image', $user) . '?v=' . optional($user->updated_at)->timestamp }}" alt="{{ $user->name }}" id="editImagePreview" class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                                @else
                                    <div id="editImagePlaceholder" class="d-flex align-items-center justify-content-center bg-light border rounded" style="width: 120px; height: 120px;">
                                        <i class="bi bi-person-circle text-secondary" style="font-size: 3rem;"></i>
                                    </div>
                                    <img src="" id="editImagePreview" class="img-thumbnail d-none" style="max-width: 120px; max-height: 120px;">
                                @endif
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-2" for="address">
                                <i class="bi bi-geo-alt-fill"></i> Address
                            </label>
                            <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="staff-validation">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">Update</button>
                    </div>

                    <!-- Permissions -->
                    @include('crm.users.partials.permission', [
                        'permissionMatrix' => $permissionMatrix ?? [],
                        'permissionActions' => $permissionActions ?? [],
                        'selectedPermissions' => old('permissions', $userPermissions ?? []),
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
        const form = document.getElementById('staffEditForm');
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');
        const imageInput = document.getElementById('image');
        const imagePreviewWrap = document.getElementById('editImagePreviewWrap');
        const imagePreview = document.getElementById('editImagePreview');

        // Clear password field on load
        if (passwordInput) passwordInput.value = '';

        // Real-time validation
        function validateField(field) {
            if (!field) return;

            const value = field.value.trim();
            let isValid = true;

            switch (field.name) {
                case 'name':
                    isValid = value.length > 0;
                    break;
                case 'password':
                    isValid = value === '' || value.length >= 8;
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

            const errorDiv = field.parentElement.querySelector('.staff-validation');
            if (errorDiv) {
                errorDiv.style.display = isValid ? 'none' : 'block';
            }
        }

        // Password Toggle
        if (passwordToggle && passwordInput && eyeIcon) {
            passwordToggle.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                eyeIcon.classList.toggle('fa-eye', !isPassword);
                eyeIcon.classList.toggle('fa-eye-slash', isPassword);
            });
        }

        // Image Preview (New upload overrides current image or placeholder)
        if (imageInput && imagePreviewWrap && imagePreview) {
            imageInput.addEventListener('change', function () {
                const file = this.files[0];

                if (file) {
                    imagePreview.src = URL.createObjectURL(file);
                    imagePreview.classList.remove('d-none');
                    
                    const placeholder = document.getElementById('editImagePlaceholder');
                    if (placeholder) {
                        placeholder.classList.add('d-none');
                    }
                    
                    imagePreviewWrap.style.display = 'inline-flex';
                } 
            });
        }

        // Attach validation listeners
        if (form) {
            form.querySelectorAll('input, textarea').forEach(field => {
                field.addEventListener('input', () => validateField(field));
                field.addEventListener('blur', () => validateField(field));
            });
        }
    });
    </script>
@endpush
