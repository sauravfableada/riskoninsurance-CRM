@extends('layouts.masters')
@section('page_title', 'Policies')
@php
    $policyUser = auth()->user();
    $canCreatePolicies = (bool) $policyUser?->hasMatrixPermission('create_policies');
    $canEditPolicies = (bool) $policyUser?->hasMatrixPermission('edit_policies');
    $canDeletePolicies = (bool) $policyUser?->hasMatrixPermission('delete_policies');
@endphp
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
@endpush
@section('masters_content')
<script>
    (function initAjaxCsrf(attempt) {
        if (typeof window.jQuery === 'undefined') {
            if (attempt < 20) {
                setTimeout(function() {
                    initAjaxCsrf(attempt + 1);
                }, 100);
            }
            return;
        }

        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';
        if (!csrfToken) {
            return;
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    })(0);
</script>

<style>
    @if(!$canEditPolicies)
    a[href*="renew-policy/"],
    a[href*="edit-policy/"],
    button[data-bs-target="#uploadDownloadModal"] {
        display: none !important;
    }
    @endif

    @if(!$canDeletePolicies)
    .delete-policy {
        display: none !important;
    }
    @endif

    .description_div {
        display: inline-block;
        width: 354px;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: keep-all;
        height: 110px;
        overflow-y: auto;
    }


    table#example tr {
        border-bottom: 1px solid #ddd;
    }

    table#example td,
    table#example th {
        border-left: none;
        border-right: none;
    }

    table#example tr:last-child {
        border-bottom: none;
    }

    .action-icon {
        font-weight: bold;
        font-size: 16px !important;
        border: 0.5px solid #e4e4e4;
        background: #e4e4e48a;
        border-radius: 6px;
        margin-right: 8px;
        height: 32px;
        width: 32px;
        text-align: center;
        line-height: 32px;
    }

    .font_size {
        font-size: 15px;
    }


    .policy-holder-name {
        max-width: 150px;
        /* adjust as needed */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 15px;
    }

    .policy-holder-name:hover {
        overflow: visible;
        white-space: normal;
        background: #fff;
        position: relative;
        z-index: 999;
    }

    /* Common class for hover full view */
    .hover-full {
        max-width: 150px;
        /* adjust per column */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hover-full:hover {
        overflow: visible;
        white-space: normal;
        background: #fff;
        position: relative;
        z-index: 999;
    }

    /* Mobile responsive styles */
    @media (max-width: 768px) {
        .policy-index-heading {
            flex-direction: row !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 0.75rem !important;
            margin-bottom: 1rem !important;
        }

        .policy-index-title {
            flex: 1 1 auto;
            min-width: 0;
        }

        .policy-index-title p {
            max-width: 180px;
            white-space: normal;
            overflow-wrap: break-word;
            line-height: 1.25;
        }

        .policy-index-heading-actions {
            flex: 0 0 auto;
            flex-wrap: nowrap !important;
        }

        .policy-index-heading-actions .btn {
            white-space: nowrap;
        }

        .policy-filter-row {
            --bs-gutter-x: 0.75rem;
            padding-right: 0.75rem !important;
            padding-left: 0.75rem !important;
        }

        .policy-filter-row .form-label {
            white-space: nowrap;
        }

        .policy-filter-row .form-control,
        .policy-filter-row .select2-container,
        .policy-filter-row .clear-policy-filters {
            width: 100% !important;
            min-width: 0;
        }

        .mobile-hide {
            display: none !important;
        }

        .desktop-actions {
            display: none !important;
        }

        .mobile-expand-btn {
            cursor: pointer;
            font-size: 27px;
            font-weight: normal;
            color: #344767;
            padding: 0;
            border: none;
            background: transparent;
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
        }

        .mobile-expand-btn:hover {
            color: #1a2332;
        }

        .mobile-details {
            display: none;
        }

        .mobile-details.show {
            display: table-row;
        }

        .mobile-details-row {
            background-color: #f8f9fa;
            padding: 10px;
            border-top: 2px solid #dee2e6;
        }

        .mobile-details-row .detail-item {
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .mobile-details-row .detail-label {
            font-weight: bold;
            color: #495057;
            margin-right: 10px;
        }

        .mobile-details-row .detail-value {
            color: #212529;
        }

        .mobile-details-row .action-buttons {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    }

    @media (min-width: 769px) {
        .mobile-only {
            display: none !important;
        }
    }

    @media (max-width: 576px) {
        .card-header {
            height: auto;
            min-height: 60px;
        }

        #issue {
            margin-top: 0 !important;
        }

        .card-header .d-flex {
            flex-wrap: nowrap !important;
        }

        .card-header h6 {
            white-space: nowrap;
            font-size: 14px;
            margin-bottom: 12px !important;
        }

        .card-header .btn {
            white-space: nowrap;
            /* font-size: 12px; */
            /* padding: 5px 10px !important; */
        }
    }

    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 2),
    only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 2) {
        #issue {
            margin-top: -3px !important;
        }
    }

    .custom-pills .nav-link {
        border-radius: 20px;
        padding: 10px 20px;
        font-weight: 500;
        color: #495057;
        background-color: #f8f9fa;
        margin-right: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    .custom-pills .nav-link:hover {
        background-color: #e9ecef;
        color: #0d6efd;
        transform: translateY(-2px);
    }

    .custom-pills .nav-link.active {
        background: linear-gradient(45deg, #0d6efd, #4dabf7);
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    /* KYC Document Icons */
    .view-icon,
    .download-icon {
        display: inline-block;
        text-decoration: none;
    }

    .view-icon i,
    .download-icon i {
        font-size: 18px;
    }

    /* Image Gallery Modal */
    #imageGalleryContainer img {
        max-width: 100%;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    #imageGalleryContainer .gallery-image {
        cursor: pointer;
        transition: transform 0.2s;
    }

    #imageGalleryContainer .gallery-image:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    #imageGalleryContainer h6 {
        margin-top: 15px;
        margin-bottom: 10px;
        font-weight: bold;
    }
    .modal-content {
    
    margin-left: 0 !important;
}
.modal-dialog {
    margin-left: auto !important;
}
.fg-member-row.d-flex.gap-2.mb-2.align-items-center {
    flex-direction: column;
}

    /* ── Action button icon visibility fix ── */
    .desktop-actions,
    .action-buttons {
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
    }

    .desktop-actions .btn,
    .desktop-actions button,
    .action-buttons .btn,
    .action-buttons button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 32px;
        min-height: 32px;
        overflow: visible !important;
    }

    .desktop-actions .btn i,
    .desktop-actions button i,
    .action-buttons .btn i,
    .action-buttons button i {
        font-size: 14px !important;
        line-height: 1 !important;
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        color: inherit !important;
        width: auto !important;
        height: auto !important;
        pointer-events: none;
    }

    .desktop-actions.policy-actions-dropdown {
        position: relative;
        flex-wrap: nowrap !important;
    }

    .desktop-actions .policy-actions-toggle {
        width: 34px;
        min-width: 34px;
        height: 34px;
        padding: 0 !important;
        border: 1px solid #dce2ea;
        border-radius: 7px;
        color: #0b2341;
        background: #fff;
    }

    .desktop-actions .policy-actions-menu {
        min-width: 215px;
        padding: 6px;
        z-index: 1080;
    }

    .desktop-actions .policy-action-item {
        display: flex !important;
        width: 100%;
        min-width: 0;
        min-height: 36px;
        padding: 8px 10px !important;
        justify-content: flex-start !important;
        gap: 10px;
        border: 0;
        border-radius: 5px;
        color: #17243b;
        background: transparent;
        font-size: 13px;
        text-decoration: none;
        white-space: nowrap;
    }

    .desktop-actions .policy-action-item:hover,
    .desktop-actions .policy-action-item:focus {
        color: #0b2341;
        background: #f1f5f9;
    }

    .desktop-actions .policy-action-item i {
        width: 18px !important;
        color: #52657d !important;
        text-align: center;
    }

    .kyc-file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .kyc-preview-item {
        display: flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        padding: 6px 8px;
        border: 1px solid #dce2ea;
        border-radius: 7px;
        background: #f8fafc;
        color: #52657d;
        font-size: 12px;
    }

    .kyc-preview-item img {
        width: 48px;
        height: 48px;
        border-radius: 5px;
        object-fit: cover;
    }

    .kyc-preview-name {
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .desktop-actions {
            display: none !important;
        }
    }

    /* Ensure FA6 solid class renders correctly */
    .fa-solid {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 900 !important;
        font-style: normal !important;
    }

    /* Padding for DataTables controls to match leads page styling */
    div.dataTables_wrapper > div.row:first-child {
        padding: 0 1.5rem;
    }
    div.dataTables_wrapper > div.row:last-child {
        padding: 1rem 1.5rem;
    }

    /* Padding for the first and last columns to align with padded controls */
    table.dataTable > thead > tr > th:first-child,
    table.dataTable > tbody > tr > td:first-child {
        padding-left: 1.5rem !important;
    }
    table.dataTable > thead > tr > th:last-child,
    table.dataTable > tbody > tr > td:last-child {
        padding-right: 1.5rem !important;
    }

    .policy-filter-select2 + .select2-container {
        width: 100% !important;
    }
    .policy-filter-select2 + .select2-container .select2-selection--single {
        height: 46px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: #fff;
    }
    .policy-filter-select2 + .select2-container .select2-selection__rendered {
        line-height: 44px;
        padding-left: 14px;
        padding-right: 34px;
        color: #212529;
    }
    .policy-filter-select2 + .select2-container .select2-selection__arrow {
        height: 44px;
        right: 8px;
    }
    .policy-filter-select2 + .select2-container.select2-container--focus .select2-selection--single,
    .policy-filter-select2 + .select2-container.select2-container--open .select2-selection--single {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .policy-filter-row {
        --bs-gutter-x: 0.75rem;
    }
    .filter-date-range,
    .clear-policy-filters {
        height: 46px;
    }
    .clear-policy-filters {
        padding: 0 8px;
        white-space: nowrap;
        font-size: 14px;
        background: #0f172a !important;
        border-color: #0f172a !important;
        color: #fff !important;
    }

    .custom-filter-card {
        background-color: #f8faff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .custom-filter-card .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
    }
    .custom-filter-card .form-control {
        border-color: #e2e8f0;
        border-radius: 8px;
        height: 42px;
    }
    .custom-filter-card .select2-container .select2-selection--single {
        height: 42px !important;
        border-color: #e2e8f0 !important;
        border-radius: 8px !important;
    }
    .custom-filter-card .select2-container .select2-selection__rendered {
        line-height: 40px !important;
    }
    .custom-filter-card .select2-container .select2-selection__arrow {
        height: 40px !important;
    }
    .btn-reset-filter {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 500;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        height: 42px;
        transition: all 0.2s;
    }
    .btn-reset-filter:hover {
        background-color: #f1f5f9;
        color: #1e293b;
    }
    .btn-apply-filter {
        background-color: #3b82f6;
        border: 1px solid #3b82f6;
        color: #ffffff;
        font-weight: 500;
        border-radius: 8px;
        padding: 0.5rem 1.5rem;
        height: 42px;
        transition: all 0.2s;
    }
    .btn-apply-filter:hover {
        background-color: #2563eb;
        color: #ffffff;
    }

    @media (min-width: 1200px) {
        .policy-filter-single-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
            align-items: end;
            gap: 1rem;
        }

        .policy-filter-single-row > .row {
            display: contents;
        }

        .policy-filter-single-row > .row > div {
            width: auto;
            margin-bottom: 0 !important;
            padding: 0;
        }

        .policy-filter-single-row .select2-container {
            width: 100% !important;
        }

        .staff-policy-filter-single-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            align-items: end;
            gap: 1rem;
        }

        .staff-policy-filter-single-row > .row {
            display: contents;
        }

        .staff-policy-filter-single-row > .row > div {
            width: auto;
            margin-bottom: 0 !important;
            padding: 0;
        }

        .staff-policy-filter-single-row .select2-container {
            width: 100% !important;
        }
    }

    @media (max-width: 768px) {
        table.dataTable {
            width: 100% !important;
        }

        table.dataTable > thead > tr > th:last-child,
        table.dataTable > tbody > tr:not(.mobile-details) > td:last-child {
            width: 44px !important;
            min-width: 44px !important;
            padding-right: 0.25rem !important;
            padding-left: 0.25rem !important;
        }

        table.dataTable > thead > tr > th:first-child,
        table.dataTable > tbody > tr:not(.mobile-details) > td:first-child {
            padding-left: 0.5rem !important;
        }
    }
