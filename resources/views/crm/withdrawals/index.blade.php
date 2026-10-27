@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">
                <i class="fas fa-hand-holding-dollar text-primary me-2"></i> Sub-Agent Withdrawal Requests
            </h4>
            <p class="text-muted small mb-0">Review, approve, or reject balance withdrawal requests submitted by sub-agents</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6">
                <i class="fas fa-bell me-1"></i> {{ $totalPendingCount }} Pending Requests
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Pending Payouts</span>
                        <h3 class="fw-bold mb-0 mt-1 text-warning">₹{{ number_format($totalPendingAmount, 2) }}</h3>
                        <small class="text-muted">{{ $totalPendingCount }} requests pending</small>
                    </div>
                    <div class="rounded-3 bg-warning-subtle text-warning-emphasis p-3 fs-4">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Approved & Paid</span>
                        <h3 class="fw-bold mb-0 mt-1 text-success">₹{{ number_format($totalApprovedAmount, 2) }}</h3>
                        <small class="text-muted">Disbursed to Sub-Agents</small>
                    </div>
                    <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
                        <i class="fas fa-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Rejected Requests</span>
                        <h3 class="fw-bold mb-0 mt-1 text-danger">{{ $totalRejectedCount }}</h3>
                        <small class="text-muted">Declined requests</small>
                    </div>
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Active Sub-Agents</span>
                        <h3 class="fw-bold mb-0 mt-1 text-primary">{{ $subagents->count() }}</h3>
                        <small class="text-muted">Registered in CRM</small>
                    </div>
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="fas fa-users-gear"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-0">Withdrawal Applications</h5>
                <small class="text-muted">Verify payout details and approve or reject submissions</small>
            </div>
            <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="d-flex flex-wrap gap-2">
                <select name="status" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                <select name="subagent_id" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()">
                    <option value="">All Sub-Agents</option>
                    @foreach($subagents as $agent)
                        <option value="{{ $agent->id }}" {{ ($subagentId == $agent->id) ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>

                @if($status !== 'all' || !empty($subagentId))
                    <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-sm btn-light border">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Request ID</th>
                        <th>Sub-Agent</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Payout Details</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">#WR-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $w->created_at->format('d M Y, h:i A') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $w->user?->name ?? 'Sub-Agent #' . $w->user_id }}</div>
                                <small class="text-muted">{{ $w->user?->email }}</small>
                            </td>
                            <td class="fw-bold text-dark fs-6">
                                ₹{{ number_format($w->amount, 2) }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase">
                                    {{ str_replace('_', ' ', $w->payout_method) }}
                                </span>
                            </td>
                            <td>
                                <div class="small text-muted text-break" style="max-width: 280px;">
                                    {{ $w->payout_details }}
                                </div>
                            </td>
                            <td>
                                <div class="small" style="max-width: 200px;">
                                    @if($w->subagent_note)
                                        <div class="text-dark"><strong>Sub-Agent:</strong> {{ $w->subagent_note }}</div>
                                    @endif
                                    @if($w->admin_remarks)
                                        <div class="text-muted"><strong>Admin:</strong> {{ $w->admin_remarks }}</div>
                                    @endif
                                    @if(!$w->subagent_note && !$w->admin_remarks)
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                {!! $w->status_badge !!}
                                @if($w->reviewed_at)
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        By: {{ $w->reviewer?->name ?? 'Admin' }} ({{ $w->reviewed_at->format('d M') }})
                                    </div>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($w->status === 'pending')
                                    <div class="d-inline-flex gap-2">
                                        <!-- Approve form -->
                                        <form method="POST" id="approveWithdrawalForm{{ $w->id }}" action="{{ route('admin.withdrawals.approve', $w->id) }}">
                                            @csrf
                                            <input type="hidden" name="admin_remarks" value="Approved by Admin">
                                            <button type="button" class="btn btn-sm btn-success px-3 btn-approve-withdrawal"
                                                    data-id="{{ $w->id }}"
                                                    data-amount="{{ number_format($w->amount, 2) }}"
                                                    data-agent="{{ $w->user?->name ?? 'Sub-Agent' }}"
                                                    title="Approve Withdrawal">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                        </form>

                                        <!-- Reject button opening modal -->
                                        <button type="button" class="btn btn-sm btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $w->id }}" title="Reject Withdrawal">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade text-start" id="rejectModal{{ $w->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-bottom">
                                                    <h5 class="modal-title fw-bold text-danger">
                                                        <i class="fas fa-times-circle me-1"></i> Reject Withdrawal #WR-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }}
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('admin.withdrawals.reject', $w->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body p-4">
                                                        <p class="small text-muted mb-3">
                                                            Rejecting withdrawal of <strong>₹{{ number_format($w->amount, 2) }}</strong> for <strong>{{ $w->user?->name }}</strong>. Please provide a reason below for the sub-agent.
                                                        </p>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                                                            <textarea name="admin_remarks" rows="3" class="form-control" placeholder="e.g. Incorrect bank account details / insufficient settled policy balance" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top p-3">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger px-4">Confirm Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-2 mb-2 d-block opacity-50"></i>
                                No withdrawal requests found matching this filter.
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
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-approve-withdrawal').forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const id = this.getAttribute('data-id');
                const amount = this.getAttribute('data-amount');
                const agent = this.getAttribute('data-agent');
                const form = document.getElementById('approveWithdrawalForm' + id);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Approve Payout?',
                        html: `Are you sure you want to approve this withdrawal of <strong>₹${amount}</strong> for <strong>${agent}</strong>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-check me-1"></i> Yes, approve it!',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        customClass: {
                            popup: 'rounded-4 shadow'
                        }
                    }).then((result) => {
                        if (result.isConfirmed && form) {
                            form.submit();
                        }
                    });
                } else if (confirm(`Are you sure you want to approve this withdrawal of ₹${amount}?`)) {
                    if (form) form.submit();
                }
            });
        });
    });
</script>
@endpush
@endsection
