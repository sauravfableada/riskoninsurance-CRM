@extends('layouts.app')

@section('page_title', 'Leads - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
            <div class="card-body p-0">
                {{-- Header Section --}}
                <div class="p-4 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-bold">Edit Lead</h1>
                            <p class="text-muted small mb-0">{{ $lead->name }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            <a href="{{ route('leads.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
            @if($lead->is_converted && $lead->converted_customer_id)
                <div class="alert alert-warning border border-warning-subtle d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 rounded-4 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-check fs-4 text-success"></i>
                        <div>
                            <strong class="text-dark">Converted Lead:</strong> This lead was converted into customer 
                            <a href="{{ route('masters.customers.edit', $lead->converted_customer_id) }}" class="fw-bold text-dark text-decoration-underline">
                                {{ $lead->convertedCustomer?->name ?: ('#' . $lead->converted_customer_id) }}
                            </a>.
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('masters.customers.edit', $lead->converted_customer_id) }}" class="btn btn-sm btn-outline-dark-blue">
                            <i class="bi bi-person me-1"></i>View Customer
                        </a>
                        <button type="button" class="btn btn-sm btn-warning text-dark js-revert-from-edit" data-id="{{ $lead->id }}">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Revert to Lead
                        </button>
                    </div>
                </div>
            @endif

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

            <form method="POST" action="/api/leads/{{ $lead->id }}" enctype="multipart/form-data" class="needs-validation ajax-lead-form js-status-comment-form" novalidate id="leadEditForm">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    @if($isVisible('name', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('name', 'Name') }} @if($isRequired('name', true))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $lead->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="{{ $fieldLabel('name', 'Lead Name') }}" {{ $isRequired('name', true) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('assigned_user_id', true))
                    <div class="col-md-6">
                        @include('crm.partials.assigned-user-field', [
                            'users' => $users,
                            'selected' => $lead->assigned_user_id,
                            'label' => $fieldLabel('assigned_user_id', 'Assigned To'),
                            'required' => $isRequired('assigned_user_id', false)
                        ])
                    </div>
                    @endif

                    @if($isVisible('email', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('email', 'Email') }} @if($isRequired('email', false))<span class="text-danger">*</span>@endif</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $lead->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="{{ $fieldLabel('email', 'Email Address') }}" {{ $isRequired('email', false) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="email-error">@error('email') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('phone', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('phone', 'Phone') }} @if($isRequired('phone', true))<span class="text-danger">*</span>@endif</label>
                        @php
                            $leadCountryCode = old('country_code', $lead->country_code ?: '+91');
                            $leadLocalPhone = str_starts_with((string) $lead->phone, $leadCountryCode) ? substr($lead->phone, strlen($leadCountryCode)) : $lead->phone;
                        @endphp
                        <div class="input-group has-validation">
                            <input type="text" name="country_code" value="{{ $leadCountryCode }}" class="form-control flex-grow-0" list="leadCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $leadLocalPhone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="{{ $fieldLabel('phone', 'Phone Number') }}" maxlength="15" inputmode="numeric" {{ $isRequired('phone', true) ? 'required' : '' }}>
                            <div class="invalid-feedback @error('phone') d-block @enderror" id="phone-error">@error('phone') {{ $message }} @enderror</div>
                        </div>
                        <datalist id="leadCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                    </div>
                    @endif

                    @if($isVisible('whatsapp', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('whatsapp', 'WhatsApp') }} @if($isRequired('whatsapp', false))<span class="text-danger">*</span>@endif</label>
                        @php
                            $leadWhatsappCountryCode = old('whatsapp_country_code', $lead->whatsapp_country_code ?: '+91');
                            $leadLocalWhatsapp = str_starts_with((string) $lead->whatsapp, $leadWhatsappCountryCode) ? substr($lead->whatsapp, strlen($leadWhatsappCountryCode)) : $lead->whatsapp;
                        @endphp
                        <div class="input-group has-validation">
                            <input type="text" name="whatsapp_country_code" value="{{ $leadWhatsappCountryCode }}" class="form-control flex-grow-0" list="leadCountryCodes" maxlength="5" style="width:95px" aria-label="WhatsApp country code">
                            <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $leadLocalWhatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror" placeholder="{{ $fieldLabel('whatsapp', 'WhatsApp Number') }}" maxlength="15" inputmode="numeric" {{ $isRequired('whatsapp', false) ? 'required' : '' }}>
                            <div class="invalid-feedback @error('whatsapp') d-block @enderror" id="whatsapp-error">@error('whatsapp') {{ $message }} @enderror</div>
                        </div>
                    </div>
                    @endif

                    @if($isVisible('address', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('address', 'Address') }} @if($isRequired('address', false))<span class="text-danger">*</span>@endif</label>
                        <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="1" placeholder="{{ $fieldLabel('address', 'Lead Address') }}" {{ $isRequired('address', false) ? 'required' : '' }}>{{ old('address', $lead->address) }}</textarea>
                        <div class="invalid-feedback" id="address-error">@error('address') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('image', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('image', 'Image') }} @if($isRequired('image', false))<span class="text-danger">*</span>@endif</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="file" name="image" id="image" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" class="form-control @error('image') is-invalid @enderror" onchange="previewImage(this, 'leadImagePreview')" {{ $isRequired('image', false) ? 'required' : '' }}>
                            <div class="border rounded bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                @php
                                    $hasImage = $lead->image && Storage::disk('public')->exists($lead->image);
                                @endphp
                                <img id="leadImagePreview" src="{{ $hasImage ? route('leads.image', $lead) . '?v=' . $lead->updated_at?->timestamp : '' }}" class="w-100 h-100 object-fit-cover rounded {{ $hasImage ? '' : 'd-none' }}" alt="Preview">
                                <i id="leadImageIcon" class="bi bi-image text-muted {{ $hasImage ? 'd-none' : '' }}"></i>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block" id="image-error">@error('image') {{ $message }} @enderror</div>
                        <small class="text-muted">Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG. Max 2MB.</small>
                    </div>
                    @endif

                    @if($isVisible('company_name', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('company_name', 'Company Name') }} @if($isRequired('company_name', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $lead->company_name) }}" class="form-control @error('company_name') is-invalid @enderror" placeholder="{{ $fieldLabel('company_name', 'Company Name') }}" {{ $isRequired('company_name', false) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="company_name-error">@error('company_name') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('group', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('group', 'Family Group') }} @if($isRequired('group', false))<span class="text-danger">*</span>@endif</label>
                        <select name="group" id="group" class="form-select @error('group') is-invalid @enderror" {{ $isRequired('group', false) ? 'required' : '' }}>
                            <option value="">Select Family Group</option>
                            @foreach($groups ?? [] as $g)
                                <option value="{{ $g->group_name }}" @selected(old('group', $lead->group) === $g->group_name)>{{ $g->group_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="group-error">@error('group') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('source', true))
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('source', 'Lead Source') }} @if($isRequired('source', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="source" id="source" value="{{ old('source', $lead->source) }}" class="form-control @error('source') is-invalid @enderror" placeholder="{{ $fieldLabel('source', 'Lead Source') }}" {{ $isRequired('source', false) ? 'required' : '' }}>
                        <div class="invalid-feedback" id="source-error">@error('source') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('status', true))
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">{{ $fieldLabel('status', 'Lead Status') }} @if($isRequired('status', true))<span class="text-danger">*</span>@endif</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror js-status-comment-trigger" {{ $isRequired('status', true) ? 'required' : '' }}>
                            <option value="">Select Status</option>
                            @foreach (['new' => 'New', 'qualified' => 'Qualified', 'working' => 'Working', 'ready_to_close' => 'Ready to Close', 'won' => 'Closed Won', 'lost' => 'Closed Lost'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', $lead->status) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
                    </div>
                    @endif

                    @if($isVisible('notes', true))
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('notes', 'Comment') }} @if($isRequired('notes', false))<span class="text-danger">*</span>@endif</label>
                        <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="{{ $fieldLabel('notes', 'Comments') }}" {{ $isRequired('notes', false) ? 'required' : '' }}>{{ old('notes', $lead->notes) }}</textarea>
                        <div class="invalid-feedback" id="notes-error">@error('notes') {{ $message }} @enderror</div>
                    </div>
                    @endif

                </div>

                @include('partials.custom_fields', ['model' => $lead])

                @include('crm.leads.partials.kyc-section', ['kyc' => $kyc ?? $lead->kycDocument])

                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('leads.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                        <span id="btnText">Update</span>
                    </button>
                </div>
            </form>

            @include('crm.partials.status-history-table', ['histories' => $lead->statusHistories])
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const revertBtn = document.querySelector('.js-revert-from-edit');
            if (revertBtn) {
                revertBtn.addEventListener('click', function() {
                    const leadId = this.dataset.id;
                    const btn = this;

                    Swal.fire({
                        title: "Revert from Customer?",
                        text: "This will revert the conversion and restore this record back to an active lead.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d97706",
                        cancelButtonColor: "#64748b",
                        confirmButtonText: '<i class="bi bi-arrow-counterclockwise me-1"></i> Yes, Revert to Lead',
                        cancelButtonText: "Cancel",
                        customClass: {
                            popup: "rounded-4 shadow",
                            cancelButton: "text-dark border",
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const originalHtml = btn.innerHTML;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                            btn.disabled = true;

                            fetch(`/leads/${leadId}/revert`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    if (typeof window.showAlert === 'function') {
                                        window.showAlert('success', data.message || 'Lead converted status reverted successfully.');
                                    }
                                    window.location.reload();
                                } else {
                                    throw new Error(data.message || 'Failed to revert lead.');
                                }
                            })
                            .catch(err => {
                                btn.innerHTML = originalHtml;
                                btn.disabled = false;
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('error', err.message || 'Failed to revert lead.');
                                }
                            });
                        }
                    });
                });
            }
        });
    </script>
@endpush
