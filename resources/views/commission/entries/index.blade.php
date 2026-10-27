@extends('layouts.app')

@section('page_title', 'Commission Entries')

@push('styles')
<style>
    .commission-summary-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
        overflow: hidden;
    }
    .commission-summary-card .summary-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }
    .summary-total .summary-icon { color: #2563eb; background: #dbeafe; }
    .summary-pending .summary-icon { color: #d97706; background: #fef3c7; }
    .summary-paid .summary-icon { color: #16a34a; background: #dcfce7; }
    .commission-table-card { border: 0; border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, .07); }
    .commission-table-card .table thead th { background: #f8fafc; color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
    .commission-table-card .table td { vertical-align: middle; }
    .commission-status { display: inline-flex; padding: .32rem .7rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
    .commission-status.pending { color: #92400e; background: #fef3c7; }
    .commission-status.paid { color: #166534; background: #dcfce7; }
    .commission-status.cancelled { color: #991b1b; background: #fee2e2; }
</style>
@endpush

@section('page_actions')
    <a href="{{ route('commission-rules.index') }}" class="btn btn-sm" style="background-color: #1B2A4A; color: #FFFFFF; border-color: #1B2A4A;">
        <i class="fa fa-arrow-left me-1" style="color: #FFFFFF;"></i> Back to Rules
    </a>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ in_array(auth()->user()?->type, ['staff', 'sub_agents'], true) ? 'My Commission Earnings' : 'Commission Entries' }}</h4>
            <p class="text-muted mb-0">Track total, pending, and paid commission earned from policies.</p>
        </div>
        @if($isSubAgent ?? false)
            <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-semibold px-3 py-2 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestWithdrawalModal">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Request Payout</span>
                </button>
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('amount'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ $errors->first('amount') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @if($isSubAgent ?? false)
            <div class="col-md-3">
                <div class="card commission-summary-card summary-total h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                    <div><div class="text-muted small">Total Earned</div><div class="h4 fw-bold mb-0">₹{{ number_format($totalEarned, 2) }}</div></div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card commission-summary-card summary-paid h-100" style="background: #f0fdf4;"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon" style="background: #bbf7d0; color: #15803d;"><i class="fa-solid fa-wallet"></i></span>
                    <div><div class="text-muted small">Available Balance</div><div class="h4 fw-bold mb-0 text-success">₹{{ number_format($availableBalance ?? 0, 2) }}</div></div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card commission-summary-card summary-pending h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div><div class="text-muted small">Pending Payouts</div><div class="h4 fw-bold mb-0 text-warning">₹{{ number_format($pendingWithdrawalAmount ?? 0, 2) }}</div></div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card commission-summary-card summary-paid h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon"><i class="fa-solid fa-circle-check"></i></span>
                    <div><div class="text-muted small">Withdrawn / Paid</div><div class="h4 fw-bold mb-0">₹{{ number_format($totalWithdrawnApproved ?? 0, 2) }}</div></div>
                </div></div>
            </div>
        @else
            <div class="col-md-3">
                <div class="card commission-summary-card summary-total h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon"><i class="fa-solid fa-arrow-down-left"></i></span>
                    <div><div class="text-muted small">Total Pay-In (From Insurers)</div><div class="h4 fw-bold mb-0 text-primary">₹{{ number_format($totalPayIn, 2) }}</div></div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card commission-summary-card summary-pending h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                    <div><div class="text-muted small">Total Pay-Out (To Agents)</div><div class="h4 fw-bold mb-0 text-dark">₹{{ number_format($totalPayOut, 2) }}</div></div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card commission-summary-card summary-paid h-100" style="background: #f0fdf4;"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon" style="background: #bbf7d0; color: #15803d;"><i class="fa-solid fa-hand-holding-dollar"></i></span>
                    <div><div class="text-muted small">Admin Net Profit</div><div class="h4 fw-bold mb-0 text-success">₹{{ number_format($totalAdminProfit, 2) }}</div></div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card commission-summary-card summary-pending h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="summary-icon"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div><div class="text-muted small">Pending Payouts</div><div class="h4 fw-bold mb-0 text-warning">₹{{ number_format($pendingCommission, 2) }}</div></div>
                </div></div>
            </div>
        @endif
    </div>

    <!-- Commission History Table -->
    <div class="row mt-4 mb-4">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card form-card commission-table-card">
                <div class="card-header pb-0 pt-3 p-4 px-2 px-md-4">
                    <div class="d-flex flex-row flex-nowrap justify-content-between align-items-center w-100 mt-lg-n3" style="gap: 10px;">
                        <div class="d-flex align-items-center flex-shrink-0">
                            <h5 class="fw-bold mb-0">Commission History (Pay-In & Pay-Out)</h5>
                        </div>
                    </div>
                </div>

                <div class="p-4 overflow-scroll">
                    <table id="commissionEntriesTable" class="table table-flush" style="width:100%">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center">Sr.No</th>
                                <th>Rule / Insurer</th>
                                <th>Staff / Sub Agent</th>
                                <th>Policy</th>
                                <th>Premium (₹)</th>
                                @if(!($isSubAgent ?? false))
                                    <th class="text-primary">Pay-In (Admin)</th>
                                    <th class="text-warning">Pay-Out (Agent)</th>
                                    <th class="text-success">Net Profit</th>
                                @else
                                    <th>My Commission (₹)</th>
                                    <th>Commission %</th>
                                @endif
                                <th>Status</th>
                                <th>Entry Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($commissionEntries as $index => $entry)
                                @php
                                    $inAmt = (float) ($entry->pay_in_amount > 0 ? $entry->pay_in_amount : $entry->commission_amount);
                                    $outAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
                                    $profit = (float) ($entry->net_admin_profit > 0 ? $entry->net_admin_profit : max(0, $inAmt - $outAmt));
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $entry->commissionRule->rule_name ?? 'Policy Commission' }}</div>
                                        @if($entry->commissionRule?->company)
                                            <small class="text-muted">{{ $entry->commissionRule->company->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $entry->user?->name ?? $entry->policy?->user?->name ?? $entry->agent?->name ?? '—' }}</td>
                                    <td>{{ $entry->policy->policy_number ?? $entry->policy_id ?? '—' }}</td>
                                    <td>₹{{ number_format($entry->premium_amount, 2) }}</td>
                                    @if(!($isSubAgent ?? false))
                                        <td class="fw-bold text-primary">₹{{ number_format($inAmt, 2) }}</td>
                                        <td class="fw-bold text-dark">₹{{ number_format($outAmt, 2) }}</td>
                                        <td class="fw-bold text-success">₹{{ number_format($profit, 2) }}</td>
                                    @else
                                        <td class="fw-bold text-success">₹{{ number_format($entry->commission_amount, 2) }}</td>
                                        <td>
                                            {{ $entry->commission_percentage !== null
                                                ? number_format($entry->commission_percentage, 2) . '%'
                                                : '—' }}
                                        </td>
                                    @endif
                                    <td>
                                        @if($entry->payment_status === 'paid')
                                            <span class="commission-status paid">Paid</span>
                                        @elseif($entry->payment_status === 'pending')
                                            <span class="commission-status pending">Pending</span>
                                        @else
                                            <span class="commission-status cancelled">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $entry->entry_date ? $entry->entry_date->format('d-m-Y') : '—' }}</td>
                                    <td><small class="text-muted">{{ $entry->remarks ?? '—' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(($isSubAgent ?? false) && !empty($myWithdrawals) && $myWithdrawals->count() > 0)
    <!-- Sub-Agent Withdrawal Requests Table -->
    <div class="row mt-4 mb-4">
        <div class="col-lg-12">
            <div class="card form-card commission-table-card">
                <div class="card-header pb-0 pt-3 p-4 px-2 px-md-4">
                    <div class="d-flex justify-content-between align-items-center w-100 mt-lg-n3">
                        <h5 class="fw-bold mb-0">My Withdrawal Requests</h5>
                    </div>
                </div>
                <div class="p-4 overflow-scroll">
                    <table class="table align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Request ID</th>
                                <th>Date</th>
                                <th>Amount (₹)</th>
                                <th>Method</th>
                                <th>Details</th>
                                <th>Status</th>
                                <th>Admin Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myWithdrawals as $w)
                                <tr>
                                    <td class="fw-semibold">#WR-{{ str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $w->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="fw-bold text-dark">₹{{ number_format($w->amount, 2) }}</td>
                                    <td><span class="badge bg-light text-dark border text-uppercase">{{ str_replace('_', ' ', $w->payout_method) }}</span></td>
                                    <td><small class="text-muted">{{ $w->payout_details }}</small></td>
                                    <td>{!! $w->status_badge !!}</td>
                                    <td><small class="text-muted">{{ $w->admin_remarks ?: '—' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($isSubAgent ?? false)
<!-- Modal: Submit Withdrawal Request -->
<div class="modal fade" id="requestWithdrawalModal" tabindex="-1" aria-labelledby="requestWithdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="requestWithdrawalModalLabel">
                    <i class="fa-solid fa-money-bill-transfer text-primary me-2"></i> Request Payout / Withdrawal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subagent.withdrawals.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    @if(($availableBalance ?? 0) <= 0)
                        <div class="alert alert-warning border-0 small mb-3 d-flex align-items-center gap-2">
                            <i class="fas fa-exclamation-circle text-warning fs-5"></i>
                            <div>
                                <strong>Insufficient Available Balance:</strong> You currently have <strong>₹0.00</strong> available to withdraw because all your earned commissions (₹{{ number_format($totalEarned ?? 0, 2) }}) have already been withdrawn or paid out.
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info bg-primary-subtle text-primary border-0 small mb-3">
                            <i class="fas fa-wallet me-1"></i> Current Available Balance: <strong>₹{{ number_format($availableBalance ?? 0, 2) }}</strong>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Withdrawal Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="amount" class="form-control form-control-lg" placeholder="Enter amount to withdraw (e.g. 5000)" value="{{ old('amount') }}" required>
                        <div class="form-text small text-muted">Maximum you can withdraw right now: ₹{{ number_format($availableBalance ?? 0, 2) }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payout Method <span class="text-danger">*</span></label>
                        <select name="payout_method" class="form-select" required>
                            <option value="bank_transfer" {{ old('payout_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer (NEFT/IMPS)</option>
                            <option value="upi" {{ old('payout_method') == 'upi' ? 'selected' : '' }}>UPI ID / VPA</option>
                            <option value="cheque" {{ old('payout_method') == 'cheque' ? 'selected' : '' }}>Bank Cheque</option>
                            <option value="cash" {{ old('payout_method') == 'cash' ? 'selected' : '' }}>Cash Settlement</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payout Account / UPI Details <span class="text-danger">*</span></label>
                        <textarea name="payout_details" rows="3" class="form-control" placeholder="Account Number, IFSC code or UPI ID" required>{{ old('payout_details') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note / Remarks (Optional)</label>
                        <input type="text" name="subagent_note" class="form-control" placeholder="Optional reference note" value="{{ old('subagent_note') }}">
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" {{ ($availableBalance ?? 0) <= 0 ? 'disabled' : '' }}>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        if ($('#commissionEntriesTable').length) {
            $('#commissionEntriesTable').DataTable({
                pageLength: 10,
                order: [[8, 'desc']],
                language: {
                    emptyTable: "No Commission Entries Found",
                },
                columnDefs: [
                    { targets: [0], orderable: false }
                ]
            });
        }
    });
</script>
@endpush
@endsection
