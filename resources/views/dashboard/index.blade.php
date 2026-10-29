@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
<div class="container-fluid p-0 dashboard-page" id="dashboardPage" data-user-id="{{ auth()->id() }}"
    data-show-renewal-modal="{{ $showRenewalPoliciesModal ? '1' : '0' }}">

    <div class="row g-3 mb-2" id="dashboardStats">
        <div class="col-6 col-sm-6 col-md-3 col-lg-3" data-perm="view_customers">
            <a href="{{ route('masters.customers.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Customers</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricCustomers">0</h3>
                            <span class="metric-icon icon-customers"><i class="bi bi-people-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-sm-6 col-md-3 col-lg-3" data-perm="view_policies">
            <a href="{{ route('policies.legacy.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Total Policies</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricTotalPolicies">0</h3>
                            <span class="metric-icon icon-followups"><i class="bi bi-shield-check"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-sm-6 col-md-3 col-lg-3" data-perm="view_policies">
            <a href="{{ route('policies.legacy.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Active Policies</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricActivePolicies">0</h3>
                            <span class="metric-icon icon-leads"><i class="bi bi-file-earmark-check-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-sm-6 col-md-3 col-lg-3" data-perm="view_policies">
            <a href="{{ route('policies.legacy.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Renewal Due</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricRenewalDue">0</h3>
                            <span class="metric-icon icon-deals"><i class="bi bi-calendar2-week-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Lead status board hidden for the insurance-focused dashboard.
    <div class="lead-board-wrapper p-0" id="leadBoardWrapper">
        <button type="button" class="lead-board-arrow lead-board-arrow--left" id="leadBoardLeft" title="Scroll Left">
            <i class="fa-solid fa-angle-left fs-5"></i>
        </button>
        <div class="status-board mb-0 pb-1 px-0" id="leadBoardContainer" data-perm-section="view_leads">
            <div class="card border-0 shadow-sm w-100">
                <div class="card-body text-muted small">Loading lead board...</div>
            </div>
        </div>
        <button type="button" class="lead-board-arrow lead-board-arrow--right" id="leadBoardRight" title="Scroll Right">
            <i class="fa-solid fa-angle-right fs-5"></i>
        </button>
    </div>
    --}}

    <!-- Policy Tabs Section -->
    <style>
        /* Modern tab styles */
        .policy-tabs .nav-tabs {
            border-bottom: none;
            border-radius: 1rem 1rem 0 0;
            overflow-x: auto;
            white-space: nowrap;
            flex-wrap: nowrap;
        }

        .policy-tabs .nav-link {
            color: #495057;
            font-size: 0.82rem;
            font-weight: 500;
            border: none;
            border-radius: 16px;
            margin-right: 0.25rem;
            transition: background 0.2s, color 0.2s;
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            font-weight: 600;
        }

        .policy-tabs .nav-link.active {
            color: #fff;
            background: #0f172a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            border-radius: 16px;
            font-weight: 600;
        }

        .policy-tabs .nav-link i {
            margin-right: 0.5rem;
        }

        .policy-tabs .tab-content {
            background: #0f172a;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 1rem 1rem 0rem 1rem;
            margin-top: 5px;
        }

        .policy-tabs .table {
            border-radius: 15px;
            overflow: hidden;
            background: #fff;
        }

        .policy-tabs .dashboard-policy-table thead {
            display: table-header-group;
            position: static;
        }

        .policy-tabs .table th {
            background: #f1f3f6;
            color: #495057;
            font-weight: 600;
            border: none;
            white-space: nowrap;
        }

        .policy-tabs .table td {
            white-space: nowrap;
            font-size: 0.82rem;
        }

        .policy-tabs .table-responsive {
            overflow-x: visible;
        }

        .policy-tabs .policy-type-company {
            display: block;
            margin-top: 0.2rem;
            color: #0f766e;
            font-size: 0.68rem;
            font-weight: 500;
            line-height: 1.2;
        }

        .policy-number-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }

        .policy-number-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.32rem 0.65rem;
            border: 1px solid #d9dee5;
            border-radius: 0.55rem;
            background: #f1f3f5;
            color: #1468dc;
            font-weight: 600;
            line-height: 1.2;
            text-decoration: none;
        }

        .policy-number-badge:hover {
            background: #e7eaee;
            color: #0d55b8;
        }

        .policy-tabs .table-striped>tbody>tr:nth-of-type(odd) {
            background-color: #f8f9fa;
        }

        .policy-tabs .table-hover tbody tr:hover {
            background: #e9ecef;
            transition: background 0.2s;
        }

        .policy-tabs .mobile-only {
            display: none;
        }

        .policy-tabs .policy-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 6px;
            border: 1px solid #d7e0ff;
            background: #f3f6ff;
            color: #1b59f8;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        }

        .policy-tabs .policy-action-btn:hover {
            background: #eaf0ff;
            border-color: #c8d4ff;
            color: #1448cf;
            text-decoration: none;
        }

        .policy-tabs .policy-action-btn i {
            font-size: 16px;
            line-height: 1;
        }

        .desktop-only td a.action {
            padding: 6px 10px;
            color: white;
            border-radius: 6px;
            background: linear-gradient(45deg, #0f172a, #24B4F0) !important;
            text-decoration: none;
        }

        .mobile-summary td a.action {
            padding: 6px 10px;
            color: white;
            border-radius: 6px;
            background: linear-gradient(45deg, #0f172a, #24B4F0) !important;
            text-decoration: none;
        }

        .policy-tabs .dataTables_wrapper {
            color: #fff;
            padding-bottom: 0.75rem;
        }

        .policy-tabs .dataTables_wrapper .dataTables_length,
        .policy-tabs .dataTables_wrapper .dataTables_filter,
        .policy-tabs .dataTables_wrapper .dataTables_info,
        .policy-tabs .dataTables_wrapper .dataTables_paginate {
            color: #fff !important;
        }

        .policy-tabs .dataTables_wrapper .dataTables_length select,
        .policy-tabs .dataTables_wrapper .dataTables_filter input {
            height: 28px;
            min-height: 28px;
            padding: 2px 8px;
            color: #fff;
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.75);
            border-radius: 6px;
            font-size: 0.75rem;
            line-height: 1.2;
            box-shadow: none;
        }

        .policy-tabs .dataTables_wrapper .dataTables_length select {
            width: 52px;
            padding-right: 20px;
        }

        .policy-tabs .dataTables_wrapper .dataTables_filter input {
            width: 170px;
            margin-left: 6px;
        }

        .policy-tabs .dataTables_wrapper .dataTables_length select:focus,
        .policy-tabs .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #60a5fa;
            outline: 0;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.16);
        }

        .policy-tabs .dataTables_wrapper .dataTables_scrollBody {
            border-bottom: none;
            scrollbar-width: thin;
        }

        .policy-tabs .dataTables_wrapper .dataTables_scrollHead {
            margin-bottom: 5px;
        }

        .policy-tabs .dataTables_wrapper .dataTables_info,
        .policy-tabs .dataTables_wrapper .dataTables_paginate {
            margin-top: 5px;
        }

        .policy-tabs .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #fff !important;
            border: none !important;
            border-radius: 4px !important;
        }

        .policy-tabs .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .policy-tabs .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #fff !important;
            background: #0f172a !important;
            border: none !important;
        }

        .policy-tabs .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .policy-tabs .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: rgba(255, 255, 255, 0.65) !important;
            background: transparent !important;
            border: none !important;
        }

        .policy-tabs .dataTables_wrapper .dataTables_paginate .page-item .page-link {
            color: #0f172a !important;
            background: #fff !important;
            border-color: #e5e7eb !important;
            box-shadow: none !important;
        }

        .policy-tabs .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background: #0f172a !important;
            border-color: #0f172a !important;
            color: #fff !important;
        }

        .policy-tabs .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
            color: #6b7280 !important;
            background: #f8f9fa !important;
            border-color: #e5e7eb !important;
        }

        .dashboard-mobile-pagination {
            display: none;
        }


        @media (max-width: 767px) {
            .policy-tabs .nav-tabs {
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .policy-tabs .nav-tabs::-webkit-scrollbar {
                display: none;
                width: 0;
                height: 0;
            }

            .policy-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .policy-tabs .tab-content {
                padding: 1rem 0 0.5rem 0;
            }
    
            .policy-tabs .table-responsive {
                overflow-x: hidden;
            }

            .policy-tabs .desktop-only {
                display: none;
            }

            .policy-tabs .mobile-only {
                display: table-row;
            }

            .policy-tabs .mobile-summary {
                display: table-row;
            }

            .policy-tabs .mobile-details {
                display: none;
            }

            .policy-tabs .mobile-details.show {
                display: table-row;
            }

            .policy-tabs .dataTables_wrapper .dataTables_length,
            .policy-tabs .dataTables_wrapper .dataTables_filter,
            .policy-tabs .dataTables_wrapper .dataTables_info,
            .policy-tabs .dataTables_wrapper .dataTables_paginate {
                float: none;
                text-align: center;
                display: block;
                margin-bottom: 0.5rem;
                margin-top: 0.5rem;
            }
            
            .policy-tabs .dataTables_wrapper .dataTables_paginate {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 5px;
            }

            .dashboard-mobile-pagination {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.75rem 0.5rem 0.25rem;
            }


            .dashboard-mobile-pagination .dashboard-page-btn {
                min-width: 70px;
                color: #fff;
                background: #0f172a;
                border-color: #0f172a;
            }

            .dashboard-mobile-pagination .dashboard-page-btn:disabled {
                color: #64748b;
                background: #e2e8f0;
                border-color: #e2e8f0;
            }

            .dashboard-mobile-pagination .dashboard-page-status {
                color: #475569;
                font-size: 0.75rem;
                font-weight: 600;
                white-space: nowrap;
            }
        }

        @media (max-width: 567px) {
            .policy-tabs .desktop-only {
                display: none !important;
            }

            .policy-tabs thead .mobile-only {
                display: table-row !important;
            }

            .policy-tabs .mobile-summary {
                display: table-row !important;
                cursor: pointer;
            }

            .policy-tabs .mobile-summary.mobile-page-hidden {
                display: none !important;
            }

            .policy-tabs .mobile-details.show {
                display: table-row;
            }

            .policy-tabs .mobile-details td {
                padding: 0;
                border: 1px solid #dee2e6;
            }

            .policy-tabs .mobile-details .details-content {
                padding: 1rem;
                background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                border-radius: 12px;
                margin: 6px 6px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            }

            .policy-tabs .toggle-btn {
                cursor: pointer;
            }

            .policy-tabs .table-responsive {
                padding-left: 0;
                padding-right: 0;
            }

            .policy-tabs .tab-content {
                padding-left: 0;
                padding-right: 0;
            }
            
            .policy-tabs .table {
                border-radius: 0;
            }
        }
    </style>

    <div class="row mt-3 policy-tabs">
        <div class="col-12">
            <div class="card border-0 shadow-lg rounded-4 p-0">
                <div class="card-header pb-0 bg-transparent border-0 p-3">
                    <ul class="nav nav-tabs pb-2" id="policyTypeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                <i class="fa-solid fa-layer-group"></i>All
                            </button>
                        </li>
                        @if (!empty($insuranceTypes))
                            @foreach ($insuranceTypes as $type)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="type-{{ $type['id'] }}-tab" data-bs-toggle="tab" data-bs-target="#type-{{ $type['id'] }}" type="button" role="tab" aria-controls="type-{{ $type['id'] }}" aria-selected="false">
                                        <i class="fa-solid fa-shield-halved"></i>{{ $type['name'] }}
                                    </button>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
                <div class="card-body tab-content" id="policyTypeTabsContent">
                    <!-- All Policies Tab -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center dashboard-policy-table w-100">
                                <thead>
                                    <tr class="desktop-only">
                                        <th class="text-center">Action</th>
                                        <th><i class="fa-solid fa-hashtag"></i> Policy No.</th>
                                        <th><i class="fa-solid fa-user"></i> Policy Holder</th>
                                        <th><i class="fa-solid fa-shield-halved"></i> Policy Type</th>
                                        <th><i class="fa-solid fa-calendar"></i> Start Date</th>
                                        <th><i class="fa-solid fa-calendar-check"></i> End Date</th>
                                        <th class="text-center"><i class="fa-solid fa-indian-rupee-sign"></i> Premium</th>
                                    </tr>
                                    <tr class="mobile-only">
                                        <th><i class="fa-solid fa-hashtag"></i> Policy No.</th>
                                        <th class="text-center">Expand</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!empty($allPolicies))
                                        @foreach ($allPolicies as $policy)
                                            @php($hasPolicyDocument = !empty($policy['document']))
                                            <tr class="desktop-only" data-policy-id="{{ $policy['id'] }}" data-has-policy-document="{{ $hasPolicyDocument ? 1 : 0 }}" data-policy-document-url="{{ $hasPolicyDocument ? route('policies.legacy.documents.view', $policy['id']) : '' }}">
                                                <td class="text-center">
                                                    <a class="action" href="{{ route('policies.view', $policy['id']) }}" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    @if ($hasPolicyDocument)
                                                        <a class="action ms-1 dashboard-policy-doc-action" href="{{ route('policies.legacy.documents.view', $policy['id']) }}" target="_blank" rel="noopener noreferrer" title="View Policy Document">
                                                            <i class="fa fa-file-pdf"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>
                                                    @include('dashboard._policy_numbers', ['policy' => $policy])
                                                </td>
                                                <td>{{ $policy['policy_holder_name'] ?? '--' }}</td>
                                                <td>
                                                    <span>{{ $policy['policy_type_name'] ?? '--' }}</span>
                                                    <small class="policy-type-company">({{ $policy['company_name'] ?? '--' }})</small>
                                                </td>
                                                <td>{{ !empty($policy['start_date']) ? date('d-m-Y', strtotime($policy['start_date'])) : '--' }}</td>
                                                <td>{{ !empty($policy['end_date']) ? date('d-m-Y', strtotime($policy['end_date'])) : '--' }}</td>
                                                <td class="text-center">
                                                    {{ !empty($policy['premium']) ? number_format($policy['premium'], 2) : '--' }}
                                                </td>
                                            </tr>
                                            <tr class="mobile-only mobile-summary">
                                                <td>
                                                    @include('dashboard._policy_numbers', ['policy' => $policy])
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn-user-expand toggle-btn" data-target="#policy-details-{{ $policy['id'] }}">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="mobile-only mobile-details" id="policy-details-{{ $policy['id'] }}">
                                                <td colspan="2" class="p-0 border-0">
                                                    <div class="details-content">
                                                        <div class="row g-3">
                                                            <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                <div class="expand-label"><i class="fa-solid fa-user"></i> Policy Holder :</div>
                                                                <div class="expand-value text-end">{{ $policy['policy_holder_name'] ?? '--' }}</div>
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                <div class="expand-label"><i class="fa-solid fa-shield-halved"></i> Policy Type :</div>
                                                                <div class="expand-value text-end">{{ $policy['policy_type_name'] ?? '--' }}</div>
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                <div class="expand-label"><i class="fa-solid fa-building"></i> Company :</div>
                                                                <div class="expand-value text-end">{{ $policy['company_name'] ?? '--' }}</div>
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                <div class="expand-label"><i class="fa-solid fa-calendar"></i> Start Date :</div>
                                                                <div class="expand-value text-end">{{ !empty($policy['start_date']) ? date('d-m-Y', strtotime($policy['start_date'])) : '--' }}</div>
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> End Date :</div>
                                                                <div class="expand-value text-end">{{ !empty($policy['end_date']) ? date('d-m-Y', strtotime($policy['end_date'])) : '--' }}</div>
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                <div class="expand-value text-end">{{ !empty($policy['premium']) ? number_format($policy['premium'], 2) : '--' }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                            <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                                <a href="{{ route('policies.view', $policy['id']) }}" title="View" class="btn crm-action-btn btn-sm">
                                                                    <i class="fa fa-eye"></i>
                                                                </a>
                                                                @if ($hasPolicyDocument)
                                                                    <a href="{{ route('policies.legacy.documents.view', $policy['id']) }}" target="_blank" rel="noopener noreferrer" title="View Policy Document" class="btn crm-action-btn btn-sm dashboard-policy-doc-action">
                                                                        <i class="fa fa-file-pdf"></i>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="7" class="text-center">Data Not Available</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Individual Policy Type Tabs -->
                    @if (!empty($insuranceTypes))
                        @foreach ($insuranceTypes as $type)
                            <div class="tab-pane fade" id="type-{{ $type['id'] }}" role="tabpanel" aria-labelledby="type-{{ $type['id'] }}-tab">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-items-center dashboard-policy-table w-100">
                                        <thead>
                                            <tr class="desktop-only">
                                                <th class="text-center">Action</th>
                                                <th><i class="fa-solid fa-hashtag"></i> Policy No.</th>
                                                <th><i class="fa-solid fa-user"></i> Policy Holder</th>
                                                <th><i class="fa-solid fa-shield-halved"></i> Policy Type</th>
                                                <th><i class="fa-solid fa-calendar"></i> Start Date</th>
                                                <th><i class="fa-solid fa-calendar-check"></i> End Date</th>
                                                <th class="text-center"><i class="fa-solid fa-indian-rupee-sign"></i> Premium</th>
                                            </tr>
                                            <tr class="mobile-only">
                                                <th><i class="fa-solid fa-hashtag"></i> Policy No.</th>
                                                <th class="text-center">Expand</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php($hasTypePolicy = false)
                                            @if (!empty($allPolicies))
                                                @foreach ($allPolicies as $policy)
                                                    @if (isset($policy['policy_type_id']) && $policy['policy_type_id'] == $type['id'])
                                                        @php($hasTypePolicy = true)
                                                        @php($hasPolicyDocument = !empty($policy['document']))
                                                        <tr class="desktop-only" data-policy-id="{{ $policy['id'] }}" data-has-policy-document="{{ $hasPolicyDocument ? 1 : 0 }}" data-policy-document-url="{{ $hasPolicyDocument ? route('policies.legacy.documents.view', $policy['id']) : '' }}">
                                                            <td class="text-center">
                                                                <a class="action" href="{{ route('policies.view', $policy['id']) }}" title="View">
                                                                    <i class="fa fa-eye"></i>
                                                                </a>
                                                                @if ($hasPolicyDocument)
                                                                    <a class="action ms-1 dashboard-policy-doc-action" href="{{ route('policies.legacy.documents.view', $policy['id']) }}" target="_blank" rel="noopener noreferrer" title="View Policy Document">
                                                                        <i class="fa fa-file-pdf"></i>
                                                                    </a>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @include('dashboard._policy_numbers', ['policy' => $policy])
                                                            </td>
                                                            <td>{{ $policy['policy_holder_name'] ?? '--' }}</td>
                                                            <td>
                                                                <span>{{ $policy['policy_type_name'] ?? '--' }}</span>
                                                                <small class="policy-type-company">({{ $policy['company_name'] ?? '--' }})</small>
                                                            </td>
                                                            <td>{{ !empty($policy['start_date']) ? date('d-m-Y', strtotime($policy['start_date'])) : '--' }}</td>
                                                            <td>{{ !empty($policy['end_date']) ? date('d-m-Y', strtotime($policy['end_date'])) : '--' }}</td>
                                                            <td class="text-center">
                                                                {{ !empty($policy['premium']) ? number_format($policy['premium'], 2) : '--' }}
                                                            </td>
                                                        </tr>
                                                        <tr class="mobile-only mobile-summary">
                                                            <td>
                                                                @include('dashboard._policy_numbers', ['policy' => $policy])
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn-user-expand toggle-btn" data-target="#policy-type-{{ $type['id'] }}-details-{{ $policy['id'] }}">
                                                                    <i class="fa-solid fa-plus"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="mobile-only mobile-details" id="policy-type-{{ $type['id'] }}-details-{{ $policy['id'] }}">
                                                            <td colspan="2" class="p-0 border-0">
                                                                <div class="details-content">
                                                                    <div class="row g-3">
                                                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                            <div class="expand-label"><i class="fa-solid fa-user"></i> Policy Holder :</div>
                                                                            <div class="expand-value text-end">{{ $policy['policy_holder_name'] ?? '--' }}</div>
                                                                        </div>
                                                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                            <div class="expand-label"><i class="fa-solid fa-shield-halved"></i> Policy Type :</div>
                                                                            <div class="expand-value text-end">{{ $policy['policy_type_name'] ?? '--' }}</div>
                                                                        </div>
                                                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                            <div class="expand-label"><i class="fa-solid fa-building"></i> Company :</div>
                                                                            <div class="expand-value text-end">{{ $policy['company_name'] ?? '--' }}</div>
                                                                        </div>
                                                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                            <div class="expand-label"><i class="fa-solid fa-calendar"></i> Start Date :</div>
                                                                            <div class="expand-value text-end">{{ !empty($policy['start_date']) ? date('d-m-Y', strtotime($policy['start_date'])) : '--' }}</div>
                                                                        </div>
                                                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                            <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> End Date :</div>
                                                                            <div class="expand-value text-end">{{ !empty($policy['end_date']) ? date('d-m-Y', strtotime($policy['end_date'])) : '--' }}</div>
                                                                        </div>
                                                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                                                            <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                            <div class="expand-value text-end">{{ !empty($policy['premium']) ? number_format($policy['premium'], 2) : '--' }}</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                                            <a href="{{ route('policies.view', $policy['id']) }}" title="View" class="btn crm-action-btn btn-sm">
                                                                                <i class="fa fa-eye"></i>
                                                                            </a>
                                                                            @if ($hasPolicyDocument)
                                                                                <a href="{{ route('policies.legacy.documents.view', $policy['id']) }}" target="_blank" rel="noopener noreferrer" title="View Policy Document" class="btn crm-action-btn btn-sm dashboard-policy-doc-action">
                                                                                    <i class="fa fa-file-pdf"></i>
                                                                                </a>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endif
                                            @if (!$hasTypePolicy)
                                                <tr><td colspan="7" class="text-center">Data Not Available</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile toggle for policy details
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.policy-tabs .toggle-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const target = document.querySelector(this.getAttribute('data-target'));
                    const icon = this.querySelector('i');
                    if (target) {
                        if (target.style.display === 'none' || target.classList.contains('show') === false) {
                            target.classList.add('show');
                            if (icon) {
                                icon.classList.replace('fa-plus', 'fa-minus');
                            }
                            this.classList.add('active');
                        } else {
                            target.classList.remove('show');
                            if (icon) {
                                icon.classList.replace('fa-minus', 'fa-plus');
                            }
                            this.classList.remove('active');
                        }
                    }
                });
            });

            function initMobilePolicyPagination() {
                if (window.innerWidth >= 768) {
                    return;
                }

                document.querySelectorAll('.dashboard-policy-table').forEach(function(table) {
                    const summaries = Array.from(table.querySelectorAll('tbody tr.mobile-summary'));
                    const pageSize = 5;

                    if (!summaries.length) {
                        return;
                    }

                    let currentPage = 1;
                    const filteredSummaries = summaries.slice();
                    const tableWrapper = table.closest('.table-responsive');
                    const pagination = document.createElement('div');
                    pagination.className = 'dashboard-mobile-pagination';
                    pagination.setAttribute('aria-label', 'Policy table pagination');
                    tableWrapper.insertAdjacentElement('afterend', pagination);

                    function renderPage() {
                        const totalPages = Math.ceil(filteredSummaries.length / pageSize);
                        if (totalPages > 0 && currentPage > totalPages) {
                            currentPage = totalPages;
                        }
                        const pageStart = (currentPage - 1) * pageSize;
                        const pageEnd = pageStart + pageSize;

                        summaries.forEach(function(summary) {
                            const details = summary.nextElementSibling?.classList.contains('mobile-details')
                                ? summary.nextElementSibling
                                : null;
                            const filteredIndex = filteredSummaries.indexOf(summary);
                            const visible = filteredIndex >= pageStart && filteredIndex < pageEnd;

                            summary.classList.toggle('mobile-page-hidden', !visible);
                            if (details) {
                                details.classList.remove('show');
                            }

                            const button = summary.querySelector('.toggle-btn');
                            const icon = button?.querySelector('i');
                            button?.classList.remove('active');
                            icon?.classList.remove('fa-minus');
                            icon?.classList.add('fa-plus');
                        });

                        if (totalPages <= 1) {
                            pagination.innerHTML = '';
                            return;
                        }

                        pagination.innerHTML = `
                            <button type="button" class="btn btn-sm dashboard-page-btn" data-page-action="previous" ${currentPage === 1 ? 'disabled' : ''}>Previous</button>
                            <span class="dashboard-page-status">Page ${currentPage} of ${totalPages}</span>
                            <button type="button" class="btn btn-sm dashboard-page-btn" data-page-action="next" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
                        `;
                    }

                    pagination.addEventListener('click', function(event) {
                        const button = event.target.closest('[data-page-action]');
                        if (!button || button.disabled) {
                            return;
                        }

                        currentPage += button.dataset.pageAction === 'next' ? 1 : -1;
                        renderPage();
                        table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });

                    renderPage();
                });
            }

            function initDashboardPolicyTables(attempt) {
                if (window.innerWidth < 768) {
                    return;
                }

                if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
                    if (attempt < 20) {
                        setTimeout(function() {
                            initDashboardPolicyTables(attempt + 1);
                        }, 100);
                    }
                    return;
                }

                function ensurePolicyDocumentIcons($scope) {
                    $scope.find('tbody tr[data-has-policy-document="1"]').each(function() {
                        var $row = jQuery(this);
                        var documentUrl = $row.data('policy-document-url');

                        if (!documentUrl) {
                            return;
                        }

                        var $actionCell = $row.children('td').eq(0);
                        if ($actionCell.length && $actionCell.find('.dashboard-policy-doc-action').length === 0) {
                            $actionCell.append(
                                '<a class="action ms-1 dashboard-policy-doc-action" href="' + documentUrl + '" target="_blank" rel="noopener noreferrer" title="View Policy Document">' +
                                    '<i class="fa fa-file-pdf"></i>' +
                                '</a>'
                            );
                        }

                    });
                }

                jQuery('.dashboard-policy-table').each(function() {
                    var $table = jQuery(this);

                    if ($table.data('dt-ready') || $table.find('tbody tr.desktop-only').length === 0) {
                        return;
                    }

                    $table.find('thead tr.mobile-only, tbody tr.mobile-only').remove();
                    var dataTable = $table.DataTable({
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                        ordering: true,
                        order: [],
                        columnDefs: [
                            { orderable: false, targets: 0 }
                        ],
                        searching: true,
                        paging: true,
                        info: true,
                        // Horizontal overflow is handled by the surrounding
                        // .table-responsive container. Enabling scrollX here
                        // creates a second, cloned header that can overlap the
                        // first data row.
                        scrollX: false,
                        autoWidth: false,
                        language: {
                            search: 'Search:'
                        }
                    });

                    ensurePolicyDocumentIcons($table);
                    dataTable.on('draw.dt', function() {
                        ensurePolicyDocumentIcons($table);
                    });
                    $table.data('dt-ready', true);
                });
            }

            initMobilePolicyPagination();
            initDashboardPolicyTables(0);

            document.querySelectorAll('#policyTypeTabs button[data-bs-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function() {
                    if (typeof window.jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
                        jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    }
                });
            });
        });
    </script>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0 fw-bold">Renewal Policies</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('policies.legacy.index') }}" class="text-dark badge bg-light px-3 py-2 fw-semibold small" style="color: #0c0c0c !important;">View All</a>
                    </div>
                </div>
                <div class="card-body p-0" data-perm-section="view_policies">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="dashboardRenewalPoliciesTable">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Policy No.</th>
                                    <th style="width: 25%;" class="d-none d-md-table-cell">Proposer Name</th>
                                    <th style="width: 20%;" class="text-center">End Date</th>
                                    <th style="width: 15%;" class="text-center d-none d-md-table-cell">Due In</th>
                                    <th style="width: 10%;" class="text-center d-md-none">Expand</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Loading renewal policies...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3">
                    <h5 class="mb-0 fw-bold">Policy Portfolio Mix</h5>
                    <small class="dashboard-widget-subtitle">Policies by insurance category</small>
                </div>
                <div class="card-body portfolio-mix-body" data-perm-section="view_policies">
                    <div class="portfolio-mix-layout">
                        <div class="portfolio-chart-box">
                            <canvas id="policyPortfolioChart"></canvas>
                        </div>
                        <div class="portfolio-category-list" id="policyPortfolioLegend">
                            <div class="text-muted small">Loading portfolio details...</div>
                        </div>
                    </div>
                    <div class="portfolio-summary-strip">
                        <div class="portfolio-summary-item">
                            <span class="portfolio-summary-icon portfolio-summary-icon--premium"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                            <div><small>Total premium</small><strong id="portfolioTotalPremium">₹0</strong></div>
                        </div>
                        <div class="portfolio-summary-divider"></div>
                        <div class="portfolio-summary-item">
                            <span class="portfolio-summary-icon portfolio-summary-icon--segment"><i class="fa-solid fa-chart-pie"></i></span>
                            <div><small>Largest segment</small><strong id="portfolioTopSegment">--</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3">
                    <h5 class="mb-0 fw-bold text-white">Policy Expiry &amp; Premium Exposure</h5>
                    <small class="dashboard-widget-subtitle">Actionable renewal pipeline by maturity window</small>
                </div>
                <div class="card-body pt-2" data-perm-section="view_policies">
                    <div class="chart-wrap">
                        <canvas id="policyExpiryChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3">
                    <h5 class="mb-0 fw-bold text-white">Insurer Performance</h5>
                    <small class="dashboard-widget-subtitle">Top partners ranked by premium</small>
                </div>
                <div class="card-body p-0" data-perm-section="view_policies">
                    <div class="table-responsive insurer-performance-wrap">
                        <table class="table table-hover align-middle mb-0" id="insurerPerformanceTable">
                            <thead>
                                <tr><th>Insurer</th><th class="text-center">Policies</th><th class="text-center">Active</th><th class="text-end">Premium</th></tr>
                            </thead>
                            <tbody><tr><td colspan="4" class="text-center text-muted py-4">Loading insurer performance...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-white">Staff &amp; Sub-Agent Commissions</h5>
                        <small class="dashboard-widget-subtitle">Recent policy commission payouts (Last 5 records)</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ Route::has('commission-rules.entries.index') ? route('commission-rules.entries.index') : url('/commission-entries') }}" class="text-dark badge bg-light px-3 py-2 fw-semibold small" style="color: #0c0c0c !important;">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive insurer-performance-wrap">
                        <table class="table table-hover align-middle mb-0" id="staffSubAgentCommissionTable">
                            <thead>
                                <tr>
                                    <th style="width: 32%;">Agent / Staff</th>
                                    <th style="width: 28%;">Policy</th>
                                    <th style="width: 15%;" class="text-end">Premium</th>
                                    <th style="width: 15%;" class="text-end">Commission</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted py-4">Loading commission records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3">
                    <h5 class="mb-0 fw-bold text-white">Commission Distribution</h5>
                    <small class="dashboard-widget-subtitle">Earnings mix by staff &amp; sub-agents</small>
                </div>
                <div class="card-body portfolio-mix-body">
                    <div class="portfolio-mix-layout">
                        <div class="portfolio-chart-box">
                            <canvas id="commissionDistributionChart"></canvas>
                        </div>
                        <div class="portfolio-category-list" id="commissionDistributionLegend">
                            <div class="text-muted small">Loading commission mix...</div>
                        </div>
                    </div>
                    <div class="portfolio-summary-strip">
                        <div class="portfolio-summary-item">
                            <span class="portfolio-summary-icon portfolio-summary-icon--premium"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                            <div><small>Total Commission</small><strong id="commissionTotalAmount">₹0</strong></div>
                        </div>
                        <div class="portfolio-summary-divider"></div>
                        <div class="portfolio-summary-item">
                            <span class="portfolio-summary-icon portfolio-summary-icon--segment"><i class="fa-solid fa-chart-pie"></i></span>
                            <div><small>Top Earning Group</small><strong id="commissionTopGroup">--</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="dashboard-footer text-center py-2 mt-4">
        © {{ date('Y') }} Copyright - <a href="https://www.fableadtechnolabs.com/" target="_blank"
            rel="noopener noreferrer" style="color: inherit; text-decoration: none;">Fablead Developers Technolab</a>
    </footer>
