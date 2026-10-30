@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
    .commission-type-pills {
        display: inline-flex;
        background-color: #f1f5f9;
        padding: 4px;
        border-radius: 30px;
        gap: 4px;
        border: 1px solid #e2e8f0;
    }
    .commission-type-pills .filter-type-btn {
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 5px 16px;
        border-radius: 20px;
        transition: all 0.2s ease;
        white-space: nowrap;
        box-shadow: none !important;
        cursor: pointer;
    }
    .commission-type-pills .filter-type-btn:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.7);
    }
    .commission-type-pills .filter-type-btn.active {
        background: #0a2540;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(10, 37, 64, 0.25) !important;
    }

    /* Select2 filter dropdown custom styling */
    #withdrawalFilterToolbar .select2-container {
        width: 100% !important;
    }
    #withdrawalFilterToolbar .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.375rem !important;
        background-color: #ffffff !important;
        padding: 4px 24px 4px 10px !important;
        display: flex !important;
        align-items: center !important;
        font-size: 0.84rem !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
    }
    #withdrawalFilterToolbar .select2-container--open .select2-selection--single,
    #withdrawalFilterToolbar .select2-container .select2-selection--single:hover {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12) !important;
    }
    #withdrawalFilterToolbar .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        padding-left: 0 !important;
        color: #1e293b !important;
        font-weight: 500 !important;
        font-size: 0.84rem !important;
    }
    #withdrawalFilterToolbar .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
    #withdrawalFilterToolbar .select2-container .select2-selection--single .select2-selection__clear {
        margin-right: 14px !important;
        font-size: 1rem !important;
        color: #94a3b8 !important;
    }
    #withdrawalFilterToolbar .select2-container .select2-selection--single .select2-selection__clear:hover {
        color: #ef4444 !important;
    }

    #btnResetWithdrawalFilters {
        height: 38px;
        padding: 0 12px;
        font-size: 0.84rem;
        font-weight: 600;
        border-radius: 0.375rem;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    #btnResetWithdrawalFilters:hover {
        background-color: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    /* Select2 inside modal styling */
    .modal .select2-container {
        width: 100% !important;
    }
    .modal .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem !important;
        padding: 5px 8px !important;
    }
    .modal .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 0 !important;
    }
    .modal .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 6px !important;
    }
    .select2-dropdown {
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #cbd5e1 !important;
        z-index: 1065 !important;
    }
    .select2-search--dropdown .select2-search__field {
        border-radius: 0.375rem !important;
        border: 1px solid #cbd5e1 !important;
        padding: 6px 10px !important;
    }

    #btnResetWithdrawalFilters {
        height: 36px;
        padding: 0 12px;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 0.5rem;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }
    #btnResetWithdrawalFilters:hover {
        background-color: #e2e8f0;
        color: #0f172a;
        border-color: #94a3b8;
    }
