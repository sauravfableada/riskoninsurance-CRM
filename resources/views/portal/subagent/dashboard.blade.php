@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header / Title -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">
                <i class="fas fa-hand-holding-usd text-primary me-2"></i> Sub-Agent Earnings & Commissions
            </h4>
            <p class="text-muted small mb-0">Track your generated policy commissions, payouts, and request balance withdrawals</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newWithdrawalModal">
                <i class="fas fa-paper-plane me-1"></i> Request Withdrawal
            </button>
            <a href="{{ route('subagent.portal.withdrawals') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history me-1"></i> Withdrawal History
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Financial KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Earned</span>
                        <h3 class="fw-bold mb-0 mt-1" style="color: #0f172a;">₹{{ number_format($totalCommissionEarned, 2) }}</h3>
                        <small class="text-success"><i class="fas fa-chart-line me-1"></i> Lifetime commissions</small>
                    </div>
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Available for Withdrawal</span>
                        <h3 class="fw-bold mb-0 mt-1 text-success">₹{{ number_format($availableBalance, 2) }}</h3>
                        <small class="text-muted">Cleared & ready for payout</small>
                    </div>
                    <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Pending Withdrawals</span>
                        <h3 class="fw-bold mb-0 mt-1 text-warning">₹{{ number_format($pendingWithdrawalAmount, 2) }}</h3>
                        <small class="text-muted">Under Admin review</small>
                    </div>
                    <div class="rounded-3 bg-warning-subtle text-warning-emphasis p-3 fs-4">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Withdrawn</span>
                        <h3 class="fw-bold mb-0 mt-1 text-info">₹{{ number_format($totalWithdrawnApproved, 2) }}</h3>
                        <small class="text-muted">Paid out to your account</small>
                    </div>
                    <div class="rounded-3 bg-info-subtle text-info p-3 fs-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commissions Table Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-0">Commission Entries & Policy Breakdown</h5>
                <small class="text-muted">Detailed view of earnings per booked policy</small>
            </div>
            <form method="GET" action="{{ route('subagent.portal.dashboard') }}" class="d-flex flex-wrap gap-2">
                <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ ($statusFilter ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cancelled" {{ ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" name="search" class="form-control" placeholder="Search policy..." value="{{ $search ?? '' }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </div>
                @if(!empty($search) || !empty($statusFilter))
                    <a href="{{ route('subagent.portal.dashboard') }}" class="btn btn-sm btn-light border">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Policy Number</th>
                        <th>Client / Holder</th>
                        <th>Insurer</th>
                        <th>Premium</th>
                        <th>Commission %</th>
                        <th>Earned Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $comm)
                        <tr>
                            <td class="ps-4 text-muted small">
                                {{ $comm->entry_date ? \Carbon\Carbon::parse($comm->entry_date)->format('d M Y') : $comm->created_at->format('d M Y') }}
                            </td>
                            <td class="fw-semibold">
                                {{ $comm->policy?->policy_number_no ?: $comm->policy?->policy_number ?: 'Policy #' . $comm->policy_id }}
                            </td>
                            <td>{{ $comm->policy?->holder?->name ?? 'N/A' }}</td>
                            <td>{{ $comm->policy?->company?->name ?? 'N/A' }}</td>
                            <td>₹{{ number_format($comm->premium_amount, 2) }}</td>
                            <td>
                                @if($comm->commission_percentage)
                                    <span class="badge bg-light text-dark border">{{ $comm->commission_percentage }}%</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="fw-bold text-success">
                                ₹{{ number_format($comm->commission_amount, 2) }}
                            </td>
                            <td>
                                @if($comm->payment_status === 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                                @elseif($comm->payment_status === 'cancelled')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cancelled</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-receipt fs-2 mb-2 d-block opacity-50"></i>
                                No commission records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($commissions->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $commissions->links() }}
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
                        <select name="payout_method" class="form-select" required>
                            <option value="bank_transfer">Bank Transfer (NEFT/IMPS)</option>
                            <option value="upi">UPI ID / VPA</option>
                            <option value="cheque">Bank Cheque</option>
                            <option value="cash">Cash Settlement</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payout Account / UPI Details <span class="text-danger">*</span></label>
                        <textarea name="payout_details" rows="3" class="form-control" placeholder="Provide Account Number, Bank Name, IFSC code or UPI ID (e.g. name@okaxis)" required></textarea>
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
