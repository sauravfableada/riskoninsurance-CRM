@extends('layouts.masters')

@section('page_title', 'Masters - Add Client')

@section('masters_content')
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h4 mb-1 fw-semibold">Add Client</h1>
                <p class="text-muted small mb-0">Create a new client profile in the master database.</p>
            </div>
            <a href="{{ route('masters.customers.index') }}" class="btn btn-dark-blue customer-back-btn">
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
                return $customService->getFieldLabel('customers', $field, $default);
            };
            $isRequired = function(string $field, bool $default = false) use ($customService) {
                return $customService->isFieldRequired('customers', $field);
            };
            $isVisible = function(string $field, bool $default = true) use ($customService) {
                return $customService->isFieldVisible('customers', $field);
            };
        @endphp

        <form id="customerForm" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="mb-4">
                <div class="d-flex flex-wrap gap-2" id="customerFormSteps">
                    <button type="button" class="btn btn-outline-dark-blue active flex-grow-1 flex-md-grow-0"
                        data-step="1">Personal Details</button>
                    <button type="button" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0" data-step="2">Address
                        Details</button>
                    <button type="button" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0" data-step="3">Contact
                        Details</button>
                </div>
            </div>

            <div class="customer-form-step" data-step="1">
                <div class="border-bottom mb-4 pb-3">
                    <h5 class="mb-1">Personal Details</h5>
                    <p class="text-muted small mb-0">Enter the client name, contact and basic profile information.
                    </p>
                </div>
                <div class="row g-4 mb-4">
                    @if($isVisible('name', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('name', 'Name') }} @if($isRequired('name', true))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="{{ $fieldLabel('name', 'Client Full Name') }}" {{ $isRequired('name', true) ? 'required' : '' }}>
                    </div>
                    @endif

                    @if($isVisible('email', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('email', 'Email') }} @if($isRequired('email', false))<span class="text-danger">*</span>@endif</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                            placeholder="{{ $fieldLabel('email', 'email@example.com') }}" {{ $isRequired('email', false) ? 'required' : '' }}>
                        <div class="invalid-feedback"></div>
                    </div>
                    @endif

                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Portal Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Set customer portal password (optional)">
                        <div class="form-text small text-muted">Leave blank to use default phone/passcode for client login.</div>
                    </div>

                    @if($isVisible('phone', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('phone', 'Phone no.') }} @if($isRequired('phone', true))<span class="text-danger">*</span>@endif</label>
                        <div class="input-group">
                            <input type="text" name="country_code" class="form-control flex-grow-0" value="{{ old('country_code', '+91') }}" list="customerCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                            <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="15" inputmode="numeric" autocomplete="tel" placeholder="{{ $fieldLabel('phone', 'Phone number') }}" {{ $isRequired('phone', true) ? 'required' : '' }}>
                        </div>
                        <datalist id="customerCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        <div class="invalid-feedback"></div>
                    </div>
                    @endif

                    @if($isVisible('whatsapp', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('whatsapp', 'WhatsApp no.') }} @if($isRequired('whatsapp', false))<span class="text-danger">*</span>@endif</label>
                        <div class="input-group">
                            <input type="text" name="whatsapp_country_code" class="form-control flex-grow-0" value="{{ old('whatsapp_country_code', '+91') }}" list="customerCountryCodes" maxlength="5" style="width:95px" aria-label="WhatsApp country code">
                            <input type="tel" name="whatsapp" class="form-control" value="{{ old('whatsapp') }}" maxlength="15" inputmode="numeric" autocomplete="tel" placeholder="{{ $fieldLabel('whatsapp', 'WhatsApp Number') }}" {{ $isRequired('whatsapp', false) ? 'required' : '' }}>
                        </div>
                    </div>
                    @endif

                    @if($isVisible('image', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('image', 'Image') }} @if($isRequired('image', false))<span class="text-danger">*</span>@endif</label>
                        <input type="file" name="image" class="form-control" {{ $isRequired('image', false) ? 'required' : '' }}>
                    </div>
                    @endif

                    @if($isVisible('company_name', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('company_name', 'Company Name') }} @if($isRequired('company_name', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}"
                            placeholder="{{ $fieldLabel('company_name', 'Business / Company Name') }}" {{ $isRequired('company_name', false) ? 'required' : '' }}>
                    </div>
                    @endif
                </div>
            </div>

            <div class="customer-form-step d-none" data-step="2">
                <div class="border-bottom mb-4 pb-3">
                    <h5 class="mb-1">Address Details</h5>
                    <p class="text-muted small mb-0">Provide the client address and location information.</p>
                </div>
                <div class="row g-4 mb-4">
                    @if($isVisible('address', true))
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('address', 'Address') }} @if($isRequired('address', false))<span class="text-danger">*</span>@endif</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="{{ $fieldLabel('address', 'Full residential or office address') }}" {{ $isRequired('address', false) ? 'required' : '' }}>{{ old('address') }}</textarea>
                    </div>
                    @endif

                    @if($isVisible('country_id', true))
                    <div class="col-md-4 col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">{{ $fieldLabel('country_id', 'Country') }} @if($isRequired('country_id', false))<span class="text-danger">*</span>@endif</label>
                            <a href="#" class="btn btn-sm" style="background-color: #1B2850; color: white;" data-bs-toggle="modal" data-bs-target="#addCountryModal"><i class="fa-solid fa-plus pe-1"></i>Add</a>
                        </div>
                        <select name="country_id" id="country_id" class="form-select" {{ $isRequired('country_id', false) ? 'required' : '' }}>
                            <option value="">-- Search Country --</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($isVisible('city_id', true))
                    <div class="col-md-4 col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">{{ $fieldLabel('city_id', 'City') }} @if($isRequired('city_id', false))<span class="text-danger">*</span>@endif</label>
                            <a href="#" class="btn btn-sm" style="background-color: #1B2850; color: white;" data-bs-toggle="modal" data-bs-target="#addCityModal"><i class="fa-solid fa-plus pe-1"></i>Add</a>
                        </div>
                        <select name="city_id" id="city_id" class="form-select" {{ $isRequired('city_id', false) ? 'required' : '' }}>
                            <option value="">-- Search City --</option>
                        </select>
                    </div>
                    @endif

                    @if($isVisible('type', true))
                    <div class="col-md-4 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('type', 'Type') }} @if($isRequired('type', false))<span class="text-danger">*</span>@endif</label>
                        <select name="type" class="form-select" {{ $isRequired('type', false) ? 'required' : '' }}>
                            @foreach (['Individual', 'Corporate', 'Government', 'NGO'] as $type)
                                <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>

            <div class="customer-form-step d-none" data-step="3">
                <div class="border-bottom mb-4 pb-3">
                    <h5 class="mb-1">Contact Details</h5>
                    <p class="text-muted small mb-0">Complete the remaining client settings and profile details.
                    </p>
                </div>
                <div class="row g-4 mb-4">
                    @if($isVisible('website', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('website', 'Website') }} @if($isRequired('website', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="website" class="form-control" value="{{ old('website') }}"
                            placeholder="{{ $fieldLabel('website', 'https://example.com') }}" {{ $isRequired('website', false) ? 'required' : '' }}>
                    </div>
                    @endif

                    @if($isVisible('dob', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('dob', 'Date of Birth') }} @if($isRequired('dob', false))<span class="text-danger">*</span>@endif</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}" {{ $isRequired('dob', false) ? 'required' : '' }}>
                    </div>
                    @endif

                    @if($isVisible('anniversary_date', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('anniversary_date', 'Anniversary Date') }} @if($isRequired('anniversary_date', false))<span class="text-danger">*</span>@endif</label>
                        <input type="date" name="anniversary_date" class="form-control"
                            value="{{ old('anniversary_date') }}" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}" {{ $isRequired('anniversary_date', false) ? 'required' : '' }}>
                    </div>
                    @endif

                    @if($isVisible('tax_number', true))
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">{{ $fieldLabel('tax_number', 'Tax Number') }} @if($isRequired('tax_number', false))<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number') }}"
                            placeholder="{{ $fieldLabel('tax_number', 'GST / VAT / Tax ID') }}" {{ $isRequired('tax_number', false) ? 'required' : '' }}>
                    </div>
                    @endif
                </div>

                @include('partials.custom_fields', ['module' => 'Customer'])
            </div>

            <div class="mt-4 pt-4 border-top row g-2 customer-form-actions">
                <div class="col-6 col-md-auto">
                    <a href="{{ route('masters.customers.index') }}" class="btn btn-outline-dark-blue w-100 px-4 cancel-step">Cancel</a>
                    <button type="button" class="btn btn-outline-dark-blue w-100 prev-step d-none px-4">Previous</button>
                </div>
                <div class="col-6 col-md-auto ms-auto text-end">
                    <button type="button" class="btn btn-dark-blue w-100 next-step px-4">Next</button>
                    <button type="submit" class="btn btn-dark-blue w-100 d-none px-4">Submit</button>
                </div>
            </div>
        </form>
    </div>
    </div>

    @include('partials.add_country_city_modals')
