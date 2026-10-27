@extends('layouts.app')

@section('page_title', 'Manage Refunds - ' . $booking->booking_no)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="mb-0">Refunds for Booking: {{ $booking->booking_no }}</h4>
            <p class="text-muted">Customer: {{ $booking->customer->name }} | Total Amount: {{ number_format($booking->total_amount, 2) }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-angle-left pe-2"></i> Back to Booking
            </a>
            <button class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#addRefundModal">
                <i class="bi bi-plus-lg"></i> Record Refund
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th class="pe-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($booking->refunds as $refund)
                        <tr>
                            <td class="ps-4">{{ $refund->refund_date }}</td>
                            <td class="fw-bold text-danger">{{ number_format($refund->amount, 2) }}</td>
                            <td>{{ $refund->payment_method }}</td>
                            <td><code>{{ $refund->transaction_id }}</code></td>
                            <td>
                                <span class="badge crm-status-pill rounded-pill {{ $refund->status == 'processed' ? 'bg-success' : ($refund->status == 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ ucfirst($refund->status) }}
                                </span>
                            </td>
                            <td class="pe-4 text-muted small">{{ $refund->notes }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-cash-stack display-4 text-secondary mb-3 d-block"></i>
                                <p class="text-muted">No refunds recorded for this booking yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Refund Modal --}}
<div class="modal fade" id="addRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="refundForm" action="{{ route('bookings.refunds.store', $booking) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record New Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Refund Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" max="{{ $booking->total_amount }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Refund Date</label>
                        <input type="date" name="refund_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Stripe">Stripe</option>
                            <option value="Razorpay">Razorpay</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction ID (Optional)</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="TXN-XXXXXX">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Save Refund</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('refundForm').addEventListener('submit', function(e) {
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
            window.crmAlert('Error recording refund');
        }
    });
});
</script>
@endsection
