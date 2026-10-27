@extends('layouts.app')

@section('page_title', 'Prospects - Create')

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Prospect</h1>
                        <p class="text-muted small mb-0">Create a new prospect profile in your sales pipeline.</p>
                    </div>
                    <a href="{{ route('prospects.index') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form id="add_prospect_form" novalidate>
                    @csrf
                    <input type="hidden" name="base_url" id="base_url" value="{{ url('/') }}/">

                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Enter name">
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold" for="mobile_no">Mobile No <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" list="prospectCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" name="mobile_no" id="mobile_no" placeholder="Enter mobile number" maxlength="15" inputmode="numeric">
                            </div>
                            <datalist id="prospectCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold" for="area">Area <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="area" id="area" placeholder="Enter area">
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold" for="status">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="prospect" selected>Prospect</option>
                                <option value="client">Client</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('prospects.index') }}" class="btn btn-outline-dark-blue px-4">Cancel</a>
                        <button class="btn btn-dark-blue px-4" id="add_prospect" type="button">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#add_prospect').on('click', function() {
                let btn = $(this);
                let base_url = $('#base_url').val();
                let hasError = false;

                $('.invalid-feedback').remove();
                $('.is-invalid').removeClass('is-invalid');

                let name = $('#name').val().trim();
                let mobile_no = $('#mobile_no').val().trim();
                let area = $('#area').val().trim();

                if (name === '') {
                    $('#name').addClass('is-invalid').after('<div class="invalid-feedback">Please enter a name.</div>');
                    hasError = true;
                }

                if (mobile_no === '') {
                    $('#mobile_no').addClass('is-invalid').after(
                        '<div class="invalid-feedback">Please enter a mobile number.</div>');
                    hasError = true;
                }

                if (area === '') {
                    $('#area').addClass('is-invalid').after('<div class="invalid-feedback">Please enter an area.</div>');
                    hasError = true;
                }

                if (hasError) return;

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Submitting...');

                $.ajax({
                    url: base_url + 'prospects/store',
                    type: 'POST',
                    data: $('#add_prospect_form').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                customClass: { popup: 'rounded-4 shadow' }
                            }).then(() => {
                                window.location.href = base_url + 'prospects';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                customClass: { popup: 'rounded-4 shadow' }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            customClass: { popup: 'rounded-4 shadow' }
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('Submit');
                    }
                });
            });
        });
    </script>
@endpush