</style>
@endpush

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">
                <i class="fas fa-hand-holding-dollar text-primary me-2"></i> Sub-Agent Withdrawal Requests &amp; Commissions
            </h4>
            <p class="text-muted small mb-0">Review Pay-In, Pay-Out, and Sub-Agent balance withdrawal applications</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6">
                <i class="fas fa-bell me-1"></i> <span id="badgePendingCount">{{ $totalPendingCount }}</span> Pending Requests
            </span>
            <button type="button" class="btn btn-dark shadow-sm px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createPayoutModal" style="background-color: #0f172a; border-radius: 8px;">
                <i class="fas fa-plus text-warning me-1"></i> Request Payout
            </button>
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
                        <h3 class="fw-bold mb-0 mt-1 text-warning" id="metricPendingAmount">₹{{ number_format($totalPendingAmount, 2) }}</h3>
                        <small class="text-muted"><span id="metricPendingCount">{{ $totalPendingCount }}</span> requests pending</small>
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
                        <span class="text-muted small fw-semibold text-uppercase">Total Approved &amp; Paid</span>
                        <h3 class="fw-bold mb-0 mt-1 text-success" id="metricApprovedAmount">₹{{ number_format($totalApprovedAmount, 2) }}</h3>
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
                        <span class="text-muted small fw-semibold text-uppercase">Total Pay-In (Admin)</span>
                        <h3 class="fw-bold mb-0 mt-1 text-primary" id="metricPayInAmount">₹{{ number_format($totalPayInAmount, 2) }}</h3>
                        <small class="text-muted">Admin Earnings from Insurers</small>
                    </div>
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Active Sub-Agents</span>
                        <h3 class="fw-bold mb-0 mt-1 text-dark" id="metricActiveSubagents">{{ $activeSubAgentsCount ?? $subagents->count() }}</h3>
                        <small class="text-muted">Registered in CRM</small>
                    </div>
                    <div class="rounded-3 bg-light text-secondary p-3 fs-4 border">
                        <i class="fas fa-users-gear"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card border-0 shadow-sm rounded-4 position-relative">
        <!-- Loading Overlay -->
        <div id="tableLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-none justify-content-center align-items-center rounded-4" style="z-index: 10;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-0">Withdrawal Applications</h5>
                <small class="text-muted">Verify payout details and approve or reject submissions</small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- 3 Filter Pills: All | Pay-In | Pay-Out -->
                <div class="commission-type-pills">
                    <button type="button" class="filter-type-btn {{ ($type ?? 'all') === 'all' ? 'active' : '' }}" data-type="all">All</button>
                    <button type="button" class="filter-type-btn {{ ($type ?? '') === 'pay_in' ? 'active' : '' }}" data-type="pay_in">Pay-In</button>
                    <button type="button" class="filter-type-btn {{ ($type ?? '') === 'pay_out' ? 'active' : '' }}" data-type="pay_out">Pay-Out</button>
                </div>
            </div>
        </div>

        <!-- Dedicated Filter Toolbar -->
        <div class="px-3 py-3 border-bottom" style="background-color: #f8fafc;" id="withdrawalFilterToolbar">
            <form id="withdrawalFilterForm" method="GET" action="{{ route('admin.withdrawals.index') }}">
                <input type="hidden" name="type" id="filterTypeInput" value="{{ $type ?? 'all' }}">
                <div class="row g-2 align-items-end">
                    <!-- Company Filter (Select2) -->
                    <div class="col-12 col-md-4 col-xl-4">
                        <label class="form-label small fw-semibold text-muted mb-1 d-flex align-items-center gap-1">
                            <i class="fa-solid fa-building-shield text-primary"></i> Company / Insurer
                        </label>
                        <select id="filterCompany" name="company_id" class="form-select form-select-sm" style="width: 100%;">
                            <option value="">All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ ($companyId == $company->id) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter (Select2) -->
                    <div class="col-12 col-md-3 col-xl-3">
                        <label class="form-label small fw-semibold text-muted mb-1 d-flex align-items-center gap-1">
                            <i class="fa-solid fa-circle-check text-warning"></i> Status
                        </label>
                        <select id="filterStatus" name="status" class="form-select form-select-sm" style="width: 100%;">
                            <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ in_array(($status ?? ''), ['approved', 'paid']) ? 'selected' : '' }}>Approved / Paid</option>
                            <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <!-- Sub-Agent / Staff Filter (Select2) -->
                    <div class="col-12 col-md-4 col-xl-4">
                        <label class="form-label small fw-semibold text-muted mb-1 d-flex align-items-center gap-1">
                            <i class="fa-solid fa-user-tie text-success"></i> Sub-Agent / Staff
                        </label>
                        <select id="filterSubagent" name="subagent_id" class="form-select form-select-sm" style="width: 100%;">
                            <option value="">All Sub-Agents & Staff</option>
                            @foreach($subagents as $agent)
                                <option value="{{ $agent->id }}" {{ ($subagentId == $agent->id) ? 'selected' : '' }}>
                                    {{ $agent->name }} ({{ ($agent->type ?? 'staff') === 'sub_agents' ? 'Sub-Agent' : 'Staff' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Reset Filters Button -->
                    <div class="col-12 col-md-1 col-xl-1">
                        <button type="button" class="btn btn-sm w-100 d-flex align-items-center justify-content-center gap-1" id="btnResetWithdrawalFilters" title="Reset All Filters">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Container (updated via JS/AJAX) -->
        <div id="withdrawalsTableContainer">
            @include('crm.withdrawals.partials.table', ['withdrawals' => $withdrawals, 'type' => $type ?? 'all'])
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: CREATE PAYOUT REQUEST (ADMIN FOR SUB-AGENT / STAFF)    -->
<!-- ============================================================== -->
<div class="modal fade" id="createPayoutModal" tabindex="-1" aria-labelledby="createPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form id="createPayoutForm">
                @csrf
                <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-hand-holding-dollar fs-4 text-warning"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="createPayoutModalLabel">Request / Issue Payout</h5>
                            <small class="text-white opacity-75">Create a commission withdrawal or manual payout for a Sub-Agent or Staff member</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4" style="background-color: #f8fafc;">
                    <div class="row g-3">
                        <!-- Select Member -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Select Sub-Agent or Staff Member <span class="text-danger">*</span></label>
                            <select name="user_id" id="modal_create_user_id" class="form-select" required style="width: 100%;">
                                <option value="">-- Choose Member --</option>
                                @foreach($subagents as $agent)
                                    <option value="{{ $agent->id }}">
                                        {{ $agent->name }} ({{ ($agent->type ?? 'staff') === 'sub_agents' ? 'Sub-Agent' : 'Staff' }}) — {{ $agent->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Live Member Summary Card -->
                        <div class="col-12 d-none" id="create_member_info_card">
                            <div class="p-3 bg-white rounded-3 border d-flex flex-wrap justify-content-between align-items-center gap-2 shadow-sm">
                                <div>
                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Available Balance</small>
                                    <span class="h4 fw-bold text-success mb-0" id="create_member_balance">₹0.00</span>
                                </div>
                                <div>
                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Total Earned</small>
                                    <span class="fw-semibold text-dark" id="create_member_earned">₹0.00</span>
                                </div>
                                <div>
                                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.72rem;">Policies Booked</small>
                                    <span class="badge bg-light text-dark border px-2 py-1" id="create_member_policies">0 policies</span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success px-3" id="btnFillMaxBalance">
                                        <i class="fa-solid fa-arrow-down-to-bracket me-1"></i> Fill Full Balance
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Payout Amount (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.01" name="amount" id="modal_create_amount" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>

                        <!-- Payout Method -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Payout Method <span class="text-danger">*</span></label>
                            <select name="payout_method" id="modal_create_payout_method" class="form-select" required>
                                <option value="bank_transfer">Bank Transfer (NEFT / IMPS)</option>
                                <option value="upi">UPI (GPay / PhonePe / Paytm)</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <!-- Payout Account / UPI Details -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Payout Account / UPI Details <span class="text-danger">*</span></label>
                            <textarea name="payout_details" id="modal_create_payout_details" rows="2" class="form-control font-monospace" placeholder="e.g. Bank: HDFC, A/C: 1234567890, IFSC: HDFC0001234 or UPI: name@icici" required></textarea>
                            <small class="text-muted" style="font-size: 0.75rem;">Pre-filled automatically with the member's last registered account details when available.</small>
                        </div>

                        <!-- Status Selection -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" id="modal_create_status" class="form-select">
                                <option value="pending">Pending (Review & Disburse later)</option>
                                <option value="approved" selected>Approved &amp; Paid (Disburse immediately)</option>
                            </select>
                        </div>

                        <!-- Admin Remarks -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Admin Reference Note (Optional)</label>
                            <input type="text" name="admin_remarks" id="modal_create_admin_remarks" class="form-control" placeholder="e.g. Disbursed via Bank Transfer">
                        </div>

                        <!-- Sub-Agent Note -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Remarks / Purpose Note (Optional)</label>
                            <input type="text" name="subagent_note" id="modal_create_subagent_note" class="form-control" placeholder="e.g. Monthly Commission Payout">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4 bg-white">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark px-4" id="btnSubmitCreatePayout" style="background-color: #0f172a; border-color: #0f172a;">
                        <i class="fas fa-paper-plane me-1 text-warning"></i> Submit Payout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: VIEW PAYOUT DETAILS                                    -->
<!-- ============================================================== -->
<div class="modal fade" id="viewPayoutModal" tabindex="-1" aria-labelledby="viewPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info fs-4 text-info"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="viewPayoutModalLabel">Payout &amp; Commission Details</h5>
                        <small class="text-white opacity-75" id="view_payout_ref">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Highlight Card -->
                <div class="p-3 bg-white rounded-3 border mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Beneficiary / Member</small>
                        <h5 class="fw-bold text-dark mb-0" id="view_member_name">—</h5>
                        <small class="text-muted" id="view_member_email">—</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Payout Status</small>
                        <span id="view_status_badge" class="badge bg-light text-dark border px-3 py-1 fs-6">—</span>
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-white rounded-3 border text-center">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Gross Premium</small>
                            <div class="fw-bold text-dark fs-6" id="view_gross_premium">₹0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-white rounded-3 border text-center">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Pay-In (Admin)</small>
                            <div class="fw-bold text-primary fs-6" id="view_pay_in">₹0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-white rounded-3 border text-center" style="background-color: #fefce8 !important; border-color: #fef08a !important;">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Pay-Out (Agent)</small>
                            <div class="fw-bold text-dark fs-5" id="view_pay_out">₹0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 bg-white rounded-3 border text-center">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Net Profit</small>
                            <div class="fw-bold text-success fs-6" id="view_net_profit">₹0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Remittance & Bank Info -->
                <div class="p-3 bg-white rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-wallet text-primary me-2"></i>Remittance Account / UPI</h6>
                        <span class="badge bg-light text-secondary border text-uppercase" id="view_payout_method">—</span>
                    </div>
                    <div class="p-2 bg-light rounded-2 font-monospace text-dark small" id="view_payout_details">
                        —
                    </div>
                </div>

                <!-- Notes & Remarks -->
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Purpose / Subagent Note</small>
                            <p class="small text-dark mb-0" id="view_subagent_note">—</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded-3 border h-100">
                            <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem;">Admin Remarks &amp; Clearance</small>
                            <p class="small text-dark mb-0" id="view_admin_remarks">—</p>
                            <small class="text-muted d-block mt-2" id="view_reviewer_info" style="font-size: 0.7rem;"></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-2 px-4 bg-white">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: EDIT PAYOUT                                            -->
<!-- ============================================================== -->
<div class="modal fade" id="editPayoutModal" tabindex="-1" aria-labelledby="editPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form id="editPayoutForm">
                @csrf
                <input type="hidden" name="id" id="edit_payout_id">
                <input type="hidden" name="kind" id="edit_payout_kind">

                <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-pen-to-square fs-5 text-warning"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="editPayoutModalLabel">Edit Payout Application</h5>
                            <small class="text-white opacity-75" id="edit_payout_ref_title">—</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4" style="background-color: #f8fafc;">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Beneficiary</label>
                            <input type="text" class="form-control bg-light" id="edit_payout_user_name" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Payout Amount (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" name="amount" id="edit_payout_amount" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Payout Method <span class="text-danger">*</span></label>
                            <select name="payout_method" id="edit_payout_method" class="form-select" required>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Payout Account / UPI Details <span class="text-danger">*</span></label>
                            <textarea name="payout_details" id="edit_payout_details" rows="2" class="form-control font-monospace" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_payout_status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved / Paid</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Admin Remarks (Optional)</label>
                            <input type="text" name="admin_remarks" id="edit_payout_admin_remarks" class="form-control" placeholder="e.g. Cleared via Bank Transfer">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4 bg-white">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark px-4" id="btnUpdatePayout" style="background-color: #0f172a; border-color: #0f172a;">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Payout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterTypeInput = document.getElementById('filterTypeInput');
        const filterCompany = document.getElementById('filterCompany');
        const filterStatus = document.getElementById('filterStatus');
        const filterSubagent = document.getElementById('filterSubagent');
        const tableContainer = document.getElementById('withdrawalsTableContainer');
        const loadingOverlay = document.getElementById('tableLoadingOverlay');

        // Metric elements
        const metricPendingAmount = document.getElementById('metricPendingAmount');
        const metricPendingCount = document.getElementById('metricPendingCount');
        const badgePendingCount = document.getElementById('badgePendingCount');
        const metricApprovedAmount = document.getElementById('metricApprovedAmount');
        const metricPayInAmount = document.getElementById('metricPayInAmount');
        const metricActiveSubagents = document.getElementById('metricActiveSubagents');

        // Initialize Select2 on filters
        if (typeof jQuery !== 'undefined') {
            $('#filterCompany').select2({
                placeholder: 'All Companies',
                allowClear: true,
                width: '100%'
            });

            $('#filterStatus').select2({
                placeholder: 'All Statuses',
                allowClear: false,
                width: '100%'
            });

            $('#filterSubagent').select2({
                placeholder: 'All Sub-Agents & Staff',
                allowClear: true,
                width: '100%'
            });

            $('#modal_create_user_id').select2({
                dropdownParent: $('#createPayoutModal'),
                placeholder: '-- Choose Sub-Agent or Staff Member --',
                allowClear: true,
                width: '100%'
            });

            // Listen to Select2 filter changes
            $('#filterCompany, #filterStatus, #filterSubagent').on('change', function () {
                fetchFilteredData();
            });

            // Reset filters button
            $('#btnResetWithdrawalFilters').on('click', function () {
                $('#filterCompany').val(null).trigger('change.select2');
                $('#filterStatus').val('all').trigger('change.select2');
                $('#filterSubagent').val(null).trigger('change.select2');
                $('.filter-type-btn').removeClass('active');
                $('.filter-type-btn[data-type="all"]').addClass('active');
                if (filterTypeInput) filterTypeInput.value = 'all';
                fetchFilteredData();
            });

            // When member is selected in Create Payout modal
            let selectedMemberBalance = 0;
            $('#modal_create_user_id').on('change', function () {
                const userId = $(this).val();
                if (!userId) {
                    $('#create_member_info_card').addClass('d-none');
                    return;
                }

                $.ajax({
                    url: `{{ url('/admin/withdrawals/user-balance') }}/${userId}`,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        if (res.success) {
                            selectedMemberBalance = parseFloat(res.available_balance) || 0;
                            $('#create_member_balance').text(res.available_balance_formatted);
                            $('#create_member_earned').text('₹' + (parseFloat(res.total_earned) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                            $('#create_member_policies').text((res.policies_count || 0) + ' policies');
                            $('#create_member_info_card').removeClass('d-none');

                            if (res.last_payout_method) {
                                $('#modal_create_payout_method').val(res.last_payout_method);
                            }
                            if (res.last_payout_details) {
                                $('#modal_create_payout_details').val(res.last_payout_details);
                            }
                        }
                    }
                });
            });

            $('#btnFillMaxBalance').on('click', function () {
                if (selectedMemberBalance > 0) {
                    $('#modal_create_amount').val(selectedMemberBalance);
                }
            });

            // Submit Create Payout form via AJAX
            $('#createPayoutForm').on('submit', function (e) {
                e.preventDefault();
                const btn = $('#btnSubmitCreatePayout');
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

                $.ajax({
                    url: `{{ route('admin.withdrawals.store-payout') }}`,
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        btn.prop('disabled', false).html(origHtml);
                        if (res.success) {
                            $('#createPayoutModal').modal('hide');
                            $('#createPayoutForm')[0].reset();
                            $('#modal_create_user_id').val(null).trigger('change.select2');
                            $('#create_member_info_card').addClass('d-none');
                            fetchFilteredData();

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Payout Created!', text: res.message, timer: 2000, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function (err) {
                        btn.prop('disabled', false).html(origHtml);
                        const msg = err.responseJSON?.message || 'Failed to create payout request.';
                        alert(msg);
                    }
                });
            });

            // Submit Edit Payout form via AJAX
            $('#editPayoutForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#edit_payout_id').val();
                const btn = $('#btnUpdatePayout');
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

                $.ajax({
                    url: `{{ url('/admin/withdrawals') }}/${id}/update`,
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        btn.prop('disabled', false).html(origHtml);
                        if (res.success) {
                            $('#editPayoutModal').modal('hide');
                            fetchFilteredData();

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Updated!', text: res.message, timer: 1800, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function (err) {
                        btn.prop('disabled', false).html(origHtml);
                        const msg = err.responseJSON?.message || 'Failed to update payout record.';
                        alert(msg);
                    }
                });
            });
        }

        // View Payout click
        $(document).on('click', '.btn-view-payout', function () {
            const id = $(this).data('id');
            const kind = $(this).data('kind') || 'withdrawal';

            $.ajax({
                url: `{{ url('/admin/withdrawals') }}/${id}/details?kind=${kind}`,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.success && res.data) {
                        const d = res.data;
                        $('#view_payout_ref').text(`Reference: ${d.ref_no} ${d.withdrawal_ref && d.withdrawal_ref !== '—' ? '(' + d.withdrawal_ref + ')' : ''}`);
                        $('#view_member_name').text(d.user_name);
                        $('#view_member_email').text(`${d.user_email} • ${d.user_type}`);

                        let stBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                        if (d.status === 'approved' || d.status === 'paid') {
                            stBadge = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Approved</span>';
                        } else if (d.status === 'rejected') {
                            stBadge = '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Rejected</span>';
                        }
                        $('#view_status_badge').html(stBadge);

                        $('#view_gross_premium').text(d.gross_premium !== null ? '₹' + parseFloat(d.gross_premium).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—');
                        $('#view_pay_in').text(d.pay_in_amount !== null ? '₹' + parseFloat(d.pay_in_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—');
                        $('#view_pay_out').text(d.pay_out_amount !== null ? '₹' + parseFloat(d.pay_out_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—');
                        $('#view_net_profit').text(d.net_profit !== null ? '₹' + parseFloat(d.net_profit).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—');

                        $('#view_payout_method').text((d.payout_method || '—').replace('_', ' '));
                        $('#view_payout_details').text(d.payout_details || '—');
                        $('#view_subagent_note').text(d.subagent_note || 'No notes provided');
                        $('#view_admin_remarks').text(d.admin_remarks || 'None');
                        $('#view_reviewer_info').text(d.reviewer_name ? `Approved by ${d.reviewer_name} on ${d.reviewed_at}` : `Created on ${d.created_at}`);

                        $('#viewPayoutModal').modal('show');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to load payout details.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // Edit Payout click
        $(document).on('click', '.btn-edit-payout', function () {
            const id = $(this).data('id');
            const kind = $(this).data('kind') || 'withdrawal';

            $.ajax({
                url: `{{ url('/admin/withdrawals') }}/${id}/details?kind=${kind}`,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.success && res.data) {
                        const d = res.data;
                        $('#edit_payout_id').val(d.id);
                        $('#edit_payout_kind').val(d.kind);
                        $('#edit_payout_ref_title').text(`Ref: ${d.ref_no}`);
                        $('#edit_payout_user_name').val(`${d.user_name} (${d.user_email})`);
                        $('#edit_payout_amount').val(d.pay_out_amount);
                        $('#edit_payout_method').val(d.payout_method || 'bank_transfer');
                        $('#edit_payout_details').val(d.payout_details || '');
                        $('#edit_payout_status').val(d.status);
                        $('#edit_payout_admin_remarks').val(d.admin_remarks || '');

                        $('#editPayoutModal').modal('show');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to load payout details.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // Delete Payout click
        $(document).on('click', '.btn-delete-payout', function () {
            const id = $(this).data('id');
            const kind = $(this).data('kind') || 'withdrawal';
            const ref = $(this).data('ref') || 'this record';

            const doDelete = () => {
                $.ajax({
                    url: `{{ url('/admin/withdrawals') }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        kind: kind
                    },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (res) {
                        if (res.success) {
                            fetchFilteredData();
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1600, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function (err) {
                        const msg = err.responseJSON?.message || 'Failed to delete record.';
                        alert(msg);
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Payout Record?',
                    text: `Are you sure you want to delete ${ref}? This cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        doDelete();
                    }
                });
            } else if (confirm(`Are you sure you want to delete ${ref}?`)) {
                doDelete();
            }
        });

        // Function to bind approve confirmation handlers on current DOM
        function initApproveButtons() {
            document.querySelectorAll('.btn-approve-withdrawal').forEach(function (button) {
                const newBtn = button.cloneNode(true);
                button.parentNode.replaceChild(newBtn, button);

                newBtn.addEventListener('click', function (e) {
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

            document.querySelectorAll('.btn-approve-commission').forEach(function (button) {
                const newBtn = button.cloneNode(true);
                button.parentNode.replaceChild(newBtn, button);

                newBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const id = this.getAttribute('data-id');
                    const amount = this.getAttribute('data-amount');
                    const agent = this.getAttribute('data-agent');
                    const form = document.getElementById('approveCommissionForm' + id);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Approve Commission?',
                            html: `Are you sure you want to approve commission payout of <strong>₹${amount}</strong> for <strong>${agent}</strong>?`,
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
                    } else if (confirm(`Are you sure you want to approve commission payout of ₹${amount}?`)) {
                        if (form) form.submit();
                    }
                });
            });
        }

        // Clean URL to keep address bar free of query strings
        if (window.location.search) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Fetch filtered data via AJAX without altering browser URL
        function fetchFilteredData(url = null) {
            const typeVal = filterTypeInput ? filterTypeInput.value : 'all';
            const companyVal = filterCompany ? filterCompany.value : '';
            const statusVal = filterStatus ? filterStatus.value : 'all';
            const subagentVal = filterSubagent ? filterSubagent.value : '';

            if (!url) {
                const params = new URLSearchParams();
                if (typeVal) params.append('type', typeVal);
                if (companyVal) params.append('company_id', companyVal);
                if (statusVal) params.append('status', statusVal);
                if (subagentVal) params.append('subagent_id', subagentVal);
                url = `{{ route('admin.withdrawals.index') }}?` + params.toString();
            }

            // Show loading overlay
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.classList.add('d-flex');

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                loadingOverlay.classList.remove('d-flex');
                loadingOverlay.classList.add('d-none');

                if (data.success) {
                    // Update table HTML
                    tableContainer.innerHTML = data.html;

                    // Update Metrics filter-wise
                    if (data.metrics) {
                        if (metricPendingAmount) metricPendingAmount.textContent = data.metrics.pending_amount_formatted;
                        if (metricPendingCount) metricPendingCount.textContent = data.metrics.pending_count;
                        if (badgePendingCount) badgePendingCount.textContent = data.metrics.pending_count;
                        if (metricApprovedAmount) metricApprovedAmount.textContent = data.metrics.approved_amount_formatted;
                        if (metricPayInAmount && data.metrics.payin_amount_formatted) metricPayInAmount.textContent = data.metrics.payin_amount_formatted;
                        if (metricActiveSubagents) metricActiveSubagents.textContent = data.metrics.active_subagents_count;
                    }

                    // Re-bind approve handlers
                    initApproveButtons();
                    bindPaginationLinks();
                }
            })
            .catch(err => {
                loadingOverlay.classList.remove('d-flex');
                loadingOverlay.classList.add('d-none');
                console.error('Error fetching withdrawal data:', err);
            });
        }

        // Handle AJAX pagination
        function bindPaginationLinks() {
            tableContainer.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchFilteredData(this.getAttribute('href'));
                });
            });
        }

        // Pills click handlers: All | Pay-In | Pay-Out
        document.querySelectorAll('.filter-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                if (filterTypeInput) filterTypeInput.value = this.getAttribute('data-type');
                fetchFilteredData();
            });
        });

        // Initialize approve button handlers and pagination
        initApproveButtons();
        bindPaginationLinks();
    });
</script>
@endpush
@endsection
