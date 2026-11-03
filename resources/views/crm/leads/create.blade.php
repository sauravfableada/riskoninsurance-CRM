@extends('layouts.app')

@section('page_title', 'Leads - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Lead</h1>
                        <p class="text-muted small mb-0">Create a new lead entry.</p>
                    </div>
                    <a href="{{ route('leads.index') }}" class="btn btn-dark-blue lead-back-btn">
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
                    return $customService->getFieldLabel('leads', $field, $default);
                };
                $isRequired = function(string $field, bool $default = false) use ($customService) {
                    return $customService->isFieldRequired('leads', $field);
                };
                $isVisible = function(string $field, bool $default = true) use ($customService) {
                    return $customService->isFieldVisible('leads', $field);
                };
            @endphp
            <form method="POST" action="/api/leads" enctype="multipart/form-data" class="needs-validation ajax-lead-form" novalidate id="leadCreateForm">
                @csrf

                <div class="row g-3">
                    @if($isVisible('name', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('name', 'Name') }} @if($isRequired('name', true))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="{{ $fieldLabel('name', 'Lead Name') }}" {{ $isRequired('name', true) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('assigned_user_id', true))
                    <div class="col-md-6">  
                        @include('crm.partials.assigned-user-field', [
                            'users' => $users,
                            'label' => $fieldLabel('assigned_user_id', 'Assigned To'),
                            'required' => $isRequired('assigned_user_id', false)
                        ])
                    </div>
                    @endif

                    @if($isVisible('email', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('email', 'Email') }} @if($isRequired('email', false))<span class="text-danger">*</span>@endif</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="{{ $fieldLabel('email', 'Email Address') }}" {{ $isRequired('email', false) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="email-error">@error('email') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('phone', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('phone', 'Phone') }} @if($isRequired('phone', true))<span class="text-danger">*</span>@endif</label>
                        <div class="input-group has-validation">
                            <input type="text" name="country_code" value="{{ old('country_code', '+91') }}" class="form-control flex-grow-0" list="leadCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="{{ $fieldLabel('phone', 'Phone Number') }}" maxlength="15" inputmode="numeric" {{ $isRequired('phone', true) ? 'required' : '' }}>
                            <div class="invalid-feedback @error('phone') d-block @enderror" id="phone-error">@error('phone') {{ $message }} @enderror</div>
                        </div>
                        <datalist id="leadCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                    </div>
                    @endif

                    @if($isVisible('whatsapp', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('whatsapp', 'WhatsApp') }} @if($isRequired('whatsapp', false))<span class="text-danger">*</span>@endif</label>
                        <div class="input-group has-validation">
                            <input type="text" name="whatsapp_country_code" value="{{ old('whatsapp_country_code', '+91') }}" class="form-control flex-grow-0" list="leadCountryCodes" maxlength="5" style="width:95px" aria-label="WhatsApp country code">
                            <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp') }}" class="form-control @error('whatsapp') is-invalid @enderror" placeholder="{{ $fieldLabel('whatsapp', 'WhatsApp Number') }}" maxlength="15" inputmode="numeric" {{ $isRequired('whatsapp', false) ? 'required' : '' }}>
                            <div class="invalid-feedback @error('whatsapp') d-block @enderror" id="whatsapp-error">@error('whatsapp') {{ $message }} @enderror</div>
                        </div>
                    </div>
                    @endif

                    @if($isVisible('address', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('address', 'Address') }} @if($isRequired('address', false))<span class="text-danger">*</span>@endif</label>
                        <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="1" placeholder="{{ $fieldLabel('address', 'Lead Address') }}" {{ $isRequired('address', false) ? 'required' : '' }}>{{ old('address') }}</textarea>
                        <div class="invalid-feedback" id="address-error">@error('address') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('image', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('image', 'Image') }} @if($isRequired('image', false))<span class="text-danger">*</span>@endif</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="file" name="image" id="image" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" class="form-control @error('image') is-invalid @enderror" onchange="previewImage(this, 'leadImagePreview')" {{ $isRequired('image', false) ? 'required' : '' }}>
                            <div class="border rounded bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <img id="leadImagePreview" src="" class="w-100 h-100 object-fit-cover rounded d-none" alt="Preview">
                                <i id="leadImageIcon" class="bi bi-image text-muted"></i>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block" id="image-error">@error('image') {{ $message }} @enderror</div>
                        <small class="text-muted">Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG. Max 2MB.</small>
                    </div>
                    @endif

                    @if($isVisible('company_name', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('company_name', 'Company Name') }} @if($isRequired('company_name', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" class="form-control @error('company_name') is-invalid @enderror" placeholder="{{ $fieldLabel('company_name', 'Company Name') }}" {{ $isRequired('company_name', false) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="company_name-error">@error('company_name') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('group', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('group', 'Family Group') }} @if($isRequired('group', false))<span class="text-danger">*</span>@endif</label>
                        <select name="group" id="group" class="form-select @error('group') is-invalid @enderror" {{ $isRequired('group', false) ? 'required' : '' }}>
                            <option value="">Select Family Group</option>
                            @foreach($groups ?? [] as $g)
                                <option value="{{ $g->group_name }}" @selected(old('group') === $g->group_name)>{{ $g->group_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="group-error">@error('group') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('source', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('source', 'Lead Source') }} @if($isRequired('source', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="source" id="source" value="{{ old('source') }}" class="form-control @error('source') is-invalid @enderror" placeholder="{{ $fieldLabel('source', 'Lead Source') }}" {{ $isRequired('source', false) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="source-error">@error('source') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('status', true))
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('status', 'Lead Status') }} @if($isRequired('status', true))<span class="text-danger">*</span>@endif</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" {{ $isRequired('status', true) ? 'required' : '' }}>
                            <option value="">Select Status</option>
                            @foreach (['new' => 'New', 'qualified' => 'Qualified', 'working' => 'Working', 'ready_to_close' => 'Ready to Close', 'won' => 'Closed Won', 'lost' => 'Closed Lost'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', 'new') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('notes', true))
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('notes', 'Comment') }} @if($isRequired('notes', false))<span class="text-danger">*</span>@endif</label>
                        <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="{{ $fieldLabel('notes', 'Comments') }}" {{ $isRequired('notes', false) ? 'required' : '' }}>{{ old('notes') }}</textarea>
                        <div class="invalid-feedback" id="notes-error">@error('notes') {{ $message }} @enderror</div>
                    </div>
                    @endif

                </div>

                @include('partials.custom_fields', ['module' => 'Lead'])

                @include('crm.leads.partials.kyc-section')

                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('leads.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                        <span id="btnText">Submit</span>
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
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/leads.js' : '/js/leads.js') }}"></script>
@endpush
