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
    <div class="card-footer bg-white border-top p-3 d-flex justify-content-end pagination-wrapper">
        {{ $withdrawals->links() }}
    </div>
@endif
