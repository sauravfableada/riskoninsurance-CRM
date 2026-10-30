@extends('layouts.masters')

@section('page_title', 'Commission Management - Sub-Agents & Staff')

@section('masters_content')

    @push('styles')
        <link rel="stylesheet"
            href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
        <link rel="stylesheet"
            href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
        <style>
            .stat-badge {
                border-radius: 12px;
                padding: 12px 18px;
                display: flex;
                align-items: center;
                gap: 14px;
                background: #fff;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            .stat-badge .icon-box {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.15rem;
            }
            .stat-badge.primary .icon-box { background: rgba(37,99,235,0.1); color: #2563eb; }
            .stat-badge.teal .icon-box { background: rgba(13,148,136,0.1); color: #0d9488; }
            .stat-badge.success .icon-box { background: rgba(22,163,74,0.1); color: #16a34a; }
            .stat-badge.warning .icon-box { background: rgba(217,119,6,0.1); color: #d97706; }
            
            .nav-tabs .nav-link {
                color: #64748b;
                font-weight: 600;
                border: none;
                border-bottom: 2.5px solid transparent;
                padding: 0.75rem 1.25rem;
            }
            .nav-tabs .nav-link.active {
                color: #0f172a;
                border-color: #0f172a;
                background: transparent;
            }
            
            #agentCommissionTable_wrapper .dataTables_length,
            #agentCommissionTable_wrapper .dataTables_filter,
            #commissionEntriesTable_wrapper .dataTables_length,
            #commissionEntriesTable_wrapper .dataTables_filter {
                padding: 1rem 1.25rem;
            }
            #agentCommissionTable_wrapper .dataTables_info,
            #agentCommissionTable_wrapper .dataTables_paginate,
            #commissionEntriesTable_wrapper .dataTables_info,
            #commissionEntriesTable_wrapper .dataTables_paginate {
                padding: 0.75rem 1.25rem;
            }
            .member-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 0.95rem;
            }
            .commission-role-pills {
                display: inline-flex;
                background-color: #f1f5f9;
                padding: 4px;
                border-radius: 30px;
                gap: 4px;
                border: 1px solid #e2e8f0;
            }
            .commission-role-pills .filter-role-btn {
                border: none;
                background: transparent;
                color: #64748b;
                font-weight: 600;
                font-size: 0.82rem;
                padding: 6px 16px;
                border-radius: 20px;
                transition: all 0.25s ease;
                white-space: nowrap;
                box-shadow: none !important;
            }
            .commission-role-pills .filter-role-btn:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.6);
            }
            .commission-role-pills .filter-role-btn.active {
                background: linear-gradient(135deg, #0d6efd, #0b5ed7);
                color: #ffffff !important;
                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.35) !important;
            }

            #commissionEntriesTable {
                width: 100% !important;
                table-layout: auto;
            }
            #commissionEntriesTable th, 
            #commissionEntriesTable td {
                white-space: nowrap;
                vertical-align: middle;
            }
            #commissionEntriesTable th:nth-child(2),
            #commissionEntriesTable td:nth-child(2) {
                max-width: 180px;
                white-space: normal;
                word-break: break-word;
            }
            #commissionEntriesTable th:nth-child(3),
            #commissionEntriesTable td:nth-child(3) {
                max-width: 170px;
                white-space: normal;
                word-break: break-word;
            }

            /* Select2 inside modal styling */
            .select2-container {
                width: 100% !important;
            }
            .select2-container .select2-selection--single {
                height: 38px !important;
                border: 1px solid #dee2e6 !important;
                border-radius: 0.375rem !important;
                padding: 5px 8px !important;
            }
            .select2-container .select2-selection--single .select2-selection__rendered {
                line-height: 26px !important;
                padding-left: 0 !important;
                color: #212529 !important;
            }
            .select2-container .select2-selection--single .select2-selection__arrow {
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

            .commission-status {
                display: inline-flex;
                padding: .32rem .7rem;
                border-radius: 999px;
                font-size: .75rem;
                font-weight: 700;
            }
            .commission-status.pending { color: #92400e; background: #fef3c7; }
            .commission-status.paid { color: #166534; background: #dcfce7; }
            .commission-status.cancelled { color: #991b1b; background: #fee2e2; }
        </style>
    @endpush

    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3 gap-3">
            <div class="pe-3">
                <h4 class="fw-bold mb-1">Commission Management</h4>
                <p class="text-muted small mb-0">Manage commission rates for Sub-Agents & Staff and track policy transaction commission entries.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                <a href="{{ route('masters.masters.index') }}" class="btn btn-outline-secondary px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Masters
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-md-3">
                <div class="stat-badge primary">
                    <div class="icon-box"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Members</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ count($agentsWithCommission) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="stat-badge teal">
                    <div class="icon-box"><i class="fa-solid fa-user-tie"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Sub-Agents</div>
                        <h4 class="fw-bold mb-0" style="color: #0d9488;">{{ $subAgentsCount }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="stat-badge warning">
                    <div class="icon-box"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Agent Pay-Out</div>
                        <h4 class="fw-bold mb-0 text-dark">₹{{ number_format($totalPayOut ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="stat-badge success">
                    <div class="icon-box"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Admin Net Retained</div>
                        <h4 class="fw-bold mb-0 text-success">₹{{ number_format($totalAdminProfit ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB NAVIGATION -->
        <ul class="nav nav-tabs border-bottom mb-0" id="commissionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="members-tab" data-bs-toggle="tab" data-bs-target="#tab-members" type="button" role="tab" aria-controls="tab-members" aria-selected="true">
                    <i class="fa-solid fa-users me-1"></i> Sub-Agents & Staff Rates
                    <span class="badge bg-dark rounded-pill ms-1">{{ count($agentsWithCommission) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="entries-tab" data-bs-toggle="tab" data-bs-target="#tab-entries" type="button" role="tab" aria-controls="tab-entries" aria-selected="false">
                    <i class="fa-solid fa-receipt me-1"></i> Commission Entries (History)
                    <span class="badge bg-primary rounded-pill ms-1">{{ count($commissionEntries) }}</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="commissionTabsContent">
            
            <!-- TAB 1: SUB-AGENTS & STAFF COMMISSION LIST -->
            <div class="tab-pane fade show active" id="tab-members" role="tabpanel" aria-labelledby="members-tab">
                <!-- Filter bar -->
                <div class="px-4 py-3 bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="small fw-bold text-dark text-uppercase d-flex align-items-center gap-1" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-filter text-primary"></i> Filter by Role:
                        </span>
                        <div class="commission-role-pills" role="group">
                            <button type="button" class="filter-role-btn active" data-role="">All Members</button>
                            <button type="button" class="filter-role-btn" data-role="Sub Agent">Sub-Agents</button>
                            <button type="button" class="filter-role-btn" data-role="Team Member / Staff">Staff</button>
                        </div>
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-info text-info"></i>
                        <span>Click <strong>"Set Commission"</strong> or <strong>"Edit Rate"</strong> on any member row to configure.</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="agentCommissionTable" class="table table-hover align-middle mb-0" style="width:100%;">
                        <thead class="table-light text-uppercase text-secondary" style="font-size: 0.76rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 py-3" style="width: 50px;">SR.NO</th>
                                <th class="py-3">Member & Details</th>
                                <th class="py-3">Role</th>
                                <th class="py-3">Pay-In (Admin Share)</th>
                                <th class="py-3">Pay-Out (Agent Share)</th>
                                <th class="py-3">Calculation Basis</th>
                                <th class="py-3">Wallet</th>
                                <th class="py-3">Status</th>
                                <th class="pe-4 py-3 text-end" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agentsWithCommission as $idx => $item)
                                @php
                                    $isSubAgent = ($item['type'] === 'sub_agents');
                                    $initials = strtoupper(substr($item['name'], 0, 2));
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="member-avatar {{ $isSubAgent ? 'bg-primary-subtle text-primary border border-primary border-opacity-25' : 'bg-secondary-subtle text-dark border' }}">
                                                {{ $initials }}
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block fs-6">{{ $item['name'] }}</strong>
                                                <small class="text-muted d-block"><i class="fa-regular fa-envelope me-1"></i>{{ $item['email'] }}</small>
                                                @if(!empty($item['phone']) && $item['phone'] !== '—')
                                                    <small class="text-muted d-block"><i class="fa-solid fa-phone me-1"></i>{{ $item['phone'] }}</small>
                                                @endif
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    Updated: <span id="member_updated_{{ $item['id'] }}">{{ $item['updated_at'] }}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill {{ $isSubAgent ? 'bg-teal-subtle border' : 'bg-secondary-subtle text-dark border' }}" 
                                              style="{{ $isSubAgent ? 'background-color: rgba(13, 148, 136, 0.12); color: #0d9488; border-color: rgba(13, 148, 136, 0.3) !important;' : '' }}; font-size: 0.78rem;">
                                            <i class="fa-solid {{ $isSubAgent ? 'fa-user-tie' : 'fa-id-badge' }} me-1"></i>
                                            {{ $item['type_label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div id="member_payin_badge_{{ $item['id'] }}">
                                            @if($item['has_rule'])
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                    {{ $item['pay_in_type'] === 'percentage' ? number_format($item['pay_in_value'], 2) . '%' : '₹' . number_format($item['pay_in_value'], 2) }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border">Default / Global</span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">Admin from Insurer</small>
                                    </td>
                                    <td>
                                        <div id="member_payout_badge_{{ $item['id'] }}">
                                            @if($item['has_rule'])
                                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                                    {{ $item['pay_out_type'] === 'percentage' ? number_format($item['pay_out_value'], 2) . '%' : '₹' . number_format($item['pay_out_value'], 2) }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border">Default / Global</span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">Disbursed to Member</small>
                                    </td>
                                    <td id="member_basis_{{ $item['id'] }}">
                                        <span class="badge bg-light text-secondary border">
                                            {{ $item['pay_out_based_on'] === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-inline-flex flex-column">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                <i class="fa-solid fa-wallet me-1"></i> ₹{{ number_format($item['wallet_balance'], 2) }}
                                            </span>
                                            @if($item['total_earned'] > 0)
                                                <small class="text-muted" style="font-size: 0.72rem;">Earned: ₹{{ number_format($item['total_earned'], 2) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td id="member_status_{{ $item['id'] }}">
                                        @if($item['has_rule'] && $item['is_active'])
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-circle-check me-1"></i> Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                Default / Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end" id="member_action_{{ $item['id'] }}">
                                        <button type="button" class="btn btn-sm {{ $item['has_rule'] ? 'btn-outline-dark' : 'btn-dark' }} px-3 open-commission-modal-btn" data-user-id="{{ $item['id'] }}">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> {{ $item['has_rule'] ? 'Edit Rate' : 'Set Commission' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: COMMISSION ENTRIES (TRANSACTION HISTORY) -->
            <div class="tab-pane fade" id="tab-entries" role="tabpanel" aria-labelledby="entries-tab">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <strong class="text-dark"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Policy Commission Transaction Log</strong>
                        <small class="text-muted d-block">Individual transactions with gross premiums, Pay-In, Pay-Out, and Admin profits.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" id="badge_paid_commission">
                            Paid: ₹{{ number_format($paidCommission ?? 0, 2) }}
                        </span>
                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1" id="badge_pending_commission">
                            Pending: ₹{{ number_format($pendingCommission ?? 0, 2) }}
                        </span>
                        <button type="button" class="btn btn-sm btn-dark px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCommissionEntryModal" style="background-color: #0f172a;">
                            <i class="fa-solid fa-plus me-1 text-warning"></i> Add Transaction Entry
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="commissionEntriesTable" class="table table-hover align-middle mb-0" style="width:100%">
                        <thead class="table-light text-uppercase text-secondary" style="font-size: 0.76rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 py-3" style="width: 50px;">SR.NO</th>
                                <th class="py-3">Rule / Insurer</th>
                                <th class="py-3">Staff / Sub Agent</th>
                                <th class="py-3">Policy Number</th>
                                <th class="py-3">Gross Premium</th>
                                <th class="py-3 text-primary">Pay-In (Admin)</th>
                                <th class="py-3 text-warning">Pay-Out (Agent)</th>
                                <th class="py-3 text-success">Net Profit</th>
                                <th class="py-3">Date</th>
                                <th class="pe-4 py-3 text-end" style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($commissionEntries as $index => $entry)
                                @php
                                    $inAmt = (float) ($entry->pay_in_amount > 0 ? $entry->pay_in_amount : $entry->commission_amount);
                                    $outAmt = (float) ($entry->pay_out_amount > 0 ? $entry->pay_out_amount : $entry->commission_amount);
                                    $profit = (float) ($entry->net_admin_profit > 0 ? $entry->net_admin_profit : max(0, $inAmt - $outAmt));
                                    $status = $entry->payment_status ?: 'pending';
                                @endphp
                                <tr id="entry_row_{{ $entry->id }}" 
                                    data-status="{{ $status }}" 
                                    data-payout="{{ $outAmt }}" 
                                    data-payin="{{ $inAmt }}" 
                                    data-profit="{{ $profit }}" 
                                    data-premium="{{ (float)$entry->premium_amount }}">
                                    <td class="ps-4 text-muted fw-semibold">{{ $index + 1 }}</td>
                                    <td>
                                        @php
                                            $ruleName = $entry->commissionRule->rule_name ?? 'Policy Commission';
                                            // If rule name is something like "Policy #POL-xxx Custom Commission", show clean "Custom Commission"
                                            if (preg_match('/Policy\s*#[^\s]+\s*(.*)/i', $ruleName, $m)) {
                                                $displayRuleName = trim($m[1]) ?: 'Custom Commission';
                                            } else {
                                                $displayRuleName = $ruleName;
                                            }
                                        @endphp
                                        <strong class="text-dark d-block">{{ $displayRuleName }}</strong>
                                        <small class="text-muted">{{ $entry->commissionRule?->company?->name ?? 'Direct Insurance' }}</small>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block">{{ $entry->user?->name ?? $entry->policy?->user?->name ?? '—' }}</strong>
                                        <small class="text-muted">{{ $entry->user?->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $entry->policy?->policy_number ?? ('#' . $entry->policy_id) }}
                                        </span>
                                        @if($entry->policy?->holder)
                                             <small class="text-muted d-block">{{ $entry->policy->holder->name }}</small>
                                        @endif
                                    </td>
                                    <td class="fw-semibold text-dark">₹{{ number_format($entry->premium_amount, 2) }}</td>
                                    <td class="fw-bold text-primary">₹{{ number_format($inAmt, 2) }}</td>
                                    <td class="fw-bold text-dark">₹{{ number_format($outAmt, 2) }}</td>
                                    <td class="fw-bold text-success">₹{{ number_format($profit, 2) }}</td>
                                    <td class="text-muted small">
                                        {{ $entry->entry_date ? $entry->entry_date->format('d M Y') : ($entry->created_at ? $entry->created_at->format('d M Y') : '—') }}
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1 view-entry-btn" data-id="{{ $entry->id }}" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-dark px-2 py-1 edit-entry-btn" data-id="{{ $entry->id }}" title="Edit Transaction">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 delete-entry-btn" data-id="{{ $entry->id }}" data-policy="{{ $entry->policy?->policy_number ?? ('#' . $entry->policy_id) }}" title="Delete Transaction">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- CREATE / EDIT COMMISSION MODAL -->
    <div class="modal fade" id="memberCommissionModal" tabindex="-1" aria-labelledby="memberCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="memberCommissionModalForm">
                    @csrf
                    <input type="hidden" name="agent_id" id="modal_member_agent_id">
                    <input type="hidden" name="user_id" id="modal_member_user_id">
                    
                    <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-hand-holding-dollar fs-4 text-warning"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-white" id="memberCommissionModalLabel">Set Member Commission Rate</h5>
                                <small class="text-white opacity-75">Configure personalized Pay-In & Pay-Out rates for this Sub-Agent or Staff member</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4" style="background-color: #f8fafc;">
                        <!-- Highlight Header -->
                        <div class="p-3 bg-white rounded-4 border mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Agent / Team Member</span>
                                <h4 class="mb-0 fw-bold text-dark" id="modal_member_name">—</h4>
                                <small class="text-muted" id="modal_member_email">—</small>
                            </div>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold border" id="modal_member_role_badge" style="background-color: rgba(13, 148, 136, 0.1); color: #0d9488;">
                                Sub-Agent
                            </span>
                        </div>

                        <div class="row g-4 mb-4">
                            <!-- PAY-IN (Admin's Share from Insurance Company) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-primary border-opacity-25 rounded-4 shadow-sm" style="background: #ffffff;">
                                    <div class="card-header bg-primary text-white py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="fa-solid fa-hand-holding-dollar me-1"></i> PAY-IN (Admin Share)</span>
                                        <span class="badge bg-white text-primary">From Company</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="text-muted small mb-3">Expected Admin gross commission on policies booked by this member.</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Type <span class="text-danger">*</span></label>
                                                <select name="pay_in_type" id="modal_member_pay_in_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹ Amount)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_in_value" id="modal_member_pay_in_value" class="form-control" value="20" required>
                                                    <span class="input-group-text modal-member-payin-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-primary border-opacity-25">
                                            <small class="text-muted fw-semibold">Admin Rate</small>
                                            <div class="h4 fw-bold text-primary mb-0" id="modal_member_preview_payin">20.00%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PAY-OUT (Agent / Sub-Agent Share) -->
                            <div class="col-md-6">
                                <div class="card h-100 rounded-4 shadow-sm" style="background: #ffffff; border-color: rgba(13, 148, 136, 0.4);">
                                    <div class="card-header text-white py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center" style="background-color: #0d9488;">
                                        <span class="fw-bold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> PAY-OUT (Member Share)</span>
                                        <span class="badge bg-white text-teal" style="color: #0d9488;">To Member</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="text-muted small mb-3">Commission payout disbursed to this sub-agent or staff member.</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                                                <select name="pay_out_type" id="modal_member_pay_out_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Base</label>
                                                <select name="pay_out_based_on" id="modal_member_pay_out_based_on" class="form-select form-select-sm">
                                                    <option value="admin_commission">% of Pay-In</option>
                                                    <option value="premium">% of Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_out_value" id="modal_member_pay_out_value" class="form-control" value="10" required>
                                                    <span class="input-group-text modal-member-payout-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border" style="border-color: rgba(13, 148, 136, 0.4) !important;">
                                            <small class="text-muted fw-semibold">Member Share</small>
                                            <div class="h4 fw-bold mb-0" id="modal_member_preview_payout" style="color: #0d9488;">10.00%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LIVE PROFIT BANNER -->
                        <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Retained Earnings</h6>
                                <small class="text-secondary fw-semibold">Admin commission minus member payout</small>
                            </div>
                            <div class="h3 fw-bold mb-0" id="modal_member_preview_profit" style="color: #047857 !important;">18.00%</div>
                        </div>

                        <!-- Active Switch -->
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="modal_member_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="modal_member_is_active">Rule is Active for all policies booked by this member</label>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark px-4" id="saveMemberCommissionBtn" style="background-color: #0f172a; border-color: #0f172a;">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Commission Rate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CREATE COMMISSION TRANSACTION ENTRY MODAL -->
    <div class="modal fade" id="createCommissionEntryModal" tabindex="-1" aria-labelledby="createCommissionEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="createCommissionEntryForm">
                    @csrf
                    
                    <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-invoice-dollar fs-4 text-warning"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-white">Record Commission Transaction</h5>
                                <small class="text-white-50">Create a new Pay-In & Pay-Out entry for a policy</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Calculation Method Selector -->
                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-calculator text-primary me-1"></i> Commission Calculation Method <span class="text-danger">*</span>
                                </label>
                                <select name="calculation_method" id="entry_calculation_method" class="form-select bg-light fw-semibold">
                                    <option value="company_wise">1. Company-wise Commission</option>
                                    <option value="category_wise">2. Category/Type-wise Commission</option>
                                    <option value="sub_type_wise">3. Sub Type Commission</option>
                                    <option value="agent_wise">4. Agent / Team Member-wise Commission</option>
                                    <option value="policy_wise">5. Policy-wise Commission</option>
                                </select>
                                <small class="text-muted d-block mt-1">Rates auto-calculate according to the selected priority method rules.</small>
                            </div>

                            <!-- Policy Select -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-file-shield text-primary me-1"></i> Policy <span class="text-danger">*</span>
                                </label>
                                <select name="policy_id" id="entry_policy_id" class="form-select" required>
                                    <option value="">-- Select Policy --</option>
                                    @foreach($availablePolicies ?? [] as $pol)
                                        @php
                                            $assignedUserId = $pol->user_id ?: ($pol->created_by ?: null);
                                            $assignedUserName = $pol->user?->name ?: ($pol->creator?->name ?: '');
                                        @endphp
                                        <option value="{{ $pol->id }}" 
                                            data-premium="{{ (float) ($pol->premium_amount ?? 0) }}"
                                            data-user-id="{{ $assignedUserId }}"
                                            data-user-name="{{ $assignedUserName }}"
                                            data-policy-number="{{ $pol->policy_number }}"
                                            data-holder="{{ $pol->holder?->name ?? 'Unknown Holder' }}"
                                            data-company="{{ $pol->company?->name ?? 'Direct Insurance' }}">
                                            #{{ $pol->policy_number }} - {{ $pol->holder?->name ?? 'N/A' }} (₹{{ number_format((float) ($pol->premium_amount ?? 0), 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted" id="entry_policy_detail_hint">Select a policy to auto-fill premium and member.</small>
                            </div>

                            <!-- Member / Agent Select -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-user text-secondary me-1"></i> Staff / Sub-Agent <span class="text-danger">*</span>
                                </label>
                                <select name="user_id" id="entry_user_id" class="form-select" required>
                                    <option value="">-- Search or Select Staff / Sub-Agent --</option>
                                    @php
                                        $dropdownMembers = ($allMembers ?? collect([]))->isNotEmpty() ? $allMembers : ($agentsList ?? collect([]));
                                    @endphp
                                    @foreach($dropdownMembers as $ag)
                                        <option value="{{ $ag->id }}">
                                            {{ $ag->name }} ({{ $ag->type === 'sub_agents' ? 'Sub-Agent' : ($ag->type === 'staff' ? 'Staff' : ($ag->name ?? 'User')) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Gross Premium Amount -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-indian-rupee-sign text-dark me-1"></i> Gross Premium <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">₹</span>
                                    <input type="number" step="0.01" name="premium_amount" id="entry_premium_amount" class="form-control fw-bold" placeholder="0.00" required>
                                </div>
                            </div>

                            <!-- Payment Status -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-wallet text-secondary me-1"></i> Payment Status <span class="text-danger">*</span>
                                </label>
                                <select name="payment_status" id="entry_payment_status" class="form-select" required>
                                    <option value="pending" selected>Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <!-- Entry Date -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-regular fa-calendar text-secondary me-1"></i> Transaction Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="entry_date" id="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <!-- COMMISSION SHARE CALCULATION CARDS -->
                            <div class="col-12 mt-3">
                                <div class="row g-3">
                                    <!-- Pay-In Card (Admin Commission) -->
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                                    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Pay-In (Admin Share)
                                                </span>
                                                <small class="text-muted">From Insurer</small>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-muted">Type</label>
                                                    <select name="pay_in_type" id="entry_pay_in_type" class="form-select form-select-sm">
                                                        <option value="percentage">Percentage (%)</option>
                                                        <option value="fixed">Fixed (₹)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-muted">Value <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" name="pay_in_value" id="entry_pay_in_value" class="form-control" value="20" required>
                                                        <span class="input-group-text entry-payin-unit">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded-3 text-center bg-light border border-primary border-opacity-25">
                                                <small class="text-muted fw-semibold">Calculated Admin Pay-In</small>
                                                <div class="h4 fw-bold text-primary mb-0" id="entry_preview_payin_amount">₹0.00</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pay-Out Card (Member Commission) -->
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge rounded-pill bg-teal-subtle text-dark border px-2 py-1" style="background-color: rgba(13, 148, 136, 0.12); color: #0d9488; border-color: rgba(13, 148, 136, 0.3) !important;">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Pay-Out (Agent Share)
                                                </span>
                                                <small class="text-muted">To Member</small>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold text-muted">Type</label>
                                                    <select name="pay_out_type" id="entry_pay_out_type" class="form-select form-select-sm">
                                                        <option value="percentage">Percentage (%)</option>
                                                        <option value="fixed">Fixed (₹)</option>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold text-muted">Base</label>
                                                    <select name="pay_out_based_on" id="entry_pay_out_based_on" class="form-select form-select-sm">
                                                        <option value="admin_commission">% of Pay-In</option>
                                                        <option value="premium">% of Premium</option>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold text-muted">Value <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" name="pay_out_value" id="entry_pay_out_value" class="form-control" value="10" required>
                                                        <span class="input-group-text entry-payout-unit">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded-3 text-center bg-light border" style="border-color: rgba(13, 148, 136, 0.4) !important;">
                                                <small class="text-muted fw-semibold">Calculated Agent Pay-Out</small>
                                                <div class="h4 fw-bold mb-0" id="entry_preview_payout_amount" style="color: #0d9488;">₹0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- LIVE NET ADMIN PROFIT DISPLAY -->
                            <div class="col-12">
                                <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                                    <div>
                                        <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Retained Earnings</h6>
                                        <small class="text-secondary fw-semibold">Admin Pay-In minus Member Pay-Out</small>
                                    </div>
                                    <div class="h3 fw-bold mb-0" id="entry_preview_profit_amount" style="color: #047857 !important;">₹0.00</div>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Remarks / Transaction Note (Optional)</label>
                                <textarea name="remarks" id="entry_remarks" rows="2" class="form-control" placeholder="Add any details or transaction notes..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark px-4" id="saveCommissionEntryBtn" style="background-color: #0f172a; border-color: #0f172a;">
                            <i class="fa-solid fa-check me-1"></i> Record Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- VIEW COMMISSION TRANSACTION MODAL -->
    <div class="modal fade" id="viewCommissionEntryModal" tabindex="-1" aria-labelledby="viewCommissionEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-info fs-4 text-info"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="viewCommissionEntryModalLabel">Transaction Details</h5>
                            <small class="text-white-50">Comprehensive view of Pay-In, Pay-Out, and Admin Retained Earnings</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Calculation Method & Insurer Info -->
                        <div class="col-12">
                            <div class="p-2 px-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                                <span class="small text-muted fw-semibold"><i class="fa-solid fa-calculator text-primary me-1"></i> Calculation Method:</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" id="view_entry_calc_method">Company-wise Commission</span>
                            </div>
                        </div>

                        <!-- Top Summary Cards -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 bg-light border">
                                <small class="text-muted fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Policy & Insurer</small>
                                <div class="h5 fw-bold text-dark mb-1" id="view_entry_policy_number">—</div>
                                <div class="text-muted small mb-1"><i class="fa-solid fa-user me-1"></i>Holder: <span class="fw-semibold text-dark" id="view_entry_holder_name">—</span></div>
                                <div class="text-muted small"><i class="fa-solid fa-building me-1"></i>Insurer: <span class="fw-semibold text-dark" id="view_entry_company_name">—</span></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 bg-light border">
                                <small class="text-muted fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sub-Agent / Staff</small>
                                <div class="h5 fw-bold text-dark mb-1" id="view_entry_member_name">—</div>
                                <div class="text-muted small mb-1"><i class="fa-regular fa-envelope me-1"></i><span id="view_entry_member_email">—</span></div>
                                <div class="text-muted small"><i class="fa-solid fa-id-badge me-1"></i>Role: <span class="badge bg-secondary-subtle text-dark border" id="view_entry_member_type">—</span></div>
                            </div>
                        </div>

                        <!-- Financial Figures -->
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 border bg-white text-center shadow-sm h-100">
                                <small class="text-muted fw-semibold d-block">Gross Premium</small>
                                <div class="h4 fw-bold text-dark mb-0 mt-1" id="view_entry_premium">—</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 border bg-primary bg-opacity-10 text-center shadow-sm h-100 border-primary border-opacity-25">
                                <small class="text-primary fw-semibold d-block">Admin Pay-In</small>
                                <div class="h4 fw-bold text-primary mb-0 mt-1" id="view_entry_pay_in">—</div>
                                <small class="text-muted" id="view_entry_pay_in_pct" style="font-size: 0.75rem;">—</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 border text-center shadow-sm h-100" style="background-color: rgba(13, 148, 136, 0.08); border-color: rgba(13, 148, 136, 0.25) !important;">
                                <small class="fw-semibold d-block" style="color: #0d9488;">Agent Pay-Out</small>
                                <div class="h4 fw-bold mb-0 mt-1" id="view_entry_pay_out" style="color: #0d9488;">—</div>
                                <small class="text-muted" id="view_entry_pay_out_pct" style="font-size: 0.75rem;">—</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 border border-success border-opacity-25 bg-success bg-opacity-10 text-center shadow-sm h-100">
                                <small class="text-success fw-semibold d-block">Net Admin Profit</small>
                                <div class="h4 fw-bold text-success mb-0 mt-1" id="view_entry_profit">—</div>
                                <small class="text-success" style="font-size: 0.75rem;">Retained</small>
                            </div>
                        </div>

                        <!-- Payment Status & Date -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border">
                                <span class="fw-semibold text-muted">Payment Status:</span>
                                <span id="view_entry_status_badge">—</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border">
                                <span class="fw-semibold text-muted">Transaction Date:</span>
                                <span class="fw-bold text-dark" id="view_entry_date">—</span>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="col-12">
                            <div class="p-3 rounded-3 bg-light border">
                                <small class="text-muted fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Remarks / Notes</small>
                                <p class="mb-0 text-dark" id="view_entry_remarks">—</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 bg-white">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-dark px-4" id="viewModalToEditBtn">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit This Transaction
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT COMMISSION TRANSACTION MODAL -->
    <div class="modal fade" id="editCommissionEntryModal" tabindex="-1" aria-labelledby="editCommissionEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="editCommissionEntryForm">
                    @csrf
                    <input type="hidden" name="entry_id" id="edit_entry_id">
                    
                    <div class="modal-header py-3 px-4 text-white" style="background-color: #0f172a;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-pen-to-square fs-4 text-warning"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-white">Edit Commission Transaction</h5>
                                <small class="text-white-50">Modify amounts, rates, payment status, or remarks</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Calculation Method Selector -->
                            <div class="col-12">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-calculator text-primary me-1"></i> Commission Calculation Method <span class="text-danger">*</span>
                                </label>
                                <select name="calculation_method" id="edit_entry_calculation_method" class="form-select bg-light fw-semibold">
                                    <option value="company_wise">1. Company-wise Commission</option>
                                    <option value="category_wise">2. Category/Type-wise Commission</option>
                                    <option value="sub_type_wise">3. Sub Type Commission</option>
                                    <option value="agent_wise">4. Agent / Team Member-wise Commission</option>
                                    <option value="policy_wise">5. Policy-wise Commission</option>
                                </select>
                                <small class="text-muted d-block mt-1">Rates recalculate according to the selected priority method rules.</small>
                            </div>

                            <!-- Policy Select -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-file-shield text-primary me-1"></i> Policy <span class="text-danger">*</span>
                                </label>
                                <select name="policy_id" id="edit_entry_policy_id" class="form-select" required>
                                    <option value="">-- Select Policy --</option>
                                    @foreach($availablePolicies ?? [] as $pol)
                                        @php
                                            $assignedUserId = $pol->user_id ?: ($pol->created_by ?: null);
                                            $assignedUserName = $pol->user?->name ?: ($pol->creator?->name ?: '');
                                        @endphp
                                        <option value="{{ $pol->id }}" 
                                            data-premium="{{ (float) ($pol->premium_amount ?? 0) }}"
                                            data-user-id="{{ $assignedUserId }}"
                                            data-user-name="{{ $assignedUserName }}"
                                            data-policy-number="{{ $pol->policy_number }}"
                                            data-holder="{{ $pol->holder?->name ?? 'Unknown Holder' }}"
                                            data-company="{{ $pol->company?->name ?? 'Direct Insurance' }}">
                                            #{{ $pol->policy_number }} - {{ $pol->holder?->name ?? 'N/A' }} (₹{{ number_format((float) ($pol->premium_amount ?? 0), 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Member / Agent Select -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-user text-secondary me-1"></i> Staff / Sub-Agent <span class="text-danger">*</span>
                                </label>
                                <select name="user_id" id="edit_entry_user_id" class="form-select" required>
                                    <option value="">-- Search or Select Staff / Sub-Agent --</option>
                                    @foreach($dropdownMembers as $ag)
                                        <option value="{{ $ag->id }}">
                                            {{ $ag->name }} ({{ $ag->type === 'sub_agents' ? 'Sub-Agent' : ($ag->type === 'staff' ? 'Staff' : ($ag->name ?? 'User')) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Gross Premium Amount -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-indian-rupee-sign text-dark me-1"></i> Gross Premium <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">₹</span>
                                    <input type="number" step="0.01" name="premium_amount" id="edit_entry_premium_amount" class="form-control fw-bold" placeholder="0.00" required>
                                </div>
                            </div>

                            <!-- Payment Status -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-wallet text-secondary me-1"></i> Payment Status <span class="text-danger">*</span>
                                </label>
                                <select name="payment_status" id="edit_entry_payment_status" class="form-select" required>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <!-- Entry Date -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fa-regular fa-calendar text-secondary me-1"></i> Transaction Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="entry_date" id="edit_entry_date" class="form-control" required>
                            </div>

                            <!-- COMMISSION SHARE CALCULATION CARDS -->
                            <div class="col-12 mt-3">
                                <div class="row g-3">
                                    <!-- Pay-In Card (Admin Commission) -->
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                                    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Pay-In (Admin Share)
                                                </span>
                                                <small class="text-muted">From Insurer</small>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-muted">Type</label>
                                                    <select name="pay_in_type" id="edit_entry_pay_in_type" class="form-select form-select-sm">
                                                        <option value="percentage">Percentage (%)</option>
                                                        <option value="fixed">Fixed (₹)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-muted">Value <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" name="pay_in_value" id="edit_entry_pay_in_value" class="form-control" required>
                                                        <span class="input-group-text edit-entry-payin-unit">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded-3 text-center bg-light border border-primary border-opacity-25">
                                                <small class="text-muted fw-semibold">Calculated Admin Pay-In</small>
                                                <div class="h4 fw-bold text-primary mb-0" id="edit_entry_preview_payin_amount">₹0.00</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pay-Out Card (Member Commission) -->
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge rounded-pill bg-teal-subtle text-dark border px-2 py-1" style="background-color: rgba(13, 148, 136, 0.12); color: #0d9488; border-color: rgba(13, 148, 136, 0.3) !important;">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Pay-Out (Agent Share)
                                                </span>
                                                <small class="text-muted">To Member</small>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold text-muted">Type</label>
                                                    <select name="pay_out_type" id="edit_entry_pay_out_type" class="form-select form-select-sm">
                                                        <option value="percentage">Percentage (%)</option>
                                                        <option value="fixed">Fixed (₹)</option>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold text-muted">Base</label>
                                                    <select name="pay_out_based_on" id="edit_entry_pay_out_based_on" class="form-select form-select-sm">
                                                        <option value="admin_commission">% of Pay-In</option>
                                                        <option value="premium">% of Premium</option>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold text-muted">Value <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" name="pay_out_value" id="edit_entry_pay_out_value" class="form-control" required>
                                                        <span class="input-group-text edit-entry-payout-unit">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded-3 text-center bg-light border" style="border-color: rgba(13, 148, 136, 0.4) !important;">
                                                <small class="text-muted fw-semibold">Calculated Agent Pay-Out</small>
                                                <div class="h4 fw-bold mb-0" id="edit_entry_preview_payout_amount" style="color: #0d9488;">₹0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- LIVE NET ADMIN PROFIT DISPLAY -->
                            <div class="col-12">
                                <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                                    <div>
                                        <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Retained Earnings</h6>
                                        <small class="text-secondary fw-semibold">Admin Pay-In minus Member Pay-Out</small>
                                    </div>
                                    <div class="h3 fw-bold mb-0" id="edit_entry_preview_profit_amount" style="color: #047857 !important;">₹0.00</div>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Remarks / Transaction Note (Optional)</label>
                                <textarea name="remarks" id="edit_entry_remarks" rows="2" class="form-control" placeholder="Add any details or transaction notes..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark px-4" id="updateCommissionEntryBtn" style="background-color: #0f172a; border-color: #0f172a;">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Tab 1: Member commission rates table
            const tableMembers = $('#agentCommissionTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [5, 10, 20, 50, 100],
                "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search members by name, email, phone...",
                    "lengthMenu": "Show _MENU_ members per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ members",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": '<i class="fa fa-angle-right"></i>',
                        "previous": '<i class="fa fa-angle-left"></i>'
                    }
                },
                "ordering": false
            });

            // Tab 2: Commission entries table
            const tableEntries = $('#commissionEntriesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [5, 10, 20, 50, 100],
                "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search entries by rule, insurer, policy...",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": '<i class="fa fa-angle-right"></i>',
                        "previous": '<i class="fa fa-angle-left"></i>'
                    }
                },
                "ordering": false
            });

            // Initialize Select2 on modals
            $('#entry_policy_id').select2({
                dropdownParent: $('#createCommissionEntryModal'),
                width: '100%',
                placeholder: '-- Search or Select Policy --'
            });
            $('#entry_user_id').select2({
                dropdownParent: $('#createCommissionEntryModal'),
                width: '100%',
                placeholder: '-- Search or Select Staff / Sub-Agent --'
            });

            $('#edit_entry_policy_id').select2({
                dropdownParent: $('#editCommissionEntryModal'),
                width: '100%',
                placeholder: '-- Search or Select Policy --'
            });
            $('#edit_entry_user_id').select2({
                dropdownParent: $('#editCommissionEntryModal'),
                width: '100%',
                placeholder: '-- Search or Select Staff / Sub-Agent --'
            });

            // Function to update Paid & Pending badges dynamically based on filtered rows
            function updateEntrySummaryBadges() {
                let paidSum = 0;
                let pendingSum = 0;

                tableEntries.rows({ filter: 'applied' }).every(function() {
                    const rowNode = this.node();
                    if (rowNode) {
                        const status = ($(rowNode).attr('data-status') || '').toLowerCase();
                        const payout = parseFloat($(rowNode).attr('data-payout')) || 0;

                        if (status === 'paid') {
                            paidSum += payout;
                        } else if (status === 'pending' || status === '') {
                            pendingSum += payout;
                        }
                    }
                });

                $('#badge_paid_commission').text('Paid: ₹' + paidSum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#badge_pending_commission').text('Pending: ₹' + pendingSum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            // Hook draw event on tableEntries so search and pagination update the badges
            tableEntries.on('draw', function() {
                updateEntrySummaryBadges();
            });

            // Adjust columns on tab switch
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });

            // Quick Role Filter Buttons
            $('.filter-role-btn').on('click', function() {
                $('.filter-role-btn').removeClass('active');
                $(this).addClass('active');
                const role = $(this).data('role');
                tableMembers.column(2).search(role).draw();
            });

            // Live recalculation formula preview
            function recalculateMemberModal() {
                const inType = $('#modal_member_pay_in_type').val();
                const inVal = parseFloat($('#modal_member_pay_in_value').val()) || 0;
                const outType = $('#modal_member_pay_out_type').val();
                const outVal = parseFloat($('#modal_member_pay_out_value').val()) || 0;
                const outBase = $('#modal_member_pay_out_based_on').val();

                $('.modal-member-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                $('.modal-member-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                if (inType === 'percentage') {
                    $('#modal_member_preview_payin').text(inVal.toFixed(2) + '%');
                    if (outType === 'percentage') {
                        if (outBase === 'admin_commission') {
                            const agentEffective = (inVal * outVal) / 100;
                            const adminNet = Math.max(0, inVal - agentEffective);
                            $('#modal_member_preview_payout').text(outVal.toFixed(2) + '% (' + agentEffective.toFixed(2) + '% eff.)');
                            $('#modal_member_preview_profit').text(adminNet.toFixed(2) + '%');
                        } else {
                            const adminNet = Math.max(0, inVal - outVal);
                            $('#modal_member_preview_payout').text(outVal.toFixed(2) + '% of Gross');
                            $('#modal_member_preview_profit').text(adminNet.toFixed(2) + '%');
                        }
                    } else {
                        $('#modal_member_preview_payout').text('₹' + outVal.toFixed(2));
                        $('#modal_member_preview_profit').text(inVal.toFixed(2) + '% - ₹' + outVal.toFixed(2));
                    }
                } else {
                    $('#modal_member_preview_payin').text('₹' + inVal.toFixed(2));
                    if (outType === 'percentage') {
                        const agentAmt = (inVal * outVal) / 100;
                        const profit = Math.max(0, inVal - agentAmt);
                        $('#modal_member_preview_payout').text('₹' + agentAmt.toFixed(2) + ' (' + outVal.toFixed(2) + '%)');
                        $('#modal_member_preview_profit').text('₹' + profit.toFixed(2));
                    } else {
                        const profit = Math.max(0, inVal - outVal);
                        $('#modal_member_preview_payout').text('₹' + outVal.toFixed(2));
                        $('#modal_member_preview_profit').text('₹' + profit.toFixed(2));
                    }
                }
            }

            $('#modal_member_pay_in_type, #modal_member_pay_out_type, #modal_member_pay_out_based_on').on('change', recalculateMemberModal);
            $('#modal_member_pay_in_value, #modal_member_pay_out_value').on('input', recalculateMemberModal);

            // Open Modal via AJAX
            $(document).on('click', '.open-commission-modal-btn', function() {
                const userId = $(this).data('user-id');
                const btn = $(this);
                btn.prop('disabled', true);

                fetch(`{{ url('/settings/agent-commission') }}/${userId}`)
                    .then(r => r.json())
                    .then(data => {
                        btn.prop('disabled', false);
                        if (data.success) {
                            const aId = data.agent_id || data.user_id;
                            const aName = data.agent_name || data.user_name || '—';
                            const aEmail = data.agent_email || data.user_email || '—';
                            const aRole = data.user_type_label || data.agent_type || 'Member';

                            $('#modal_member_agent_id').val(aId);
                            $('#modal_member_user_id').val(aId);
                            $('#modal_member_name').text(aName);
                            $('#modal_member_email').text(aEmail);
                            $('#modal_member_role_badge').text(aRole);
                            $('#modal_member_pay_in_type').val(data.pay_in_type);
                            $('#modal_member_pay_in_value').val(data.pay_in_value);
                            $('#modal_member_pay_out_type').val(data.pay_out_type);
                            $('#modal_member_pay_out_value').val(data.pay_out_value);
                            $('#modal_member_pay_out_based_on').val(data.pay_out_based_on);
                            $('#modal_member_is_active').prop('checked', data.is_active);

                            recalculateMemberModal();
                            $('#memberCommissionModal').modal('show');
                        }
                    })
                    .catch(() => {
                        btn.prop('disabled', false);
                        alert('Unable to load member commission details. Please try again.');
                    });
            });

            // Submit Commission Form via AJAX
            $('#memberCommissionModalForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $('#saveMemberCommissionBtn');
                const origHtml = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');

                $.ajax({
                    url: `{{ route('settings.agent-commission.save') }}`,
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(res) {
                        submitBtn.prop('disabled', false).html(origHtml);
                        if (res.success) {
                            $('#memberCommissionModal').modal('hide');
                            
                            // Update table badges dynamically
                            $(`#member_payin_badge_${res.user_id}`).html(`
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                    ${res.pay_in}
                                </span>
                            `);
                            $(`#member_payout_badge_${res.user_id}`).html(`
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                    ${res.pay_out}
                                </span>
                            `);
                            $(`#member_basis_${res.user_id}`).html(`
                                <span class="badge bg-light text-secondary border">
                                    ${res.pay_out_based_on}
                                </span>
                            `);
                            $(`#member_status_${res.user_id}`).html(
                                res.is_active
                                    ? '<span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Active</span>'
                                    : '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Default / Inactive</span>'
                            );
                            $(`#member_action_${res.user_id}`).html(`
                                <button type="button" class="btn btn-sm btn-outline-dark px-3 open-commission-modal-btn" data-user-id="${res.user_id}">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Rate
                                </button>
                            `);
                            $(`#member_updated_${res.user_id}`).text(res.updated_at);

                            if (typeof toastr !== 'undefined') {
                                toastr.success(res.message);
                            } else if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Saved!', text: res.message, timer: 1500, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function(err) {
                        submitBtn.prop('disabled', false).html(origHtml);
                        const msg = err.responseJSON?.message || 'Failed to save member commission.';
                        alert(msg);
                    }
                });
            });

            // ==========================================
            // TAB 2: CREATE COMMISSION TRANSACTION ENTRY
            // ==========================================
            function recalculateEntryModal() {
                const premium = parseFloat($('#entry_premium_amount').val()) || 0;
                const inType = $('#entry_pay_in_type').val();
                const inVal = parseFloat($('#entry_pay_in_value').val()) || 0;
                const outType = $('#entry_pay_out_type').val();
                const outVal = parseFloat($('#entry_pay_out_value').val()) || 0;
                const outBase = $('#entry_pay_out_based_on').val();

                $('.entry-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                $('.entry-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                let inAmount = 0;
                if (inType === 'percentage') {
                    inAmount = (premium * inVal) / 100;
                } else {
                    inAmount = inVal;
                }

                let outAmount = 0;
                if (outType === 'percentage') {
                    const base = (outBase === 'premium') ? premium : inAmount;
                    outAmount = (base * outVal) / 100;
                } else {
                    outAmount = outVal;
                }

                const profit = Math.max(0, inAmount - outAmount);

                $('#entry_preview_payin_amount').text('₹' + inAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#entry_preview_payout_amount').text('₹' + outAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#entry_preview_profit_amount').text('₹' + profit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            // Helper function to auto-fetch rates by selected calculation method
            function fetchRatesByMethod(prefix) {
                const method = $(`#${prefix}_calculation_method`).val();
                const policyId = $(`#${prefix}_policy_id`).val();
                const userId = $(`#${prefix}_user_id`).val();
                const premium = parseFloat($(`#${prefix}_premium_amount`).val()) || 0;

                if (!policyId && !userId) {
                    return;
                }

                $.ajax({
                    url: `{{ route('commission.calculate-by-method') }}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        calculation_method: method,
                        policy_id: policyId,
                        user_id: userId,
                        premium_amount: premium
                    },
                    success: function(res) {
                        if (res && res.has_rule) {
                            $(`#${prefix}_pay_in_type`).val(res.pay_in_type);
                            $(`#${prefix}_pay_in_value`).val(res.pay_in_value);
                            $(`#${prefix}_pay_out_type`).val(res.pay_out_type);
                            $(`#${prefix}_pay_out_value`).val(res.pay_out_value);
                            if (res.pay_out_based_on && $(`#${prefix}_pay_out_based_on`).length) {
                                $(`#${prefix}_pay_out_based_on`).val(res.pay_out_based_on);
                            }
                        }
                        if (prefix === 'entry') {
                            recalculateEntryModal();
                        } else {
                            recalculateEditEntryModal();
                        }
                    }
                });
            }

            // Auto-fill premium and member when policy changes
            $('#entry_policy_id').on('change', function() {
                const selected = $(this).find('option:selected');
                const premium = parseFloat(selected.data('premium')) || 0;
                const userId = selected.data('user-id');
                const userName = selected.data('user-name');
                const holder = selected.data('holder');
                const company = selected.data('company');

                if (premium > 0) {
                    $('#entry_premium_amount').val(premium);
                }
                if (userId) {
                    // Check if option already exists in dropdown
                    if (!$(`#entry_user_id option[value="${userId}"]`).length && userName) {
                        const newOption = new Option(userName, userId, true, true);
                        $('#entry_user_id').append(newOption);
                    }
                    $('#entry_user_id').val(userId).trigger('change.select2');
                }
                if (holder) {
                    $('#entry_policy_detail_hint').html(`<strong>Policy:</strong> ${selected.data('policy-number')} | <strong>Holder:</strong> ${holder} | <strong>Insurer:</strong> ${company}`);
                } else {
                    $('#entry_policy_detail_hint').text('Select a policy to auto-fill premium and member.');
                }

                fetchRatesByMethod('entry');
            });

            $('#entry_calculation_method, #entry_user_id').on('change', function() {
                fetchRatesByMethod('entry');
            });

            $('#entry_premium_amount, #entry_pay_in_value, #entry_pay_out_value').on('input', recalculateEntryModal);
            $('#entry_pay_in_type, #entry_pay_out_type, #entry_pay_out_based_on').on('change', recalculateEntryModal);

            // Submit Transaction Entry Form via AJAX
            $('#createCommissionEntryForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $('#saveCommissionEntryBtn');
                const origHtml = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Recording...');

                $.ajax({
                    url: `{{ route('commission-rules.entries.store') }}`,
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(res) {
                        submitBtn.prop('disabled', false).html(origHtml);
                        if (res.status === 'success') {
                            $('#createCommissionEntryModal').modal('hide');
                            $('#createCommissionEntryForm')[0].reset();
                            recalculateEntryModal();

                            // Prepend new row to tableEntries DataTable
                            const e = res.entry;
                            let statusBadge = '<span class="badge bg-warning-subtle text-dark border border-warning border-opacity-50 px-2 py-1">Pending</span>';
                            if (e.payment_status === 'paid') {
                                statusBadge = '<span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">Paid</span>';
                            } else if (e.payment_status === 'cancelled') {
                                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1">Cancelled</span>';
                            }

                            const actionsHtml = `
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info px-2 py-1 view-entry-btn" data-id="${e.id}" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-dark px-2 py-1 edit-entry-btn" data-id="${e.id}" title="Edit Transaction">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 delete-entry-btn" data-id="${e.id}" data-policy="${e.policy_number}" title="Delete Transaction">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            `;

                            const rowNode = tableEntries.row.add([
                                '<span class="ps-4 text-muted fw-semibold">New</span>',
                                `<strong class="text-dark d-block">${e.rule_name}</strong><small class="text-muted">${e.company_name}</small>`,
                                `<strong class="text-dark d-block">${e.member_name}</strong><small class="text-muted">${e.member_email}</small>`,
                                `<span class="badge bg-light text-dark border">${e.policy_number}</span><small class="text-muted d-block">${e.holder_name}</small>`,
                                `<span class="fw-semibold text-dark">₹${e.premium_amount}</span>`,
                                `<span class="fw-bold text-primary">₹${e.pay_in_amount}</span>`,
                                `<span class="fw-bold text-dark">₹${e.pay_out_amount}</span>`,
                                `<span class="fw-bold text-success">₹${e.net_admin_profit}</span>`,
                                `<span class="text-muted small">${e.entry_date}</span>`,
                                `<span class="pe-4 text-end">${actionsHtml}</span>`
                            ]).draw(false).node();

                            $(rowNode).attr('id', `entry_row_${e.id}`);
                            $(rowNode).attr('data-status', (e.payment_status || 'pending').toLowerCase());
                            $(rowNode).attr('data-payout', parseFloat(e.pay_out_amount) || 0);
                            $(rowNode).attr('data-payin', parseFloat(e.pay_in_amount) || 0);
                            $(rowNode).attr('data-profit', parseFloat(e.net_admin_profit) || 0);
                            $(rowNode).attr('data-premium', parseFloat(e.premium_amount) || 0);
                            $(rowNode).addClass('table-success');
                            setTimeout(() => $(rowNode).removeClass('table-success'), 3000);
                            updateEntrySummaryBadges();

                            // Switch to entries tab to view the newly created record
                            const entriesTabTrigger = document.querySelector('#entries-tab');
                            if (entriesTabTrigger) {
                                bootstrap.Tab.getInstance(entriesTabTrigger)?.show() || new bootstrap.Tab(entriesTabTrigger).show();
                            }

                            if (typeof toastr !== 'undefined') {
                                toastr.success(res.message);
                            } else if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Recorded!', text: res.message, timer: 1800, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function(err) {
                        submitBtn.prop('disabled', false).html(origHtml);
                        const msg = err.responseJSON?.message || 'Failed to record transaction entry.';
                        alert(msg);
                    }
                });
            });

            // ==========================================
            // VIEW TRANSACTION ENTRY
            // ==========================================
            let currentViewingEntryId = null;

            $(document).on('click', '.view-entry-btn', function() {
                const entryId = $(this).data('id');
                currentViewingEntryId = entryId;

                fetch(`{{ url('/commission-entries') }}/${entryId}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            const d = res.data;
                            $('#view_entry_policy_number').text(d.policy_number);
                            $('#view_entry_holder_name').text(d.holder_name);
                            $('#view_entry_company_name').text(d.company_name);
                            $('#view_entry_member_name').text(d.member_name);
                            $('#view_entry_member_email').text(d.member_email);
                            $('#view_entry_member_type').text(d.member_type);

                            const methodLabels = {
                                'company_wise': '🏢 Company-wise Commission',
                                'category_wise': '📂 Category/Type-wise Commission',
                                'sub_type_wise': '📑 Sub Type Commission',
                                'agent_wise': '👤 Agent / Team Member-wise Commission',
                                'policy_wise': '📄 Policy-wise Commission'
                            };
                            $('#view_entry_calc_method').text(methodLabels[d.calculation_method] || '🏢 Company-wise Commission');

                            $('#view_entry_premium').text('₹' + Number(d.premium_amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                            $('#view_entry_pay_in').text('₹' + Number(d.pay_in_amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                            $('#view_entry_pay_in_pct').text(d.pay_in_percentage > 0 ? d.pay_in_percentage + '% Rate' : 'Direct Share');
                            $('#view_entry_pay_out').text('₹' + Number(d.pay_out_amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                            $('#view_entry_pay_out_pct').text(d.pay_out_percentage > 0 ? d.pay_out_percentage + '% Rate' : 'Disbursed');
                            $('#view_entry_profit').text('₹' + Number(d.net_admin_profit).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                            let stHtml = '<span class="badge bg-warning-subtle text-dark border border-warning border-opacity-50 px-2 py-1">Pending</span>';
                            if (d.payment_status === 'paid') {
                                stHtml = '<span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">Paid</span>';
                            } else if (d.payment_status === 'cancelled') {
                                stHtml = '<span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1">Cancelled</span>';
                            }
                            $('#view_entry_status_badge').html(stHtml);
                            $('#view_entry_date').text(d.entry_date_formatted);
                            $('#view_entry_remarks').text(d.remarks || 'No remarks recorded for this transaction.');

                            $('#viewCommissionEntryModal').modal('show');
                        }
                    })
                    .catch(() => alert('Failed to fetch transaction details.'));
            });

            // Quick transition from View modal to Edit modal
            $('#viewModalToEditBtn').on('click', function() {
                if (currentViewingEntryId) {
                    $('#viewCommissionEntryModal').modal('hide');
                    setTimeout(() => {
                        $(`.edit-entry-btn[data-id="${currentViewingEntryId}"]`).trigger('click');
                    }, 350);
                }
            });

            // ==========================================
            // EDIT TRANSACTION ENTRY
            // ==========================================
            function recalculateEditEntryModal() {
                const premium = parseFloat($('#edit_entry_premium_amount').val()) || 0;
                const inType = $('#edit_entry_pay_in_type').val();
                const inVal = parseFloat($('#edit_entry_pay_in_value').val()) || 0;
                const outType = $('#edit_entry_pay_out_type').val();
                const outVal = parseFloat($('#edit_entry_pay_out_value').val()) || 0;
                const outBase = $('#edit_entry_pay_out_based_on').val();

                $('.edit-entry-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                $('.edit-entry-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                let inAmount = 0;
                if (inType === 'percentage') {
                    inAmount = (premium * inVal) / 100;
                } else {
                    inAmount = inVal;
                }

                let outAmount = 0;
                if (outType === 'percentage') {
                    const base = (outBase === 'premium') ? premium : inAmount;
                    outAmount = (base * outVal) / 100;
                } else {
                    outAmount = outVal;
                }

                const profit = Math.max(0, inAmount - outAmount);

                $('#edit_entry_preview_payin_amount').text('₹' + inAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#edit_entry_preview_payout_amount').text('₹' + outAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#edit_entry_preview_profit_amount').text('₹' + profit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            $('#edit_entry_policy_id').on('change', function() {
                const selected = $(this).find('option:selected');
                const premium = parseFloat(selected.data('premium')) || 0;
                const userId = selected.data('user-id');
                const userName = selected.data('user-name');

                if (premium > 0) {
                    $('#edit_entry_premium_amount').val(premium);
                }
                if (userId) {
                    if (!$(`#edit_entry_user_id option[value="${userId}"]`).length && userName) {
                        const newOption = new Option(userName, userId, true, true);
                        $('#edit_entry_user_id').append(newOption);
                    }
                    $('#edit_entry_user_id').val(userId).trigger('change.select2');
                }
                fetchRatesByMethod('edit_entry');
            });

            $('#edit_entry_calculation_method, #edit_entry_user_id').on('change', function() {
                fetchRatesByMethod('edit_entry');
            });

            $('#edit_entry_premium_amount, #edit_entry_pay_in_value, #edit_entry_pay_out_value').on('input', recalculateEditEntryModal);
            $('#edit_entry_pay_in_type, #edit_entry_pay_out_type, #edit_entry_pay_out_based_on').on('change', recalculateEditEntryModal);

            $(document).on('click', '.edit-entry-btn', function() {
                const entryId = $(this).data('id');
                const btn = $(this);
                btn.prop('disabled', true);

                fetch(`{{ url('/commission-entries') }}/${entryId}`)
                    .then(r => r.json())
                    .then(res => {
                        btn.prop('disabled', false);
                        if (res.status === 'success') {
                            const d = res.data;
                            $('#edit_entry_id').val(d.id);
                            if (d.calculation_method) {
                                $('#edit_entry_calculation_method').val(d.calculation_method);
                            } else {
                                $('#edit_entry_calculation_method').val('company_wise');
                            }
                            if (d.policy_id) {
                                $('#edit_entry_policy_id').val(d.policy_id).trigger('change.select2');
                            }
                            if (d.user_id) {
                                $('#edit_entry_user_id').val(d.user_id).trigger('change.select2');
                            }
                            $('#edit_entry_premium_amount').val(d.premium_amount);
                            $('#edit_entry_payment_status').val(d.payment_status);
                            $('#edit_entry_date').val(d.entry_date);
                            $('#edit_entry_remarks').val(d.remarks);

                            if (d.pay_in_percentage > 0) {
                                $('#edit_entry_pay_in_type').val('percentage');
                                $('#edit_entry_pay_in_value').val(d.pay_in_percentage);
                            } else {
                                $('#edit_entry_pay_in_type').val('fixed');
                                $('#edit_entry_pay_in_value').val(d.pay_in_amount);
                            }

                            if (d.pay_out_percentage > 0) {
                                $('#edit_entry_pay_out_type').val('percentage');
                                $('#edit_entry_pay_out_value').val(d.pay_out_percentage);
                            } else {
                                $('#edit_entry_pay_out_type').val('fixed');
                                $('#edit_entry_pay_out_value').val(d.pay_out_amount);
                            }

                            recalculateEditEntryModal();
                            $('#editCommissionEntryModal').modal('show');
                        }
                    })
                    .catch(() => {
                        btn.prop('disabled', false);
                        alert('Failed to load entry details.');
                    });
            });

            // Submit Edit Form via AJAX
            $('#editCommissionEntryForm').on('submit', function(e) {
                e.preventDefault();
                const entryId = $('#edit_entry_id').val();
                const submitBtn = $('#updateCommissionEntryBtn');
                const origHtml = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Updating...');

                $.ajax({
                    url: `{{ url('/commission-entries') }}/${entryId}/update`,
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(res) {
                        submitBtn.prop('disabled', false).html(origHtml);
                        if (res.status === 'success') {
                            $('#editCommissionEntryModal').modal('hide');

                            // Find and update row in DataTable
                            const e = res.entry;
                            let statusBadge = '<span class="badge bg-warning-subtle text-dark border border-warning border-opacity-50 px-2 py-1">Pending</span>';
                            if (e.payment_status === 'paid') {
                                statusBadge = '<span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">Paid</span>';
                            } else if (e.payment_status === 'cancelled') {
                                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1">Cancelled</span>';
                            }

                            const actionsHtml = `
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info px-2 py-1 view-entry-btn" data-id="${e.id}" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-dark px-2 py-1 edit-entry-btn" data-id="${e.id}" title="Edit Transaction">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 delete-entry-btn" data-id="${e.id}" data-policy="${e.policy_number}" title="Delete Transaction">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            `;

                            const tr = $(`#entry_row_${e.id}`);
                            if (tr.length) {
                                tableEntries.row(tr).data([
                                    tr.find('td:eq(0)').html(),
                                    `<strong class="text-dark d-block">${e.rule_name}</strong><small class="text-muted">${e.company_name}</small>`,
                                    `<strong class="text-dark d-block">${e.member_name}</strong><small class="text-muted">${e.member_email}</small>`,
                                    `<span class="badge bg-light text-dark border">${e.policy_number}</span><small class="text-muted d-block">${e.holder_name}</small>`,
                                    `<span class="fw-semibold text-dark">₹${e.premium_amount}</span>`,
                                    `<span class="fw-bold text-primary">₹${e.pay_in_amount}</span>`,
                                    `<span class="fw-bold text-dark">₹${e.pay_out_amount}</span>`,
                                    `<span class="fw-bold text-success">₹${e.net_admin_profit}</span>`,
                                    `<span class="text-muted small">${e.entry_date}</span>`,
                                    `<span class="pe-4 text-end">${actionsHtml}</span>`
                                ]).draw(false);

                                tr.attr('data-status', (e.payment_status || 'pending').toLowerCase());
                                tr.attr('data-payout', parseFloat(e.pay_out_amount) || 0);
                                tr.attr('data-payin', parseFloat(e.pay_in_amount) || 0);
                                tr.attr('data-profit', parseFloat(e.net_admin_profit) || 0);
                                tr.attr('data-premium', parseFloat(e.premium_amount) || 0);
                                tr.addClass('table-primary');
                                setTimeout(() => tr.removeClass('table-primary'), 3000);
                                updateEntrySummaryBadges();
                            }

                            if (typeof toastr !== 'undefined') {
                                toastr.success(res.message);
                            } else if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Updated!', text: res.message, timer: 1800, showConfirmButton: false });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function(err) {
                        submitBtn.prop('disabled', false).html(origHtml);
                        const msg = err.responseJSON?.message || 'Failed to update transaction entry.';
                        alert(msg);
                    }
                });
            });

            // ==========================================
            // DELETE TRANSACTION ENTRY
            // ==========================================
            $(document).on('click', '.delete-entry-btn', function() {
                const entryId = $(this).data('id');
                const policyNo = $(this).data('policy') || 'this transaction';
                const tr = $(`#entry_row_${entryId}`);

                const doDelete = () => {
                    $.ajax({
                        url: `{{ url('/commission-entries') }}/${entryId}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            if (res.status === 'success') {
                                if (tr.length) {
                                    tableEntries.row(tr).remove().draw(false);
                                }
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message);
                                } else if (typeof Swal !== 'undefined') {
                                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1500, showConfirmButton: false });
                                } else {
                                    alert(res.message);
                                }
                            }
                        },
                        error: function(err) {
                            const msg = err.responseJSON?.message || 'Failed to delete transaction.';
                            alert(msg);
                        }
                    });
                };

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Delete commission transaction for ${policyNo}? This cannot be undone.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            doDelete();
                        }
                    });
                } else {
                    if (confirm(`Are you sure you want to delete transaction for ${policyNo}?`)) {
                        doDelete();
                    }
                }
            });
        });
    </script>
    @endpush
@endsection