@endsection


@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/customer.js' : '/js/customer.js') }}?v={{ filemtime(public_path('js/customer.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const countrySelect = new TomSelect('#country_id', {
                placeholder: '-- Search Country --',
                allowEmptyOption: true,
                dropdownParent: 'body'
            });
            const citySelect = new TomSelect('#city_id', {
                placeholder: '-- Search City --',
                allowEmptyOption: true,
                dropdownParent: 'body'
            });

            document.getElementById('country_id').addEventListener('change', function () {
                const countryId = this.value;

                citySelect.clear();
                citySelect.clearOptions();
                citySelect.addOption({ value: 'loading', text: 'Loading...' });
                citySelect.setValue('loading');

                if (!countryId) {
                    citySelect.clear();
                    citySelect.clearOptions();
                    citySelect.addOption({ value: '', text: '-- Search City --' });
                    return;
                }

                fetch(`{{ url('masters/cities-by-country') }}/${countryId}`)
                    .then(response => response.json())
                    .then(data => {
                        citySelect.clear();
                        citySelect.clearOptions();
                        citySelect.addOption({ value: '', text: '-- Search City --' });
                        data.forEach(city => {
                            citySelect.addOption({ value: city.id, text: city.name });
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching cities:', error);
                        citySelect.clear();
                        citySelect.clearOptions();
                        citySelect.addOption({ value: '', text: 'Error loading cities' });
                    });
            });
        });
    </script>
@endpush
