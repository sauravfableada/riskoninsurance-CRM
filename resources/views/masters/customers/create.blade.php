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
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Client Full Name">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                            placeholder="email@example.com">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Portal Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Set customer portal password (optional)">
                        <div class="form-text small text-muted">Leave blank to use default phone/passcode for client login.</div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Phone no.<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="country_code" class="form-control flex-grow-0" value="{{ old('country_code', '+91') }}" list="customerCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                            <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="15" inputmode="numeric" autocomplete="tel" placeholder="Phone number">
                        </div>
                        <datalist id="customerCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">WhatsApp no.</label>
                        <div class="input-group">
                            <input type="text" name="whatsapp_country_code" class="form-control flex-grow-0" value="{{ old('whatsapp_country_code', '+91') }}" list="customerCountryCodes" maxlength="5" style="width:95px" aria-label="WhatsApp country code">
                            <input type="tel" name="whatsapp" class="form-control" value="{{ old('whatsapp') }}" maxlength="15" inputmode="numeric" autocomplete="tel" placeholder="WhatsApp Number">
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}"
                            placeholder="Business / Company Name">
                    </div>
                </div>
            </div>

            <div class="customer-form-step d-none" data-step="2">
                <div class="border-bottom mb-4 pb-3">
                    <h5 class="mb-1">Address Details</h5>
                    <p class="text-muted small mb-0">Provide the client address and location information.</p>
                </div>
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="Full residential or office address">{{ old('address') }}</textarea>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Country</label>
                            <a href="#" class="btn btn-sm" style="background-color: #1B2850; color: white;" data-bs-toggle="modal" data-bs-target="#addCountryModal"><i class="fa-solid fa-plus pe-1"></i>Add</a>
                        </div>
                        <select name="country_id" id="country_id" class="form-select">
                            <option value="">-- Search Country --</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">City</label>
                            <a href="#" class="btn btn-sm" style="background-color: #1B2850; color: white;" data-bs-toggle="modal" data-bs-target="#addCityModal"><i class="fa-solid fa-plus pe-1"></i>Add</a>
                        </div>
                        <select name="city_id" id="city_id" class="form-select">
                            <option value="">-- Search City --</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="type" class="form-select">
                            @foreach (['Individual', 'Corporate', 'Government', 'NGO'] as $type)
                                <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="customer-form-step d-none" data-step="3">
                <div class="border-bottom mb-4 pb-3">
                    <h5 class="mb-1">Contact Details</h5>
                    <p class="text-muted small mb-0">Complete the remaining client settings and profile details.
                    </p>
                </div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Website</label>
                        <input type="text" name="website" class="form-control" value="{{ old('website') }}"
                            placeholder="https://example.com">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Anniversary Date</label>
                        <input type="date" name="anniversary_date" class="form-control"
                            value="{{ old('anniversary_date') }}" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold">Tax Number</label>
                        <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number') }}"
                            placeholder="GST / VAT / Tax ID">
                    </div>
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
