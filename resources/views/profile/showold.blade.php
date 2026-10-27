@extends('layouts.app')

@section('page_title', 'Profile')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/profile.css' : '/css/profile.css') }}?v={{ filemtime(public_path('css/profile.css')) }}">
@endpush

@section('content')
@php
    $roleName = auth()->user()->isAdmin()
        ? 'Administrator'
        : (auth()->user()->roles->first()?->name
            ? \Illuminate\Support\Str::headline(auth()->user()->roles->first()->name)
            : (auth()->user()->job_title ?: 'Staff'));

    $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random&color=fff';
    $defaultLogo = (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/images/template/crmfavicon.png' : '/images/template/crmfavicon.png');
    
    $avatarUrl = !empty($user->avatar_path)
        ? route('users.image', $user) . '?v=' . optional($user->updated_at)->timestamp
        : $defaultAvatar;

    $companyLogoPath = $settings['company_logo_path'] ?? null;
    $companyLogoUrl = $companyLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
        ? route('profile.company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyLogoPath)
        : $defaultLogo;
@endphp

<div class="profile-shell">
    <div class="profile-hero">
        <div class="profile-hero-card d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $avatarUrl }}" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';" alt="{{ $user->name }}" class="profile-avatar-mini">
                <div>
                    <h4 class="profile-hero-name">{{ $user->name }}</h4>
                    <div class="text-muted">({{ ucfirst($roleName) }})</div>
                </div>
            </div>
            <button type="button" class="profile-dark-btn profile-password-btn" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="bi bi-key-fill"></i>
                <span>Change Password</span>
            </button>
        </div>

        <div class="profile-hero-card d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="profile-google-icon">G</span>
                <div>
                    <h3 class="profile-hero-subtitle">{{ $googleCalendarConnected ? 'Google Connected' : 'Connect To Google' }}</h3>
                    <div class="text-muted">{{ $user->email }}</div>
                </div>
            </div>
            <div id="profileGoogleAction">
                @if($googleCalendarConnected)
                    <form method="POST" action="{{ route('profile.google.disconnect') }}" class="m-0 google-disconnect-form">
                        @csrf
                        <button type="submit" class="profile-dark-btn profile-google-btn" style="min-width: 180px;">
                            <i class="bi bi-google"></i>
                            <span>Disconnect Google</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('google.auth') }}" class="profile-dark-btn profile-google-btn" style="min-width: 180px;">
                        <i class="bi bi-link-45deg"></i>
                        <span>Connect to Google</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <form id="profileForm" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="card profile-card">
            <div class="profile-card-head">UPDATE PROFILE</div>
            <div class="card-body p-3 p-md-4">
                <div class="profile-section">
                    <div class="profile-section-label">USER INFORMATION</div>
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-3 profile-image-col">
                            <h5 class="profile-image-title">Profile Image</h5>
                            <img src="{{ $avatarUrl }}" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';" alt="Profile" class="profile-circle-image" id="avatar-preview">
                        </div>
                        <div class="col-lg-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Name</label>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                                    @error('name')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email address</label>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                                    @error('email')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Contact No.</label>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                    @error('phone')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Profile Image (Upload)</label>
                                    <input type="file" name="avatar" id="avatar-input" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                                    @error('avatar')<div class="profile-field-error">{{ $message }}</div>@enderror
                                    <small class="text-muted">Any image file - max 2 MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->isAdmin())
                <div class="profile-section">
                    <div class="profile-section-label">COMPANY INFORMATION</div>
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-3 profile-image-col">
                            <h5 class="profile-image-title">Company Logo</h5>
                            <img src="{{ $companyLogoUrl }}" onerror="this.onerror=null;this.src='{{ $defaultLogo }}';" alt="Company Logo" class="profile-circle-image" id="company-logo-preview">
                        </div>
                        <div class="col-lg-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Name</label>
                                    <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" class="form-control @error('company_name') is-invalid @enderror">
                                    @error('company_name')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Address</label>
                                    <input type="text" name="company_address" value="{{ old('company_address', $settings['company_address'] ?? '') }}" class="form-control @error('company_address') is-invalid @enderror">
                                    @error('company_address')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Tax No.</label>
                                    <input type="text" name="company_tax_id" value="{{ old('company_tax_id', $settings['company_tax_id'] ?? '') }}" class="form-control @error('company_tax_id') is-invalid @enderror">
                                    @error('company_tax_id')<div class="profile-field-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company Logo (Upload)</label>
                                    <input type="file" name="company_logo_path" id="company-logo-input" accept="image/*" class="form-control @error('company_logo_path') is-invalid @enderror">
                                    @error('company_logo_path')<div class="profile-field-error">{{ $message }}</div>@enderror
                                    <small class="text-muted">Any image file - max 2 MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="profile-submit-wrap">
                    <button type="submit" class="btn btn-dark-blue profile-submit-btn">Update</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade profile-password-modal" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.password.update') }}" id="changePasswordForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue profile-submit-btn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.profilePageConfig = {
    updateUrl: @json(route('profile.update')),
    passwordUrl: @json(route('profile.password.update')),
    disconnectGoogleUrl: @json(route('profile.google.disconnect')),
    googleStatusUrl: @json(url('/api/meetings/google/auth-status')),
    googleAuthUrl: @json(route('google.auth')),
    openPasswordModal: @json($errors->has('current_password') || $errors->has('password')),
};
</script>
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/profile.js' : '/js/profile.js') }}?v={{ filemtime(public_path('js/profile.js')) }}"></script>
@endpush