</style>

        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-0 gap-3 policy-index-heading">
                <div class="policy-index-title">
                    <h4 class="fw-bold mb-0">Manage Policies</h4>
                    <p class="text-muted small mb-0">Track and manage your insurance policies.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 policy-index-heading-actions">
                    @if($canCreatePolicies)
                        <a href="<?php echo base_url('add-policy'); ?>" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Add Policy
                        </a>
                    @endif
                </div>
            </div>
        </div>

            <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
            <?php
                $session = session();
                $userRole = $session->get('user_role');

                if ($userRole == 3): ?>
                    <div>

                        <!-- Main Tabs -->
                        <ul class="nav nav-tabs mb-3 px-4" id="policyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="myPolicies-tab" data-bs-toggle="tab" data-bs-target="#myPolicies" type="button" role="tab" aria-controls="myPolicies" aria-selected="true">Created by Me</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="staffPolicies-tab" data-bs-toggle="tab" data-bs-target="#staffPolicies" type="button" role="tab" aria-controls="staffPolicies" aria-selected="false">Created by Staff</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="policyTabsContent">
                            <!-- ================= My Policies ================= -->
                            <div class="tab-pane fade show active" id="myPolicies" role="tabpanel" aria-labelledby="myPolicies-tab">

                                <!-- Inner Tabs (My Policies) -->
                                <ul class="nav nav-pills mb-3 custom-pills px-4" id="myPoliciesInnerTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active"
                                            id="allMyPolicies-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#allMyPolicies"
                                            type="button" role="tab"
                                            aria-controls="allMyPolicies"
                                            aria-selected="true"><i class="fa fa-list me-2"></i>
                                            All Policies
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link"
                                            id="currentMyPolicies-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#currentMyPolicies"
                                            type="button" role="tab"
                                            aria-controls="currentMyPolicies"
                                            aria-selected="false"><i class="fa fa-file-text-o me-2"></i>
                                            Policies
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link"
                                            id="renewedMyPolicies-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#renewedMyPolicies"
                                            type="button" role="tab"
                                            aria-controls="renewedMyPolicies"
                                            aria-selected="false"><i class="fa fa-refresh me-2"></i>
                                            Renewed Policies
                                        </button>
                                    </li>
                                </ul>

                                <!-- 🔹 Filter Row -->
                                <div class="custom-filter-card policy-filter-single-row mx-4">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Sub Agent</label>
                                            <select class="form-control filter-subagent">
                                                <option value="">-- All Sub Agents --</option>
                                                <?php foreach (($subAgents ?? []) as $subAgent): ?>
                                                    <option value="<?= esc($subAgent->name) ?>"><?= esc($subAgent->name) ?><?= $subAgent->email ? ' — ' . esc($subAgent->email) : '' ?><?= $subAgent->phone ? ' — ' . esc($subAgent->phone) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="filter_holder_me" class="form-label">Policy Holder</label>
                                            <select id="filter_holder_me" class="form-control filter-holder">
                                                <option value="">-- All Holders --</option>
                                                <?php if (!empty($holders)): ?>
                                                    <?php foreach ($holders as $holder): ?>
                                                        <option value="<?= esc($holder['name']) ?>"><?= esc($holder['name']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="filter_policy_type_me" class="form-label">Policy Type</label>
                                            <select id="filter_policy_type_me" class="form-control filter-policy-type">
                                                <option value="">-- All Policy Types --</option>
                                                <?php if (!empty($policyTypes)): ?>
                                                    <?php foreach ($policyTypes as $type): ?>
                                                        <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="filter_company_me" class="form-label">Company</label>
                                            <select id="filter_company_me" class="form-control filter-company">
                                                <option value="">-- All Companies --</option>
                                                <?php if (!empty($companies)): ?>
                                                    <?php foreach ($companies as $company): ?>
                                                        <option value="<?= esc($company['name']) ?>"><?= esc($company['name']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-2">
                                            <label for="filter_date_range" class="form-label">Start Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0"><i class="fa fa-calendar-o"></i></span>
                                                <input type="text" id="filter_date_range" class="form-control filter-date-range border-start-0 ps-0" placeholder="Select start date" autocomplete="off">
                                            </div>
                                            <input type="hidden" id="filter_start_date">
                                            <input type="hidden" id="filter_end_date">
                                        </div>
                                        <div class="col-md-9 mb-2 d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-reset-filter clear-policy-filters" title="Clear all filters">
                                                Clear
                                            </button>
                                            <button type="button" class="btn btn-apply-filter d-none" title="Filters are applied automatically">
                                                <i class="fa fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content" id="myPoliciesInnerContent">
                                    <!-- All My Policies -->
                                    <div class="tab-pane fade show active" id="allMyPolicies" role="tabpanel"
                                        aria-labelledby="allMyPolicies-tab">
                                        <table id="allMyPoliciesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="policy-type mobile-hide">Policy Type</th>
                                                    <th class="company mobile-hide">Company</th>
                                                    <th class="start-date mobile-hide">Start Date</th>
                                                    <th class="end-date mobile-hide">End Date</th>
                                                    <th class="premium mobile-hide">Premium</th>
                                                    <th class="action text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $allIdx = 1; @endphp
                                                @foreach(($all_myPolicies ?? $myPolicies) as $policy)
                                                    @include('crm.policy.policy_table_row', ['policy' => $policy, 'index' => $allIdx++, 'showCompanyCol' => true])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Current My Policies -->
                                    <div class="tab-pane fade" id="currentMyPolicies" role="tabpanel"
                                        aria-labelledby="currentMyPolicies-tab">
                                        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
                                        <table id="currentMyPoliciesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="policy-type mobile-hide">Policy Type</th>
                                                    <th class="company mobile-hide">Company</th>
                                                    <th class="start-date mobile-hide">Start Date</th>
                                                    <th class="end-date mobile-hide">End Date</th>
                                                    <th class="premium mobile-hide">Premium</th>
                                                    <th class="action text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1;
                                                foreach ($myPolicies as $policy): ?>
                                                    <?php if (empty($policy['is_renew']) && $policy['renew_id'] == null && $policy['renew_status'] == null): ?>
                                                        <tr data-id="<?= $policy['id']; ?>" data-company="<?= esc($policy['company_name'] ?? '') ?>">
                                                            <td class="font_size text-center"><?= $i++; ?></td>
                                                            <td class="font_size policy-holder-name" title="<?= esc($policy['policy_holder_name']); ?>">
                                                                <?= esc($policy['policy_holder_name']); ?>
                                                            </td>
                                                            <td class="font_size mobile-hide hover-full" title="<?= esc($policy['policy_type_name']); ?>">
                                                                <?= esc($policy['policy_type_name']); ?>
                                                            </td>

                                                            <td class="font_size mobile-hide hover-full" title="<?= esc($policy['company_name']); ?>">
                                                                <?= esc($policy['company_name']); ?>
                                                            </td>

                                                            <td class="font_size mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></td>
                                                            <td class="font_size mobile-hide"><?= esc($policy['premium']); ?></td>
                                                            <td class="font_size text-center">
                                                                <div class="desktop-actions">

                                                                    <!-- View KYC Button -->
                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-secondary p-2 view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>

                                                                    <!-- Upload / Download Button -->
                                                                    <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>



                                                                    <!-- Renewal Button -->
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-success p-2" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf"></i>
                                                                    </a>



                                                                    <!-- View Button -->
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-info p-2" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>

                                                                    <!-- Edit Button -->
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-light p-2" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>

                                                                    <!-- Delete Button -->
                                                                    <a href="javascript:void(0);" class="btn btn-danger p-2 delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash"></i>
                                                                    </a>

                                                                </div>


                                                                <button type="button" class="btn-user-expand mobile-only d-md-none" onclick="toggleMobileDetails(<?= $policy['id']; ?>, this)">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>

                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                <tr class="details-row d-md-none border-0" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>" style="display: none;">
                                                    <td colspan="8" class="p-0 border">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                                                                    <div class="expand-value"><?= esc($policy['staff_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                                                                    <div class="expand-value"><?= esc($policy['policy_type_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                    <div class="expand-value"><?= esc($policy['premium'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                        <a href="javascript:void(0);"
                                                                        class="btn crm-action-btn btn-sm view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="bi bi-person-vcard"></i>
                                                                    </a>
                                                                    <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="bi bi-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Renew Policy">
                                                                        <i class="bi bi-arrow-repeat"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn crm-action-btn btn-sm view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="View Policy">
                                                                        <i class="bi bi-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Edit Policy">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn crm-action-btn btn-sm text-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="bi bi-trash text-danger"></i>
                                                                    </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Renewed My Policies -->
                                    <div class="tab-pane fade" id="renewedMyPolicies" role="tabpanel"
                                        aria-labelledby="renewedMyPolicies-tab">
                                        <table id="renewedMyPoliciesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="policy-type mobile-hide">Policy Type</th>
                                                    <th class="company mobile-hide">Company</th>
                                                    <th class="start-date mobile-hide">Start Date</th>
                                                    <th class="end-date mobile-hide">End Date</th>
                                                    <th class="premium mobile-hide">Premium</th>
                                                    <th class="action text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; ?>
                                                <?php foreach ($renewed_myPolicies as $policy): ?>
                                                    <?php if (!empty($policy['renew_status']) && strtolower(trim($policy['renew_status'])) === 'renewed'): ?>
                                                        <tr data-id="<?= $policy['id']; ?>" data-company="<?= esc($policy['company_name'] ?? '') ?>">
                                                            <td class="font_size text-center"><?= $i++; ?></td>
                                                            <td class="font_size policy-holder-name" title="<?= esc($policy['policy_holder_name']); ?>">
                                                                <?= esc($policy['policy_holder_name']); ?>
                                                            </td>
                                                            <td class="font_size mobile-hide hover-full" title="<?= esc($policy['policy_type_name']); ?>">
                                                                <?= esc($policy['policy_type_name']); ?>
                                                            </td>

                                                            <td class="font_size mobile-hide hover-full" title="<?= esc($policy['company_name']); ?>">
                                                                <?= esc($policy['company_name']); ?>
                                                            </td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                            <td class="font_size mobile-hide"><?= esc($policy['premium']); ?></td>
                                                            <td class="font_size text-center">
                                                                <div class="desktop-actions">

                                                                    <!-- View KYC Button -->
                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-secondary p-2 view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>
                                                                    <!-- Upload / Download Button -->
                                                                    <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>

                                                                    <!-- Renewal Button -->
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-success p-2" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf"></i>
                                                                    </a>

                                                                    <!-- View Button -->
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-info p-2" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>

                                                                    <!-- Edit Button -->
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-light p-2" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>

                                                                    <!-- Delete Button -->
                                                                    <a href="javascript:void(0);" class="btn btn-danger p-2 delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash"></i>
                                                                    </a>
                                                                </div>

                                                                <button type="button" class="btn-user-expand mobile-only d-md-none" onclick="toggleMobileDetails(<?= $policy['id']; ?>, this)">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                <tr class="details-row d-md-none border-0" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>" style="display: none;">
                                                    <td colspan="8" class="p-0 border">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                                                                    <div class="expand-value"><?= esc($policy['staff_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                                                                    <div class="expand-value"><?= esc($policy['policy_type_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                    <div class="expand-value"><?= esc($policy['premium'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                        <a href="javascript:void(0);"
                                                                        class="btn crm-action-btn btn-sm view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="bi bi-person-vcard"></i>
                                                                    </a>


                                                                    <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="bi bi-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Renew Policy">
                                                                        <i class="bi bi-arrow-repeat"></i>
                                                                    </a>
                                                                    <!-- <a href="<? //= base_url('download-policy-invoice/' . $policy['id']); 
                                                                                    ?>" target="_blank" class="btn crm-action-btn btn-sm" title="Download Invoice PDF">
                                                                        <i class="bi bi-file-earmark-pdf"></i> Invoice
                                                                    </a> -->
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn crm-action-btn btn-sm view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="View Policy">
                                                                        <i class="bi bi-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Edit Policy">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn crm-action-btn btn-sm text-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="bi bi-trash text-danger"></i>
                                                                    </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ================= Staff Policies ================= -->
                            <div class="tab-pane fade" id="staffPolicies" role="tabpanel" aria-labelledby="staffPolicies-tab">

                                <!-- Inner Tabs -->
                                <ul class="nav nav-pills mb-3 custom-pills px-4" id="staffPoliciesInnerTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="allStaffPolicies-tab" data-bs-toggle="tab"
                                            data-bs-target="#allStaffPolicies" type="button" role="tab"
                                            aria-controls="allStaffPolicies" aria-selected="true">
                                            <i class="fa fa-list me-2"></i> All Policies
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="currentStaffPolicies-tab" data-bs-toggle="tab"
                                            data-bs-target="#currentStaffPolicies" type="button" role="tab"
                                            aria-controls="currentStaffPolicies" aria-selected="false">
                                            <i class="fa fa-file-text-o me-2"></i> Policies
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="renewedStaffPolicies-tab" data-bs-toggle="tab"
                                            data-bs-target="#renewedStaffPolicies" type="button" role="tab"
                                            aria-controls="renewedStaffPolicies" aria-selected="false">
                                            <i class="fa fa-refresh me-2"></i> Renewed Policies
                                        </button>
                                    </li>
                                </ul>

                                <!-- 🔹 Filter Row -->
                                <div class="custom-filter-card policy-filter-single-row mx-4">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Sub Agent</label>
                                            <select class="form-control filter-subagent">
                                                <option value="">-- All Sub Agents --</option>
                                                <?php foreach (($subAgents ?? []) as $subAgent): ?>
                                                    <option value="<?= esc($subAgent->name) ?>"><?= esc($subAgent->name) ?><?= $subAgent->email ? ' — ' . esc($subAgent->email) : '' ?><?= $subAgent->phone ? ' — ' . esc($subAgent->phone) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="filter_holder_staff" class="form-label">Policy Holder</label>
                                            <select id="filter_holder_staff" class="form-control filter-holder">
                                                <option value="">-- All Holders --</option>
                                                <?php if (!empty($holders)): ?>
                                                    <?php foreach ($holders as $holder): ?>
                                                        <option value="<?= esc($holder['name']) ?>"><?= esc($holder['name']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="filter_policy_type_staff" class="form-label">Policy Type</label>
                                            <select id="filter_policy_type_staff" class="form-control filter-policy-type">
                                                <option value="">-- All Policy Types --</option>
                                                <?php if (!empty($policyTypes)): ?>
                                                    <?php foreach ($policyTypes as $type): ?>
                                                        <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="filter_company_staff" class="form-label">Company</label>
                                            <select id="filter_company_staff" class="form-control filter-company">
                                                <option value="">-- All Companies --</option>
                                                <?php if (!empty($companies)): ?>
                                                    <?php foreach ($companies as $company): ?>
                                                        <option value="<?= esc($company['name']) ?>"><?= esc($company['name']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-2">
                                            <label for="filter_date_range_staff" class="form-label">Start Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0"><i class="fa fa-calendar-o"></i></span>
                                                <input type="text" id="filter_date_range_staff" class="form-control filter-date-range border-start-0 ps-0" placeholder="Select start date" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-9 mb-2 d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-reset-filter clear-policy-filters" title="Clear all filters">
                                                Clear
                                            </button>
                                            <button type="button" class="btn btn-apply-filter d-none" title="Filters are applied automatically">
                                                <i class="fa fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content" id="staffPoliciesInnerContent">
                                    <!-- All Staff Policies -->
                                    <div class="tab-pane fade show active" id="allStaffPolicies" role="tabpanel"
                                        aria-labelledby="allStaffPolicies-tab">
                                        <table id="allStaffPoliciesTable" class="table policy-table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="company mobile-hide">Agent</th>
                                                    <th class="policy-type mobile-hide">Policy Type</th>
                                                    <th class="start-date mobile-hide">Start Date</th>
                                                    <th class="end-date mobile-hide">End Date</th>
                                                    <th class="premium mobile-hide">Premium</th>
                                                    <th class="action text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $allStaffIdx = 1; @endphp
                                                @foreach(($staffPolicies ?? []) as $policy)
                                                    @include('crm.policy.policy_table_row', ['policy' => $policy, 'index' => $allStaffIdx++, 'showAgentCol' => true])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Current Staff Policies -->
                                    <div class="tab-pane fade" id="currentStaffPolicies" role="tabpanel"
                                        aria-labelledby="currentStaffPolicies-tab">
                                        <table id="currentStaffPoliciesTable" class="table policy-table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="company mobile-hide">Agent</th>
                                                    <th class="policy-type mobile-hide">Policy Type</th>
                                                    <th class="start-date mobile-hide">Start Date</th>
                                                    <th class="end-date mobile-hide">End Date</th>
                                                    <th class="premium mobile-hide">Premium</th>
                                                    <th class="action text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1;
                                                foreach ($staffPolicies as $policy) : ?>
                                                    <?php if (empty($policy['is_renew']) && $policy['renew_id'] == null && $policy['renew_status'] == null): ?>
                                                        <tr data-id="<?= $policy['id']; ?>" data-company="<?= esc($policy['company_name'] ?? '') ?>">
                                                            <td class="font_size text-center"><?= $i++; ?></td>
                                                            <td class="font_size policy-holder-name" title="<?= esc($policy['policy_holder_name']); ?>">
                                                                <?= esc($policy['policy_holder_name']); ?>
                                                            </td>
                                                            <td class="font_size mobile-hide hover-full"><?= esc($policy['staff_name'] ?? ''); ?></td>
                                                            <td class="font_size mobile-hide hover-full"><?= esc($policy['policy_type_name'] ?? ''); ?></td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                            <td class="font_size mobile-hide"><?= esc($policy['premium'] ?? ''); ?></td>
                                                            <td class="font_size text-center">
                                                                <div class="desktop-actions">

                                                                    <!-- View KYC Button -->
                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-secondary p-2 view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>

                                                                    <!-- Upload / Download Button -->
                                                                    <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>

                                                                    <!-- Renewal Button -->
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-success p-2" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf"></i>
                                                                    </a>

                                                                    <!-- View Button -->
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-info p-2" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>

                                                                    <!-- Edit Button -->
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-light p-2" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>

                                                                    <!-- Delete Button -->
                                                                    <a href="javascript:void(0);" class="btn btn-danger p-2 delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash"></i>
                                                                    </a>
                                                                </div>

                                                                <button type="button" class="btn-user-expand mobile-only d-md-none" onclick="toggleMobileDetails(<?= $policy['id']; ?>, this)">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                <tr class="details-row d-md-none border-0" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>" style="display: none;">
                                                    <td colspan="8" class="p-0 border">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                                                                    <div class="expand-value"><?= esc($policy['staff_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                                                                    <div class="expand-value"><?= esc($policy['policy_type_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                    <div class="expand-value"><?= esc($policy['premium'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                        <a href="javascript:void(0);"
                                                                        class="btn crm-action-btn btn-sm view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="bi bi-person-vcard"></i>
                                                                    </a>


                                                                    <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="bi bi-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Renew Policy">
                                                                        <i class="bi bi-arrow-repeat"></i>
                                                                    </a>
                                                                    <!-- <a href="<? //= base_url('download-policy-invoice/' . $policy['id']); 
                                                                                    ?>" target="_blank" class="btn crm-action-btn btn-sm" title="Download Invoice PDF">
                                                                        <i class="bi bi-file-earmark-pdf"></i> Invoice
                                                                    </a> -->
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn crm-action-btn btn-sm view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="View Policy">
                                                                        <i class="bi bi-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Edit Policy">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn crm-action-btn btn-sm text-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="bi bi-trash text-danger"></i>
                                                                    </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Renewed Staff Policies -->
                                    <div class="tab-pane fade" id="renewedStaffPolicies" role="tabpanel"
                                        aria-labelledby="renewedStaffPolicies-tab">
                                        <table id="renewedStaffPoliciesTable" class="table policy-table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="company mobile-hide">Agent</th>
                                                    <th class="policy-type mobile-hide">Policy Type</th>
                                                    <th class="start-date mobile-hide">Start Date</th>
                                                    <th class="end-date mobile-hide">End Date</th>
                                                    <th class="premium mobile-hide">Premium</th>
                                                    <th class="action text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1;
                                                foreach ($staffPolicies as $policy) : ?>
                                                    <?php if (!empty($policy['renew_status']) && strtolower(trim($policy['renew_status'])) === 'renewed'): ?>
                                                        <tr data-id="<?= $policy['id']; ?>" data-company="<?= esc($policy['company_name'] ?? '') ?>">
                                                            <td class="font_size text-center"><?= $i++; ?></td>
                                                            <td class="font_size policy-holder-name" title="<?= esc($policy['policy_holder_name']); ?>">
                                                                <?= esc($policy['policy_holder_name']); ?>
                                                            </td>
                                                            <td class="font_size mobile-hide hover-full"><?= esc($policy['staff_name'] ?? ''); ?></td>
                                                            <td class="font_size mobile-hide hover-full"><?= esc($policy['policy_type_name'] ?? ''); ?></td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                            <td class="font_size mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                            <td class="font_size mobile-hide"><?= esc($policy['premium'] ?? ''); ?></td>
                                                            <td class="font_size text-center">
                                                                <div class="desktop-actions">

                                                                    <!-- View KYC Button -->
                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-secondary p-2 view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>

                                                                    <!-- Upload / Download Button -->
                                                                    <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>

                                                                    <!-- Renewal Button -->
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-success p-2" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf"></i>
                                                                    </a>

                                                                    <!-- View Button -->
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-info p-2" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>

                                                                    <!-- Edit Button -->
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-light p-2" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>

                                                                    <!-- Delete Button -->
                                                                    <a href="javascript:void(0);" class="btn btn-danger p-2 delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash"></i>
                                                                    </a>
                                                                </div>

                                                                <button type="button" class="btn-user-expand mobile-only d-md-none" onclick="toggleMobileDetails(<?= $policy['id']; ?>, this)">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                <tr class="details-row d-md-none border-0" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>" style="display: none;">
                                                    <td colspan="8" class="p-0 border">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                                                                    <div class="expand-value"><?= esc($policy['staff_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                                                                    <div class="expand-value"><?= esc($policy['policy_type_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                    <div class="expand-value"><?= esc($policy['premium'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                        
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                $session = session();
                $userRole = $session->get('user_role');

                if ($userRole == 1): ?>
                    <div class="px-4 pb-4 overflow-scroll">
                        <ul class="nav nav-tabs mb-3" id="policyOwnershipTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active policy-ownership-tab" type="button"
                                    data-policy-scope="created" aria-selected="true">Created by Me</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link policy-ownership-tab" type="button"
                                    data-policy-scope="assigned" aria-selected="false">Assigned to Me</button>
                            </li>
                        </ul>

                        <!-- ================= Staff Policies ================= -->
                        <!-- Inner Tabs -->
                        <ul class="nav nav-pills mb-3 custom-pills" id="staffPoliciesInnerTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="allStaffPoliciesStaff-tab" data-bs-toggle="tab"
                                    data-bs-target="#allStaffPoliciesStaff" type="button" role="tab"
                                    aria-controls="allStaffPoliciesStaff" aria-selected="true">
                                    <i class="fa fa-list me-2"></i> All Policies
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="currentStaffPolicies-tab" data-bs-toggle="tab"
                                    data-bs-target="#currentStaffPoliciesStaff" type="button" role="tab"
                                    aria-controls="currentStaffPoliciesStaff" aria-selected="false">
                                    <i class="fa fa-file-text-o me-2"></i> Policies
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="renewedStaffPolicies-tab" data-bs-toggle="tab"
                                    data-bs-target="#renewedStaffPoliciesStaff" type="button" role="tab"
                                    aria-controls="renewedStaffPoliciesStaff" aria-selected="false">
                                    <i class="fa fa-refresh me-2"></i> Renewed Policies
                                </button>
                            </li>
                        </ul>

                        <!-- 🔹 Filter Row -->
                        <div class="custom-filter-card staff-policy-filter-single-row mx-4">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="filter_holder_staff" class="form-label">Policy Holder</label>
                                    <select id="filter_holder_staff" class="form-control filter-holder">
                                        <option value="">-- All Holders --</option>
                                        <?php if (!empty($holders)): ?>
                                            <?php foreach ($holders as $holder): ?>
                                                <option value="<?= esc($holder['name']) ?>"><?= esc($holder['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="filter_policy_type_staff" class="form-label">Policy Type</label>
                                    <select id="filter_policy_type_staff" class="form-control filter-policy-type">
                                        <option value="">-- All Policy Types --</option>
                                        <?php if (!empty($policyTypes)): ?>
                                            <?php foreach ($policyTypes as $type): ?>
                                                <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="filter_company_staff_user" class="form-label">Company</label>
                                    <select id="filter_company_staff_user" class="form-control filter-company">
                                        <option value="">-- All Companies --</option>
                                        <?php foreach (($companies ?? []) as $company): ?>
                                            <option value="<?= esc($company['name']) ?>"><?= esc($company['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="filter_date_range_staff_user" class="form-label">Start Date</label>
                                    <input type="text" id="filter_date_range_staff_user"
                                        class="form-control filter-date-range" placeholder="Select start date" autocomplete="off">
                                    <input type="hidden" id="filter_start_date">
                                    <input type="hidden" id="filter_end_date">
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-md-12 mb-2 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-reset-filter clear-policy-filters" title="Clear all filters">
                                        Clear
                                    </button>
                                    <button type="button" class="btn btn-apply-filter d-none" title="Filters are applied automatically">
                                        <i class="fa fa-filter me-1"></i> Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content overflow-scroll" id="staffPoliciesInnerContentStaff">
                            <!-- All Staff Policies -->
                            <div class="tab-pane fade show active" id="allStaffPoliciesStaff" role="tabpanel"
                                aria-labelledby="allStaffPoliciesStaff-tab">
                                <table id="allStaffPoliciesTableStaff" class="table policy-table">
                                    <thead>
                                        <tr>
                                            <th class="sr-no text-center">Sr.No</th>
                                            <th class="policy-holder">Policy Holder</th>
                                            <th class="company mobile-hide">Agent</th>
                                            <th class="policy-type mobile-hide">Policy Type</th>
                                            <th class="start-date mobile-hide">Start Date</th>
                                            <th class="end-date mobile-hide">End Date</th>
                                            <th class="premium mobile-hide">Premium</th>
                                            <th class="created-by mobile-hide">Created By</th>
                                            <th class="action text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $allStaffUserIdx = 1; @endphp
                                        @foreach(($staffPolicies ?? []) as $policy)
                                            @include('crm.policy.policy_table_row', [
                                                'policy' => $policy,
                                                'index' => $allStaffUserIdx++,
                                                'showAgentCol' => true,
                                                'showCreatedByCol' => true,
                                                'policyScope' => (int) ($policy['created_by'] ?? 0) === (int) auth()->id() ? 'created' : 'assigned'
                                            ])
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Current Staff Policies -->
                            <div class="tab-pane fade" id="currentStaffPoliciesStaff" role="tabpanel"
                                aria-labelledby="currentStaffPolicies-tab">
                                <table id="currentStaffPoliciesTableStaff" class="table policy-table">
                                    <thead>
                                        <tr>
                                            <th class="sr-no text-center">Sr.No</th>
                                            <th class="policy-holder">Policy Holder</th>
                                            <th class="company mobile-hide">Agent</th>
                                            <th class="policy-type mobile-hide">Policy Type</th>
                                            <th class="start-date mobile-hide">Start Date</th>
                                            <th class="end-date mobile-hide">End Date</th>
                                            <th class="premium mobile-hide">Premium</th>
                                            <th class="created-by mobile-hide">Created By</th>
                                            <th class="action text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        foreach ($staffPolicies as $policy) : ?>
                                            <?php if (empty($policy['is_renew']) && $policy['renew_id'] == null && $policy['renew_status'] == null): ?>
                                                <tr data-id="<?= $policy['id']; ?>"
                                                    data-company="<?= esc($policy['company_name'] ?? '') ?>"
                                                    data-policy-scope="<?= (int) ($policy['created_by'] ?? 0) === (int) auth()->id() ? 'created' : 'assigned' ?>">
                                                    <td class="text-center"><?= $i++; ?></td>
                                                    <td><?= esc($policy['policy_holder_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['staff_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['policy_type_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['premium'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['created_by_name'] ?? '--'); ?></td>
                                                    <td class="text-center">
                                                        <div class="desktop-actions">
                                                            <!-- View KYC Button -->
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-sm btn-secondary p-2 view-kyc-btn"
                                                                data-policy-id="<?= $policy['id']; ?>"
                                                                title="View KYC Details">
                                                                <i class="fa fa-id-card"></i>
                                                            </a>
                                                            <!-- Upload / Download Button -->
                                                            <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                <i class="fa fa-upload"></i>
                                                            </button>

                                                            <!-- Renewal Button -->
                                                            <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-success p-2" title="Renew Policy">
                                                                <i class="fa fa-refresh"></i>
                                                            </a>

                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                class="btn btn-warning p-2 view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="fa fa-file-pdf"></i>
                                                            </a>

                                                            <!-- View Button -->
                                                            <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                <i class="fa fa-eye"></i>
                                                            </a>

                                                            <!-- Edit Button -->
                                                            <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                <i class="fa fa-edit"></i>
                                                            </a>

                                                            <!-- Delete Button -->
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- Mobile Details Row -->
                                                <tr class="details-row d-md-none border-0" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>" style="display: none;">
                                                    <td colspan="8" class="p-0 border">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                                                                    <div class="expand-value"><?= esc($policy['staff_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                                                                    <div class="expand-value"><?= esc($policy['policy_type_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                    <div class="expand-value"><?= esc($policy['premium'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user-pen"></i> Created By :</div>
                                                                    <div class="expand-value"><?= esc($policy['created_by_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                        <a href="javascript:void(0);"
                                                                class="btn crm-action-btn btn-sm view-kyc-btn"
                                                                data-policy-id="<?= $policy['id']; ?>"
                                                                title="View KYC Details">
                                                                <i class="bi bi-person-vcard"></i>
                                                            </a>
                                                            <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                <i class="bi bi-upload"></i>
                                                            </button>
                                                            <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Renew Policy">
                                                                <i class="bi bi-arrow-repeat"></i>
                                                            </a>
                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                class="btn crm-action-btn btn-sm view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="bi bi-file-earmark-pdf"></i>
                                                            </a>
                                                            <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="View Policy">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Edit Policy">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="javascript:void(0);" class="btn crm-action-btn btn-sm text-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                <i class="bi bi-trash text-danger"></i>
                                                            </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Renewed Staff Policies -->
                            <div class="tab-pane fade" id="renewedStaffPoliciesStaff" role="tabpanel"
                                aria-labelledby="renewedStaffPolicies-tab">
                                <table id="renewedStaffPoliciesTableStaff" class="table policy-table">
                                    <thead>
                                        <tr>
                                            <th class="sr-no text-center">Sr.No</th>
                                            <th class="policy-holder">Policy Holder</th>
                                            <th class="company mobile-hide">Agent</th>
                                            <th class="policy-type mobile-hide">Policy Type</th>
                                            <th class="start-date mobile-hide">Start Date</th>
                                            <th class="end-date mobile-hide">End Date</th>
                                            <th class="premium mobile-hide">Premium</th>
                                            <th class="created-by mobile-hide">Created By</th>
                                            <th class="action text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        foreach ($staffPolicies as $policy) : ?>
                                            <?php if (!empty($policy['renew_status']) && strtolower(trim($policy['renew_status'])) === 'renewed'): ?>
                                                <tr data-id="<?= $policy['id']; ?>"
                                                    data-company="<?= esc($policy['company_name'] ?? '') ?>"
                                                    data-policy-scope="<?= (int) ($policy['created_by'] ?? 0) === (int) auth()->id() ? 'created' : 'assigned' ?>">
                                                    <td class="text-center"><?= $i++; ?></td>
                                                    <td><?= esc($policy['policy_holder_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['staff_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['policy_type_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['premium'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['created_by_name'] ?? '--'); ?></td>
                                                    <td class="text-center">
                                                        <div class="desktop-actions">

                                                            <!-- View KYC Button -->
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-secondary p-2 view-kyc-btn"
                                                                data-policy-id="<?= $policy['id']; ?>"
                                                                title="View KYC Details">
                                                                <i class="fa fa-id-card"></i>
                                                            </a>
                                                            <!-- Upload / Download Button -->
                                                            <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                <i class="fa fa-upload"></i>
                                                            </button>

                                                            <!-- Renewal Button -->
                                                            <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-success p-2" title="Renew Policy">
                                                                <i class="fa fa-refresh"></i>
                                                            </a>

                                                            <!-- Download Invoice PDF Button -->
                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                class="btn btn-warning p-2 view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="fa fa-file-pdf"></i>
                                                            </a>

                                                            <!-- View Button -->
                                                            <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-info p-2" title="View Policy">
                                                                <i class="fa fa-eye"></i>
                                                            </a>

                                                            <!-- Edit Button -->
                                                            <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-light p-2" title="Edit Policy">
                                                                <i class="fa fa-edit"></i>
                                                            </a>

                                                            <!-- Delete Button -->
                                                            <a href="javascript:void(0);" class="btn btn-danger p-2 delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </div>

                                                        <button type="button" class="btn-user-expand mobile-only d-md-none" onclick="toggleMobileDetails(<?= $policy['id']; ?>, this)">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Mobile Details Row -->
                                                <tr class="details-row d-md-none border-0" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>" style="display: none;">
                                                    <td colspan="8" class="p-0 border">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                                                                    <div class="expand-value"><?= esc($policy['staff_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                                                                    <div class="expand-value"><?= esc($policy['policy_type_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                                                    <div class="expand-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--'; ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                                                                    <div class="expand-value"><?= esc($policy['premium'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-user-pen"></i> Created By :</div>
                                                                    <div class="expand-value"><?= esc($policy['created_by_name'] ?? '--'); ?></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                                                    <div class="d-flex gap-2 flex-wrap">
                                                                        <a href="javascript:void(0);"
                                                                class="btn crm-action-btn btn-sm view-kyc-btn"
                                                                data-policy-id="<?= $policy['id']; ?>"
                                                                title="View KYC Details">
                                                                <i class="bi bi-person-vcard"></i>
                                                            </a>
                                                            <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                <i class="bi bi-upload"></i>
                                                            </button>
                                                            <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Renew Policy">
                                                                <i class="bi bi-arrow-repeat"></i>
                                                            </a>
                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>" target="_blank" rel="noopener noreferrer"
                                                                class="btn crm-action-btn btn-sm view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="bi bi-file-earmark-pdf"></i>
                                                            </a>
                                                            <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="View Policy">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn crm-action-btn btn-sm" title="Edit Policy">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="javascript:void(0);" class="btn crm-action-btn btn-sm text-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                <i class="bi bi-trash text-danger"></i>
                                                            </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadDownloadModal" tabindex="-1" aria-labelledby="uploadDownloadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="uploadDownloadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadDownloadModalLabel">Upload / Download Documents</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="policy_id" id="upload_policy_id">

                        <!-- Upload Section -->
                        <div class="mb-3">
                            <label for="document" class="form-label">Upload Main Policy PDF</label>
                            <input type="file" name="document" id="document" class="form-control" accept="application/pdf,.pdf" required>
                            <div class="form-text">This updates the current main Policy PDF and keeps all earlier PDFs in history. Renewal PDFs can only be uploaded from the Renew Policy page.</div>
                        </div>

                        <!-- Download Section -->
                        <div class="mb-3">
                            <label class="form-label">All Policy PDF History:</label>
                            <ul id="documentList" class="list-group" style="max-height: 280px; overflow-y: auto; padding-right: 4px;">
                                <!-- Filled dynamically -->
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-dark-blue">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Policy Commission (Pay-In & Pay-Out) Modal -->
    <div class="modal fade" id="policyCommissionModal" tabindex="-1" aria-labelledby="policyCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <form id="policyCommissionForm">
                    @csrf
                    <input type="hidden" name="policy_id" id="modal_comm_policy_id">
                    <div class="modal-header border-bottom py-3 px-4">
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="policyCommissionModalLabel">Policy Commission (Pay-In & Pay-Out)</h5>
                            <small class="text-muted" id="modal_comm_policy_subtitle">Set or edit custom commission for this policy.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Gross Premium (₹)</label>
                                <input type="text" id="modal_comm_premium_display" class="form-control bg-light" readonly>
                                <input type="hidden" id="modal_comm_premium_val" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Assigned Agent / Creator</label>
                                <input type="text" id="modal_comm_agent_name" class="form-control bg-light" readonly>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <!-- PAY-IN (Admin) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-primary border-opacity-25 rounded-4 shadow-sm" style="background: #f8fafc;">
                                    <div class="card-header bg-primary text-white py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="fa-solid fa-arrow-down-left me-1"></i> PAY-IN (Admin)</span>
                                        <span class="badge bg-white text-primary">From Insurer</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Type</label>
                                                <select name="pay_in_type" id="modal_pay_in_type" class="form-select form-select-sm">
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹ Amount)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Value</label>
                                                <input type="number" step="0.01" name="pay_in_value" id="modal_pay_in_value" class="form-control form-control-sm" value="0">
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-white border border-primary border-opacity-25">
                                            <small class="text-muted fw-semibold">Admin Total</small>
                                            <div class="h4 fw-bold text-primary mb-0" id="modal_display_pay_in">₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PAY-OUT (Agent) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-warning border-opacity-50 rounded-4 shadow-sm" style="background: #fffbeb;">
                                    <div class="card-header bg-warning py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center" style="color: #ffffff !important;">
                                        <span class="fw-bold" style="color: #ffffff !important;"><i class="fa-solid fa-arrow-up-right-from-square me-1" style="color: #ffffff !important;"></i> PAY-OUT (Agent)</span>
                                        <span class="badge bg-dark text-white">To Agent</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Type</label>
                                                <select name="pay_out_type" id="modal_pay_out_type" class="form-select form-select-sm">
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Base</label>
                                                <select name="pay_out_based_on" id="modal_pay_out_based_on" class="form-select form-select-sm">
                                                    <option value="admin_commission">% of Pay-In</option>
                                                    <option value="premium">% of Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Value</label>
                                                <input type="number" step="0.01" name="pay_out_value" id="modal_pay_out_value" class="form-control form-control-sm" value="0">
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-white border border-warning border-opacity-50">
                                            <small class="text-muted fw-semibold">Agent Share</small>
                                            <div class="h4 fw-bold text-dark mb-0" id="modal_display_pay_out">₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Profit</h6>
                                <small class="text-secondary fw-semibold">Pay-In received minus Pay-Out given</small>
                            </div>
                            <div class="h3 fw-bold mb-0" id="modal_display_net_profit" style="color: #047857 !important;">₹0.00</div>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue px-4" id="savePolicyCommissionBtn">Save Policy Commission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kycModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">KYC Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="kycModalBody">
                    <!-- Tabs -->
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="kycTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="idproof-tab" data-bs-toggle="tab" data-bs-target="#idproof" type="button" role="tab">
                                ID Proof
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address" type="button" role="tab">
                                Address Proof
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="photo-sign-tab" data-bs-toggle="tab" data-bs-target="#photosign" type="button" role="tab">
                                Photo & Signature
                            </button>
                        </li>
                    </ul>

                    <form id="kycForm" enctype="multipart/form-data">
                        @csrf
                        <div id="kyc-errors" class="alert alert-danger mt-3" style="display:none;"></div>
                        <input type="hidden" name="policy_id" id="kyc_policy_id">

                        <div class="tab-content mt-3" id="kycTabsContent">

                            <!-- ================= ID PROOF TAB ================= -->
                            <div class="tab-pane fade show active" id="idproof" role="tabpanel">
                                <h5 class="mt-2">ID Proof</h5>

                                <div class="row">

                                    <div class="col-md-4 mb-3">
                                        <label>Aadhaar Card Upload Type</label>
                                        <select class="form-select mb-2" id="aadhaar_type_select">
                                            <option value="single">Single File</option>
                                            <option value="front_back">Front & Back Files</option>
                                        </select>
                                        
                                        <!-- Single Upload -->
                                        <div id="aadhaar_single_div">
                                            <div class="d-flex align-items-center">
                                                <input type="file" name="aadhaar_card[]" class="form-control" id="aadhaar_single_input">
                                            </div>
                                        </div>
                                        
                                        <!-- Front & Back Upload -->
                                        <div id="aadhaar_front_back_div" style="display:none;">
                                            <div class="mb-2">
                                                <small class="text-muted">Front Side</small>
                                                <div class="d-flex align-items-center">
                                                    <input type="file" name="aadhaar_card[]" class="form-control aadhaar-multi-input" id="aadhaar_front_input" disabled>
                                                </div>
                                            </div>
                                            <div>
                                                <small class="text-muted">Back Side</small>
                                                <div class="d-flex align-items-center">
                                                    <input type="file" name="aadhaar_card[]" class="form-control aadhaar-multi-input" id="aadhaar_back_input" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>PAN Card</label>
                                        <input type="file" name="pan_card[]" class="form-control" multiple>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>Driving Licence</label>
                                        <input type="file" name="driving_licence[]" class="form-control" multiple>
                                    </div>

                                </div>
                            </div>

                            <!-- ================= ADDRESS PROOF TAB ================= -->
                            <div class="tab-pane fade" id="address" role="tabpanel">
                                <h5 class="mt-2">Address Proof</h5>

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label>Aadhaar Card</label>
                                        <input type="file" name="addr_aadhaar[]" class="form-control" multiple>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Driving Licence</label>
                                        <input type="file" name="addr_driving_licence[]" class="form-control" multiple>
                                    </div>

                                </div>
                            </div>

                            <!-- ================= PHOTO & SIGNATURE TAB ================= -->
                            <div class="tab-pane fade" id="photosign" role="tabpanel">
                                <h5 class="mt-2">Photo & Signature</h5>

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label>Passport Size Photo</label>
                                        <input type="file" name="photo[]" class="form-control" multiple>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Signature</label>
                                        <input type="file" name="signature[]" class="form-control" multiple>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-dark-blue px-4" id="btnSaveKyc">
                                <i class="fa fa-save me-1"></i> Save
                            </button>
                        </div>

                    </form>


                </div>









    <!-- Modal for viewing multiple images -->
    <div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-labelledby="imageGalleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageGalleryModalLabel">Document Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="imageGalleryContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Viewing All Policy Documents -->
    <div class="modal fade" id="policyDocumentsViewModal" tabindex="-1" aria-labelledby="policyDocumentsViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="policyDocumentsViewModalLabel">
                        <i class="fa fa-folder-open text-warning"></i>
                        <span>Policy Documents</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="policyDocsLoadingSpinner" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2 text-muted small">Loading policy documents...</div>
                    </div>
                    <ul id="policyDocumentsViewList" class="list-group list-group-flush" style="display: none;">
                        <!-- Filled dynamically -->
                    </ul>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery is already loaded globally in layout/header.php -->
    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
    <script>
        // Keep policy tables compact by placing every desktop action in one menu.
        $('.desktop-actions').each(function() {
            var $actions = $(this);
            if ($actions.hasClass('policy-actions-dropdown')) return;

            var $items = $actions.children('a, button').detach();
            var $menu = $('<div class="dropdown-menu dropdown-menu-end policy-actions-menu"></div>');

            $items.each(function() {
                var $item = $(this);
                var label = $item.attr('title') || 'Policy Action';
                $item
                    .removeClass('btn btn-sm btn-secondary btn-dark-blue btn-success btn-warning btn-info btn-light btn-danger p-2')
                    .addClass('dropdown-item policy-action-item')
                    .removeAttr('title');
                $item.append($('<span></span>').text(label));
                $menu.append($item);
            });

            var $toggle = $(
                '<button type="button" class="btn policy-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Policy actions" title="Actions">' +
                    '<i class="fa-solid fa-ellipsis-vertical"></i>' +
                '</button>'
            );

            $actions
                .addClass('dropdown policy-actions-dropdown')
                .append($toggle, $menu);
        });

        var kycPreviewUrls = [];

        function clearKycLocalPreviews() {
            kycPreviewUrls.forEach(function(url) {
                URL.revokeObjectURL(url);
            });
            kycPreviewUrls = [];
            $('#kycForm .kyc-file-preview').remove();
        }

        $(document).on('change', '#kycForm input[type="file"]', function() {
            var input = this;
            var $field = $(input).closest('.mb-3');
            $field.find('.kyc-file-preview').remove();

            if (!input.files || !input.files.length) return;

            var $preview = $('<div class="kyc-file-preview" aria-label="Selected file preview"></div>');
            Array.from(input.files).forEach(function(file) {
                var $item = $('<div class="kyc-preview-item"></div>');
                if (file.type && file.type.indexOf('image/') === 0) {
                    var objectUrl = URL.createObjectURL(file);
                    kycPreviewUrls.push(objectUrl);
                    $item.append($('<img alt="Selected document preview">').attr('src', objectUrl));
                } else {
                    $item.append('<i class="fa-solid fa-file-pdf text-danger fs-4"></i>');
                }
                $item.append($('<span class="kyc-preview-name"></span>').text(file.name));
                $preview.append($item);
            });
            $field.append($preview);
        });

        // Function to clear KYC form errors
        function clearKycErrors() {
            $('#kycForm').find('.error-text, .invalid-feedback').remove();
            $('#kycForm').find('.is-invalid').removeClass('is-invalid');
            $('#kyc-errors').hide().text('');
            $('#kycForm').removeData('is-update');
            $('#kycForm').data('is-update', false);
            clearKycLocalPreviews();
        }

        // Also clear errors when modal is hidden
        $('#kycModal').on('hidden.bs.modal', function(e) {
            // Prevent bubbling events from nested modals (like imageGalleryModal) from clearing the form
            if (e.target.id !== 'kycModal') return;

            clearKycErrors();
            $('#kycForm')[0].reset();
            // Remove any view/download icons
            $('#kycForm .view-icon').remove();
            $('#kycForm .download-icon').remove();
        });

        // Function to show image gallery modal
        function showImageGallery(fieldName, files) {
            const modal = $('#imageGalleryModal');
            const container = $('#imageGalleryContainer');

            // Set modal title
            $('#imageGalleryModalLabel').text(fieldName + ' Viewer');

            // Clear previous content
            container.empty();

            // Add images to the container
            if (files && files.length > 0) {
                files.forEach((file, index) => {
                    if (file) {
                        const fileName = file.split('/').pop();
                        const fileExt = fileName.split('.').pop().toLowerCase();

                        // Check if it's an image file
                        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                            container.append(`
                                <div class="mb-3 text-center">
                                    <h6>${fileName}</h6>
                                    <img src="${file}" class="img-fluid mb-2 gallery-image" alt="${fileName}" style="max-height: 400px; cursor: pointer;" onclick="window.open('${file}', '_blank')">
                                    <div class="mt-2">
                                        <a href="${file}" target="_blank" class="btn btn-dark-blue btn-sm">View</a>
                                        <a href="${file}" download="${fileName}" class="btn btn-success btn-sm ms-2">Download</a>
                                    </div>
                                </div>
                            `);
                        } else {
                            // For non-image files (PDF, etc.), show a link
                            container.append(`
                                <div class="mb-3 text-center">
                                    <h6>${fileName}</h6>
                                    <p>
                                        <a href="${file}" target="_blank" class="btn btn-dark-blue">View Document</a>
                                        <a href="${file}" download="${fileName}" class="btn btn-success ms-2">Download</a>
                                    </p>
                                </div>
                            `);
                        }
                    }
                });
            }

            // Show the modal
            modal.modal('show');
        }

        // Function to download all files
        function downloadAllFiles(files) {
            if (files && files.length > 0) {
                files.forEach((file, index) => {
                    if (file) {
                        // Create a temporary link and trigger download
                        const link = document.createElement('a');
                        link.href = file;
                        link.download = file.split('/').pop(); // Use the filename
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        // Add a small delay between downloads
                        setTimeout(() => {}, 500);
                    }
                });
            }
        }
    </script>
    <script>
        $(document).on('click', '.view-kyc-btn', function() {

            let policyId = $(this).data('policy-id');

            // Reset form and remove errors
            $('#kycForm')[0].reset();
            clearKycLocalPreviews();
            $('#kycForm').find('.error-text, .invalid-feedback').remove();
            $('#kycForm').find('.is-invalid').removeClass('is-invalid');

            $('#kycForm .view-icon').remove();
            $('#kycForm .download-icon').remove();

            // Clear update flag
            $('#kycForm').removeData('is-update');
            $('#kycForm').data('is-update', false);

            // Set policy ID
            $("#kyc_policy_id").val(policyId);

            // Show modal
            $("#kycModal").modal("show");
            
            $('#aadhaar_type_select').off('change').on('change', function() {
                if ($(this).val() === 'front_back') {
                    $('#aadhaar_single_div').hide();
                    $('#aadhaar_single_input').prop('disabled', true);
                    $('#aadhaar_front_back_div').show();
                    $('.aadhaar-multi-input').prop('disabled', false);
                } else {
                    $('#aadhaar_front_back_div').hide();
                    $('.aadhaar-multi-input').prop('disabled', true);
                    $('#aadhaar_single_div').show();
                    $('#aadhaar_single_input').prop('disabled', false);
                }
            }).trigger('change');

            let isUpdate = false;

            // AJAX to fetch existing KYC
            $.ajax({
                url: "{{ route('policies.legacy.kyc.view') }}",
                type: 'POST',
                data: {
                    policy_id: policyId
                },
                dataType: 'json',
                success: function(response) {

                    if (response.status === 'success') {

                        let data = response.data;

                        if (
                            (data.aadhaar_card && data.aadhaar_card.length) ||
                            (data.pan_card && data.pan_card.length) ||
                            (data.driving_licence && data.driving_licence.length) ||
                            (data.addr_aadhaar && data.addr_aadhaar.length) ||
                            (data.addr_driving_licence && data.addr_driving_licence.length) ||
                            data.photo || data.signature
                        ) {
                            isUpdate = true;
                        }

                        // Function to show a single view icon that opens modal with all images
                        const addDocumentIcons = (inputSelector, filePaths, fieldName) => {
                            if (!filePaths || filePaths.length === 0) return;

                            const $input = $(inputSelector);
                            const files = Array.isArray(filePaths) ? filePaths : [filePaths];

                            // Create a single icon that opens modal with all images
                            const iconHtml = `
                                <a href="#" class="ms-2 view-icon" onclick="showImageGallery('${fieldName}', ${JSON.stringify(files).replace(/"/g, '&quot;')}); return false;" title="View Documents (${files.length} file(s))">
                                    <i class="fa fa-eye text-primary"></i>
                                </a>
                                <a href="#" class="ms-2 download-icon" onclick="downloadAllFiles(${JSON.stringify(files).replace(/"/g, '&quot;')}); return false;" title="Download All Documents (${files.length} file(s))">
                                    <i class="fa fa-download text-success"></i>
                                </a>`;

                            // Check if input is wrapped in a .d-flex container (like Aadhaar field)
                            // We now just append after the input so it sits side-by-side inside the flex container.
                            // If the container is not d-flex, we wrap the input first.
                            if (!$input.parent().hasClass('d-flex')) {
                                $input.wrap('<div class="d-flex align-items-center"></div>');
                            }
                            $input.after(iconHtml);

                            // Show update button and hide submit button for this field
                            const fieldClass = inputSelector.replace('[name="', '').replace('[]"]', '').replace('"]', '');
                            $(`.submit-single-btn[data-field="${fieldClass}"]`).hide();
                            $(`.update-single-btn[data-field="${fieldClass}"]`).show();
                        };

                        // Aadhaar (can have multiple files)
                        if (data.aadhaar_card && data.aadhaar_card.length > 0) {
                            addDocumentIcons('#aadhaar_single_input', data.aadhaar_card, 'Aadhaar Card');
                            addDocumentIcons('#aadhaar_back_input', data.aadhaar_card, 'Aadhaar Card');
                        }

                        // PAN (single file)
                        if (data.pan_card && data.pan_card.length > 0) {
                            addDocumentIcons('input[name="pan_card[]"]', data.pan_card[0], 'PAN Card');
                        }

                        // Driving Licence (can have multiple files)
                        if (data.driving_licence && data.driving_licence.length > 0) {
                            addDocumentIcons('input[name="driving_licence[]"]', data.driving_licence, 'Driving Licence');
                        }

                        // Address Aadhaar (can have multiple files)
                        if (data.addr_aadhaar && data.addr_aadhaar.length > 0) {
                            addDocumentIcons('input[name="addr_aadhaar[]"]', data.addr_aadhaar, 'Address Aadhaar');
                        }

                        // Address Driving Licence (can have multiple files)
                        if (data.addr_driving_licence && data.addr_driving_licence.length > 0) {
                            addDocumentIcons('input[name="addr_driving_licence[]"]', data.addr_driving_licence, 'Address Driving Licence');
                        }

                        // Photo (single file)
                        if (data.photo) {
                            addDocumentIcons('input[name="photo[]"]', data.photo, 'Photo');
                        }

                        // Signature (single file)
                        if (data.signature) {
                            addDocumentIcons('input[name="signature[]"]', data.signature, 'Signature');
                        }
                    }

                    $('#kycForm').data('is-update', isUpdate);
                }
            });
        });


        // Handle individual document submission
        $(document).on('click', '.submit-single-btn', function() {
            const field = $(this).data('field');
            const policyId = $('#kyc_policy_id').val();

            // Get the file inputs for this field
            let fileInputs = [];
            if (field === 'aadhaar_card') {
                fileInputs = $('input[name="aadhaar_card[]"]:not(:disabled)').toArray();
            } else {
                fileInputs = [ $(`input[name="${field}[]"]`)[0] || $(`input[name="${field}"]`)[0] ];
            }

            // Check if there's already an existing document for this field
            const hasExistingDocument = $(this).closest('.mb-3').find('.view-icon').length > 0;

            // Only show error if no file is selected AND no existing document
            let hasFiles = fileInputs.some(input => input && input.files && input.files.length > 0);
            if (!hasFiles && !hasExistingDocument) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a file to upload for ' + field.replace('_', ' ')
                });
                return;
            }

            // If no new file is selected but document exists, do nothing
            if (!hasFiles) {
                return;
            }

            // Create FormData for this single field
            const formData = new FormData();
            formData.append('policy_id', policyId);

            // Append all files for this field
            fileInputs.forEach(input => {
                if (input && input.files) {
                    for (let i = 0; i < input.files.length; i++) {
                        formData.append(`${field}[]`, input.files[i]);
                    }
                }
            });

            // Disable button during upload
            const button = $(this);
            const originalText = button.html();
            button.html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
            button.prop('disabled', true);

            // Submit via AJAX
            $.ajax({
                url: "{{ route('policies.legacy.kyc.submit_single') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message
                        }).then(() => {
                            // Refresh the modal to show the new document
                            $('.view-kyc-btn[data-policy-id="' + policyId + '"]').click();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to upload document.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong while uploading the document.'
                    });
                },
                complete: function() {
                    // Re-enable button
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            });
        });

        // Handle individual document update
        $(document).on('click', '.update-single-btn', function() {
            const field = $(this).data('field');
            const policyId = $('#kyc_policy_id').val();

            // Get the file inputs for this field
            let fileInputs = [];
            if (field === 'aadhaar_card') {
                fileInputs = $('input[name="aadhaar_card[]"]:not(:disabled)').toArray();
            } else {
                fileInputs = [ $(`input[name="${field}[]"]`)[0] || $(`input[name="${field}"]`)[0] ];
            }

            // Check if there's already an existing document for this field
            const hasExistingDocument = $(this).closest('.mb-3').find('.view-icon').length > 0;

            let hasFiles = fileInputs.some(input => input && input.files && input.files.length > 0);
            // Only show error if no file is selected AND document exists (update scenario)
            if (!hasFiles && hasExistingDocument) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a file to update for ' + field.replace('_', ' ')
                });
                return;
            }

            // If no new file is selected, do nothing
            if (!hasFiles) {
                return;
            }

            // Create FormData for this single field
            const formData = new FormData();
            formData.append('policy_id', policyId);

            // Append all files for this field
            fileInputs.forEach(input => {
                if (input && input.files) {
                    for (let i = 0; i < input.files.length; i++) {
                        formData.append(`${field}[]`, input.files[i]);
                    }
                }
            });

            // Disable button during upload
            const button = $(this);
            const originalText = button.html();
            button.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
            button.prop('disabled', true);

            // Submit via AJAX
            $.ajax({
                url: "{{ route('policies.legacy.kyc.submit_single') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message
                        }).then(() => {
                            // Refresh the modal to show the updated document
                            $('.view-kyc-btn[data-policy-id="' + policyId + '"]').click();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to update document.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong while updating the document.'
                    });
                },
                complete: function() {
                    // Re-enable button
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            });
        });

        $(document).ready(function() {

            $('#kycForm').on('submit', function(e) {
                e.preventDefault();

                let isUpdate = $(this).data('is-update') || false;
                let isValid = true;
                let allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];
                let allowedDocTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

                // Clear previous errors
                $('#kycForm').find('.invalid-feedback, .error-text').remove();
                $('#kycForm').find('.is-invalid').removeClass('is-invalid');
                $('#kyc-errors').hide();

                function showError(input, message) {
                    const $input = $(input);
                    $input.addClass('is-invalid');
                    $input.closest('.mb-3').find('.invalid-feedback, .error-text').remove();
                    if ($input.parent().hasClass('d-flex')) {
                        $('<div class="invalid-feedback d-block">' + message + '</div>').insertAfter($input.parent());
                    } else {
                        $('<div class="invalid-feedback d-block">' + message + '</div>').insertAfter($input);
                    }
                    isValid = false;
                }

                // Validate required fields for first-time submission
                if (!isUpdate) {
                    // Aadhaar card validation (if provided)
                    let aadhaarType = $('#aadhaar_type_select').val();
                    let aadhaarFiles = [];
                    if (aadhaarType === 'single') {
                        let singleFiles = $('#aadhaar_single_input')[0].files;
                        if(singleFiles.length) aadhaarFiles.push(singleFiles[0]);
                    } else {
                        let frontFiles = $('#aadhaar_front_input')[0].files;
                        let backFiles = $('#aadhaar_back_input')[0].files;
                        if (frontFiles.length) aadhaarFiles.push(frontFiles[0]);
                        if (backFiles.length) aadhaarFiles.push(backFiles[0]);
                    }
                    
                    if (aadhaarFiles.length > 0) {
                        for (let i = 0; i < aadhaarFiles.length; i++) {
                            let ext = aadhaarFiles[i].name.split('.').pop().toLowerCase();
                            if (!allowedDocTypes.includes(ext)) {
                                showError('#aadhaar_type_select', 'Aadhaar card files must be jpg, png, gif or pdf.');
                                break;
                            }
                        }
                    }

                    // PAN card validation (if provided)
                    let panFiles = $('input[name="pan_card[]"]')[0].files;
                    if (panFiles.length > 0) {
                        let ext = panFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedDocTypes.includes(ext)) {
                            showError('input[name="pan_card[]"]', 'PAN card file must be jpg, png, gif or pdf.');
                        }
                    }

                    // Photo validation (if provided)
                    let photoFiles = $('input[name="photo[]"]')[0].files;
                    if (photoFiles.length > 0) {
                        let ext = photoFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedImageTypes.includes(ext)) {
                            showError('input[name="photo[]"]', 'Photo must be jpg, png, or gif.');
                        }
                    }

                    // Signature validation (if provided)
                    let signFiles = $('input[name="signature[]"]')[0].files;
                    if (signFiles.length > 0) {
                        let ext = signFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedImageTypes.includes(ext)) {
                            showError('input[name="signature[]"]', 'Signature must be jpg, png, or gif.');
                        }
                    }
                }

                // For updates, validate only the files that are being uploaded
                if (isUpdate) {
                    // Check any newly uploaded Aadhaar files
                    let aadhaarFiles = $('input[name="aadhaar_card[]"]')[0].files;
                    if (aadhaarFiles.length > 0) {
                        for (let i = 0; i < aadhaarFiles.length; i++) {
                            let ext = aadhaarFiles[i].name.split('.').pop().toLowerCase();
                            if (!allowedDocTypes.includes(ext)) {
                                showError('input[name="aadhaar_card[]"]', 'Aadhaar card files must be jpg, png, gif or pdf.');
                                break;
                            }
                        }
                    }

                    // Check any newly uploaded PAN files
                    let panFiles = $('input[name="pan_card[]"]')[0].files;
                    if (panFiles.length > 0) {
                        let ext = panFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedDocTypes.includes(ext)) {
                            showError('input[name="pan_card[]"]', 'PAN card file must be jpg, png, gif or pdf.');
                        }
                    }

                    // Check any newly uploaded Photo files
                    let photoFiles = $('input[name="photo[]"]')[0].files;
                    if (photoFiles.length > 0) {
                        let ext = photoFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedImageTypes.includes(ext)) {
                            showError('input[name="photo[]"]', 'Photo must be jpg, png, or gif.');
                        }
                    }

                    // Check any newly uploaded Signature files
                    let signFiles = $('input[name="signature[]"]')[0].files;
                    if (signFiles.length > 0) {
                        let ext = signFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedImageTypes.includes(ext)) {
                            showError('input[name="signature[]"]', 'Signature must be jpg, png, or gif.');
                        }
                    }
                }

                // Always validate driving license and address proof if files are selected
                // Driving License (optional but validate if provided)
                let drivingLicenseFiles = $('input[name="driving_licence[]"]')[0].files;
                if (drivingLicenseFiles.length > 0) {
                    for (let i = 0; i < drivingLicenseFiles.length; i++) {
                        let ext = drivingLicenseFiles[i].name.split('.').pop().toLowerCase();
                        if (!allowedDocTypes.includes(ext)) {
                            showError('input[name="driving_licence[]"]', 'Driving license files must be jpg, png, gif or pdf.');
                            break;
                        }
                    }
                }

                // Address Aadhaar (optional but validate if provided)
                let addrAadhaarFiles = $('input[name="addr_aadhaar[]"]')[0].files;
                if (addrAadhaarFiles.length > 0) {
                    for (let i = 0; i < addrAadhaarFiles.length; i++) {
                        let ext = addrAadhaarFiles[i].name.split('.').pop().toLowerCase();
                        if (!allowedDocTypes.includes(ext)) {
                            showError('input[name="addr_aadhaar[]"]', 'Address Aadhaar files must be jpg, png, gif or pdf.');
                            break;
                        }
                    }
                }

                // Address Driving License (optional but validate if provided)
                let addrDrivingLicenseFiles = $('input[name="addr_driving_licence[]"]')[0].files;
                if (addrDrivingLicenseFiles.length > 0) {
                    for (let i = 0; i < addrDrivingLicenseFiles.length; i++) {
                        let ext = addrDrivingLicenseFiles[i].name.split('.').pop().toLowerCase();
                        if (!allowedDocTypes.includes(ext)) {
                            showError('input[name="addr_driving_licence[]"]', 'Address Driving License files must be jpg, png, gif or pdf.');
                            break;
                        }
                    }
                }

                if (!isValid) return;

                // Submit form via AJAX
                var formData = new FormData(this);
                formData.set('policy_id', $('#kyc_policy_id').val());

                $.ajax({
                    url: "{{ route('policies.legacy.kyc.submit') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message
                            }).then(() => {
                                $('#kycModal').modal('hide');
                                $('#kycForm')[0].reset();
                                // Clear any previous errors
                                $('#kycForm').find('.error-text').remove();
                                $('#kyc-errors').hide();
                                location.reload();
                            });
                        } else if (response.status === 'error') {
                            // Hide general error message
                            $('#kyc-errors').hide();

                            // Clear previous field errors
                            $('#kycForm').find('.error-text').remove();

                            // Check if we have general form errors
                            if (response.message) {
                                $('#kyc-errors').text(response.message).show();
                            }

                            // Loop through validation errors
                            $.each(response.errors, function(field, error) {
                                // Handle wildcard field names (e.g., "aadhaar_card.*")
                                var baseField = field.replace(/\.\*$/, ''); // Remove .*
                                var input = $('[name="' + baseField + '[]"]');

                                if (input.length) {
                                    // Remove any existing error for this field
                                    input.closest('.mb-3').find('.error-text').remove();
                                    // Add error message after the input container
                                    input.closest('.mb-3').append('<small class="error-text text-danger d-block mt-1">' + error + '</small>');
                                } else {
                                    // If we can't find the specific field, show error at the top
                                    $('#kyc-errors').append('<div>' + field + ': ' + error + '</div>').show();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Something went wrong.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hide any field-specific errors
                        $('#kycForm').find('.error-text').remove();

                        // Show general error message
                        $('#kyc-errors').text('Something went wrong while uploading KYC. Please try again.').show();
                        // console.log('AJAX Error: ' + error);
                    }
                });
            });
        });
    </script>




    <script>
        // Function to toggle mobile details
        function toggleMobileDetails(id, btn) {
            const detailsRow = document.getElementById('details-' + id);
            const icon = btn.querySelector("i");
            
            if (detailsRow.style.display === "none") {
                // Hide all other open details
                document.querySelectorAll('.details-row.d-md-none').forEach(row => {
                    if (row.style.display !== "none") {
                        row.style.display = "none";
                        const prevId = row.getAttribute('data-id');
                        const prevBtn = document.querySelector(`button.btn-user-expand[onclick="toggleMobileDetails(${prevId}, this)"]`);
                        if (prevBtn) {
                            prevBtn.classList.remove("active");
                            prevBtn.querySelector("i").classList.replace("fa-minus", "fa-plus");
                        }
                    }
                });
                
                detailsRow.style.display = "table-row";
                icon.classList.replace("fa-plus", "fa-minus");
                btn.classList.add("active");
            } else {
                detailsRow.style.display = "none";
                icon.classList.replace("fa-minus", "fa-plus");
                btn.classList.remove("active");
            }
        }

        $(document).on('click', '.delete-policy', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var originalBtnText = $btn.html();
            var policyId = $btn.data('id');
            var $row = $btn.closest('tr');

            // If clicked from mobile details, also remove the details row
            if ($row.hasClass('mobile-details')) {
                $row = $row.prev('tr');
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ');

                    $.ajax({
                        url: '{{ url("delete-policy") }}/' + policyId,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: policyId
                        },
                        success: function(res) {
                            if (res.status === true) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: res.message,
                                    icon: 'success',
                                    confirmButtonColor: '#0F172A'
                                });
                                // Remove both main row and mobile details row if exists
                                $row.next('.mobile-details').remove();
                                $row.fadeOut(500, function() {
                                    $(this).remove();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: res.message,
                                    icon: 'error',
                                    confirmButtonColor: '#0F172A'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to communicate with the server.',
                                icon: 'error',
                                confirmButtonColor: '#0F172A'
                            });
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html(originalBtnText);
                        }
                    });
                }
            });
        });

        $(document).ready(function() {
            // When modal opens
            $('#uploadDownloadModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var policyId = button.data('policy-id');
                var modal = $(this);
                modal.find('#upload_policy_id').val(policyId);
                fetchDocuments(policyId);
            });

            function fetchDocuments(policyId) {
                $.ajax({
                    url: "<?= base_url('get-policy-documents'); ?>",
                    method: "GET",
                    data: {
                        policy_id: policyId
                    },
                    success: function(res) {
                        var list = $('#documentList');
                        list.empty();

                        if (res.status === 'success' && Array.isArray(res.documents)) {
                            if (!res.documents.length) {
                                list.append('<li class="list-group-item text-muted">No policy PDFs have been uploaded.</li>');
                                return;
                            }

                            res.documents.forEach(function(document) {
                                var item = $('<li class="list-group-item d-flex justify-content-between align-items-center gap-3"></li>');
                                var details = $('<div class="overflow-hidden"></div>');
                                details.append($('<div class="fw-semibold"></div>').text(document.label || 'Policy PDF'));
                                details.append($('<div class="small text-muted text-truncate"></div>').text(document.name || 'Policy PDF'));
                                var metadata = [document.policy_number, document.uploaded_at];
                                if (document.uploaded_by) metadata.push('by ' + document.uploaded_by);
                                details.append($('<div class="small text-muted"></div>').text(metadata.filter(Boolean).join(' · ')));

                                var link = $('<a class="btn btn-outline-danger btn-sm flex-shrink-0" target="_blank" rel="noopener noreferrer" title="View PDF"><i class="fa fa-file-pdf"></i></a>');
                                link.attr('href', document.url);
                                item.append(details, link);
                                list.append(item);
                            });
                            return;
                        }

                        if (res.status === 'success' && res.document) {

                            // res.document = full URL e.g. /storage/policies/filename.jpg
                            // res.name     = just the filename e.g. filename.jpg
                            var fileUrl  = res.document;
                            var fileName = res.name || res.document.split('/').pop();

                            list.append(
                                '<div class="row p-2 align-items-center">' +

                                // LEFT SIDE → Document Name (10 cols)
                                '<div class="col-10 d-flex align-items-center border rounded mb-3 p-2" style="overflow: auto; scrollbar-width: thin; white-space: nowrap; direction:ltr;display:block !important; font-size:0.95rem; color:#495057;">' +
                                fileName +
                                '</div>' +
                                // RIGHT SIDE → Download Button (2 cols)
                                '<div class="col-2 text-end">' +
                                '<a href="' + fileUrl + '" class="btn btn-success" download="' + fileName + '" target="_blank">' +
                                '<i class="fa fa-download"></i>' +
                                '</a>' +
                                '</div>' +

                                '</div>'
                            );

                        } else {
                            list.append('<li class="list-group-item">No document found</li>');
                        }
                    },


                    error: function() {
                        $('#documentList')
                            .empty()
                            .append('<li class="list-group-item text-muted">No policy PDFs have been uploaded.</li>');
                    }
                });
            }

            $('#uploadDownloadForm').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);

                $.ajax({
                    url: "<?= base_url('upload-policy-document'); ?>",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#uploadDownloadForm button[type="submit"]').prop('disabled', true).text('Uploading...');
                    },
                    success: function(res) {
                        $('#uploadDownloadForm button[type="submit"]').prop('disabled', false).text('Upload');

                        if (res.status === true || res.status === 'success') {

                            Swal.fire({
                                icon: 'success',
                                title: 'Uploaded!',
                                text: 'Document uploaded successfully',
                            }).then(() => {
                                location.reload(); // 🔄 Reload full page after closing alert
                            });

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: res.message || 'Upload failed'
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#uploadDownloadForm button[type="submit"]').prop('disabled', false).text('Upload');
                        let msg = 'Upload failed';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: msg
                        });
                    }
                });
            });
        });
    </script>

    <script>
        // Initialize DataTable and filters
        $(document).ready(function() {
            // Remove mobile details rows from tbody BEFORE initializing DataTables
            var mobileDetailsRows = [];
            $('#allMyPoliciesTable tbody tr.details-row, #currentMyPoliciesTable tbody tr.details-row, #renewedMyPoliciesTable tbody tr.details-row, #allStaffPoliciesTable tbody tr.details-row, #currentStaffPoliciesTable tbody tr.details-row, #renewedStaffPoliciesTable tbody tr.details-row, #allStaffPoliciesTableStaff tbody tr.details-row, #currentStaffPoliciesTableStaff tbody tr.details-row, #renewedStaffPoliciesTableStaff tbody tr.details-row').each(function() {
                mobileDetailsRows.push($(this).detach());
            });

            // Initialize DataTables for all existing policy tables
            var tables = {};
            var activePolicyScope = 'created';

            const policyEmptyState = `<div style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                <i class="fa-solid fa-file-shield d-block" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                <p class="mb-3">No policies found.</p>
                <a href="{{ route('policies.legacy.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Policy</a>
            </div>`;

            // My policies
            if ($('#allMyPoliciesTable').length) {
                tables.allMyPolicies = $('#allMyPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            if ($('#currentMyPoliciesTable').length) {
                tables.currentMyPolicies = $('#currentMyPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            if ($('#renewedMyPoliciesTable').length) {
                tables.renewedMyPolicies = $('#renewedMyPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            // Staff policies (admin view)
            if ($('#allStaffPoliciesTable').length) {
                tables.allStaffPolicies = $('#allStaffPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            if ($('#currentStaffPoliciesTable').length) {
                tables.currentStaffPolicies = $('#currentStaffPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            if ($('#renewedStaffPoliciesTable').length) {
                tables.renewedStaffPolicies = $('#renewedStaffPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            // Staff policies (Created by Staff section)
            if ($('#allStaffPoliciesTableStaff').length) {
                tables.allStaffPoliciesTableStaff = $('#allStaffPoliciesTableStaff').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            if ($('#currentStaffPoliciesTableStaff').length) {
                tables.currentStaffPoliciesStaff = $('#currentStaffPoliciesTableStaff').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            if ($('#renewedStaffPoliciesTableStaff').length) {
                tables.renewedStaffPoliciesStaff = $('#renewedStaffPoliciesTableStaff').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    language: { emptyTable: policyEmptyState }
                });
            }

            var $searchablePolicyFilters = $('select.filter-subagent, select.filter-holder, select.filter-policy-type, select.filter-company');
            $searchablePolicyFilters.addClass('policy-filter-select2').select2({
                width: '100%',
                allowClear: false
            });

            var policyDateSelectionCount = 0;
            function redrawPolicyTables() {
                Object.keys(tables).forEach(function(key) {
                    if (tables[key]) tables[key].draw();
                });
            }

            function updateStaffAgentColumn() {
                var showAgent = activePolicyScope !== 'created';

                if (tables.allStaffPoliciesTableStaff) {
                    tables.allStaffPoliciesTableStaff.column(2).visible(showAgent, false);
                }

                if (tables.currentStaffPoliciesStaff) {
                    tables.currentStaffPoliciesStaff.column(2).visible(showAgent, false);
                }

                if (tables.renewedStaffPoliciesStaff) {
                    tables.renewedStaffPoliciesStaff.column(2).visible(showAgent, false);
                }
            }

            var datePickers = [];
            if (typeof flatpickr !== 'undefined') {
                $('.filter-date-range').each(function() {
                    var picker = flatpickr(this, {
                    mode: 'range',
                    dateFormat: 'd-m-Y',
                    allowInput: false,
                    onChange: function(selectedDates) {
                        policyDateSelectionCount = selectedDates.length;
                        $('#filter_start_date').val(selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '');
                        $('#filter_end_date').val(selectedDates[1] ? flatpickr.formatDate(selectedDates[1], 'Y-m-d') : '');
                        datePickers.forEach(function(otherPicker) {
                            if (otherPicker !== picker) otherPicker.setDate(selectedDates, false);
                        });
                        redrawPolicyTables();
                        showTabContainingMatches();
                    },
                    onClose: function(selectedDates) {
                        // A single selected date is valid and shows policies
                        // starting on or after that date.
                        policyDateSelectionCount = selectedDates.length;
                        redrawPolicyTables();
                        showTabContainingMatches();
                    }
                    });
                    datePickers.push(picker);
                });
            }

            function applyExactColumnFilter(table, columnIndex, value) {
                if (!table) {
                    return;
                }

                table.column(columnIndex).search(value ? '^' + $.fn.dataTable.util.escapeRegex(value) + '$' : '', true, false).draw();
            }

            function syncFilter($source, selector) {
                $(selector).not($source).val($source.val()).trigger('change.select2');
            }

            function showTabContainingMatches() {
                if (!document.getElementById('myPolicies-tab') || !document.getElementById('staffPolicies-tab')) return;

                var myCount = (tables.allMyPolicies ? tables.allMyPolicies.rows({ search: 'applied' }).count() : 0) +
                    (tables.currentMyPolicies ? tables.currentMyPolicies.rows({ search: 'applied' }).count() : 0) +
                    (tables.renewedMyPolicies ? tables.renewedMyPolicies.rows({ search: 'applied' }).count() : 0);
                var staffCount = (tables.allStaffPolicies ? tables.allStaffPolicies.rows({ search: 'applied' }).count() : 0) +
                    (tables.currentStaffPolicies ? tables.currentStaffPolicies.rows({ search: 'applied' }).count() : 0) +
                    (tables.renewedStaffPolicies ? tables.renewedStaffPolicies.rows({ search: 'applied' }).count() : 0);
                var myTabActive = $('#myPolicies-tab').hasClass('active');

                if (myTabActive && myCount === 0 && staffCount > 0) {
                    bootstrap.Tab.getOrCreateInstance(document.getElementById('staffPolicies-tab')).show();
                } else if (!myTabActive && staffCount === 0 && myCount > 0) {
                    bootstrap.Tab.getOrCreateInstance(document.getElementById('myPolicies-tab')).show();
                }
            }

            function applySharedFilters() {
                var holder = $('.filter-holder').first().val() || '';
                var policyType = $('.filter-policy-type').first().val() || '';

                applyExactColumnFilter(tables.allMyPolicies, 1, holder);
                applyExactColumnFilter(tables.currentMyPolicies, 1, holder);
                applyExactColumnFilter(tables.renewedMyPolicies, 1, holder);
                applyExactColumnFilter(tables.allStaffPolicies, 1, holder);
                applyExactColumnFilter(tables.currentStaffPolicies, 1, holder);
                applyExactColumnFilter(tables.renewedStaffPolicies, 1, holder);
                applyExactColumnFilter(tables.allStaffPoliciesTableStaff, 1, holder);
                applyExactColumnFilter(tables.currentStaffPoliciesStaff, 1, holder);
                applyExactColumnFilter(tables.renewedStaffPoliciesStaff, 1, holder);

                applyExactColumnFilter(tables.allMyPolicies, 2, policyType);
                applyExactColumnFilter(tables.currentMyPolicies, 2, policyType);
                applyExactColumnFilter(tables.renewedMyPolicies, 2, policyType);
                applyExactColumnFilter(tables.allStaffPolicies, 3, policyType);
                applyExactColumnFilter(tables.currentStaffPolicies, 3, policyType);
                applyExactColumnFilter(tables.renewedStaffPolicies, 3, policyType);
                applyExactColumnFilter(tables.allStaffPoliciesTableStaff, 3, policyType);
                applyExactColumnFilter(tables.currentStaffPoliciesStaff, 3, policyType);
                applyExactColumnFilter(tables.renewedStaffPoliciesStaff, 3, policyType);

                redrawPolicyTables();
                showTabContainingMatches();
            }

            $('.filter-subagent').on('change', function() {
                var subAgentName = this.value || '';

                // Keep the filter synchronized in both main tabs.
                $('.filter-subagent').not(this).val(subAgentName).trigger('change.select2');
                applyExactColumnFilter(tables.allStaffPolicies, 2, subAgentName);
                applyExactColumnFilter(tables.currentStaffPolicies, 2, subAgentName);
                applyExactColumnFilter(tables.renewedStaffPolicies, 2, subAgentName);
                applyExactColumnFilter(tables.allStaffPoliciesTableStaff, 2, subAgentName);
                applyExactColumnFilter(tables.currentStaffPoliciesStaff, 2, subAgentName);
                applyExactColumnFilter(tables.renewedStaffPoliciesStaff, 2, subAgentName);

                showTabContainingMatches();
            });

            $('.filter-holder, .filter-policy-type, .filter-company').on('change', function() {
                var selector = $(this).hasClass('filter-holder') ? '.filter-holder' :
                    ($(this).hasClass('filter-policy-type') ? '.filter-policy-type' : '.filter-company');
                syncFilter($(this), selector);
                applySharedFilters();
            });

            $('.clear-policy-filters').on('click', function() {
                $searchablePolicyFilters.val('').trigger('change.select2');
                $('#filter_start_date, #filter_end_date').val('');
                policyDateSelectionCount = 0;

                datePickers.forEach(function(picker) {
                    picker.clear(false);
                });

                Object.keys(tables).forEach(function(key) {
                    if (tables[key]) {
                        tables[key].search('');
                        tables[key].columns().search('');
                    }
                });

                redrawPolicyTables();
            });

            $('.policy-ownership-tab').on('click', function() {
                activePolicyScope = $(this).data('policy-scope') || 'created';
                $('.policy-ownership-tab')
                    .removeClass('active')
                    .attr('aria-selected', 'false');
                $(this).addClass('active').attr('aria-selected', 'true');
                updateStaffAgentColumn();
                redrawPolicyTables();
            });

            // Custom Date Range Filter for DataTables
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var allowedTables = [
                    'allMyPoliciesTable',
                    'currentMyPoliciesTable',
                    'renewedMyPoliciesTable',
                    'allStaffPoliciesTable',
                    'currentStaffPoliciesTable',
                    'renewedStaffPoliciesTable',
                    'allStaffPoliciesTableStaff',
                    'currentStaffPoliciesTableStaff',
                    'renewedStaffPoliciesTableStaff'
                ];

                if ($.inArray(settings.nTable.id, allowedTables) === -1) {
                    return true;
                }

                var selectedCompany = $('.filter-company').first().val() || '';
                var rowNode = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
                if ($.inArray(settings.nTable.id, [
                    'allStaffPoliciesTableStaff',
                    'currentStaffPoliciesTableStaff',
                    'renewedStaffPoliciesTableStaff'
                ]) !== -1) {
                    var rowScope = rowNode ? ($(rowNode).attr('data-policy-scope') || '') : '';
                    if (rowScope !== activePolicyScope) {
                        return false;
                    }
                }

                var rowCompany = rowNode ? ($(rowNode).attr('data-company') || '') : '';
                if (selectedCompany && rowCompany.toLowerCase() !== selectedCompany.toLowerCase()) {
                    return false;
                }

                var startDate = $('#filter_start_date').val();
                var endDate = $('#filter_end_date').val();

                var policyStart = data[4];
                if (!startDate && !endDate) {
                    return true;
                }

                var start = startDate ? new Date(startDate + 'T00:00:00') : null;
                var end = endDate ? new Date(endDate + 'T23:59:59') : null;
                var policyStartDate = policyStart ? new Date(policyStart.split('-').reverse().join('-') + 'T00:00:00') : null;
                if (!policyStartDate || isNaN(policyStartDate)) {
                    return false;
                }

                // One date: show policies starting on the selected date or later.
                if (policyDateSelectionCount === 1 && start) {
                    return policyStartDate >= start;
                }

                // Completed range: compare only the policy start date.
                return (!start || policyStartDate >= start) && (!end || policyStartDate <= end);
            });

            // Apply the default "Created by Me" ownership scope immediately.
            updateStaffAgentColumn();
            redrawPolicyTables();

            // Re-insert mobile details rows after DataTables initialization
            mobileDetailsRows.forEach(function(row) {
                var rowId = row.attr('data-id');
                var mainRow = $('#allMyPoliciesTable tbody tr[data-id="' + rowId + '"], #currentMyPoliciesTable tbody tr[data-id="' + rowId + '"], #renewedMyPoliciesTable tbody tr[data-id="' + rowId + '"], #allStaffPoliciesTable tbody tr[data-id="' + rowId + '"], #currentStaffPoliciesTable tbody tr[data-id="' + rowId + '"], #renewedStaffPoliciesTable tbody tr[data-id="' + rowId + '"], #allStaffPoliciesTableStaff tbody tr[data-id="' + rowId + '"], #currentStaffPoliciesTableStaff tbody tr[data-id="' + rowId + '"], #renewedStaffPoliciesTableStaff tbody tr[data-id="' + rowId + '"]');
                if (mainRow.length) {
                    row.insertAfter(mainRow);
                }
            });
        });
    </script>
    <script>
        $(document).on('click', '.view-document-btn', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var policyId = $btn.data('policy-id');

            // If not directly on the button, try to extract from href e.g. policy-document-view/5
            if (!policyId) {
                var href = $btn.attr('href') || '';
                var match = href.match(/policy-document-view\/(\d+)/);
                if (match) {
                    policyId = match[1];
                }
            }

            if (!policyId) {
                // Check closest row or parent container for data-id
                var rowId = $btn.closest('tr').data('id') || $btn.closest('[data-policy-id]').data('policy-id');
                if (rowId) {
                    policyId = rowId;
                }
            }

            if ($btn.data('has-doc') == "0" && !policyId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Document Found',
                    text: 'This policy does not have any document uploaded.',
                    confirmButtonColor: '#d33'
                });
                return false;
            }

            var $modal = $('#policyDocumentsViewModal');
            var $list = $('#policyDocumentsViewList');
            var $spinner = $('#policyDocsLoadingSpinner');

            $list.empty().hide();
            $spinner.show();
            $modal.modal('show');

            $.ajax({
                url: "<?= base_url('get-policy-documents'); ?>",
                method: "GET",
                data: {
                    policy_id: policyId
                },
                success: function(res) {
                    $spinner.hide();
                    $list.empty().show();

                    if (res.status === 'success' && Array.isArray(res.documents) && res.documents.length > 0) {
                        res.documents.forEach(function(doc, idx) {
                            var item = $('<li class="list-group-item d-flex justify-content-between align-items-center gap-3 py-3 border-bottom"></li>');
                            
                            var details = $('<div class="overflow-hidden flex-grow-1"></div>');
                            
                            var badgeClass = 'bg-secondary';
                            if (doc.type === 'renewal') {
                                badgeClass = 'bg-info text-white';
                            } else if (doc.type === 'policy') {
                                badgeClass = 'bg-primary';
                            } else if (doc.type === 'endorsement') {
                                badgeClass = 'bg-warning text-dark';
                            }
                            
                            var titleRow = $('<div class="d-flex align-items-center gap-2 mb-1 flex-wrap"></div>');
                            titleRow.append($('<span class="badge ' + badgeClass + '"></span>').text(doc.label || 'Policy Document'));
                            if (doc.policy_number) {
                                titleRow.append($('<span class="small text-muted fw-bold"></span>').text('Policy: ' + doc.policy_number));
                            }
                            details.append(titleRow);

                            details.append($('<div class="fw-semibold text-truncate text-dark mb-1"></div>').text(doc.name || 'document.pdf'));

                            var metaArr = [];
                            if (doc.uploaded_at) metaArr.push('<i class="fa fa-calendar me-1"></i>' + doc.uploaded_at);
                            if (doc.uploaded_by) metaArr.push('<i class="fa fa-user me-1"></i>' + doc.uploaded_by);
                            if (doc.comment) metaArr.push('<i class="fa fa-comment me-1"></i>' + doc.comment);
                            if (metaArr.length > 0) {
                                details.append($('<div class="small text-muted"></div>').html(metaArr.join(' &nbsp;·&nbsp; ')));
                            }

                            var actions = $('<div class="d-flex align-items-center gap-2 flex-shrink-0"></div>');
                            var viewBtn = $('<a class="btn btn-outline-danger btn-sm" target="_blank" rel="noopener noreferrer"><i class="fa fa-file-pdf me-1"></i>View PDF</a>');
                            viewBtn.attr('href', doc.url);
                            actions.append(viewBtn);

                            item.append(details, actions);
                            $list.append(item);
                        });
                    } else if (res.status === 'success' && res.document) {
                        var fileUrl = res.document;
                        var fileName = res.name || res.document.split('/').pop();
                        
                        var item = $('<li class="list-group-item d-flex justify-content-between align-items-center gap-3 py-3 border-bottom"></li>');
                        var details = $('<div class="overflow-hidden flex-grow-1"></div>');
                        details.append($('<span class="badge bg-primary mb-1">Policy PDF</span>'));
                        details.append($('<div class="fw-semibold text-truncate text-dark"></div>').text(fileName));
                        
                        var actions = $('<div class="d-flex align-items-center gap-2 flex-shrink-0"></div>');
                        var viewBtn = $('<a class="btn btn-outline-danger btn-sm" target="_blank" rel="noopener noreferrer"><i class="fa fa-file-pdf me-1"></i>View PDF</a>');
                        viewBtn.attr('href', fileUrl);
                        actions.append(viewBtn);

                        item.append(details, actions);
                        $list.append(item);
                    } else {
                        $list.append('<li class="list-group-item text-center text-muted py-4"><i class="fa fa-folder-open-o fa-2x mb-2 d-block"></i>No policy documents found for this policy.</li>');
                    }
                },
                error: function() {
                    $spinner.hide();
                    $list.empty().show().append('<li class="list-group-item text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i>Failed to load policy documents.</li>');
                }
            });

            return false;
        });

        // Apply CRM pagination styles to all DataTables on this page whenever they redraw
        $(document).on('draw.dt', function () {
            $('.dataTables_paginate .pagination').addClass('crm-pagination');
        });

        // ─── Single Policy Commission Override Modal ──────────────────────
        function recalculatePolicyCommissionModal() {
            var premium = parseFloat($('#modal_comm_premium_val').val()) || 0;
            var inType = $('#modal_pay_in_type').val();
            var inVal = parseFloat($('#modal_pay_in_value').val()) || 0;
            var outType = $('#modal_pay_out_type').val();
            var outVal = parseFloat($('#modal_pay_out_value').val()) || 0;
            var outBase = $('#modal_pay_out_based_on').val();

            var payInAmt = 0;
            if (inType === 'percentage') {
                payInAmt = (premium * inVal) / 100;
            } else {
                payInAmt = inVal;
            }

            var payOutAmt = 0;
            if (outType === 'percentage') {
                var base = (outBase === 'premium') ? premium : payInAmt;
                payOutAmt = (base * outVal) / 100;
            } else {
                payOutAmt = outVal;
            }

            var netProfit = Math.max(0, payInAmt - payOutAmt);

            $('#modal_display_pay_in').text('₹' + payInAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#modal_display_pay_out').text('₹' + payOutAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#modal_display_net_profit').text('₹' + netProfit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        $('#modal_pay_in_type, #modal_pay_out_type, #modal_pay_out_based_on').on('change', recalculatePolicyCommissionModal);
        $('#modal_pay_in_value, #modal_pay_out_value').on('input', recalculatePolicyCommissionModal);

        $(document).on('click', '.edit-policy-commission-btn', function (e) {
            e.preventDefault();
            var policyId = $(this).data('policy-id');
            var policyNum = $(this).data('policy-number') || policyId;

            $('#modal_comm_policy_id').val(policyId);
            $('#modal_comm_policy_subtitle').text('Policy #' + policyNum);

            // Fetch current commission data for this policy
            $.ajax({
                url: '/commission/get-policy-commission/' + policyId,
                type: 'GET',
                success: function (res) {
                    if (res.status === 'success') {
                        $('#modal_comm_premium_display').val('₹' + parseFloat(res.premium).toLocaleString(undefined, { minimumFractionDigits: 2 }));
                        $('#modal_comm_premium_val').val(res.premium);
                        $('#modal_comm_agent_name').val(res.agent_name + ' (' + res.company_name + ')');

                        $('#modal_pay_in_type').val(res.pay_in_type);
                        $('#modal_pay_in_value').val(res.pay_in_value);
                        $('#modal_pay_out_type').val(res.pay_out_type);
                        $('#modal_pay_out_value').val(res.pay_out_value);
                        $('#modal_pay_out_based_on').val(res.pay_out_based_on);

                        recalculatePolicyCommissionModal();
                        $('#policyCommissionModal').modal('show');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Unable to load commission details for this policy.', 'error');
                }
            });
        });

        $('#policyCommissionForm').on('submit', function (e) {
            e.preventDefault();
            var btn = $('#savePolicyCommissionBtn');
            var origText = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.ajax({
                url: '/commission/save-policy-commission',
                type: 'POST',
                data: $(this).serialize(),
                success: function (res) {
                    btn.prop('disabled', false).html(origText);
                    $('#policyCommissionModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: res.message || 'Policy commission successfully updated!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html(origText);
                    var msg = 'Failed to save commission.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    </script>
    @endpush
@endsection
