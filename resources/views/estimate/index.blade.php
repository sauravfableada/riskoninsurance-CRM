@extends('layouts.app')

@section('content')
<style>
  .action-icon {
    font-weight: bold;
    font-size: 16px !important;
    border: 0.5px solid #e4e4e4;
    background: #e4e4e48a;
    border-radius: 6px;
    margin-right: 8px;
    height: 32px;
    width: 32px;
    text-align: center;
    line-height: 32px;
    display: inline-block;
  }
</style>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0 p-4">
          <div class="row">
            <div class="col-6 d-flex align-items-center">
              <h6 class="mb-0">ESTIMATES</h6>
            </div>
            <div class="col-6 text-end">
              <a class="btn btn-dark-blue btn-sm mb-0" href="{{ route('estimates.create') }}">
                <i class="fas fa-plus"></i>&nbsp;&nbsp;ADD ESTIMATE
              </a>
            </div>
          </div>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-4">
            <table id="estimateTable" class="table align-items-center mb-0" style="width:100%">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 sr-no">Sr.No</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer Name</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estimate No</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estimate Date</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                  <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($estimates as $index => $estimate)
                <tr>
                  <td>{{ $estimates->firstItem() + $index }}</td>
                  <td>{{ $estimate->customer->name ?? '--' }}</td>
                  <td>{{ $estimate->estimate_no }}</td>
                  <td>{{ $estimate->estimate_date }}</td>
                  <td>{{ number_format($estimate->total, 2) }}</td>
                  <td>
                    <div class="toggle-estimate-status" data-id="{{ $estimate->estimate_id }}" data-status="{{ $estimate->status }}">
                        @if($estimate->status == 'approved')
                            <button type="button" class="btn btn-xs btn-success mb-0">Approved</button>
                        @else
                            <button type="button" class="btn btn-xs btn-secondary mb-0">Pending</button>
                        @endif
                    </div>
                  </td>
                  <td class="align-middle text-center">
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('estimates.edit', $estimate->estimate_id) }}" class="action-icon" title="Edit">
                            <i class="fas fa-edit text-primary"></i>
                        </a>
                        <a href="{{ route('estimates.show', $estimate->estimate_id) }}" class="action-icon" title="View">
                            <i class="fas fa-eye text-info"></i>
                        </a>
                        <form action="{{ route('estimates.destroy', $estimate->estimate_id) }}" method="POST" data-confirm-message="Are you sure you want to delete this estimate?" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-icon" style="border:none; cursor:pointer;" title="Delete">
                                <i class="bi bi-trash text-danger text-danger"></i>
                            </button>
                        </form>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            <div class="mt-4">
                {{ $estimates->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).on('click', '.toggle-estimate-status button', function() {
    var parentDiv = $(this).closest('.toggle-estimate-status');
    var id = parentDiv.data('id');
    var currentStatus = parentDiv.data('status');
    var newStatus = currentStatus === 'approved' ? 'pending' : 'approved';
    
    $.ajax({
      url: "{{ url('estimates') }}/" + id + "/status",
      type: 'POST',
      data: {
        _token: "{{ csrf_token() }}",
        status: newStatus
      },
      dataType: 'json',
      success: function(response) {
        if (response.success === true) {
          parentDiv.data('status', newStatus);
          if (newStatus === 'approved') {
            parentDiv.html('<button type="button" class="btn btn-xs btn-success mb-0">Approved</button>');
          } else {
            parentDiv.html('<button type="button" class="btn btn-xs btn-secondary mb-0">Pending</button>');
          }
          Swal.fire({
            title: 'Status changed successfully',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          });
        }
      }
    });
  });
</script>
@endsection
