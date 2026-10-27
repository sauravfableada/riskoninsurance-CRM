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
            <form method="POST" action="/api/leads/{{ $lead->id }}" enctype="multipart/form-data" class="needs-validation ajax-lead-form js-status-comment-form" novalidate id="leadEditForm">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $lead->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Lead Name" required>
                        <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                    </div>
                    <div class="col-md-6">
                        @include('crm.partials.assigned-user-field', ['users' => $users, 'selected' => $lead->assigned_user_id])
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $lead->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="Email Address">
                        <div class="invalid-feedback" id="email-error">@error('email') {{ $message }} @enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                        @php
                            $leadCountryCode = old('country_code', $lead->country_code ?: '+91');
                            $leadLocalPhone = str_starts_with((string) $lead->phone, $leadCountryCode) ? substr($lead->phone, strlen($leadCountryCode)) : $lead->phone;
                        @endphp
                        <div class="input-group">
                            <input type="text" name="country_code" value="{{ $leadCountryCode }}" class="form-control flex-grow-0" list="leadCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $leadLocalPhone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="Phone Number" maxlength="15" inputmode="numeric" required>
                        </div>
                        <datalist id="leadCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        <div class="invalid-feedback" id="phone-error">@error('phone') {{ $message }} @enderror</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">WhatsApp</label>
                        @php
                            $leadWhatsappCountryCode = old('whatsapp_country_code', $lead->whatsapp_country_code ?: '+91');
                            $leadLocalWhatsapp = str_starts_with((string) $lead->whatsapp, $leadWhatsappCountryCode) ? substr($lead->whatsapp, strlen($leadWhatsappCountryCode)) : $lead->whatsapp;
                        @endphp
                        <div class="input-group">
                            <input type="text" name="whatsapp_country_code" value="{{ $leadWhatsappCountryCode }}" class="form-control flex-grow-0" list="leadCountryCodes" maxlength="5" style="width:95px" aria-label="WhatsApp country code">
                            <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $leadLocalWhatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror" placeholder="WhatsApp Number" maxlength="15" inputmode="numeric">
                        </div>
                        <div class="invalid-feedback" id="whatsapp-error">@error('whatsapp') {{ $message }} @enderror</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="1" placeholder="Lead Address">{{ old('address', $lead->address) }}</textarea>
                        <div class="invalid-feedback" id="address-error">@error('address') {{ $message }} @enderror</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Image</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="file" name="image" id="image" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" class="form-control @error('image') is-invalid @enderror" onchange="previewImage(this, 'leadImagePreview')">
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
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company Name</label>
                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $lead->company_name) }}" class="form-control @error('company_name') is-invalid @enderror" placeholder="Company Name">
                        <div class="invalid-feedback" id="company_name-error">@error('company_name') {{ $message }} @enderror</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Lead Source</label>
                        <input type="text" name="source" id="source" value="{{ old('source', $lead->source) }}" class="form-control @error('source') is-invalid @enderror" placeholder="Lead Source">
                        <div class="invalid-feedback" id="source-error">@error('source') {{ $message }} @enderror</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Lead Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror js-status-comment-trigger" required>
                            <option value="">Select Status</option>
                            @foreach (['new' => 'New', 'qualified' => 'Qualified', 'working' => 'Working', 'ready_to_close' => 'Ready to Close', 'won' => 'Closed Won', 'lost' => 'Closed Lost'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', $lead->status) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Comment</label>
                        <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Comments">{{ old('notes', $lead->notes) }}</textarea>
                        <div class="invalid-feedback" id="notes-error">@error('notes') {{ $message }} @enderror</div>
                    </div>

                </div>

                @include('partials.custom_fields', ['model' => $lead])

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
@endpush
