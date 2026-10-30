@extends('layouts.app')

@section('content')
@push('styles')
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
        <div class="d-flex gap-2">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6">
                <i class="fas fa-bell me-1"></i> <span id="badgePendingCount">{{ $totalPendingCount }}</span> Pending Requests
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

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- 3 Filter Pills: All | Pay-In | Pay-Out (matching red boxes) -->
                <div class="commission-type-pills me-1">
                    <button type="button" class="filter-type-btn {{ ($type ?? 'all') === 'all' ? 'active' : '' }}" data-type="all">All</button>
                    <button type="button" class="filter-type-btn {{ ($type ?? '') === 'pay_in' ? 'active' : '' }}" data-type="pay_in">Pay-In</button>
                    <button type="button" class="filter-type-btn {{ ($type ?? '') === 'pay_out' ? 'active' : '' }}" data-type="pay_out">Pay-Out</button>
                </div>

                <form id="withdrawalFilterForm" method="GET" action="{{ route('admin.withdrawals.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="type" id="filterTypeInput" value="{{ $type ?? 'all' }}">

                    <!-- Company-wise Filter -->
                    <select id="filterCompany" name="company_id" class="form-select form-select-sm" style="width: 170px;">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ ($companyId == $company->id) ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Status Filter -->
                    <select id="filterStatus" name="status" class="form-select form-select-sm" style="width: 140px;">
                        <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ in_array(($status ?? ''), ['approved', 'paid']) ? 'selected' : '' }}>Approved / Paid</option>
                        <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <!-- Sub-Agent Filter -->
                    <select id="filterSubagent" name="subagent_id" class="form-select form-select-sm" style="width: 170px;">
                        <option value="">All Sub-Agents</option>
                        @foreach($subagents as $agent)
                            <option value="{{ $agent->id }}" {{ ($subagentId == $agent->id) ? 'selected' : '' }}>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <!-- Table Container (updated via JS/AJAX) -->
        <div id="withdrawalsTableContainer">
            @include('crm.withdrawals.partials.table', ['withdrawals' => $withdrawals, 'type' => $type ?? 'all'])
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('withdrawalFilterForm');
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
        }

        // Clean URL to keep address bar free of query strings
        if (window.location.search) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Fetch filtered data via AJAX without altering browser URL
        function fetchFilteredData(url = null) {
            const typeVal = filterTypeInput.value || 'all';
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
                filterTypeInput.value = this.getAttribute('data-type');
                fetchFilteredData();
            });
        });

        // Listen for filter changes
        if (filterCompany) filterCompany.addEventListener('change', () => fetchFilteredData());
        if (filterStatus) filterStatus.addEventListener('change', () => fetchFilteredData());
        if (filterSubagent) filterSubagent.addEventListener('change', () => fetchFilteredData());

        // Initialize approve button handlers and pagination
        initApproveButtons();
        bindPaginationLinks();
    });
</script>
@endpush
@endsection
