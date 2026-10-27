@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-10 mb-lg-0 mb-4">
            <div class="card form-card">
                <div class="card-header pb-0 pt-2 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 h6-cardheader"> Edit Insurance Company</h6>
                        <a href="{{ route('insurance-company.index') }}" class="btn btn-light mb-0"><i class="fa-solid fa-angle-left"></i> Back</a>
                    </div>
                </div>
                
                <div class="table-responsive px-0 px-lg-5 px-md-5">
                    <form class="m-3" id="edit_company_form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" id="company_id" value="{{ $company->id }}">

                        <div class="row">
                            <!-- Company Name -->
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label" for="name">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="name" value="{{ $company->name }}" placeholder="Enter Company Name">
                                <span id="name_err" class="text-danger"></span>
                            </div>

                            <!-- Description -->
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label" for="description">Description</label>
                                <input type="text" class="form-control" name="description" id="description" value="{{ $company->description }}" placeholder="Enter description">
                                <span id="description_err" class="text-danger"></span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="row mt-3">
                            <div class="col-lg-12 d-flex justify-content-end align-items-center mb-3">
                                <div class="col-lg-3 col-md-4 col-6 pe-2">
                                    <a href="{{ route('insurance-company.index') }}" class="btn btn-secondary w-100 mb-0" role="button">Cancel</a>
                                </div>
                                <div class="col-lg-3 col-md-4 col-6 ps-2">
                                    <button class="btn btn-dark-blue w-100 mb-0" id="update_company_btn" type="button">Update</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#update_company_btn').on('click', function(e) {
            e.preventDefault();
            
            var name = $('#name').val().trim();
            var desc = $('#description').val().trim();
            var id = $('#company_id').val();

            // Reset error messages
            $('#name_err').text('');
            $('#description_err').text('');
            $('.form-control').removeClass('is-invalid');

            var isValid = true;

            if (name === '') {
                $('#name').addClass('is-invalid');
                $('#name_err').text('Please enter company name.');
                isValid = false;
            }

            if (!isValid) return;

            let $btn = $(this);
            let originalBtnText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

            $.ajax({
                url: "{{ url('insurance-company') }}/" + id,
                type: 'PUT',
                dataType: 'json',
                data: {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    description: desc
                },
                success: function(res) {
                    $btn.prop('disabled', false).html(originalBtnText);
                    if (res.status === 'success' || res.status === true) {
                        Swal.fire({
                            title: 'Success!',
                            text: res.message || 'Company updated successfully',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = "{{ route('insurance-company.index') }}";
                        });
                    } else {
                        Swal.fire('Error!', res.message || 'Failed to update company', 'error');
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalBtnText);
                    let errorMessage = 'Something went wrong!';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire("Error!", errorMessage, "error");
                }
            });
        });

        // Remove error message on input
        $('#name').on('input', function() {
            $(this).removeClass('is-invalid');
            $('#name_err').text('');
        });
    });
</script>
@endpush
@endsection
