<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase text-secondary" style="font-size: 0.76rem; letter-spacing: 0.5px;">
            <tr>
                <th class="ps-4 py-3">Ref / Policy</th>
                <th class="py-3">Type</th>
                <th class="py-3">Insurer / Company</th>
                <th class="py-3">Sub-Agent / Staff</th>
                <th class="py-3 text-end">Gross Premium</th>
                <th class="py-3 text-end text-primary">Pay-In (Admin)</th>
                <th class="py-3 text-end text-dark">Pay-Out (Agent)</th>
                <th class="py-3 text-end text-success">Net Profit</th>
                <th class="py-3">Details / Notes</th>
                <th class="py-3 text-center">Status</th>
                <th class="py-3">Date</th>
                <th class="py-3 text-end pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $item)
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold text-dark">{{ $item->ref_no }}</span>
                        @if(!empty($item->withdrawal_ref) && $item->withdrawal_ref !== $item->ref_no)
                            <div class="mt-1">
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">Req: {{ $item->withdrawal_ref }}</span>
                            </div>
                        @endif
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $item->created_at ? $item->created_at->format('d M Y, h:i A') : '—' }}</div>
                    </td>
                    <td>
                        @if($item->kind === 'withdrawal')
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning">
                                <i class="fa-solid fa-hand-holding-dollar me-1"></i> Withdrawal
                            </span>
                        @elseif($item->kind === 'pay_in')
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <i class="fa-solid fa-arrow-down me-1"></i> Pay-In
                            </span>
                        @else
                            <span class="badge bg-info bg-opacity-10 text-dark border border-info border-opacity-25">
                                <i class="fa-solid fa-receipt me-1"></i> Commission
                            </span>
                        @endif
                    </td>
                    <td>
                        <strong class="text-dark d-block">{{ $item->company_name }}</strong>
                        @if(!empty($item->payout_method) && $item->payout_method !== 'policy_commission')
                            <small class="text-muted text-uppercase">{{ str_replace('_', ' ', $item->payout_method) }}</small>
                        @elseif(!empty($item->company_id))
                            <small class="text-muted"><i class="fa-solid fa-building-shield me-1 text-primary"></i>Insurer</small>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">{{ $item->user_name }}</div>
                        <small class="text-muted">{{ $item->user_email }}</small>
                    </td>
                    <td class="text-end fw-semibold text-secondary">
                        {{ $item->gross_premium !== null ? '₹' . number_format($item->gross_premium, 2) : '—' }}
                    </td>
                    <td class="text-end fw-bold text-primary">
                        @if($item->pay_in_amount !== null && $item->pay_in_amount > 0)
                            <div>₹{{ number_format($item->pay_in_amount, 2) }}</div>
                            @if(!empty($item->pay_in_percentage))
                                <small class="text-muted fw-normal" style="font-size: 0.72rem;">({{ number_format($item->pay_in_percentage, 1) }}%)</small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold text-dark">
                        @if($item->pay_out_amount !== null && $item->pay_out_amount > 0)
                            <div>₹{{ number_format($item->pay_out_amount, 2) }}</div>
                            @if(!empty($item->pay_out_percentage))
                                <small class="text-muted fw-normal" style="font-size: 0.72rem;">({{ number_format($item->pay_out_percentage, 1) }}%)</small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold text-success">
                        {{ $item->net_profit !== null ? '₹' . number_format($item->net_profit, 2) : '—' }}
                    </td>
                    <td>
                        <div class="small text-muted text-break" style="max-width: 220px;">
                            {{ $item->payout_details ?? '—' }}
                            @if(!empty($item->subagent_note))
                                <div class="text-dark mt-1"><strong>Note:</strong> {{ $item->subagent_note }}</div>
                            @endif
                            @if(!empty($item->admin_remarks))
                                <div class="text-muted mt-1"><strong>Admin:</strong> {{ $item->admin_remarks }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="text-center">
                        {!! $item->status_badge !!}
                    </td>
                    <td class="text-muted small">
                        {{ $item->created_at ? $item->created_at->format('d M Y') : '—' }}
                    </td>
                    <td class="text-end pe-4">
                        @if($item->status === 'pending')
                            @if(!empty($item->is_withdrawal))
                                <div class="d-inline-flex gap-2">
                                    <form method="POST" id="approveWithdrawalForm{{ $item->id }}" action="{{ route('admin.withdrawals.approve', $item->id) }}">
                                        @csrf
                                        <input type="hidden" name="admin_remarks" value="Approved by Admin">
                                        <button type="button" class="btn btn-sm btn-success px-3 btn-approve-withdrawal"
                                                data-id="{{ $item->id }}"
                                                data-amount="{{ number_format($item->pay_out_amount, 2) }}"
                                                data-agent="{{ $item->user_name }}"
                                                title="Approve Withdrawal">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $item->id }}" title="Reject Withdrawal">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade text-start" id="rejectModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold text-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Reject Withdrawal {{ $item->withdrawal_ref ?? $item->ref_no }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.withdrawals.reject', $item->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body p-4">
                                                    <p class="small text-muted mb-3">
                                                        Rejecting withdrawal of <strong>₹{{ number_format($item->pay_out_amount, 2) }}</strong> for <strong>{{ $item->user_name }}</strong>. Please provide a reason below.
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                                                        <textarea name="admin_remarks" rows="3" class="form-control" placeholder="e.g. Incorrect bank account details / insufficient settled policy balance" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger btn-sm px-3">Confirm Rejection</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <form method="POST" id="approveCommissionForm{{ $item->id }}" action="{{ route('admin.commissions.approve', $item->id) }}">
                                    @csrf
                                    <button type="button" class="btn btn-sm btn-success px-3 btn-approve-commission"
                                            data-id="{{ $item->id }}"
                                            data-amount="{{ number_format($item->pay_out_amount, 2) }}"
                                            data-agent="{{ $item->user_name }}"
                                            title="Approve Commission">
                                        <i class="fas fa-check me-1"></i> Approve
                                    </button>
                                </form>
                            @endif
                        @else
                            <span class="text-success small fw-semibold"><i class="fas fa-check-double me-1"></i>Completed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center py-5 text-muted">
                        <div class="py-4">
                            <i class="fas fa-inbox fa-3x text-secondary mb-3 opacity-50"></i>
                            <h6 class="fw-semibold text-dark">No records found matching this filter</h6>
                            <p class="small text-muted mb-0">Try selecting a different status, type, company, or sub-agent filter.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($withdrawals->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted small">
            Showing {{ $withdrawals->firstItem() }} to {{ $withdrawals->lastItem() }} of {{ $withdrawals->total() }} records
        </div>
        <div>
            {{ $withdrawals->links() }}
        </div>
    </div>
@endif