</div>

<div class="modal fade" id="renewalPoliciesModal" tabindex="-1" aria-labelledby="renewalPoliciesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg renewal-reminder-modal">
            <div class="modal-header border-0 text-white px-4 py-3 renewal-reminder-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="renewal-reminder-icon"><i class="fa-solid fa-calendar-check"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-1 text-white" id="renewalPoliciesModalLabel">Policies Due for Renewal</h5>
                        <div class="small opacity-75">Policies expiring today or during the next 30 days</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="renewalPoliciesModalBody">
                <div class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading renewal policies...
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('policies.legacy.index') }}" class="btn btn-dark-blue px-4">View All Policies</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/dashboard.css' : '/css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    <style>
        .dashboard-link-disabled { pointer-events: none; }
        [data-perm][style*="display: none"], [data-perm-btn][style*="display: none"] { display: none !important; }
        .renewal-reminder-modal { border-radius: 18px; overflow: hidden; }
        .renewal-reminder-header { background: #0f2747; }
        .renewal-reminder-header .modal-title { color: #fff !important; }
        .renewal-reminder-icon { width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.14); display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .renewal-reminder-item { border: 1px solid #d9dee6; border-radius: 12px; padding: 14px 16px; color: #334155; transition: background-color .15s ease, border-color .15s ease; }
        .renewal-reminder-item:hover { background: #f8fafc; border-color: #b8c2d1; }
        .renewal-reminder-item-name { font-weight: 700; color: #334155; }
        .renewal-reminder-item-meta { color: #64748b; font-size: .9rem; }
        .renewal-reminder-date { font-weight: 700; white-space: nowrap; }
        .renewal-reminder-due { color: #0f2747; font-size: .86rem; font-weight: 700; }
        @media (max-width: 575.98px) {
            .renewal-reminder-item { align-items: flex-start !important; }
            .renewal-reminder-date-wrap { text-align: left !important; margin-top: 8px; }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/dashboard.js' : '/js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
    <!-- Dashboard permissions auto-applied by DashboardAPI -->
@endpush
