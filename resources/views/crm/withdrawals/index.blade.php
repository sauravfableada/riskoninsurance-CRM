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
                        <span class="text-muted small fw-semibold text-uppercase">Total Approved & Paid</span>
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
                        <span class="text-muted small fw-semibold text-uppercase">Rejected Requests</span>
                        <h3 class="fw-bold mb-0 mt-1 text-danger" id="metricRejectedCount">{{ $totalRejectedCount }}</h3>
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
                        <h3 class="fw-bold mb-0 mt-1 text-primary" id="metricActiveSubagents">{{ $activeSubAgentsCount ?? $subagents->count() }}</h3>
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
            <form id="withdrawalFilterForm" method="GET" action="{{ route('admin.withdrawals.index') }}" class="d-flex flex-wrap gap-2">
                <select id="filterStatus" name="status" class="form-select form-select-sm" style="width: 150px;">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                <select id="filterSubagent" name="subagent_id" class="form-select form-select-sm" style="width: 180px;">
                    <option value="">All Sub-Agents</option>
                    @foreach($subagents as $agent)
                        <option value="{{ $agent->id }}" {{ ($subagentId == $agent->id) ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>

                <button type="button" id="btnResetFilter" class="btn btn-sm btn-light border {{ ($status === 'all' && empty($subagentId)) ? 'd-none' : '' }}">Reset</button>
            </form>
        </div>

        <!-- Table Container (updated via JS/AJAX) -->
        <div id="withdrawalsTableContainer">
            @include('crm.withdrawals.partials.table', ['withdrawals' => $withdrawals])
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('withdrawalFilterForm');
        const filterStatus = document.getElementById('filterStatus');
        const filterSubagent = document.getElementById('filterSubagent');
        const btnResetFilter = document.getElementById('btnResetFilter');
        const tableContainer = document.getElementById('withdrawalsTableContainer');
        const loadingOverlay = document.getElementById('tableLoadingOverlay');

        // Metric elements
        const metricPendingAmount = document.getElementById('metricPendingAmount');
        const metricPendingCount = document.getElementById('metricPendingCount');
        const metricApprovedAmount = document.getElementById('metricApprovedAmount');
        const metricRejectedCount = document.getElementById('metricRejectedCount');
        const metricActiveSubagents = document.getElementById('metricActiveSubagents');

        // Function to bind approve confirmation handlers on current DOM
        function initApproveButtons() {
            document.querySelectorAll('.btn-approve-withdrawal').forEach(function (button) {
                // remove existing listener clone to prevent duplicates
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

        // Fetch filtered data via AJAX without full page load
        function fetchFilteredData(url = null) {
            const statusVal = filterStatus.value;
            const subagentVal = filterSubagent.value;

            // Toggle reset button visibility
            if (statusVal !== 'all' || (subagentVal && subagentVal !== '')) {
                btnResetFilter.classList.remove('d-none');
            } else {
                btnResetFilter.classList.add('d-none');
            }

            if (!url) {
                const params = new URLSearchParams();
                if (statusVal) params.append('status', statusVal);
                if (subagentVal) params.append('subagent_id', subagentVal);
                url = `{{ route('admin.withdrawals.index') }}?` + params.toString();
            }

            // Update browser URL without reload
            window.history.pushState({}, '', url);

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
                        if (metricApprovedAmount) metricApprovedAmount.textContent = data.metrics.approved_amount_formatted;
                        if (metricRejectedCount) metricRejectedCount.textContent = data.metrics.rejected_count;
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
            tableContainer.querySelectorAll('.pagination-wrapper a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetchFilteredData(this.getAttribute('href'));
                });
            });
        }

        // Listen for filter changes
        filterStatus.addEventListener('change', () => fetchFilteredData());
        filterSubagent.addEventListener('change', () => fetchFilteredData());

        // Reset button
        btnResetFilter.addEventListener('click', function () {
            filterStatus.value = 'all';
            filterSubagent.value = '';
            fetchFilteredData();
        });

        // Initialize approve button handlers and pagination
        initApproveButtons();
        bindPaginationLinks();
    });
</script>
@endpush
@endsection
