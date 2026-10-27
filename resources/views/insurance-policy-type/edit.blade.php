@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-10 mb-lg-0 mb-4">
            <div class="card form-card">
                <div class="card-header pb-0 pt-2 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 h6-cardheader"> Update Insurance Policy Type</h6>
                        <a href="{{ route('policy-type.index') }}" class="btn btn-light mb-0"><i class="fa-solid fa-angle-left"></i> Back</a>
                    </div>
                </div>
                
                <div class="table-responsive px-0 px-lg-5 px-md-5">
                    <form class="m-3" id="update_policy_form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="policy_id" id="policy_id" value="{{ $policy->id ?? '' }}">

                        <div class="row">
                            <!-- Insurance Policy Type Name -->
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label" for="policy_type_name">Insurance Policy Type Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    name="name"
                                    id="policy_type_name"
                                    value="{{ $policy->name ?? '' }}"
                                    placeholder="Enter Policy Type Name">
                                <span id="policy_type_name_err" class="text-danger"></span>
                            </div>

                            <!-- Insurance Policy Description -->
                            <div class="mb-3 col-lg-6 col-md-6 col-sm-12">
                                <label class="form-label" for="policy_description">Insurance Policy Description</label>
                                <input type="text" class="form-control"
                                    name="description"
                                    id="policy_description"
                                    value="{{ $policy->description ?? '' }}"
                                    placeholder="Enter Policy Description">
                                <span id="policy_description_err" class="text-danger"></span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="row">
                            <div class="col-lg-12 text-end">
                                <button type="button" class="btn btn-dark-blue" id="update_policy_btn">Update</button>
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
        $('#update_policy_btn').on('click', function() {
            var form = $('#update_policy_form');
            var formData = form.serialize();
            var policyId = $('#policy_id').val();

            // Reset error messages
            $('#policy_type_name_err').text('');
            $('#policy_description_err').text('');

            $.ajax({
                url: "{{ url('policy-type') }}/" + policyId, 
                type: 'PUT',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $('#update_policy_btn').prop('disabled', true).text('Updating...');
                },
                success: function(res) {
                    if (res.status === 'success' || res.status === true) {
                        Swal.fire({
                            title: 'Success!',
                            text: res.message || 'Policy type updated successfully',
                            icon: 'success',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = "{{ route('policy-type.index') }}";
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: res.message || 'Failed to update policy type',
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
                    Swal.fire({
                        title: 'Oops!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    $('#update_policy_btn').prop('disabled', false).text('Update');
                }
            });
        });
    });
</script>
@endpush
@endsection
