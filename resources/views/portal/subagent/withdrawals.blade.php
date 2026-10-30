@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">
                <i class="fas fa-history text-primary me-2"></i> Withdrawal Requests & Payout History
            </h4>
            <p class="text-muted small mb-0">Track all submitted withdrawal requests and their approval statuses</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('subagent.portal.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Earnings
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newWithdrawalModal">
                <i class="fas fa-plus me-1"></i> New Request
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Request ID</th>
                        <th>Requested Date</th>
                        <th>Amount</th>
                        <th>Payout Method</th>
                        <th>Payout Details</th>
                        <th>Status</th>
                        <th>Admin Remarks</th>
                        <th>Reviewed On</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                        <tr>
                            <td class="ps-4 fw-bold">#WR-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="text-muted small">{{ $w->created_at->format('d M Y, h:i A') }}</td>
                            <td class="fw-bold text-dark fs-6">₹{{ number_format($w->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase">
                                    {{ str_replace('_', ' ', $w->payout_method) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted text-break" style="max-width: 250px; display: inline-block;">
                                    {{ $w->payout_details }}
                                </small>
                            </td>
                            <td>
                                {!! $w->status_badge !!}
                            </td>
                            <td>
                                @if($w->admin_remarks)
                                    <small class="text-dark">{{ $w->admin_remarks }}</small>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $w->reviewed_at ? $w->reviewed_at->format('d M Y, h:i A') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-receipt fs-2 mb-2 d-block opacity-50"></i>
                                You haven't submitted any withdrawal requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: New Withdrawal Request -->
<div class="modal fade" id="newWithdrawalModal" tabindex="-1" aria-labelledby="newWithdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="newWithdrawalModalLabel">
                    <i class="fas fa-money-bill-transfer text-primary me-2"></i> Request Payout / Withdrawal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subagent.portal.withdrawals.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info bg-primary-subtle text-primary border-0 small mb-3">
                        <i class="fas fa-wallet me-1"></i> Current Available Balance: <strong>₹{{ number_format($availableBalance, 2) }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Withdrawal Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" max="{{ $availableBalance }}" min="1" name="amount" class="form-control form-control-lg" placeholder="e.g. 5000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payout Method <span class="text-danger">*</span></label>
                        @php
                            $selectedMethod = old('payout_method', $lastPayoutMethod ?? 'bank_transfer');
                        @endphp
                        <select name="payout_method" class="form-select" required>
                            <option value="bank_transfer" {{ $selectedMethod == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer (NEFT/IMPS)</option>
                            <option value="upi" {{ $selectedMethod == 'upi' ? 'selected' : '' }}>UPI ID / VPA</option>
                            <option value="cheque" {{ $selectedMethod == 'cheque' ? 'selected' : '' }}>Bank Cheque</option>
                            <option value="cash" {{ $selectedMethod == 'cash' ? 'selected' : '' }}>Cash Settlement</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Payout Account / UPI Details <span class="text-danger">*</span></span>
                            @if(!empty($lastPayoutDetails))
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-normal" style="font-size: 0.72rem;">
                                    <i class="fas fa-check-circle me-1"></i>Saved Details Auto-Filled
                                </span>
                            @endif
                        </label>
                        <textarea name="payout_details" rows="3" class="form-control" placeholder="Account Number, IFSC code or UPI ID" required>{{ old('payout_details', $lastPayoutDetails ?? '') }}</textarea>
                        @if(!empty($lastPayoutDetails))
                            <div class="form-text small text-muted">
                                <i class="fas fa-info-circle me-1"></i>Auto-filled from your previous payout. You can edit this anytime.
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note / Remarks (Optional)</label>
                        <input type="text" name="subagent_note" class="form-control" placeholder="Optional reference note">
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" {{ $availableBalance <= 0 ? 'disabled' : '' }}>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
