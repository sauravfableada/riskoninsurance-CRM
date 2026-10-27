@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mt-4 mb-4">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card form-card">
                <div class="card-header pb-0 pt-3 p-4 px-2 px-md-4">
                    <div class="d-flex flex-row flex-nowrap justify-content-between align-items-center w-100 mt-lg-n3" style="gap: 10px;">
                        <div class="d-flex align-items-center flex-shrink-0">
                            <h6 class="mb-0">All Insurance Companies</h6>
                        </div>

                        <div class="d-flex align-items-center flex-shrink-0" id="issue">
                            <div class="btn-group">
                                <a class="btn btn-dark-blue px-2 px-md-3 mt-2" aria-expanded="false" href="{{ route('insurance-company.create') }}">
                                   <i class="fa-solid fa-plus text-white"></i> Add Insurance Company
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 overflow-scroll">
                    <table id="exampleData" class="table table-flush" style="width:100%">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center">Sr.No</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $index => $company)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $company->name }}</td>
                                    <td>{{ $company->description }}</td>
                                    <td>{{ $company->created_at ? $company->created_at->format('d-m-Y h:i A') : '' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('insurance-company.edit', $company->id) }}" class="btn btn-sm btn-outline-secondary me-2">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm crm-action-btn delete-company" data-id="{{ $company->id }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        if ($('#exampleData').length) {
            $('#exampleData').DataTable({
                pageLength: 10,
                language: {
                    emptyTable: "No Insurance Companies Found",
                },
                columnDefs: [
                    { targets: [0, 4], orderable: false }
                ]
            });
        }

        $(document).on('click', '.delete-company', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let companyId = $btn.data('id');
            let url = "{{ url('insurance-company') }}/" + companyId;

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        beforeSend: function() {
                            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                        },
                        success: function(response) {
                            if (response.status === 'success' || response.status === true) {
                                Swal.fire('Deleted!', response.message || 'Company deleted successfully.', 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                                Swal.fire('Error!', response.message || 'Failed to delete company.', 'error');
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                            Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection
