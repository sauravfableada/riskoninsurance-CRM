@extends('layouts.app')

@section('page_title', 'Supplier Costs - ' . $booking->booking_no)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="mb-0">Supplier Costs for Booking: {{ $booking->booking_no }}</h4>
            <p class="text-muted">Customer: {{ $booking->customer->name }} | Total Booking Amount: {{ number_format($booking->total_amount, 2) }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-angle-left pe-2"></i>Back
            </a>
            <button class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#addCostModal">
                <i class="bi bi-plus-lg"></i> Add Supplier Cost
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Supplier</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Paid Amount</th>
                            <th>Notes</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalCosts = 0; @endphp
                        @forelse($booking->payables as $payable)
                        @php $totalCosts += $payable->amount; @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold">{{ $payable->supplier->name }}</span>
                                <br><small class="text-muted">{{ $payable->supplier->type }}</small>
                            </td>
                            <td class="fw-bold text-dark">{{ number_format($payable->amount, 2) }}</td>
                            <td>
                                <span class="{{ $payable->due_date < date('Y-m-d') && $payable->status != 'paid' ? 'text-danger fw-bold' : '' }}">
                                    {{ $payable->due_date }}
                                </span>
                            </td>
                            <td>
                                <span class="badge crm-status-pill rounded-pill {{ $payable->status == 'paid' ? 'bg-success' : ($payable->status == 'partially_paid' ? 'bg-info text-dark' : 'bg-warning text-dark') }}">
                                    {{ ucfirst(str_replace('_', ' ', $payable->status)) }}
                                </span>
                            </td>
                            <td class="text-success small">{{ number_format($payable->payments->sum('amount'), 2) }}</td>
                            <td class="text-muted small">{{ $payable->notes }}</td>
                            <td class="pe-4 text-end">
                                @if($payable->payments->isEmpty())
                                <button class="btn btn-sm crm-action-btn" onclick="deleteCost({{ $payable->id }})">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-truck display-4 text-secondary mb-3 d-block"></i>
                                <p class="text-muted">No supplier costs recorded yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($booking->payables->isNotEmpty())
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="ps-4">Total Costs</td>
                            <td colspan="6">{{ number_format($totalCosts, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Cost Modal --}}
<div class="modal fade" id="addCostModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="costForm" action="{{ route('bookings.costs.store', $booking) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Supplier Cost</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Choose Supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cost Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. 2 Rooms for 3 Nights"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Save Cost</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('costForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            window.crmAlert('Error recording cost: ' + (data.message || 'Unknown error'));
        }
    });
});

function deleteCost(id) {
    window.crmConfirm('Are you sure you want to delete this cost entry?', {
        confirmButtonText: 'Yes, delete it'
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }

        fetch(`/costs/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                window.crmAlert('Error deleting cost: ' + (data.message || 'Unknown error'), 'error');
            }
        });
    });
}
</script>
@endsection
