@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-10 mb-lg-0 mb-4">
            <div class="card form-card">
                <div class="card-header pb-0 pt-2 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 h6-cardheader"> Add Insurance Policy Type</h6>
                        <a href="{{ route('policy-type.index') }}" class="btn btn-light mb-0"><i class="fa-solid fa-angle-left"></i> Back</a>
                    </div>
                </div>

                <div class="table-responsive px-0 px-lg-5 px-md-5">
                    <form class="m-3" id="add_policy_form">
                        @csrf
                        <div class="row">
                            <!-- Insurance Policy Type Name -->
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label" for="policy_type_name">Insurance Policy Type Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="policy_type_name" placeholder="Enter Policy Type Name">
                                <span id="policy_type_name_err" class="text-danger"></span>
                            </div>

                            <!-- Insurance Policy Description -->
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label" for="policy_description">Insurance Policy Description</label>
                                <input type="text" class="form-control" name="description" id="policy_description" placeholder="Enter Policy Description">
                                <span id="policy_description_err" class="text-danger"></span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="row">
                            <div class="col-lg-12 text-end">
                                <button type="button" class="btn btn-dark-blue" id="add_policy_btn">Submit</button>
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
        $('#add_policy_btn').on('click', function() {
            var name = $('#policy_type_name').val().trim();
            var desc = $('#policy_description').val().trim();

            // Reset error messages
            $('#policy_type_name_err').text('');
            $('#policy_description_err').text('');

            var isValid = true;

            if (name === '') {
                $('#policy_type_name_err').text('Policy Type Name is required');
                isValid = false;
            }

            if (!isValid) return;

            $.ajax({
                url: "{{ route('policy-type.store') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    description: desc
                },
                beforeSend: function() {
                    $('#add_policy_btn').prop('disabled', true).text('Submitting...');
                },
                success: function(res) {
                    if (res.status === 'success' || res.status === true) {
                        Swal.fire({
                            title: 'Success!',
                            text: res.message || 'Policy type added successfully',
                            icon: 'success',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = "{{ route('policy-type.index') }}";
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: res.message || 'Failed to add policy type',
                            icon: 'error',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Something went wrong!';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire("Error!", errorMessage, "error");
                },
                complete: function() {
                    $('#add_policy_btn').prop('disabled', false).text('Submit');
                }
            });
        });
    });
</script>
@endpush
@endsection
