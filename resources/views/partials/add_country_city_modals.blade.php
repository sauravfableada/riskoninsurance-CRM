<!-- Add Country Modal -->
<div class="modal fade" id="addCountryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="addCountryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Country</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="addCountryError" class="alert alert-danger d-none small"></div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. United States">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISO Code <span class="text-danger">*</span></label>
                        <input type="text" name="iso_code" class="form-control" maxlength="3" required placeholder="e.g. US">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue btn-sm">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add City Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="addCityForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="addCityError" class="alert alert-danger d-none small"></div>
                    <div class="mb-3">
                        <label class="form-label">Country <span class="text-danger">*</span></label>
                        <select name="country_id" id="modal_city_country_id" class="form-select" required>
                            <option value="">-- Select Country --</option>
                            @foreach(\App\Models\Country::orderBy('name')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. New York">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue btn-sm">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Helper function to show toast if available, or fallback to alert
    function showToast(message, type = 'success') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success!' : 'Error!',
                text: message,
                timer: 2500,
                showConfirmButton: false
            });
        } else if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    // Add Country
    const addCountryForm = document.getElementById('addCountryForm');
    if (addCountryForm) {
        addCountryForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const errorDiv = document.getElementById('addCountryError');
            
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            errorDiv.classList.add('d-none');
            
            const formData = new FormData(form);
            
            fetch("{{ route('masters.countries.store') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 || status === 201) {
                    // Success
                    const modalEl = document.getElementById('addCountryModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                    form.reset();
                    showToast(body.message || 'Country added successfully');
                    
                    // Update country dropdowns
                    const countrySelects = document.querySelectorAll('select[name="country_id"], #country_id');
                    countrySelects.forEach(select => {
                        // Check if TomSelect is initialized
                        if (select.tomselect) {
                            select.tomselect.addOption({ value: body.country.id, text: body.country.name });
                            select.tomselect.setValue(body.country.id);
                        } else {
                            const option = new Option(body.country.name, body.country.id, true, true);
                            select.appendChild(option);
                            select.value = body.country.id;
                            select.dispatchEvent(new Event('change'));
                        }
                    });

                    // Also update the modal_city_country_id dropdown
                    const modalCityCountryId = document.getElementById('modal_city_country_id');
                    if(modalCityCountryId) {
                        const option = new Option(body.country.name, body.country.id, false, false);
                        modalCityCountryId.appendChild(option);
                    }
                } else {
                    // Error
                    let errorMsg = body.message || 'An error occurred';
                    if (body.errors) {
                        errorMsg = Object.values(body.errors).map(err => err.join(', ')).join('<br>');
                    }
                    errorDiv.innerHTML = errorMsg;
                    errorDiv.classList.remove('d-none');
                }
            })
            .catch(error => {
                errorDiv.innerHTML = 'An unexpected error occurred.';
                errorDiv.classList.remove('d-none');
            })
            .finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }

    // Add City
    const addCityForm = document.getElementById('addCityForm');
    if (addCityForm) {
        addCityForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const errorDiv = document.getElementById('addCityError');
            
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            errorDiv.classList.add('d-none');
            
            const formData = new FormData(form);
            
            fetch("{{ route('masters.cities.store') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 || status === 201) {
                    // Success
                    const modalEl = document.getElementById('addCityModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                    form.reset();
                    showToast(body.message || 'City added successfully');
                    
                    // Update city dropdown
                    const citySelects = document.querySelectorAll('select[name="city_id"], #city_id');
                    citySelects.forEach(select => {
                        // Check if current country matches
                        const countrySelect = document.getElementById('country_id');
                        const currentCountryId = countrySelect ? countrySelect.value : null;
                        
                        if (currentCountryId == body.city.country_id || !currentCountryId) {
                            if (select.tomselect) {
                                select.tomselect.addOption({ value: body.city.id, text: body.city.name });
                                select.tomselect.setValue(body.city.id);
                            } else {
                                const option = new Option(body.city.name, body.city.id, true, true);
                                select.appendChild(option);
                                select.value = body.city.id;
                                select.dispatchEvent(new Event('change'));
                            }
                        }
                    });
                } else {
                    // Error
                    let errorMsg = body.message || 'An error occurred';
                    if (body.errors) {
                        errorMsg = Object.values(body.errors).map(err => err.join(', ')).join('<br>');
                    }
                    errorDiv.innerHTML = errorMsg;
                    errorDiv.classList.remove('d-none');
                }
            })
            .catch(error => {
                errorDiv.innerHTML = 'An unexpected error occurred.';
                errorDiv.classList.remove('d-none');
            })
            .finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
        
        // Auto-select country in modal when opening, if a country is already selected in main form
        const addCityModalEl = document.getElementById('addCityModal');
        if (addCityModalEl) {
            addCityModalEl.addEventListener('show.bs.modal', function () {
                const mainCountrySelect = document.getElementById('country_id');
                const modalCountrySelect = document.getElementById('modal_city_country_id');
                
                if (mainCountrySelect && mainCountrySelect.value && modalCountrySelect) {
                    modalCountrySelect.value = mainCountrySelect.value;
                }
            });
        }
    }
});
</script>
@endpush