@extends('layouts.app')

@section('page_title', 'Supplier Payables - ' . $supplier->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h4 class="mb-0">Payables for {{ $supplier->name }}</h4>
            <p class="text-muted mb-0">Manage and track outstanding payments for bookings.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('masters.suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Suppliers
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Booking #</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payables as $payable)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('bookings.show', $payable->booking) }}" class="fw-bold">
                                    {{ $payable->booking->booking_no }}
                                </a>
                            </td>
                            <td class="fw-bold">{{ number_format($payable->amount, 2) }}</td>
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
                            <td class="text-success">{{ number_format($payable->payments->sum('amount'), 2) }}</td>
                            <td class="text-danger fw-bold">{{ number_format($payable->amount - $payable->payments->sum('amount'), 2) }}</td>
                            <td class="pe-4 text-end">
                                @if($payable->status != 'paid')
                                <button class="btn btn-sm btn-dark-blue" onclick="openPaymentModal({{ $payable->id }}, {{ $payable->amount - $payable->payments->sum('amount') }})">
                                    Record Payment
                                </button>
                                @else
                                <span class="badge bg-light text-success border border-success px-3 py-2">Paid in Full</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-mailbox display-4 text-secondary mb-3 d-block"></i>
                                <p class="text-muted">No payables found for this supplier.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payables->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $payables->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Record Supplier Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="paymentForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Supplier Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount" id="paymentAmount" class="form-control" required step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI / Digital</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction Reference</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Optional">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Save Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(payableId, balance) {
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const form = document.getElementById('paymentForm');
    form.action = `/payables/${payableId}/payments`;
    document.getElementById('paymentAmount').value = balance;
    document.getElementById('paymentAmount').max = balance;
    modal.show();
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
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
            window.crmAlert('Error recording payment: ' + (data.message || 'Unknown error'));
        }
    });
});
</script>
@endsection
