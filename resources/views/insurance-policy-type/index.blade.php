@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mt-4 mb-4">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card form-card">
                <div class="card-header pb-0 pt-3 p-4 px-2 px-md-4">
                    <div class="d-flex flex-row flex-nowrap justify-content-between align-items-center w-100 mt-lg-n3" style="gap: 10px;">
                        <div class="d-flex align-items-center flex-shrink-0">
                            <h6 class="mb-0">Insurance Policy Type</h6>
                        </div>

                        <div class="d-flex align-items-center flex-shrink-0" id="issue">
                            <div class="btn-group ">
                                <a class="btn btn-dark-blue px-2 px-md-3 mt-2" aria-expanded="false" href="{{ route('policy-type.create') }}">
                                   <i class="fa-solid fa-plus text-white"></i> Add Insurance Policy Type
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
                            @foreach ($policies as $index => $policy)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $policy->name }}</td>
                                    <td>{{ $policy->description }}</td>
                                    <td>{{ $policy->created_at ? $policy->created_at->format('d-m-Y h:i A') : '' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('policy-type.edit', $policy->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm crm-action-btn delete-policy" data-id="{{ $policy->id }}">
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
                    emptyTable: "No Policy Types Found",
                }
            });
        }

        $(document).on('click', '.delete-policy', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let policyId = $btn.data('id');
            let url = "{{ url('policy-type') }}/" + policyId;

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.status === 'success' || response.status === true) {
                                Swal.fire('Deleted!', response.message, 'success');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection
