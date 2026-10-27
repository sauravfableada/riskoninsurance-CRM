@extends('layouts.masters')

@section('page_title', 'Manage Commission')

@section('masters_content')

    @push('styles')
        <link rel="stylesheet"
            href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
        <link rel="stylesheet"
            href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <style>
            .comm-type-badge {
                display: inline-block;
                padding: 0.2rem 0.7rem;
                border-radius: 50px;
                font-size: 0.78rem;
                font-weight: 600;
            }

            .comm-type-badge.percentage {
                background: #eff6ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
            }

            .comm-type-badge.flat {
                background: #f0fdf4;
                color: #15803d;
                border: 1px solid #bbf7d0;
            }

            [data-theme="dark"] .comm-type-badge.percentage {
                background: rgba(59,130,246,.15);
                color: #93c5fd;
                border-color: rgba(59,130,246,.3);
            }

            [data-theme="dark"] .comm-type-badge.flat {
                background: rgba(34,197,94,.12);
                color: #86efac;
                border-color: rgba(34,197,94,.25);
            }

            .status-pill {
                display: inline-block;
                min-width: 80px;
                padding: 0.28rem 0.75rem;
                border-radius: 50px;
                font-size: 0.78rem;
                font-weight: 700;
                text-align: center;
                color: #fff;
            }

            .status-pill.active {
                background: linear-gradient(135deg, #24c78b 0%, #19b974 100%);
                box-shadow: 0 3px 8px rgba(25,185,116,.22);
            }

            .status-pill.inactive {
                background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
                box-shadow: 0 3px 8px rgba(100,116,139,.18);
            }
            .status-pill.pending { background: #f59e0b; }
            .status-pill.paid { background: #16a34a; }
            .commission-status-select {
                min-width: 105px;
                border-radius: 999px;
                font-size: .78rem;
                font-weight: 700;
                padding: .35rem 1.8rem .35rem .7rem;
            }

            .doc-action-group {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.4rem;
            }

            .doc-icon-btn {
                width: 32px;
                height: 32px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                font-size: 0.82rem;
                border: 1.5px solid;
                background: transparent;
                transition: all 0.2s ease;
                cursor: pointer;
                text-decoration: none;
            }

            .doc-icon-btn.btn-edit {
                color: #475569;
                border-color: #cbd5e1;
            }

            .doc-icon-btn.btn-edit:hover {
                background: #f1f5f9;
                border-color: #94a3b8;
                color: #0f172a;
            }

            .doc-icon-btn.btn-del {
                color: #ef4444;
                border-color: #fecaca;
            }

            .doc-icon-btn.btn-del:hover {
                background: #fef2f2;
                border-color: #ef4444;
            }

            [data-theme="dark"] .doc-icon-btn.btn-edit {
                color: #94a3b8;
                border-color: rgba(255,255,255,.12);
            }

            [data-theme="dark"] .doc-icon-btn.btn-edit:hover {
                background: rgba(255,255,255,.06);
                color: #e2e8f0;
            }

            [data-theme="dark"] .doc-icon-btn.btn-del {
                color: #f87171;
                border-color: rgba(248,113,113,.25);
            }

            [data-theme="dark"] .doc-icon-btn.btn-del:hover {
                background: rgba(248,113,113,.1);
            }

            .doc-empty-state {
                padding: 3rem 1rem;
                text-align: center;
                color: #94a3b8;
            }

            .doc-empty-state i {
                font-size: 2.5rem;
                margin-bottom: 0.75rem;
                opacity: 0.4;
            }

            .btn-user-expand {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: none;
                background-color: #e2e8f0;
                color: #475569;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
            }
            .btn-user-expand.active {
                background-color: #0f172a;
                color: #fff;
            }
            .master-header-btn {
                min-height: 40px;
                display: inline-flex;
                align-items: center;
            }
            .details-content {
                background-color: #f8fafc;
                border-left: 4px solid #0f172a;
                padding: 1.25rem;
                border-radius: 0 8px 8px 0;
            }
            .expand-label {
                font-size: 0.8rem;
                color: #64748b;
                font-weight: 500;
            }
            .expand-value {
                font-size: 0.85rem;
                color: #0f172a;
                font-weight: 600;
                text-align: right;
            }
        </style>
    @endpush

    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 gap-3">
            <div class="pe-3">
                <h4 class="fw-bold mb-0">Manage Commission</h4>
                <p class="text-muted small mb-0">View and manage commissions for insurance policies.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                <a href="{{ route('masters.masters.index') }}" class="btn btn-dark-blue master-header-btn px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                @if(auth()->user()?->isAdmin() || auth()->user()?->hasMatrixPermission('create_commission_rules'))
                <a href="{{ route('commission-rules.create') }}" class="btn btn-dark-blue">
                    <i class="fa-solid fa-plus me-1"></i>Add Commission
                </a>
                @endif
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="commissionSearch" class="form-control border-start-0"
                        placeholder="Search by Policy Type, Policy..." value="{{ request('search') }}">
                </div>
                <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                    <i class="fa-solid fa-filter"></i> Filters
                </button>
            </div>
            <div class="d-flex align-items-center gap-2 small">
                <span class="text-muted">Show per page:</span>
                <select id="perPageSelect" class="form-select form-select-sm" style="width: 70px;">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>

        <div class="collapse show mt-3" id="filterCollapse">
            <div class="card card-body bg-light border-0 rounded-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Sub Agent</label>
                        <select id="filterSubAgent" class="form-select form-select-sm commission-filter-select" data-placeholder="All Sub Agents">
                            <option value="">All Sub Agents</option>
                            @foreach($subAgents as $subAgent)
                                <option value="{{ $subAgent->id }}">{{ $subAgent->name }}{{ $subAgent->email ? ' — '.$subAgent->email : '' }}{{ $subAgent->phone ? ' — '.$subAgent->phone : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Policy</label>
                        <select id="filterPolicy" class="form-select form-select-sm commission-filter-select" data-placeholder="All Policies">
                            <option value="">All Policies</option>
                            @foreach($policies as $policy)
                                <option value="{{ $policy->id }}">{{ $policy->policy_number_no ?? $policy->policy_number ?? 'Policy #'.$policy->id }} — {{ $policy->holder?->name ?? 'Unknown Holder' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Policy Type</label>
                        <select id="filterPolicyType" class="form-select form-select-sm commission-filter-select" data-placeholder="All Policy Types">
                            <option value="">All Policy Types</option>
                            @foreach($policyTypes as $policyType)
                                <option value="{{ $policyType->id }}">{{ $policyType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Commission Type</label>
                        <select id="filterCommissionType" class="form-select form-select-sm commission-filter-select" data-placeholder="All Commission Types">
                            <option value="">All Commission Types</option>
                            <option value="percentage">Percentage</option>
                            <option value="flat">Fixed Amount</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select id="filterStatus" class="form-select form-select-sm commission-filter-select" data-placeholder="All Statuses">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Commission On Range</label>
                        <div class="d-flex align-items-stretch gap-3">
                            <input type="text" id="filterCommissionRange" class="form-control form-control-sm flex-grow-1" placeholder="Select commission date range" autocomplete="off">
                            <input type="hidden" id="filterFromDate" value="{{ request('from_date') }}">
                            <input type="hidden" id="filterToDate" value="{{ request('to_date') }}">
                            <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3 flex-shrink-0" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="commissionRulesTable" class="table table-hover align-middle mb-0 text-nowrap" style="width:100%;">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:60px;">SR.NO</th>
                        <th>Insurance Company</th>
                        <th>Policy Type</th>
                        <th class="d-none d-md-table-cell">Policy Scope</th>
                        <th class="d-none d-md-table-cell">Sub Agent</th>
                        <th class="d-none d-md-table-cell text-primary">Pay-In (Admin)</th>
                        <th class="d-none d-md-table-cell text-warning">Pay-Out (Agent)</th>
                        <th class="d-none d-md-table-cell text-success">Net Profit</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="text-end pe-4 d-none d-md-table-cell" style="width:100px;">Action</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($commissionRules as $index => $rule)
                    @php
                        $premium = (float) ($rule->policy?->premium_amount ?? 0);

                        $payInType = $rule->pay_in_type ?: ($rule->commission_type ?: 'percentage');
                        $payInVal = (float) ($rule->pay_in_value > 0 ? $rule->pay_in_value : $rule->commission_value);
                        $payInAmount = ($payInType === 'percentage') ? round(($premium * $payInVal) / 100, 2) : $payInVal;

                        $payOutType = $rule->pay_out_type ?: ($rule->commission_type ?: 'percentage');
                        $payOutVal = (float) ($rule->pay_out_value > 0 ? $rule->pay_out_value : $rule->commission_value);
                        $payOutBasedOn = $rule->pay_out_based_on ?: 'admin_commission';

                        if ($payOutType === 'percentage') {
                            $base = ($payOutBasedOn === 'premium') ? $premium : $payInAmount;
                            $payOutAmount = round(($base * $payOutVal) / 100, 2);
                        } else {
                            $payOutAmount = $payOutVal;
                        }

                        $netAdminProfit = max(0, round($payInAmount - $payOutAmount, 2));

                        $entryStatus = $rule->commissionEntries->contains('payment_status', 'pending')
                            ? 'pending'
                            : ($rule->commissionEntries->contains('payment_status', 'paid') ? 'paid' : 'pending');

                        $subAgentName = $rule->policy?->user?->name
                            ?? $rule->agent?->name
                            ?? $rule->policy?->creator?->name
                            ?? 'All / Default';
                    @endphp
                    <tr class="main-row">
                        <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                        <td class="fw-bold text-dark">{{ $rule->company?->name ?? 'All Companies' }}</td>
                        <td class="fw-semibold">{{ $rule->policyType?->name ?? 'All Types (Whole Co.)' }}</td>
                        <td class="text-muted small d-none d-md-table-cell">
                            {{ $rule->policy?->holder?->name ?? ($rule->policy ? 'Policy #' . $rule->policy->policy_number : 'All Policies') }}
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $subAgentName }}</span>
                        </td>
                        <td class="fw-bold d-none d-md-table-cell text-primary">
                            @if($payInType === 'percentage')
                                {{ number_format($payInVal, 2) }}%
                            @else
                                ₹{{ number_format($payInVal, 2) }}
                            @endif
                            @if($premium > 0)
                                <div class="small fw-normal text-muted">(₹{{ number_format($payInAmount, 2) }})</div>
                            @endif
                        </td>
                        <td class="fw-bold d-none d-md-table-cell text-dark">
                            @if($payOutType === 'percentage')
                                {{ number_format($payOutVal, 2) }}%
                                <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">{{ $payOutBasedOn === 'premium' ? 'of Prem' : 'of Pay-In' }}</span>
                            @else
                                ₹{{ number_format($payOutVal, 2) }}
                            @endif
                            @if($premium > 0)
                                <div class="small fw-normal text-muted">(₹{{ number_format($payOutAmount, 2) }})</div>
                            @endif
                        </td>
                        <td class="fw-bold d-none d-md-table-cell text-success">
                            @if($premium > 0)
                                ₹{{ number_format($netAdminProfit, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            <select class="form-select form-select-sm commission-status-select" data-id="{{ $rule->id }}">
                                <option value="pending" {{ $entryStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ $entryStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </td>
                        <td class="pe-4 d-none d-md-table-cell">
                            <div class="doc-action-group">
                                <a href="{{ route('commission-rules.edit', $rule->id) }}"
                                   class="doc-icon-btn btn-edit" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                                <button class="doc-icon-btn btn-del delete-rule"
                                        data-id="{{ $rule->id }}" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="child-row d-none d-md-none">
                        <td colspan="9" class="p-0 border-0">
                            <div class="details-content mb-3 mx-2">
                                <div class="row g-2">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-user me-1"></i> Policy:</div>
                                        <div class="expand-value text-muted fw-normal">{{ $rule->policy?->holder?->name ?? ($rule->policy ? 'Policy #' . $rule->policy->policy_number : '—') }}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-user-tie me-1"></i> Sub Agent:</div>
                                        <div class="expand-value fw-semibold text-dark">{{ $subAgentName }}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-percent me-1"></i> Comm. Value:</div>
                                        <div class="expand-value fw-normal">
                                            @if($rule->commission_type === 'percentage')
                                                {{ number_format($rule->commission_value, 2) }}%
                                            @else
                                                ₹{{ number_format($rule->commission_value, 2) }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-day me-1"></i> Commission On:</div>
                                        <div class="expand-value fw-normal">{{ $rule->commission_on?->format('d-m-Y') ?? '—' }}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                        <div class="expand-label"><i class="fa-solid fa-circle-check me-1"></i> Status:</div>
                                        <div class="expand-value">
                                            <select class="form-select form-select-sm commission-status-select" data-id="{{ $rule->id }}">
                                                <option value="pending" {{ $entryStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="paid" {{ $entryStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center mt-1">
                                        <div class="expand-label text-uppercase fw-bold"><i class="fa-solid fa-gear me-1"></i> ACTIONS :</div>
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <a href="{{ route('commission-rules.edit', $rule->id) }}"
                                               class="btn crm-action-btn btn-sm" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <button class="btn crm-action-btn btn-sm text-danger delete-rule"
                                                    data-id="{{ $rule->id }}" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row">
                        <td colspan="9" class="text-center py-5">
                            <div class="doc-empty-state">
                                <i class="fa-solid fa-indian-rupee-sign d-block mb-2"></i>
                                <p class="mb-3">No commission rules found.</p>
                                <a href="{{ route('commission-rules.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Commission Rule</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        <div class="card-footer border-top-0 py-4 px-4" id="commissionPaginationContainer"></div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    let pageSize = parseInt(urlParams.get('per_page')) || 10;
    let currentPage = 1;
    let filteredRows = [];

    // Get all initial table rows (excluding the empty row if present)
    let $allRows = $('#commissionRulesTable tbody tr.main-row');

    function updatePagination() {
        const q = $('#commissionSearch').val().toLowerCase().trim();
        
        // Filter rows based on search
        filteredRows = [];
        $allRows.each(function () {
            const text = $(this).text().toLowerCase();
            const childRow = $(this).next('.child-row');
            if (!q || text.includes(q)) {
                filteredRows.push({ main: $(this), child: childRow });
            } else {
                $(this).hide();
                childRow.addClass('d-none').hide();
                $(this).find('.btn-user-expand').removeClass('active').find('i').removeClass('fa-minus').addClass('fa-plus');
                $(this).removeClass('border-0');
            }
        });

        const total = filteredRows.length;

        // If no rows found
        if (total === 0) {
            $('#commissionRulesTable tbody tr.empty-row').remove();
            $('#commissionRulesTable tbody').append(`
                <tr class="empty-row">
                    <td colspan="11" class="text-center py-5">
                        <div class="doc-empty-state">
                            <i class="fa-solid fa-indian-rupee-sign d-block mb-2"></i>
                            <p class="mb-3">No matching records found.</p>
                            <a href="{{ route('commission-rules.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Commission Rule</a>
                        </div>
                    </td>
                </tr>
            `);
            $('#commissionPaginationContainer').html('');
            return;
        } else {
            $('#commissionRulesTable tbody tr.empty-row').remove();
        }

        const lastPage = Math.ceil(total / pageSize);
        if (currentPage > lastPage) {
            currentPage = lastPage || 1;
        }

        const from = total > 0 ? (currentPage - 1) * pageSize + 1 : 0;
        const to = Math.min(currentPage * pageSize, total);

        // Hide all rows, then show only the ones on current page
        $allRows.hide();
        $('#commissionRulesTable tbody tr.child-row').addClass('d-none').hide();

        for (let i = from - 1; i < to; i++) {
            if (filteredRows[i]) {
                filteredRows[i].main.show();
                filteredRows[i].main.find('.btn-user-expand').removeClass('active').find('i').removeClass('fa-minus').addClass('fa-plus');
                filteredRows[i].main.removeClass('border-0');
            }
        }

        // Render Pagination UI
        let html = `
        <div class="crm-pagination-container d-flex justify-content-between align-items-center w-100">
            <div class="text-muted small">
                Showing ${from} to ${to} of ${total} results
            </div>
            <ul class="pagination crm-pagination mb-0">
        `;

        // Previous
        if (currentPage > 1) {
            html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
        }

        // Pages
        for (let i = 1; i <= lastPage; i++) {
            if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += i === currentPage
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next
        if (currentPage < lastPage) {
            html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
        }

        html += `</ul></div>`;

        $('#commissionPaginationContainer').html(html);

        // Attach click handlers
        $('#commissionPaginationContainer .page-link[data-page]').on('click', function (e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            updatePagination();
        });
    }

    // Trigger initial pagination
    updatePagination();

    // Client-side search trigger
    $('#commissionSearch').on('input', function () {
        currentPage = 1;
        updatePagination();
    });

    $('#perPageSelect').on('change', function() {
        pageSize = parseInt($(this).val()) || 10;
        currentPage = 1;
        updatePagination();
    });

    function applyCommissionFilters() {
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: {
                from_date: $('#filterFromDate').val(),
                to_date: $('#filterToDate').val(),
                sub_agent_id: $('#filterSubAgent').val(),
                policy_id: $('#filterPolicy').val(),
                policy_type_id: $('#filterPolicyType').val(),
                commission_type: $('#filterCommissionType').val(),
                status: $('#filterStatus').val()
            },
            success: function(response) {
                let tbodyHtml = '';
                if(response.data.length === 0) {
                    tbodyHtml = `
                    <tr class="empty-row">
                        <td colspan="11" class="text-center py-5">
                            <div class="doc-empty-state">
                                <i class="fa-solid fa-indian-rupee-sign d-block mb-2"></i>
                                <p class="mb-3">No matching records found.</p>
                                <a href="/commission-rules/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Commission Rule</a>
                            </div>
                        </td>
                    </tr>
                    `;
                } else {
                    response.data.forEach((rule, index) => {
                        let valStr = rule.commission_type === 'percentage' ? 
                            Number(rule.commission_value).toFixed(2) + '%' : 
                            '₹' + Number(rule.commission_value).toFixed(2);
                        let subAgentBadge = `<span class="badge bg-light text-dark border px-2 py-1">${rule.sub_agent || '—'}</span>`;
                            
                        tbodyHtml += `
                        <tr class="main-row">
                            <td class="ps-4 text-muted fw-semibold">${index + 1}</td>
                            <td class="fw-semibold">${rule.policy_type}</td>
                            <td class="text-muted small d-none d-md-table-cell">${rule.policy_holder}</td>
                            <td class="d-none d-md-table-cell">${subAgentBadge}</td>
                            <td class="fw-semibold d-none d-md-table-cell">${valStr}</td>
                            <td class="text-muted small d-none d-md-table-cell">${rule.commission_on || '—'}</td>
                            <td class="d-none d-md-table-cell">${statusBadge}</td>
                            <td class="pe-4 d-none d-md-table-cell">
                                <div class="doc-action-group">
                                    <a href="/commission-rules/${rule.id}/edit"
                                       class="doc-icon-btn btn-edit" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <button class="doc-icon-btn btn-del delete-rule"
                                            data-id="${rule.id}" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="child-row d-none d-md-none">
                            <td colspan="9" class="p-0 border-0">
                                <div class="details-content mb-3 mx-2">
                                    <div class="row g-2">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-user me-1"></i> Policy:</div>
                                            <div class="expand-value text-muted fw-normal">${rule.policy_holder}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-user-tie me-1"></i> Sub Agent:</div>
                                            <div class="expand-value fw-semibold text-dark">${rule.sub_agent || '—'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-percent me-1"></i> Comm. Value:</div>
                                            <div class="expand-value fw-normal">${valStr}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-day me-1"></i> Commission On:</div>
                                            <div class="expand-value fw-normal">${rule.commission_on || '—'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                            <div class="expand-label"><i class="fa-solid fa-circle-check me-1"></i> Status:</div>
                                            <div class="expand-value">${statusBadge}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center mt-1">
                                            <div class="expand-label text-uppercase fw-bold"><i class="fa-solid fa-gear me-1"></i> ACTIONS :</div>
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <a href="/commission-rules/${rule.id}/edit"
                                                   class="btn crm-action-btn btn-sm" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <button class="btn crm-action-btn btn-sm text-danger delete-rule"
                                                        data-id="${rule.id}" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        `;
                    });
                }
                $('#commissionRulesTable tbody').html(tbodyHtml);
                
                $allRows = $('#commissionRulesTable tbody tr.main-row');
                currentPage = 1;
                updatePagination();
            }
        });
    }

    $('.commission-filter-select').each(function() {
        $(this).select2({
            width: '100%',
            allowClear: true,
            placeholder: $(this).data('placeholder')
        });
    });

    let commissionRangePicker = null;
    if (typeof flatpickr !== 'undefined') {
        commissionRangePicker = flatpickr('#filterCommissionRange', {
            mode: 'range',
            dateFormat: 'd-m-Y',
            onChange: function(selectedDates) {
                $('#filterFromDate').val(selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '');
                $('#filterToDate').val(selectedDates[1] ? flatpickr.formatDate(selectedDates[1], 'Y-m-d') : '');
                applyCommissionFilters();
            },
            onClose: function() {
                applyCommissionFilters();
            }
        });
    }

    $('.commission-filter-select').on('change', function() {
        window.setTimeout(applyCommissionFilters, 0);
    });

    $('#clearFiltersBtn').on('click', function() {
        $('#filterFromDate').val('');
        $('#filterToDate').val('');
        $('.commission-filter-select').val('').trigger('change.select2');
        if (commissionRangePicker) commissionRangePicker.clear(false);
        $('#commissionSearch').val('');
        applyCommissionFilters();
    });

    $(document).on('change', '.commission-status-select', function () {
        const select = $(this);
        const ruleId = select.data('id');
        const status = select.val();
        const previousStatus = status === 'paid' ? 'pending' : 'paid';

        select.prop('disabled', true);

        $.ajax({
            url: "{{ url('commission-rules') }}/" + ruleId + '/status',
            type: 'PATCH',
            data: {
                _token: "{{ csrf_token() }}",
                payment_status: status
            },
            success: function (response) {
                $('.commission-status-select[data-id="' + ruleId + '"]').val(status);
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: response.message,
                    timer: 1400,
                    showConfirmButton: false
                });
            },
            error: function (xhr) {
                $('.commission-status-select[data-id="' + ruleId + '"]').val(previousStatus);
                Swal.fire('Error', xhr.responseJSON?.message || 'Unable to update commission status.', 'error');
            },
            complete: function () {
                $('.commission-status-select[data-id="' + ruleId + '"]').prop('disabled', false);
            }
        });
    });

    /* ── Delete ────────────────────────────────────────────── */
    $(document).on('click', '.delete-rule', async function () {
        const deleteId = $(this).data('id');
        const $btn = $(this);
        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Delete Commission Rule?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        });

        if (!confirmation.isConfirmed) return;

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url:  "{{ url('commission-rules') }}/" + deleteId,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: 'Deleted!',
                        text: response.message, timer: 2000, showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                    Swal.fire({ icon: 'error', title: 'Error!',
                        text: response.message || 'Failed to delete.',
                        confirmButtonColor: '#0f172a' });
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                Swal.fire({ icon: 'error', title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#0f172a' });
            }
        });
    });

    $(document).on('click', '.btn-user-expand', function() {
        const icon = $(this).find('i');
        const childRow = $(this).closest('tr').next('.child-row');
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            $(this).closest('tr').removeClass('border-0');
            icon.removeClass('fa-minus').addClass('fa-plus');
            childRow.addClass('d-none').hide();
        } else {
            $(this).addClass('active');
            $(this).closest('tr').addClass('border-0');
            icon.removeClass('fa-plus').addClass('fa-minus');
            childRow.removeClass('d-none').show();
        }
    });
});
</script>
@endpush
