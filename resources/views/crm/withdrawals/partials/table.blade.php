<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="withdrawalsMainTable">
        <thead class="table-light text-uppercase text-secondary" style="font-size: 0.76rem; letter-spacing: 0.5px;">
            <tr>
                <th class="ps-4 py-3" style="min-width: 170px;">Ref / Policy</th>
                <th class="py-3" style="min-width: 140px;">Insurer / Company</th>
                <th class="py-3" style="min-width: 190px;">Sub-Agent / Staff</th>
                <th class="py-3 text-end" style="min-width: 110px;">Gross Premium</th>
                <th class="py-3 text-end text-primary" style="min-width: 120px;">Pay-In (Admin)</th>
                <th class="py-3 text-end text-dark" style="min-width: 120px;">Pay-Out (Agent)</th>
                <th class="py-3 text-end text-success" style="min-width: 110px;">Net Profit</th>
                <th class="py-3 text-center" style="min-width: 110px;">Status</th>
                <th class="py-3 text-end pe-4" style="min-width: 150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $item)
                <tr id="withdrawal_row_{{ $item->kind }}_{{ $item->id }}">
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="fw-bold text-dark">{{ $item->ref_no }}</span>
                            @if($item->kind === 'withdrawal')
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size: 0.68rem;">Withdrawal</span>
                            @elseif($item->kind === 'pay_in')
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.68rem;">Pay-In</span>
                            @else
                                <span class="badge bg-info bg-opacity-10 text-dark border border-info border-opacity-25" style="font-size: 0.68rem;">Commission</span>
                            @endif
                        </div>
                        @if(!empty($item->withdrawal_ref) && $item->withdrawal_ref !== $item->ref_no)
                            <div class="mt-0">
                                <small class="text-secondary font-monospace" style="font-size: 0.7rem;">Req: {{ $item->withdrawal_ref }}</small>
                            </div>
                        @endif
                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                            <i class="fa-regular fa-clock me-1"></i>{{ $item->created_at ? $item->created_at->format('d M Y, h:i A') : '—' }}
                        </small>
                    </td>

                    <td>
                        <strong class="text-dark d-block">{{ $item->company_name }}</strong>
                        @if(!empty($item->payout_method) && $item->payout_method !== 'policy_commission')
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;">{{ str_replace('_', ' ', $item->payout_method) }}</small>
                        @elseif(!empty($item->company_id))
                            <small class="text-muted" style="font-size: 0.7rem;"><i class="fa-solid fa-building-shield me-1 text-primary"></i>Insurer</small>
                        @endif
                    </td>

                    <td>
                        <div class="fw-semibold text-dark">{{ $item->user_name }}</div>
                        <small class="text-muted d-block" style="font-size: 0.74rem;">{{ $item->user_email }}</small>
                        @if(!empty($item->payout_details) && $item->payout_details !== '—' && !str_starts_with($item->payout_details, 'Policy Commission'))
                            <div class="mt-1">
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;" title="{{ $item->payout_details }}">
                                    <i class="fa-solid fa-wallet me-1 text-primary"></i>{{ \Illuminate\Support\Str::limit($item->payout_details, 22) }}
                                </span>
                            </div>
                        @endif
                        @if(!empty($item->subagent_note) && !str_contains($item->subagent_note, 'Pay-In:'))
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" title="{{ $item->subagent_note }}">
                                <i class="fa-regular fa-comment-dots text-info me-1"></i>{{ \Illuminate\Support\Str::limit($item->subagent_note, 24) }}
                            </small>
                        @endif
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

                    <td class="text-center">
                        {!! $item->status_badge !!}
                    </td>

                    <td class="text-end pe-4 text-nowrap">
                        <div class="d-inline-flex align-items-center gap-1">
                            @if($item->status === 'pending')
                                @if(!empty($item->is_withdrawal))
                                    <form method="POST" id="approveWithdrawalForm{{ $item->id }}" action="{{ route('admin.withdrawals.approve', $item->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="admin_remarks" value="Approved by Admin">
                                        <button type="button" class="btn btn-sm btn-success px-2 py-1 btn-approve-withdrawal"
                                                data-id="{{ $item->id }}"
                                                data-amount="{{ number_format($item->pay_out_amount, 2) }}"
                                                data-agent="{{ $item->user_name }}"
                                                title="Approve Withdrawal">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $item->id }}" title="Reject Withdrawal">
                                        <i class="fas fa-times"></i>
                                    </button>

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
                                    <form method="POST" id="approveCommissionForm{{ $item->id }}" action="{{ route('admin.commissions.approve', $item->id) }}" class="d-inline">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-success px-2 py-1 btn-approve-commission"
                                                data-id="{{ $item->id }}"
                                                data-amount="{{ number_format($item->pay_out_amount, 2) }}"
                                                data-agent="{{ $item->user_name }}"
                                                title="Approve Commission">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif

                            <!-- View Action Button -->
                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1 btn-view-payout"
                                    data-id="{{ $item->id }}"
                                    data-kind="{{ $item->kind }}"
                                    title="View Full Details">
                                <i class="fa-solid fa-eye"></i>
                            </button>

                            <!-- Edit Action Button -->
                            <button type="button" class="btn btn-sm btn-outline-dark px-2 py-1 btn-edit-payout"
                                    data-id="{{ $item->id }}"
                                    data-kind="{{ $item->kind }}"
                                    title="Edit Payout">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>

                            <!-- Delete Action Button -->
                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 btn-delete-payout"
                                    data-id="{{ $item->id }}"
                                    data-kind="{{ $item->kind }}"
                                    data-ref="{{ $item->withdrawal_ref ?? $item->ref_no }}"
                                    title="Delete Record">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
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
