@extends('layouts.app')

@section('page_title', 'Settings')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/settings.css' : '/css/settings.css') }}?v={{ filemtime(public_path('css/settings.css')) }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        #tutorialVideosTable_wrapper .dataTables_length,
        #tutorialVideosTable_wrapper .dataTables_filter,
        #companyMasterCommissionTable_wrapper .dataTables_length,
        #companyMasterCommissionTable_wrapper .dataTables_filter,
        #categoryMasterCommissionTable_wrapper .dataTables_length,
        #categoryMasterCommissionTable_wrapper .dataTables_filter,
        #agentMasterCommissionTable_wrapper .dataTables_length,
        #agentMasterCommissionTable_wrapper .dataTables_filter,
        #policyMasterCommissionTable_wrapper .dataTables_length,
        #policyMasterCommissionTable_wrapper .dataTables_filter {
            padding: 1rem 1.25rem 0.5rem 1.25rem;
        }
        #tutorialVideosTable_wrapper .dataTables_info,
        #tutorialVideosTable_wrapper .dataTables_paginate,
        #companyMasterCommissionTable_wrapper .dataTables_info,
        #companyMasterCommissionTable_wrapper .dataTables_paginate,
        #categoryMasterCommissionTable_wrapper .dataTables_info,
        #categoryMasterCommissionTable_wrapper .dataTables_paginate,
        #agentMasterCommissionTable_wrapper .dataTables_info,
        #agentMasterCommissionTable_wrapper .dataTables_paginate,
        #policyMasterCommissionTable_wrapper .dataTables_info,
        #policyMasterCommissionTable_wrapper .dataTables_paginate {
            padding: 0.5rem 1.25rem 1rem 1.25rem;
        }
    </style>
@endpush

@section('content')
    @php
        $logoPath = $settings['company_logo_path']->value ?? null;
        $logoUrl = $logoPath && Storage::disk('public')->exists($logoPath)
            ? asset($logoPath)
            : (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/images/template/crmfavicon.png' : '/images/template/crmfavicon.png');
        $whatsappEnabled = ($settings['whatsapp_integration_enabled']->value ?? '0') === '1';
        $socialIntegrationsEnabled = ($settings['social_integrations_enabled']->value ?? '0') === '1';
        $googleConnectionEnabled = ($settings['google_connection_enabled']->value ?? '0') === '1';
        $commissionMethod = $settings['commission_calculation_method']->value ?? 'company_wise';
        $commissionAllowManual = ($settings['commission_allow_manual_override']->value ?? '1') === '1';
    @endphp

    <div class="container-fluid px-0 settings-shell">
        <div class="d-flex justify-content-between align-items-center mb-3 settings-page-head">
            <div>
                <h1 class="h4 mb-1">Settings</h1>
                <p class="text-muted small mb-0">Manage SMTP, commission, and integration preferences.</p>
            </div>
        </div>

        <div class="settings-tabs-wrap">
            <ul class="nav settings-main-tabs flex-wrap" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#commission-settings" type="button" role="tab"><i class="fa-solid fa-percent me-1 text-primary"></i> Commission Settings</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#smtp" type="button" role="tab">Email SMTP</button></li>
                {{-- WhatsApp configuration is intentionally hidden.
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#whatsapp-configure" type="button" role="tab">WhatsApp Configure Settings</button>
                </li>
                --}}
                @unless($isBasicPlan)
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#integrations" type="button" role="tab">Integrations</button></li>
                @endunless
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#google-connect" type="button" role="tab">Google Connect</button></li>
                @if(false /* Feature disabled: auth()->id() === 1 || auth()->user()->email === 'harsing.fablead@gmail.com' */)
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#tutorials" type="button" role="tab">Tutorials</button></li>
                @endif
            </ul>
        </div>

        <div class="tab-content">
            <!-- COMMISSION SETTINGS TAB -->
            <div class="tab-pane fade show active" id="commission-settings" role="tabpanel">
                <form action="{{ route('settings.update') }}" method="POST" id="commissionSettingsForm">
                    @csrf
                    @method('PUT')
                    <div class="settings-panel">
                        <div class="settings-panel-head d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-coins me-2 text-primary"></i> Commission Calculation & Priority Method</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-1 px-2">Dynamic Engine</span>
                        </div>
                        <div class="settings-panel-body">
                            <p class="text-muted small mb-4">
                                Choose how the system applies commissions by default across <strong>Pay-In (Admin earnings)</strong> and <strong>Pay-Out (Sub-Agent commissions)</strong>. The selected method is dynamically applied on policy creation, syncing, and dashboard analytics.
                            </p>

                            <div class="row g-4 mb-4">
                                <!-- Option 1: Company-wise (Default) -->
                                <div class="col-md-6">
                                    <label class="commission-opt-card card h-100 p-3 border rounded-4 cursor-pointer position-relative shadow-sm {{ $commissionMethod === 'company_wise' ? 'border-primary bg-light-primary' : 'border-light-subtle' }}" style="cursor: pointer; transition: all 0.2s ease;">
                                        <div class="d-flex align-items-start gap-3">
                                            <input type="radio" name="commission_calculation_method" value="company_wise" class="form-check-input mt-1" {{ $commissionMethod === 'company_wise' ? 'checked' : '' }} required>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="mb-0 fw-bold text-dark">1. Company-wise Commission</h6>
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 small">Recommended Default</span>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    Commissions are configured per <strong>Insurance Company</strong> (e.g. ICICI Lombard 20% Pay-In, HDFC 22%, Star Health 25%).
                                                </p>
                                                <span class="badge bg-white text-secondary border small">Admin sees earnings per insurer directly</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Option 2: Category/Type-wise -->
                                <div class="col-md-6">
                                    <label class="commission-opt-card card h-100 p-3 border rounded-4 cursor-pointer position-relative shadow-sm {{ $commissionMethod === 'category_wise' ? 'border-primary bg-light-primary' : 'border-light-subtle' }}" style="cursor: pointer; transition: all 0.2s ease;">
                                        <div class="d-flex align-items-start gap-3">
                                            <input type="radio" name="commission_calculation_method" value="category_wise" class="form-check-input mt-1" {{ $commissionMethod === 'category_wise' ? 'checked' : '' }}>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="mb-0 fw-bold text-dark">2. Category/Type-wise Commission</h6>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    Commissions are configured per <strong>Insurance Category / Policy Type</strong> (e.g. Motor, Health, Life, Fire).
                                                </p>
                                                <span class="badge bg-white text-secondary border small">Uniform rates by product category</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Option 3: Sub Type Commission -->
                                <div class="col-md-6">
                                    <label class="commission-opt-card card h-100 p-3 border rounded-4 cursor-pointer position-relative shadow-sm {{ $commissionMethod === 'sub_type_wise' ? 'border-primary bg-light-primary' : 'border-light-subtle' }}" style="cursor: pointer; transition: all 0.2s ease;">
                                        <div class="d-flex align-items-start gap-3">
                                            <input type="radio" name="commission_calculation_method" value="sub_type_wise" class="form-check-input mt-1" {{ $commissionMethod === 'sub_type_wise' ? 'checked' : '' }}>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="mb-0 fw-bold text-dark">3. Sub Type Commission</h6>
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 small">Sub-Product Specific</span>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    Commissions are configured specifically per <strong>Policy Sub Type</strong> (e.g. Comprehensive, Third Party, Term, Individual Health).
                                                </p>
                                                <span class="badge bg-white text-secondary border small">Dedicated Sub-Type rate master</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Option 4: Agent / Team Member-wise -->
                                <div class="col-md-6">
                                    <label class="commission-opt-card card h-100 p-3 border rounded-4 cursor-pointer position-relative shadow-sm {{ $commissionMethod === 'agent_wise' ? 'border-primary bg-light-primary' : 'border-light-subtle' }}" style="cursor: pointer; transition: all 0.2s ease;">
                                        <div class="d-flex align-items-start gap-3">
                                            <input type="radio" name="commission_calculation_method" value="agent_wise" class="form-check-input mt-1" {{ $commissionMethod === 'agent_wise' ? 'checked' : '' }}>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="mb-0 fw-bold text-dark">4. Agent / Team Member-wise Commission</h6>
                                                    <span class="badge bg-purple-subtle text-purple border small" style="background-color: #f3e8ff; color: #6b21a8;">Sub-Agents & Staff</span>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    Commissions are configured per <strong>Sub-Agent or Staff Member</strong>. Each agent gets their own custom Pay-In & Pay-Out rate across all bookings.
                                                </p>
                                                <span class="badge bg-white text-secondary border small">Individual agent rate master</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Option 5: Policy-wise -->
                                <div class="col-md-6">
                                    <label class="commission-opt-card card h-100 p-3 border rounded-4 cursor-pointer position-relative shadow-sm {{ $commissionMethod === 'policy_wise' ? 'border-primary bg-light-primary' : 'border-light-subtle' }}" style="cursor: pointer; transition: all 0.2s ease;">
                                        <div class="d-flex align-items-start gap-3">
                                            <input type="radio" name="commission_calculation_method" value="policy_wise" class="form-check-input mt-1" {{ $commissionMethod === 'policy_wise' ? 'checked' : '' }}>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="mb-0 fw-bold text-dark">5. Policy-wise Commission</h6>
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    Rules require selecting a specific <strong>Individual Policy</strong>. Each policy gets its own custom rule rate.
                                                </p>
                                                <span class="badge bg-white text-secondary border small">Micro-level rule assignment</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Manual Override Permission Switch -->
                            <div class="p-3 bg-light rounded-4 border d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Enable Single-Transaction Manual Override</h6>
                                    <p class="text-muted small mb-0">Allow admin to open the <code>% Commission</code> modal on any policy in the policy list to tweak custom Pay-In & Pay-Out amounts whenever needed.</p>
                                </div>
                                <div class="form-check form-switch form-switch-md">
                                    <input type="hidden" name="commission_allow_manual_override" value="0">
                                    <input class="form-check-input" type="checkbox" name="commission_allow_manual_override" id="commission_allow_manual_override" value="1" {{ $commissionAllowManual ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4 pt-2">
                                <span id="commissionSettingsStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-dark-blue px-4 ms-auto"><i class="fa-solid fa-floppy-disk me-1"></i> Save Selected Method</button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- COMPANY-WISE COMMISSION MASTER MANAGER -->
                <div class="settings-panel mt-4 commission-method-section" id="section_company_wise" style="{{ $commissionMethod === 'company_wise' ? '' : 'display: none;' }}">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <i class="fa-solid fa-building-shield me-2 text-primary"></i>
                            <strong>Company-wise Commission Master</strong>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-2">{{ count($companiesWithCommission) }} Insurance Companies</span>
                        </div>
                        <div>
                            <span class="text-muted small">Manage separate Pay-In & Pay-Out rates per insurance company</span>
                        </div>
                    </div>
                    <div class="settings-panel-body p-0">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="text-dark fw-semibold"><i class="fa-solid fa-list-check text-primary me-2"></i> Configure individual Pay-In & Pay-Out rates per insurance company</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Master Settings</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="companyMasterCommissionTable" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-3">Insurance Company</th>
                                        <th class="py-3">Pay-In (Admin Share)</th>
                                        <th class="py-3">Pay-Out (Agent Share)</th>
                                        <th class="py-3">Calculation Basis</th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-3 py-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($companiesWithCommission as $compItem)
                                        <tr class="company-row" data-company-name="{{ strtolower($compItem['name']) }}">
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold" style="width: 36px; height: 36px;">
                                                        <i class="fa-solid fa-building"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block">{{ $compItem['name'] }}</strong>
                                                        <small class="text-muted">Updated: <span id="comp_updated_{{ $compItem['id'] }}">{{ $compItem['updated_at'] }}</span></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div id="comp_payin_badge_{{ $compItem['id'] }}">
                                                    @if($compItem['has_rule'])
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                            {{ $compItem['pay_in_type'] === 'percentage' ? number_format($compItem['pay_in_value'], 2) . '%' : '₹' . number_format($compItem['pay_in_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Not Configured</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Admin from Insurer</small>
                                            </td>
                                            <td>
                                                <div id="comp_payout_badge_{{ $compItem['id'] }}">
                                                    @if($compItem['has_rule'])
                                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                                            {{ $compItem['pay_out_type'] === 'percentage' ? number_format($compItem['pay_out_value'], 2) . '%' : '₹' . number_format($compItem['pay_out_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Not Configured</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Disbursed to Agent</small>
                                            </td>
                                            <td id="comp_basis_{{ $compItem['id'] }}">
                                                <span class="badge bg-light text-secondary border">
                                                    {{ $compItem['pay_out_based_on'] === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In' }}
                                                </span>
                                            </td>
                                            <td id="comp_status_{{ $compItem['id'] }}">
                                                @if($compItem['has_rule'] && $compItem['is_active'])
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end" id="comp_action_{{ $compItem['id'] }}">
                                                <button type="button" class="btn btn-sm {{ $compItem['has_rule'] ? 'btn-outline-primary' : 'btn-primary' }} px-3 open-company-commission-modal-btn" data-company-id="{{ $compItem['id'] }}">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> {{ $compItem['has_rule'] ? 'Edit Rate' : 'Set Commission' }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY / TYPE-WISE COMMISSION MASTER MANAGER -->
                <div class="settings-panel mt-4 commission-method-section" id="section_category_wise" style="{{ $commissionMethod === 'category_wise' ? '' : 'display: none;' }}">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <i class="fa-solid fa-layer-group me-2 text-warning"></i>
                            <strong>Category / Type-wise Commission Master</strong>
                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 ms-2">{{ count($categoriesWithCommission) }} Insurance Categories</span>
                        </div>
                        <div>
                            <span class="text-muted small">Manage separate Pay-In & Pay-Out rates per category (Motor, Health, Life, etc.)</span>
                        </div>
                    </div>
                    <div class="settings-panel-body p-0">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="text-dark fw-semibold"><i class="fa-solid fa-shapes text-warning me-2"></i> Configure uniform rates applied across each insurance product category</span>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1">Category Master</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="categoryMasterCommissionTable" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-3">Category / Policy Type</th>
                                        <th class="py-3">Pay-In (Admin Share)</th>
                                        <th class="py-3">Pay-Out (Agent Share)</th>
                                        <th class="py-3">Calculation Basis</th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-3 py-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoriesWithCommission as $catItem)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-dark fw-bold" style="width: 36px; height: 36px;">
                                                        <i class="fa-solid fa-folder-open text-warning"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block">{{ $catItem['name'] }}</strong>
                                                        <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                            <small class="text-muted me-1">Sub Types ({{ $catItem['sub_types_count'] }}):</small>
                                                            @forelse($catItem['sub_types'] as $subItem)
                                                                <button type="button" 
                                                                    class="badge bg-light text-dark border px-2 py-1 open-category-commission-modal-btn border-1" 
                                                                    style="cursor: pointer; font-size: 0.72rem;"
                                                                    data-category-id="{{ $catItem['id'] }}" 
                                                                    data-sub-type-id="{{ $subItem['id'] }}"
                                                                    title="Configure {{ $subItem['name'] }} rate">
                                                                    <i class="fa-solid fa-code-fork text-warning me-1"></i>{{ $subItem['name'] }}
                                                                    @if($subItem['has_rule'])
                                                                        <span class="text-success fw-bold ms-1">({{ $subItem['pay_out_type'] === 'percentage' ? number_format($subItem['pay_out_value'], 1) . '%' : '₹' . number_format($subItem['pay_out_value'], 0) }})</span>
                                                                    @endif
                                                                </button>
                                                            @empty
                                                                <small class="text-muted fst-italic">None</small>
                                                            @endforelse
                                                        </div>
                                                        <small class="text-muted d-block mt-1">Updated: <span id="cat_updated_{{ $catItem['id'] }}">{{ $catItem['updated_at'] }}</span></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div id="cat_payin_badge_{{ $catItem['id'] }}">
                                                    @if($catItem['has_rule'])
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                            {{ $catItem['pay_in_type'] === 'percentage' ? number_format($catItem['pay_in_value'], 2) . '%' : '₹' . number_format($catItem['pay_in_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Not Configured</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Admin from Insurer</small>
                                            </td>
                                            <td>
                                                <div id="cat_payout_badge_{{ $catItem['id'] }}">
                                                    @if($catItem['has_rule'])
                                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                                            {{ $catItem['pay_out_type'] === 'percentage' ? number_format($catItem['pay_out_value'], 2) . '%' : '₹' . number_format($catItem['pay_out_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Not Configured</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Disbursed to Agent</small>
                                            </td>
                                            <td id="cat_basis_{{ $catItem['id'] }}">
                                                <span class="badge bg-light text-secondary border">
                                                    {{ $catItem['pay_out_based_on'] === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In' }}
                                                </span>
                                            </td>
                                            <td id="cat_status_{{ $catItem['id'] }}">
                                                @if($catItem['has_rule'] && $catItem['is_active'])
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end" id="cat_action_{{ $catItem['id'] }}">
                                                <button type="button" class="btn btn-sm {{ $catItem['has_rule'] ? 'btn-outline-warning text-dark' : 'btn-warning text-dark' }} px-3 open-category-commission-modal-btn" data-category-id="{{ $catItem['id'] }}" data-sub-type-id="">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> {{ $catItem['has_rule'] ? 'Edit Rate' : 'Set Commission' }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUB TYPE-WISE COMMISSION MASTER MANAGER -->
                <div class="settings-panel mt-4 commission-method-section" id="section_sub_type_wise" style="{{ $commissionMethod === 'sub_type_wise' ? '' : 'display: none;' }}">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <i class="fa-solid fa-code-fork me-2 text-warning"></i>
                            <strong>Sub Type Commission Master</strong>
                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 ms-2">{{ count($subTypesWithCommission) }} Policy Sub Types</span>
                        </div>
                        <div>
                            <span class="text-muted small">Manage specific Pay-In & Pay-Out rates per Policy Sub Type (Comprehensive, Third Party, Term, etc.)</span>
                        </div>
                    </div>
                    <div class="settings-panel-body p-0">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="text-dark fw-semibold"><i class="fa-solid fa-shapes text-warning me-2"></i> Configure rates per individual policy sub type</span>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1">Sub Type Master</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="subTypeMasterCommissionTable" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-3">Policy Sub Type</th>
                                        <th class="py-3">Parent Category</th>
                                        <th class="py-3">Pay-In (Admin Share)</th>
                                        <th class="py-3">Pay-Out (Agent Share)</th>
                                        <th class="py-3">Calculation Basis</th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-3 py-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subTypesWithCommission as $stItem)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-dark fw-bold" style="width: 36px; height: 36px;">
                                                        <i class="fa-solid fa-code-fork text-warning"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block">{{ $stItem['name'] }}</strong>
                                                        <small class="text-muted">Updated: <span id="st_updated_{{ $stItem['id'] }}">{{ $stItem['updated_at'] }}</span></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fa-solid fa-folder me-1 text-secondary"></i> {{ $stItem['category_name'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div id="st_payin_badge_{{ $stItem['id'] }}">
                                                    @if($stItem['has_rule'])
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                            {{ $stItem['pay_in_type'] === 'percentage' ? number_format($stItem['pay_in_value'], 2) . '%' : '₹' . number_format($stItem['pay_in_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Not Configured</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Admin from Insurer</small>
                                            </td>
                                            <td>
                                                <div id="st_payout_badge_{{ $stItem['id'] }}">
                                                    @if($stItem['has_rule'])
                                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                                            {{ $stItem['pay_out_type'] === 'percentage' ? number_format($stItem['pay_out_value'], 2) . '%' : '₹' . number_format($stItem['pay_out_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Not Configured</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Disbursed to Agent</small>
                                            </td>
                                            <td id="st_basis_{{ $stItem['id'] }}">
                                                <span class="badge bg-light text-secondary border">
                                                    {{ $stItem['pay_out_based_on'] === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In' }}
                                                </span>
                                            </td>
                                            <td id="st_status_{{ $stItem['id'] }}">
                                                @if($stItem['has_rule'] && $stItem['is_active'])
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end" id="st_action_{{ $stItem['id'] }}">
                                                <button type="button" class="btn btn-sm {{ $stItem['has_rule'] ? 'btn-outline-warning text-dark' : 'btn-warning text-dark' }} px-3 open-category-commission-modal-btn" data-category-id="{{ $stItem['category_id'] }}" data-sub-type-id="{{ $stItem['id'] }}">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> {{ $stItem['has_rule'] ? 'Edit Rate' : 'Set Commission' }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- AGENT / TEAM MEMBER-WISE COMMISSION MASTER MANAGER -->
                <div class="card border-0 rounded-4 shadow-sm mb-4 mt-4 commission-method-section" id="section_agent_wise" style="{{ $commissionMethod === 'agent_wise' ? '' : 'display: none;' }}">
                    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <strong class="fs-6 text-dark"><i class="fa-solid fa-users text-teal me-2" style="color: #0d9488;"></i>Agent & Team Member-wise Commission Master</strong>
                            <span class="badge bg-opacity-10 border ms-2" style="background-color: rgba(13, 148, 136, 0.1); color: #0d9488; border-color: rgba(13, 148, 136, 0.25) !important;">{{ count($agentsWithCommission) }} Members</span>
                            <p class="text-muted small mb-0 mt-1">Configure customized default Pay-In & Pay-Out commissions for specific Sub-Agents and Staff Team Members.</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="agentMasterCommissionTable" style="font-size: 0.9rem;">
                                <thead class="table-light text-uppercase text-secondary" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                                    <tr>
                                        <th class="ps-3 py-3">Member & Role</th>
                                        <th class="py-3">Pay-In (Admin Share)</th>
                                        <th class="py-3">Pay-Out (Agent Share)</th>
                                        <th class="py-3">Calculation Basis</th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-3 py-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($agentsWithCommission as $agentItem)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; background-color: rgba(13, 148, 136, 0.1); color: #0d9488;">
                                                        <i class="fa-solid {{ $agentItem['type'] === 'sub_agents' ? 'fa-user-tie' : 'fa-id-badge' }}"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block">{{ $agentItem['name'] }}</strong>
                                                        <small class="text-muted d-block">{{ $agentItem['email'] }}</small>
                                                        <span class="badge rounded-pill {{ $agentItem['type'] === 'sub_agents' ? 'bg-primary-subtle text-primary border' : 'bg-secondary-subtle text-dark border' }}" style="font-size: 0.7rem;">
                                                            {{ $agentItem['type_label'] }}
                                                        </span>
                                                        <small class="text-muted ms-1">Updated: <span id="agent_updated_{{ $agentItem['id'] }}">{{ $agentItem['updated_at'] }}</span></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div id="agent_payin_badge_{{ $agentItem['id'] }}">
                                                    @if($agentItem['has_rule'])
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                            {{ $agentItem['pay_in_type'] === 'percentage' ? number_format($agentItem['pay_in_value'], 2) . '%' : '₹' . number_format($agentItem['pay_in_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Default / Global</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Admin from Insurer</small>
                                            </td>
                                            <td>
                                                <div id="agent_payout_badge_{{ $agentItem['id'] }}">
                                                    @if($agentItem['has_rule'])
                                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                                            {{ $agentItem['pay_out_type'] === 'percentage' ? number_format($agentItem['pay_out_value'], 2) . '%' : '₹' . number_format($agentItem['pay_out_value'], 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">Default / Global</span>
                                                    @endif
                                                </div>
                                                <small class="d-block text-muted">Disbursed to Member</small>
                                            </td>
                                            <td id="agent_basis_{{ $agentItem['id'] }}">
                                                <span class="badge bg-light text-secondary border">
                                                    {{ $agentItem['pay_out_based_on'] === 'premium' ? '% of Gross Premium' : '% of Admin Pay-In' }}
                                                </span>
                                            </td>
                                            <td id="agent_status_{{ $agentItem['id'] }}">
                                                @if($agentItem['has_rule'] && $agentItem['is_active'])
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end" id="agent_action_{{ $agentItem['id'] }}">
                                                <button type="button" class="btn btn-sm {{ $agentItem['has_rule'] ? 'btn-outline-dark' : 'btn-dark' }} px-3 open-agent-commission-modal-btn" data-user-id="{{ $agentItem['id'] }}">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> {{ $agentItem['has_rule'] ? 'Edit Rate' : 'Set Commission' }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- POLICY-WISE COMMISSION MASTER MANAGER -->
                <div class="card border-0 rounded-4 shadow-sm mb-4 mt-4 commission-method-section" id="section_policy_wise" style="{{ $commissionMethod === 'policy_wise' ? '' : 'display: none;' }}">
                    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <strong class="fs-6 text-dark"><i class="fa-solid fa-file-invoice text-info me-2"></i>Policy-wise Commission Master</strong>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 ms-2">{{ count($policiesWithCommission) }} Policies</span>
                            <p class="text-muted small mb-0 mt-1">Configure customized Pay-In (Admin Share) & Pay-Out (Sub-Agent Share) per individual policy transaction.</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="policyMasterCommissionTable" style="font-size: 0.9rem;">
                                <thead class="table-light text-uppercase text-secondary" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                                    <tr>
                                        <th class="ps-4">Policy # / Holder</th>
                                        <th>Company & Category</th>
                                        <th>Agent / Assigned</th>
                                        <th>Gross Premium</th>
                                        <th>Pay-In (Admin)</th>
                                        <th>Pay-Out (Agent)</th>
                                        <th>Net Admin Profit</th>
                                        <th>Status</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($policiesWithCommission as $polItem)
                                        <tr id="policy_comm_row_{{ $polItem['id'] }}">
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">{{ $polItem['policy_number'] }}</div>
                                                <small class="text-muted"><i class="fa fa-user me-1"></i>{{ $polItem['holder_name'] }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $polItem['company_name'] }}</div>
                                                <small class="badge bg-secondary-subtle text-secondary border">{{ $polItem['category_name'] }}</small>
                                            </td>
                                            <td>
                                                <span class="fw-medium text-dark">{{ $polItem['agent_name'] }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark">₹{{ number_format($polItem['premium'], 2) }}</span>
                                            </td>
                                            <td id="pol_payin_badge_{{ $polItem['id'] }}">
                                                @if($polItem['pay_in_amount'] > 0 || $polItem['pay_in_value'] > 0)
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                        ₹{{ number_format($polItem['pay_in_amount'], 2) }}
                                                    </span>
                                                    @if($polItem['pay_in_value'] > 0)
                                                        <small class="d-block text-muted">{{ $polItem['pay_in_value'] }}{{ $polItem['pay_in_type'] === 'percentage' ? '%' : ' Flat' }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-light text-muted border">Default</span>
                                                @endif
                                            </td>
                                            <td id="pol_payout_badge_{{ $polItem['id'] }}">
                                                @if($polItem['pay_out_amount'] > 0 || $polItem['pay_out_value'] > 0)
                                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                                        ₹{{ number_format($polItem['pay_out_amount'], 2) }}
                                                    </span>
                                                    @if($polItem['pay_out_value'] > 0)
                                                        <small class="d-block text-muted">{{ $polItem['pay_out_value'] }}{{ $polItem['pay_out_type'] === 'percentage' ? '%' : ' Flat' }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-light text-muted border">Default</span>
                                                @endif
                                            </td>
                                            <td id="pol_profit_badge_{{ $polItem['id'] }}">
                                                @if($polItem['profit'] > 0)
                                                    <span class="badge bg-emerald-subtle text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                                        ₹{{ number_format($polItem['profit'], 2) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-secondary border">₹0.00</span>
                                                @endif
                                            </td>
                                            <td id="pol_status_{{ $polItem['id'] }}">
                                                @if($polItem['is_manual_override'])
                                                    <span class="badge bg-purple-subtle text-purple border" style="background-color: #f3e8ff; color: #6b21a8;"><i class="fa fa-sliders me-1"></i> Custom Override</span>
                                                @elseif($polItem['has_rule'])
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border">Default / System</span>
                                                @endif
                                            </td>
                                            <td class="pe-4 text-end" id="pol_action_{{ $polItem['id'] }}">
                                                <button type="button" class="btn btn-sm {{ $polItem['has_rule'] ? 'btn-outline-info text-dark' : 'btn-info text-white' }} px-3 open-policy-commission-settings-btn" data-policy-id="{{ $polItem['id'] }}" data-policy-number="{{ $polItem['policy_number'] }}">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i> {{ $polItem['has_rule'] ? 'Edit Rate' : 'Set Commission' }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- EMAIL SMTP TAB -->
            <div class="tab-pane fade" id="smtp" role="tabpanel">
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data"
                    id="smtpSettingsForm">
                    @csrf
                    @method('PUT')
                    <div class="settings-panel">
                        <div class="settings-panel-head">Email SMTP Settings</div>
                        <div class="settings-panel-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">SMTP Host</label><input type="text"
                                        name="mail_host" class="form-control"
                                        value="{{ $settings['mail_host']->value ?? '' }}" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-6"><label class="form-label">SMTP Port</label><input type="number"
                                        name="mail_port" class="form-control"
                                        value="{{ $settings['mail_port']->value ?? '587' }}" placeholder="587"></div>
                                <div class="col-md-6"><label class="form-label">SMTP Username</label><input type="email"
                                        name="mail_username" class="form-control"
                                        value="{{ $settings['mail_username']->value ?? '' }}" placeholder="you@gmail.com">
                                </div>
                                <div class="col-md-6"><label class="form-label">SMTP Password</label><input type="password"
                                        name="mail_password" class="form-control"
                                        value="" autocomplete="new-password"
                                        placeholder="*Leave blank to keep the password unchanged.">
                                </div>
                                <div class="col-md-6"><label class="form-label">Encryption</label><select
                                        name="mail_encryption" class="form-select">
                                        <option value="tls" {{ ($settings['mail_encryption']->value ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ ($settings['mail_encryption']->value ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="" {{ ($settings['mail_encryption']->value ?? '') == '' ? 'selected' : '' }}>None</option>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label">From Name</label><input type="text"
                                        name="mail_from_name" class="form-control"
                                        value="{{ $settings['mail_from_name']->value ?? config('app.name', 'CRM') }}"
                                        placeholder="Company Name"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                                <span id="smtpSettingsStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-dark-blue settings-submit-btn">Save SMTP
                                    Settings</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            @if(false) {{-- WhatsApp configuration is intentionally hidden. --}}
            <div class="tab-pane fade" id="whatsapp-configure" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center gap-3">
                        <span>WhatsApp Configure Settings</span>
                        <form action="{{ route('settings.update') }}" method="POST" class="integration-feature-toggle">
                            @csrf @method('PUT')
                            <input type="hidden" name="whatsapp_integration_enabled" value="0">
                            <label class="settings-switch-label">
                                <span>{{ $whatsappEnabled ? 'Enabled' : 'Disabled' }}</span>
                                <input type="checkbox" name="whatsapp_integration_enabled" value="1"
                                    {{ $whatsappEnabled ? 'checked' : '' }}>
                                <span class="settings-switch" aria-hidden="true"></span>
                            </label>
                        </form>
                    </div>
                    <div class="settings-panel-body settings-feature-content {{ $whatsappEnabled ? '' : 'is-disabled' }}"
                        aria-disabled="{{ $whatsappEnabled ? 'false' : 'true' }}">
                        <div class="mb-4">
                            <div>
                                <h4 class="fw-bold mb-1" style="color:#33496d;">Configure your WhatsApp API settings <span
                                        class="settings-status-badge settings-inline-status"><span
                                            class="settings-status-dot"></span>Connected</span></h4>
                                <div class="text-muted">Enter your WhatsApp App details and WhatsApp Business credentials.
                                </div>
                            </div>
                        </div>

                        <ul class="nav settings-subtabs mb-4" id="waSettingsTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="wa-config-tab"
                                    data-bs-toggle="pill" data-bs-target="#wa-config-pane" type="button"
                                    role="tab">Configuration</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="wa-templates-tab"
                                    data-bs-toggle="pill" data-bs-target="#wa-templates-pane" type="button"
                                    role="tab">Message Templates</button></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="wa-config-pane" role="tabpanel">
                                <div class="alert alert-primary d-flex align-items-start mb-4">
                                    <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                                    <div><strong>Credentials are stored locally.</strong>
                                        <div>Use backend storage for production and update credentials whenever you rotate
                                            tokens.</div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="text-dark bi bi-app me-2"></i>WhatsApp App ID
                                            <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_app_id">
                                        <div class="invalid-feedback" id="wa_app_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i
                                                class="text-dark bi bi-shield-lock-fill me-2"></i>WhatsApp App Secret <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_app_secret">
                                        <div class="invalid-feedback" id="wa_app_secret_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="text-dark bi bi-telephone-fill me-2"></i>Phone
                                            Number ID <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_phone_number_id">
                                        <div class="invalid-feedback" id="wa_phone_number_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="text-dark bi bi-building-fill me-2"></i>WhatsApp
                                            Business Account ID <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_business_account_id">
                                        <div class="invalid-feedback" id="wa_business_account_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="text-dark bi bi-key-fill me-2"></i>Access Token
                                            <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_access_token">
                                        <div class="invalid-feedback" id="wa_access_token_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="text-dark bi bi-link-45deg me-2"></i>Webhook
                                            URL</label>
                                        <input type="text" class="form-control" id="wa_webhook_url">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4">
                                    <span class="small" id="wa_status_msg"></span>
                                    <button type="button" class="btn btn-dark-blue settings-submit-btn" id="wa_save_btn"><i
                                            class="bi bi-floppy-fill me-1"></i> Save Configuration</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="wa-templates-pane" role="tabpanel">
                                <div class="wa-templates-title">WhatsApp Message Templates</div>
                                <div class="wa-templates-subtitle">View and manage your WhatsApp message templates from
                                    WhatsApp. Stored in database; refresh to sync from API.</div>

                                <div class="wa-templates-toolbar">
                                    <div class="wa-templates-toolbar-left">
                                        <button type="button" class="btn btn-dark-blue wa-templates-refresh-btn"
                                            id="wa_templates_refresh"><i class="bi bi-arrow-repeat me-1"></i> Refresh
                                            Templates</button>
                                        <div class="wa-templates-search-wrap"><input type="text" class="form-control"
                                                id="wa_templates_search" placeholder="Search templates..."></div>
                                    </div>
                                    <div class="wa-templates-show"><span>Show</span><select id="wa_templates_show"
                                            class="form-select">
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select><span>entries</span></div>
                                </div>

                                <div class="table-responsive wa-templates-table-wrap">
                                    <table class="table align-middle mb-0" id="wa_templates_table"
                                        data-module-options='@json($whatsappModuleOptions)'>
                                        <thead class="table-light">
                                            <tr>
                                                <th>Templates</th>
                                                <th class="d-none d-md-table-cell" style="width: 30%;">Use For Module</th>
                                                <th class="d-none d-md-table-cell" style="width: 20%;">Status</th>
                                                <th class="d-none d-md-table-cell" style="width: 20%;">Active/Inactive</th>
                                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($whatsappTemplates as $template)
                                                <tr data-template-row="main" data-template-id="{{ $template->id }}">
                                                    <td class="wa-templates-name">{{ $template->name }}</td>
                                                    <td class="d-none d-md-table-cell">
                                                        <select class="form-select form-select-sm wa-template-module-select"
                                                            data-template-id="{{ $template->id }}">
                                                            <option value="">-- Select --</option>
                                                            @foreach($whatsappModuleOptions as $key => $label)
                                                                <option value="{{ $key }}" {{ $template->use_for_module === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><span class="wa-templates-status-badge">{{ $template->status }}</span>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><select class="form-select form-select-sm wa-template-status-select"
                                                            data-template-id="{{ $template->id }}">
                                                            <option value="1" {{ $template->is_active ? 'selected' : '' }}>
                                                                Active</option>
                                                            <option value="0" {{ !$template->is_active ? 'selected' : '' }}>
                                                                Inactive</option>
                                                        </select></td>
                                                    <td class="text-center d-md-none">
                                                        <button type="button" class="btn-user-expand"
                                                            data-template-id="{{ $template->id }}">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="details-row d-md-none border-0" data-template-row="details"
                                                    id="wa-template-details-{{ $template->id }}" style="display: none;">
                                                    <td colspan="5" class="p-0">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-puzzle-piece"></i> Use For Module :</div>
                                                                    <div class="expand-value">
                                                                        <select class="form-select form-select-sm wa-template-module-select"
                                                                            data-template-id="{{ $template->id }}">
                                                                            <option value="">-- Select --</option>
                                                                            @foreach($whatsappModuleOptions as $key => $label)
                                                                                <option value="{{ $key }}" {{ $template->use_for_module === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                                                    <div class="expand-value"><span class="wa-templates-status-badge">{{ $template->status }}</span></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-toggle-on"></i> Active / Inactive :</div>
                                                                    <div class="expand-value">
                                                                        <select class="form-select form-select-sm wa-template-status-select"
                                                                            data-template-id="{{ $template->id }}">
                                                                            <option value="1" {{ $template->is_active ? 'selected' : '' }}>Active</option>
                                                                            <option value="0" {{ !$template->is_active ? 'selected' : '' }}>Inactive</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No WhatsApp message
                                                        templates found in database.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3"
                                    id="wa_templates_pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @unless($isBasicPlan)
            <div class="tab-pane fade" id="integrations" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <span>Integrations</span>
                        <div class="d-flex align-items-center">
                            <form action="{{ route('settings.update') }}" method="POST" class="integration-feature-toggle">
                                @csrf @method('PUT')
                                <input type="hidden" name="social_integrations_enabled" value="0">
                                <label class="settings-switch-label">
                                    <span>{{ $socialIntegrationsEnabled ? 'Enabled' : 'Disabled' }}</span>
                                    <input type="checkbox" name="social_integrations_enabled" value="1" {{ $socialIntegrationsEnabled ? 'checked' : '' }}>
                                    <span class="settings-switch" aria-hidden="true"></span>
                                </label>
                            </form>
                        </div>
                    </div>
                    <div class="settings-panel-body settings-feature-content {{ $socialIntegrationsEnabled ? '' : 'is-disabled' }}"
                        aria-disabled="{{ $socialIntegrationsEnabled ? 'false' : 'true' }}">
                        <div class="integrations-accordion" id="integrationsAccordion">
                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#facebookAdsPanel" aria-expanded="false"
                                    aria-controls="facebookAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon facebook"><i class="bi bi-facebook"></i></span>
                                        <span>Facebook Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="facebookAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy">This integration lets you connect your Facebook Ads
                                            account to manage campaigns and track conversions.</div>

                                        <div class="integration-action">
                                            <a href="https://www.facebook.com/login.php" target="_blank"
                                                rel="noopener noreferrer" class="btn btn-dark-blue settings-submit-btn"><i
                                                    class="bi bi-facebook me-1"></i> Connect with Facebook Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#googleAdsPanel" aria-expanded="false" aria-controls="googleAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon google">G</span>
                                        <span>Google Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="googleAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy mb-3">Connect your Google Ads workspace to sync
                                            campaigns, account IDs and conversion reporting.</div>
                                        <div class="d-flex justify-content-end">
                                            <a href="https://myaccount.google.com/" target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn crm-action-btn rounded-pill px-4">Connect Google Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#instagramAdsPanel" aria-expanded="false"
                                    aria-controls="instagramAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon instagram"><i class="bi bi-instagram"></i></span>
                                        <span>Instagram Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="instagramAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy mb-3">Connect Instagram Ads to manage campaign sources
                                            and social lead capture from a single place.</div>
                                        <div class="d-flex justify-content-end">
                                            <a href="https://www.instagram.com/accounts/login/" target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn crm-action-btn rounded-pill px-4">Connect Instagram Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endunless
            <div class="tab-pane fade" id="google-connect" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head d-flex justify-content-between align-items-center gap-3">
                        <span>Google Connect</span>
                        <form action="{{ route('settings.update') }}" method="POST" class="integration-feature-toggle">
                            @csrf @method('PUT')
                            <input type="hidden" name="google_connection_enabled" value="0">
                            <label class="settings-switch-label">
                                <span>{{ $googleConnectionEnabled ? 'Enabled' : 'Disabled' }}</span>
                                <input type="checkbox" name="google_connection_enabled" value="1"
                                    {{ $googleConnectionEnabled ? 'checked' : '' }}>
                                <span class="settings-switch" aria-hidden="true"></span>
                            </label>
                        </form>
                    </div>
                    <div class="settings-panel-body settings-feature-content {{ $googleConnectionEnabled ? '' : 'is-disabled' }}"
                        aria-disabled="{{ $googleConnectionEnabled ? 'false' : 'true' }}">
                        <div class="google-connect-setting-card">
                            <span class="integration-icon google">G</span>
                            <div>
                                <h5 class="mb-1">Google account connection</h5>
                                <p class="text-muted mb-0">Allow users to connect their Google account for Google Calendar features.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(false /* Feature disabled */)
            <div class="tab-pane fade" id="tutorials" role="tabpanel">
                <div class="settings-panel mb-4">
                    <div class="settings-panel-head">Add New Tutorial Video</div>
                    <div class="settings-panel-body">
                        <form action="{{ route('tutorial-videos.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" placeholder="Video Title" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <input type="text" name="description" class="form-control" placeholder="Short Description" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Video File (MP4, etc.) <span class="text-danger">*</span></label>
                                    <input type="file" name="video_file" class="form-control" accept="video/*" style="display: flex; align-items: center; height: 38px;" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn text-white fw-bold px-4" style="background-color: #123c73;">Save Video</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="settings-panel">
                    <div class="settings-panel-head">Existing Tutorial Videos</div>
                    <div class="settings-panel-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="tutorialVideosTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20%;">Title</th>
                                        <th style="width: 50%;">Description</th>
                                        <th style="width: 15%;">Video</th>
                                        <th style="width: 15%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tutorialVideos ?? [] as $video)
                                    <tr>
                                        <td class="fw-bold text-muted">{{ $video->title }}</td>
                                        <td class="text-muted small">{{ $video->description }}</td>
                                        <td>
                                            <a href="{{ asset('storage/' . $video->video_path) }}" target="_blank" class="text-dark fw-bold text-decoration-none">View Video</a>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editTutorialModal{{ $video->id }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTutorialModal{{ $video->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editTutorialModal{{ $video->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form action="{{ route('tutorial-videos.update', $video->id) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Tutorial Video</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-4">
                                                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                                                <input type="text" name="title" class="form-control" value="{{ $video->title }}" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                                                <input type="text" name="description" class="form-control" value="{{ $video->description }}" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Video File (MP4, etc.)</label>
                                                                <input type="file" name="video_file" class="form-control" accept="video/*" style="display: flex; align-items: center; height: 38px;">
                                                                <small class="text-muted" style="display: block; margin-top: 2px;">Leave empty to keep current video.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn text-white fw-bold" style="background-color: #123c73;">Update Video</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteTutorialModal{{ $video->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('tutorial-videos.destroy', $video->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center py-4">
                                                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                                                        <p class="mt-3 mb-0">Are you sure you want to delete this tutorial video?</p>
                                                    </div>
                                                    <div class="modal-footer border-0 justify-content-center">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No tutorial videos added yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- COMPANY-WISE COMMISSION MASTER MODAL -->
    <div class="modal fade" id="companyCommissionModal" tabindex="-1" aria-labelledby="companyCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="companyCommissionModalForm">
                    @csrf
                    <input type="hidden" name="company_id" id="modal_comp_id">
                    
                    <div class="modal-header bg-primary text-white py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-building-shield fs-4"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0" id="companyCommissionModalLabel">Set Company Commission</h5>
                                <small class="text-white opacity-75" id="modal_company_subtitle">Configure Pay-In and Pay-Out rates for this insurer</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4" style="background-color: #f8fafc;">
                        <!-- Company Highlight Header -->
                        <div class="p-3 bg-white rounded-4 border mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Insurance Company</span>
                                <h4 class="mb-0 fw-bold text-dark" id="modal_company_name">—</h4>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fw-semibold">Master Rate</span>
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
                                        <p class="text-muted small mb-3">Admin's commission received from the insurance company.</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Type <span class="text-danger">*</span></label>
                                                <select name="pay_in_type" id="modal_comp_pay_in_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹ Amount)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_in_value" id="modal_comp_pay_in_value" class="form-control" value="20" required>
                                                    <span class="input-group-text modal-payin-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-primary border-opacity-25">
                                            <small class="text-muted fw-semibold">Admin Rate</small>
                                            <div class="h4 fw-bold text-primary mb-0" id="modal_comp_preview_payin">20.00%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PAY-OUT (Agent / Sub-Agent Share) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-warning border-opacity-50 rounded-4 shadow-sm" style="background: #ffffff;">
                                    <div class="card-header bg-warning text-dark py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> PAY-OUT (Agent Share)</span>
                                        <span class="badge bg-dark text-white">To Sub-Agent</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="text-muted small mb-3">Commission share disbursed to the booking agent/staff.</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                                                <select name="pay_out_type" id="modal_comp_pay_out_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Base</label>
                                                <select name="pay_out_based_on" id="modal_comp_pay_out_based_on" class="form-select form-select-sm">
                                                    <option value="admin_commission">% of Pay-In</option>
                                                    <option value="premium">% of Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_out_value" id="modal_comp_pay_out_value" class="form-control" value="10" required>
                                                    <span class="input-group-text modal-payout-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-warning border-opacity-50">
                                            <small class="text-muted fw-semibold">Sub-Agent Share</small>
                                            <div class="h4 fw-bold text-dark mb-0" id="modal_comp_preview_payout">10.00%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LIVE PROFIT BANNER -->
                        <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Retained Earnings</h6>
                                <small class="text-secondary fw-semibold">Admin commission minus sub-agent payout</small>
                            </div>
                            <div class="h3 fw-bold mb-0" id="modal_comp_preview_profit" style="color: #047857 !important;">18.00%</div>
                        </div>

                        <!-- Active Switch -->
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="modal_comp_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="modal_comp_is_active">Rule is Active for all policies booked under this company</label>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue px-4" id="saveCompanyCommissionBtn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Company Commission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CATEGORY-WISE COMMISSION MASTER MODAL -->
    <div class="modal fade" id="categoryCommissionModal" tabindex="-1" aria-labelledby="categoryCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="categoryCommissionModalForm">
                    @csrf
                    <input type="hidden" name="policy_type_id" id="modal_cat_id">
                    
                    <div class="modal-header bg-warning text-dark py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-layer-group fs-4"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0" id="categoryCommissionModalLabel">Set Category Commission</h5>
                                <small class="text-dark opacity-75">Configure uniform Pay-In and Pay-Out rates for this insurance product category</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4" style="background-color: #f8fafc;">
                        <!-- Category & Sub Type Highlight Header -->
                        <div class="p-3 bg-white rounded-4 border mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Insurance Category / Product Type</span>
                                <h4 class="mb-0 fw-bold text-dark" id="modal_category_name">—</h4>
                            </div>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-3 py-2 fw-semibold">Category / Sub Type Master</span>
                        </div>

                        <!-- Policy Sub Type Selector -->
                        <div class="p-3 bg-white rounded-4 border mb-4">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="fa-solid fa-code-fork me-1 text-warning"></i> Policy Sub Type (Optional Specific Rate)
                            </label>
                            <p class="text-muted small mb-2">Leave as <strong>"All Sub Types (Default for Category)"</strong> to set a uniform rate, or select a specific <strong>Policy Sub Type</strong> (e.g. Comprehensive, Third Party) to apply custom rates.</p>
                            <select name="policy_sub_type_id" id="modal_cat_sub_type_id" class="form-select form-select-sm">
                                <option value="">All Sub Types (Default for Category)</option>
                            </select>
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
                                        <p class="text-muted small mb-3">Admin's commission received across companies for this category.</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Type <span class="text-danger">*</span></label>
                                                <select name="pay_in_type" id="modal_cat_pay_in_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹ Amount)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_in_value" id="modal_cat_pay_in_value" class="form-control" value="20" required>
                                                    <span class="input-group-text modal-cat-payin-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-primary border-opacity-25">
                                            <small class="text-muted fw-semibold">Admin Rate</small>
                                            <div class="h4 fw-bold text-primary mb-0" id="modal_cat_preview_payin">20.00%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PAY-OUT (Agent / Sub-Agent Share) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-warning border-opacity-50 rounded-4 shadow-sm" style="background: #ffffff;">
                                    <div class="card-header bg-warning text-dark py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> PAY-OUT (Agent Share)</span>
                                        <span class="badge bg-dark text-white">To Sub-Agent</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="text-muted small mb-3">Commission share disbursed to the booking agent/staff.</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                                                <select name="pay_out_type" id="modal_cat_pay_out_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Base</label>
                                                <select name="pay_out_based_on" id="modal_cat_pay_out_based_on" class="form-select form-select-sm">
                                                    <option value="admin_commission">% of Pay-In</option>
                                                    <option value="premium">% of Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_out_value" id="modal_cat_pay_out_value" class="form-control" value="10" required>
                                                    <span class="input-group-text modal-cat-payout-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-warning border-opacity-50">
                                            <small class="text-muted fw-semibold">Sub-Agent Share</small>
                                            <div class="h4 fw-bold text-dark mb-0" id="modal_cat_preview_payout">10.00%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LIVE PROFIT BANNER -->
                        <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Retained Earnings</h6>
                                <small class="text-secondary fw-semibold">Admin commission minus sub-agent payout</small>
                            </div>
                            <div class="h3 fw-bold mb-0" id="modal_cat_preview_profit" style="color: #047857 !important;">18.00%</div>
                        </div>

                        <!-- Active Switch -->
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="modal_cat_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="modal_cat_is_active">Rule is Active for all policies booked under this category</label>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue px-4" id="saveCategoryCommissionBtn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Category Commission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AGENT / TEAM MEMBER-WISE COMMISSION MASTER MODAL -->
    <div class="modal fade" id="agentCommissionModal" tabindex="-1" aria-labelledby="agentCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="agentCommissionModalForm">
                    @csrf
                    <input type="hidden" name="agent_id" id="modal_agent_id">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    
                    <div class="modal-header py-3 px-4 text-white" style="background-color: #0d9488;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-users fs-4"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-white" id="agentCommissionModalLabel">Set Member Commission Rate</h5>
                                <small class="text-white opacity-75">Configure personalized Pay-In & Pay-Out rates for this Sub-Agent or Staff member</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4" style="background-color: #f8fafc;">
                        <!-- Agent Highlight Header -->
                        <div class="p-3 bg-white rounded-4 border mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Agent / Team Member</span>
                                <h4 class="mb-0 fw-bold text-dark" id="modal_agent_name">—</h4>
                                <small class="text-muted" id="modal_agent_email">—</small>
                            </div>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold border" id="modal_agent_role_badge" style="background-color: rgba(13, 148, 136, 0.1); color: #0d9488;">Sub-Agent</span>
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
                                                <select name="pay_in_type" id="modal_agent_pay_in_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹ Amount)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Commission Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_in_value" id="modal_agent_pay_in_value" class="form-control" value="20" required>
                                                    <span class="input-group-text modal-agent-payin-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-primary border-opacity-25">
                                            <small class="text-muted fw-semibold">Admin Rate</small>
                                            <div class="h4 fw-bold text-primary mb-0" id="modal_agent_preview_payin">20.00%</div>
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
                                                <select name="pay_out_type" id="modal_agent_pay_out_type" class="form-select form-select-sm" required>
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Base</label>
                                                <select name="pay_out_based_on" id="modal_agent_pay_out_based_on" class="form-select form-select-sm">
                                                    <option value="admin_commission">% of Pay-In</option>
                                                    <option value="premium">% of Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_out_value" id="modal_agent_pay_out_value" class="form-control" value="10" required>
                                                    <span class="input-group-text modal-agent-payout-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border" style="border-color: rgba(13, 148, 136, 0.4) !important;">
                                            <small class="text-muted fw-semibold">Member Share</small>
                                            <div class="h4 fw-bold mb-0" id="modal_agent_preview_payout" style="color: #0d9488;">10.00%</div>
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
                            <div class="h3 fw-bold mb-0" id="modal_agent_preview_profit" style="color: #047857 !important;">18.00%</div>
                        </div>

                        <!-- Active Switch -->
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="modal_agent_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="modal_agent_is_active">Rule is Active for all policies booked by this member</label>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark px-4" id="saveAgentCommissionBtn" style="background-color: #0d9488; border-color: #0d9488;">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Member Commission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- POLICY-WISE COMMISSION MASTER MODAL -->
    <div class="modal fade" id="policyCommissionSettingsModal" tabindex="-1" aria-labelledby="policyCommissionSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <form id="policyCommissionSettingsModalForm">
                    @csrf
                    <input type="hidden" name="policy_id" id="modal_pol_settings_id">
                    <input type="hidden" id="modal_pol_settings_premium_val" value="0">

                    <div class="modal-header bg-info text-white py-3 px-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-invoice fs-4"></i>
                            <div>
                                <h5 class="modal-title fw-bold mb-0" id="policyCommissionSettingsModalLabel">Set Policy Commission</h5>
                                <small class="text-white opacity-75" id="modal_pol_settings_subtitle">Custom Pay-In & Pay-Out rates for this policy</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4" style="background-color: #f8fafc;">
                        <!-- Policy Details Card -->
                        <div class="p-3 bg-white rounded-4 border mb-4">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <span class="text-muted small text-uppercase fw-bold d-block">Policy Number</span>
                                    <h5 class="mb-0 fw-bold text-dark" id="modal_pol_settings_number">—</h5>
                                    <small class="text-muted" id="modal_pol_settings_holder">—</small>
                                </div>
                                <div class="col-md-4 border-start-md">
                                    <span class="text-muted small text-uppercase fw-bold d-block">Insurer & Agent</span>
                                    <div class="fw-semibold text-dark" id="modal_pol_settings_company">—</div>
                                    <small class="text-muted" id="modal_pol_settings_agent">—</small>
                                </div>
                                <div class="col-md-4 border-start-md text-md-end">
                                    <span class="text-muted small text-uppercase fw-bold d-block">Gross Premium</span>
                                    <div class="h4 fw-bold text-dark mb-0" id="modal_pol_settings_premium_disp">₹0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- 2-Column Split: Pay-In vs Pay-Out -->
                        <div class="row g-3 mb-4">
                            <!-- Pay-In Card (Admin Commission) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-2 rounded-4 shadow-sm" style="border-color: #10b981;">
                                    <div class="card-header bg-white border-0 pt-3 pb-0">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success text-white rounded-pill px-2 py-1"><i class="fa-solid fa-hand-holding-dollar"></i> Pay-In</span>
                                            <h6 class="mb-0 fw-bold text-dark">Admin Earnings</h6>
                                        </div>
                                        <small class="text-muted d-block mt-1">Admin's commission received from insurer.</small>
                                    </div>
                                    <div class="card-body pt-3">
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Type</label>
                                                <select name="pay_in_type" id="modal_pol_pay_in_type" class="form-select form-select-sm">
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_in_value" id="modal_pol_pay_in_value" class="form-control" value="20" required>
                                                    <span class="input-group-text modal-pol-payin-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-success border-opacity-50">
                                            <small class="text-muted fw-semibold">Calculated Pay-In Amount</small>
                                            <div class="h4 fw-bold text-success mb-0" id="modal_pol_preview_payin">₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pay-Out Card (Sub-Agent Commission) -->
                            <div class="col-md-6">
                                <div class="card h-100 border-2 rounded-4 shadow-sm" style="border-color: #f59e0b;">
                                    <div class="card-header bg-white border-0 pt-3 pb-0">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fa-solid fa-arrow-up-right"></i> Pay-Out</span>
                                            <h6 class="mb-0 fw-bold text-dark">Sub-Agent Share</h6>
                                        </div>
                                        <small class="text-muted d-block mt-1">Commission disbursed to the sub-agent.</small>
                                    </div>
                                    <div class="card-body pt-3">
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Type</label>
                                                <select name="pay_out_type" id="modal_pol_pay_out_type" class="form-select form-select-sm">
                                                    <option value="percentage">% (Percentage)</option>
                                                    <option value="flat">Fixed (₹)</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Base</label>
                                                <select name="pay_out_based_on" id="modal_pol_pay_out_based_on" class="form-select form-select-sm">
                                                    <option value="admin_commission">% of Pay-In</option>
                                                    <option value="premium">% of Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold">Value <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" name="pay_out_value" id="modal_pol_pay_out_value" class="form-control" value="10" required>
                                                    <span class="input-group-text modal-pol-payout-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded-3 text-center bg-light border border-warning border-opacity-50">
                                            <small class="text-muted fw-semibold">Calculated Pay-Out Amount</small>
                                            <div class="h4 fw-bold text-dark mb-0" id="modal_pol_preview_payout">₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LIVE PROFIT BANNER -->
                        <div class="p-3 rounded-4 d-flex justify-content-between align-items-center border border-success border-2 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #064e3b !important;">Admin Net Retained Earnings</h6>
                                <small class="text-secondary fw-semibold">Admin commission minus sub-agent payout</small>
                            </div>
                            <div class="h3 fw-bold mb-0" id="modal_pol_preview_profit" style="color: #047857 !important;">₹0.00</div>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-3 px-4 bg-white">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue px-4" id="savePolicyCommissionSettingsBtn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Policy Commission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
        <script>
            $(document).ready(function() {
                if($.fn.DataTable) {
                    $('#tutorialVideosTable').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 25, 50],
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search tutorials..."
                        },
                        "ordering": false
                    });

                    const companyTable = $('#companyMasterCommissionTable').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 20, 50, 100],
                        "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search insurance companies...",
                            "lengthMenu": "Show _MENU_ companies per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ companies",
                            "paginate": {
                                "first": "First",
                                "last": "Last",
                                "next": '<i class="fa fa-angle-right"></i>',
                                "previous": '<i class="fa fa-angle-left"></i>'
                            }
                        },
                        "ordering": false
                    });

                    // Initialize Category Master DataTable with pagination
                    $('#categoryMasterCommissionTable').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 20, 50],
                        "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search categories...",
                            "lengthMenu": "Show _MENU_ categories per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ categories",
                            "paginate": {
                                "first": "First",
                                "last": "Last",
                                "next": '<i class="fa fa-angle-right"></i>',
                                "previous": '<i class="fa fa-angle-left"></i>'
                            }
                        },
                        "ordering": false
                    });

                    // Initialize Sub Type Master DataTable with pagination
                    $('#subTypeMasterCommissionTable').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 20, 50, 100],
                        "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search policy sub types...",
                            "lengthMenu": "Show _MENU_ sub types per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ sub types",
                            "paginate": {
                                "first": "First",
                                "last": "Last",
                                "next": '<i class="fa fa-angle-right"></i>',
                                "previous": '<i class="fa fa-angle-left"></i>'
                            }
                        },
                        "ordering": false
                    });

                    // Initialize Policy Master DataTable with pagination
                    $('#policyMasterCommissionTable').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 20, 50, 100],
                        "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search policies, holders, companies...",
                            "lengthMenu": "Show _MENU_ policies per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ policies",
                            "paginate": {
                                "first": "First",
                                "last": "Last",
                                "next": '<i class="fa fa-angle-right"></i>',
                                "previous": '<i class="fa fa-angle-left"></i>'
                            }
                        },
                        "ordering": false
                    });

                    // Initialize Agent Master DataTable with pagination
                    $('#agentMasterCommissionTable').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 20, 50],
                        "dom": "<'d-flex justify-content-between align-items-center p-3 border-bottom'<'d-flex align-items-center'l><'d-flex'f>>t<'d-flex justify-content-between align-items-center p-3 border-top'ip>",
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search sub-agents, staff...",
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
                }

                // Dynamic Table Switching based on Commission Calculation Method
                function switchCommissionMethodTable(selectedMethod) {
                    // Update radio card styling
                    $('.commission-opt-card').removeClass('border-primary bg-light-primary').addClass('border-light-subtle');
                    $(`input[name="commission_calculation_method"][value="${selectedMethod}"]`)
                        .closest('.commission-opt-card')
                        .removeClass('border-light-subtle')
                        .addClass('border-primary bg-light-primary');

                    // Hide all commission method tables/sections
                    $('.commission-method-section').hide();

                    // Show only the selected method's section
                    const targetSection = $(`#section_${selectedMethod}`);
                    if (targetSection.length) {
                        targetSection.fadeIn(200, function() {
                            // Adjust DataTables column widths after becoming visible
                            if ($.fn.DataTable) {
                                targetSection.find('table.dataTable').each(function() {
                                    $(this).DataTable().columns.adjust().responsive?.recalc();
                                });
                            }
                        });
                    }
                }

                // Commission radio change event
                $('input[name="commission_calculation_method"]').on('change', function() {
                    const selectedMethod = $(this).val();
                    switchCommissionMethodTable(selectedMethod);

                    // Auto-save the selected method to backend immediately via AJAX
                    const form = $('#commissionSettingsForm');
                    const statusEl = $('#commissionSettingsStatus');
                    statusEl.removeClass('text-danger text-success').addClass('text-muted').html('<span class="spinner-border spinner-border-sm me-1"></span> Applying & saving method...');

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            statusEl.removeClass('text-muted text-danger').addClass('text-success').html('<i class="fa fa-circle-check me-1"></i> ' + (res.message || 'Method updated successfully.'));
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Commission method set to ' + selectedMethod.replace('_', ' ').toUpperCase());
                            }
                            setTimeout(function() {
                                statusEl.fadeOut(500, function() { $(this).text('').show(); });
                            }, 3000);
                        },
                        error: function(xhr) {
                            statusEl.removeClass('text-muted text-success').addClass('text-danger').html('<i class="fa fa-circle-exclamation me-1"></i> Failed to auto-save method.');
                        }
                    });
                });

                // Also handle manual save button submit on commissionSettingsForm
                $('#commissionSettingsForm').on('submit', function(e) {
                    e.preventDefault();
                    const btn = $(this).find('button[type="submit"]');
                    const origHtml = btn.html();
                    const statusEl = $('#commissionSettingsStatus');

                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');
                    statusEl.removeClass('text-danger text-success').addClass('text-muted').text('Saving settings...');

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            btn.prop('disabled', false).html(origHtml);
                            statusEl.removeClass('text-muted text-danger').addClass('text-success').html('<i class="fa fa-circle-check me-1"></i> Settings saved successfully.');
                            if (typeof toastr !== 'undefined') {
                                toastr.success(res.message || 'Settings saved successfully.');
                            } else {
                                alert(res.message || 'Settings saved successfully.');
                            }
                            setTimeout(function() {
                                statusEl.fadeOut(500, function() { $(this).text('').show(); });
                            }, 3500);
                        },
                        error: function(xhr) {
                            btn.prop('disabled', false).html(origHtml);
                            const msg = xhr.responseJSON?.message || 'Failed to save settings.';
                            statusEl.removeClass('text-muted text-success').addClass('text-danger').html('<i class="fa fa-circle-exclamation me-1"></i> ' + msg);
                        }
                    });
                });

                // Modal live formula preview re-calculation
                function recalculateCompanyModalSummary() {
                    const inType = $('#modal_comp_pay_in_type').val();
                    const inVal = parseFloat($('#modal_comp_pay_in_value').val()) || 0;
                    const outType = $('#modal_comp_pay_out_type').val();
                    const outVal = parseFloat($('#modal_comp_pay_out_value').val()) || 0;
                    const outBase = $('#modal_comp_pay_out_based_on').val();

                    $('.modal-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                    $('.modal-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                    if (inType === 'percentage') {
                        $('#modal_comp_preview_payin').text(inVal.toFixed(2) + '%');
                        if (outType === 'percentage') {
                            if (outBase === 'admin_commission') {
                                const agentEffective = (inVal * outVal) / 100;
                                const adminNet = Math.max(0, inVal - agentEffective);
                                $('#modal_comp_preview_payout').text(outVal.toFixed(2) + '% (' + agentEffective.toFixed(2) + '% eff.)');
                                $('#modal_comp_preview_profit').text(adminNet.toFixed(2) + '%');
                            } else {
                                const adminNet = Math.max(0, inVal - outVal);
                                $('#modal_comp_preview_payout').text(outVal.toFixed(2) + '% of Gross');
                                $('#modal_comp_preview_profit').text(adminNet.toFixed(2) + '%');
                            }
                        } else {
                            $('#modal_comp_preview_payout').text('₹' + outVal.toFixed(2));
                            $('#modal_comp_preview_profit').text(inVal.toFixed(2) + '% - ₹' + outVal.toFixed(2));
                        }
                    } else {
                        $('#modal_comp_preview_payin').text('₹' + inVal.toFixed(2));
                        if (outType === 'percentage') {
                            const agentAmt = (inVal * outVal) / 100;
                            const profit = Math.max(0, inVal - agentAmt);
                            $('#modal_comp_preview_payout').text('₹' + agentAmt.toFixed(2) + ' (' + outVal.toFixed(2) + '%)');
                            $('#modal_comp_preview_profit').text('₹' + profit.toFixed(2));
                        } else {
                            const profit = Math.max(0, inVal - outVal);
                            $('#modal_comp_preview_payout').text('₹' + outVal.toFixed(2));
                            $('#modal_comp_preview_profit').text('₹' + profit.toFixed(2));
                        }
                    }
                }

                $('#modal_comp_pay_in_type, #modal_comp_pay_out_type, #modal_comp_pay_out_based_on').on('change', recalculateCompanyModalSummary);
                $('#modal_comp_pay_in_value, #modal_comp_pay_out_value').on('input', recalculateCompanyModalSummary);

                // Open modal via AJAX
                $(document).on('click', '.open-company-commission-modal-btn', function() {
                    const compId = $(this).data('company-id');
                    const btn = $(this);
                    btn.prop('disabled', true);

                    fetch(`{{ url('/settings/company-commission') }}/${compId}`)
                        .then(r => r.json())
                        .then(data => {
                            btn.prop('disabled', false);
                            if (data.success) {
                                $('#modal_comp_id').val(data.company_id);
                                $('#modal_company_name').text(data.company_name);
                                $('#modal_comp_pay_in_type').val(data.pay_in_type);
                                $('#modal_comp_pay_in_value').val(data.pay_in_value);
                                $('#modal_comp_pay_out_type').val(data.pay_out_type);
                                $('#modal_comp_pay_out_value').val(data.pay_out_value);
                                $('#modal_comp_pay_out_based_on').val(data.pay_out_based_on);
                                $('#modal_comp_is_active').prop('checked', data.is_active);

                                recalculateCompanyModalSummary();
                                $('#companyCommissionModal').modal('show');
                            }
                        })
                        .catch(() => {
                            btn.prop('disabled', false);
                            alert('Unable to load company details. Please try again.');
                        });
                });

                // Submit company commission form via AJAX
                $('#companyCommissionModalForm').on('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = $('#saveCompanyCommissionBtn');
                    const origHtml = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');

                    $.ajax({
                        url: `{{ route('settings.company-commission.save') }}`,
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            if (res.success) {
                                $('#companyCommissionModal').modal('hide');
                                
                                // Update table badges dynamically
                                $(`#comp_payin_badge_${res.company_id}`).html(`
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                        ${res.pay_in}
                                    </span>
                                `);
                                $(`#comp_payout_badge_${res.company_id}`).html(`
                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                        ${res.pay_out}
                                    </span>
                                `);
                                $(`#comp_basis_${res.company_id}`).html(`
                                    <span class="badge bg-light text-secondary border">
                                        ${res.pay_out_based_on}
                                    </span>
                                `);
                                $(`#comp_status_${res.company_id}`).html(
                                    res.is_active
                                        ? '<span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>'
                                        : '<span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>'
                                );
                                $(`#comp_action_${res.company_id}`).html(`
                                    <button type="button" class="btn btn-sm btn-outline-primary px-3 open-company-commission-modal-btn" data-company-id="${res.company_id}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Rate
                                    </button>
                                `);
                                $(`#comp_updated_${res.company_id}`).text(res.updated_at);

                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message);
                                } else {
                                    alert(res.message);
                                }
                            }
                        },
                        error: function(err) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            const msg = err.responseJSON?.message || 'Failed to save company commission.';
                            alert(msg);
                        }
                    });
                });

                // Category Modal preview calculation
                function recalculateCategoryModalSummary() {
                    const inType = $('#modal_cat_pay_in_type').val();
                    const inVal = parseFloat($('#modal_cat_pay_in_value').val()) || 0;
                    const outType = $('#modal_cat_pay_out_type').val();
                    const outVal = parseFloat($('#modal_cat_pay_out_value').val()) || 0;
                    const outBase = $('#modal_cat_pay_out_based_on').val();

                    $('.modal-cat-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                    $('.modal-cat-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                    if (inType === 'percentage') {
                        $('#modal_cat_preview_payin').text(inVal.toFixed(2) + '%');
                        if (outType === 'percentage') {
                            if (outBase === 'admin_commission') {
                                const agentEffective = (inVal * outVal) / 100;
                                const adminNet = Math.max(0, inVal - agentEffective);
                                $('#modal_cat_preview_payout').text(outVal.toFixed(2) + '% (' + agentEffective.toFixed(2) + '% eff.)');
                                $('#modal_cat_preview_profit').text(adminNet.toFixed(2) + '%');
                            } else {
                                const adminNet = Math.max(0, inVal - outVal);
                                $('#modal_cat_preview_payout').text(outVal.toFixed(2) + '% of Gross');
                                $('#modal_cat_preview_profit').text(adminNet.toFixed(2) + '%');
                            }
                        } else {
                            $('#modal_cat_preview_payout').text('₹' + outVal.toFixed(2));
                            $('#modal_cat_preview_profit').text(inVal.toFixed(2) + '% - ₹' + outVal.toFixed(2));
                        }
                    } else {
                        $('#modal_cat_preview_payin').text('₹' + inVal.toFixed(2));
                        if (outType === 'percentage') {
                            const agentAmt = (inVal * outVal) / 100;
                            const profit = Math.max(0, inVal - agentAmt);
                            $('#modal_cat_preview_payout').text('₹' + agentAmt.toFixed(2) + ' (' + outVal.toFixed(2) + '%)');
                            $('#modal_cat_preview_profit').text('₹' + profit.toFixed(2));
                        } else {
                            const profit = Math.max(0, inVal - outVal);
                            $('#modal_cat_preview_payout').text('₹' + outVal.toFixed(2));
                            $('#modal_cat_preview_profit').text('₹' + profit.toFixed(2));
                        }
                    }
                }

                $('#modal_cat_pay_in_type, #modal_cat_pay_out_type, #modal_cat_pay_out_based_on').on('change', recalculateCategoryModalSummary);
                $('#modal_cat_pay_in_value, #modal_cat_pay_out_value').on('input', recalculateCategoryModalSummary);

                // Open Category Modal via AJAX
                $(document).on('click', '.open-category-commission-modal-btn', function() {
                    const catId = $(this).data('category-id');
                    const initialSubTypeId = $(this).data('sub-type-id') || '';
                    const btn = $(this);
                    btn.prop('disabled', true);

                    function loadCategoryCommissionData(catId, subTypeId) {
                        const url = `{{ url('/settings/category-commission') }}/${catId}` + (subTypeId ? `?sub_type_id=${subTypeId}` : '');
                        return fetch(url).then(r => r.json());
                    }

                    loadCategoryCommissionData(catId, initialSubTypeId)
                        .then(data => {
                            btn.prop('disabled', false);
                            if (data.success) {
                                $('#modal_cat_id').val(data.category_id);
                                $('#modal_category_name').text(data.category_name);

                                // Populate sub types select
                                const subSelect = $('#modal_cat_sub_type_id');
                                subSelect.empty().append('<option value="">All Sub Types (Default for Category)</option>');
                                if (data.sub_types && data.sub_types.length) {
                                    data.sub_types.forEach(function(st) {
                                        subSelect.append(`<option value="${st.id}">${st.name}</option>`);
                                    });
                                }
                                subSelect.val(data.sub_type_id || '');

                                $('#modal_cat_pay_in_type').val(data.pay_in_type);
                                $('#modal_cat_pay_in_value').val(data.pay_in_value);
                                $('#modal_cat_pay_out_type').val(data.pay_out_type);
                                $('#modal_cat_pay_out_value').val(data.pay_out_value);
                                $('#modal_cat_pay_out_based_on').val(data.pay_out_based_on);
                                $('#modal_cat_is_active').prop('checked', data.is_active);

                                recalculateCategoryModalSummary();
                                $('#categoryCommissionModal').modal('show');
                            }
                        })
                        .catch(() => {
                            btn.prop('disabled', false);
                            alert('Unable to load category details. Please try again.');
                        });
                });

                // When user changes Policy Sub Type in the modal, load that specific sub-type rule rate
                $('#modal_cat_sub_type_id').on('change', function() {
                    const catId = $('#modal_cat_id').val();
                    const subTypeId = $(this).val();

                    const url = `{{ url('/settings/category-commission') }}/${catId}` + (subTypeId ? `?sub_type_id=${subTypeId}` : '');
                    fetch(url)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                $('#modal_cat_pay_in_type').val(data.pay_in_type);
                                $('#modal_cat_pay_in_value').val(data.pay_in_value);
                                $('#modal_cat_pay_out_type').val(data.pay_out_type);
                                $('#modal_cat_pay_out_value').val(data.pay_out_value);
                                $('#modal_cat_pay_out_based_on').val(data.pay_out_based_on);
                                $('#modal_cat_is_active').prop('checked', data.is_active);
                                recalculateCategoryModalSummary();
                            }
                        });
                });

                // Submit category commission form via AJAX
                $('#categoryCommissionModalForm').on('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = $('#saveCategoryCommissionBtn');
                    const origHtml = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');

                    $.ajax({
                        url: `{{ route('settings.category-commission.save') }}`,
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            if (res.success) {
                                $('#categoryCommissionModal').modal('hide');
                                
                                // Update table badges dynamically
                                if (res.sub_type_id) {
                                    $(`#st_payin_badge_${res.sub_type_id}`).html(`
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                            ${res.pay_in}
                                        </span>
                                    `);
                                    $(`#st_payout_badge_${res.sub_type_id}`).html(`
                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                            ${res.pay_out}
                                        </span>
                                    `);
                                    $(`#st_basis_${res.sub_type_id}`).html(`
                                        <span class="badge bg-light text-secondary border">
                                            ${res.pay_out_based_on}
                                        </span>
                                    `);
                                    $(`#st_status_${res.sub_type_id}`).html(
                                        res.is_active
                                            ? '<span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>'
                                            : '<span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>'
                                    );
                                    $(`#st_action_${res.sub_type_id}`).html(`
                                        <button type="button" class="btn btn-sm btn-outline-warning text-dark px-3 open-category-commission-modal-btn" data-category-id="${res.category_id}" data-sub-type-id="${res.sub_type_id}">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Rate
                                        </button>
                                    `);
                                    $(`#st_updated_${res.sub_type_id}`).text(res.updated_at);
                                } else {
                                    $(`#cat_payin_badge_${res.category_id}`).html(`
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                            ${res.pay_in}
                                        </span>
                                    `);
                                    $(`#cat_payout_badge_${res.category_id}`).html(`
                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                            ${res.pay_out}
                                        </span>
                                    `);
                                    $(`#cat_basis_${res.category_id}`).html(`
                                        <span class="badge bg-light text-secondary border">
                                            ${res.pay_out_based_on}
                                        </span>
                                    `);
                                    $(`#cat_status_${res.category_id}`).html(
                                        res.is_active
                                            ? '<span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>'
                                            : '<span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>'
                                    );
                                    $(`#cat_action_${res.category_id}`).html(`
                                        <button type="button" class="btn btn-sm btn-outline-warning text-dark px-3 open-category-commission-modal-btn" data-category-id="${res.category_id}" data-sub-type-id="">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Rate
                                        </button>
                                    `);
                                    $(`#cat_updated_${res.category_id}`).text(res.updated_at);
                                }

                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message);
                                } else {
                                    alert(res.message);
                                }
                            }
                        },
                        error: function(err) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            const msg = err.responseJSON?.message || 'Failed to save category commission.';
                            alert(msg);
                        }
                    });
                });

                // ─── Agent / Team Member Commission Master Modal Logic ───────────
                function recalculateAgentModalSummary() {
                    const inType = $('#modal_agent_pay_in_type').val();
                    const inVal = parseFloat($('#modal_agent_pay_in_value').val()) || 0;
                    const outType = $('#modal_agent_pay_out_type').val();
                    const outVal = parseFloat($('#modal_agent_pay_out_value').val()) || 0;
                    const outBase = $('#modal_agent_pay_out_based_on').val();

                    $('.modal-agent-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                    $('.modal-agent-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                    if (inType === 'percentage') {
                        $('#modal_agent_preview_payin').text(inVal.toFixed(2) + '%');
                        if (outType === 'percentage') {
                            if (outBase === 'admin_commission') {
                                const agentEffective = (inVal * outVal) / 100;
                                const adminNet = Math.max(0, inVal - agentEffective);
                                $('#modal_agent_preview_payout').text(outVal.toFixed(2) + '% (' + agentEffective.toFixed(2) + '% eff.)');
                                $('#modal_agent_preview_profit').text(adminNet.toFixed(2) + '%');
                            } else {
                                const adminNet = Math.max(0, inVal - outVal);
                                $('#modal_agent_preview_payout').text(outVal.toFixed(2) + '% of Gross');
                                $('#modal_agent_preview_profit').text(adminNet.toFixed(2) + '%');
                            }
                        } else {
                            $('#modal_agent_preview_payout').text('₹' + outVal.toFixed(2));
                            $('#modal_agent_preview_profit').text(inVal.toFixed(2) + '% - ₹' + outVal.toFixed(2));
                        }
                    } else {
                        $('#modal_agent_preview_payin').text('₹' + inVal.toFixed(2));
                        if (outType === 'percentage') {
                            const agentAmt = (inVal * outVal) / 100;
                            const profit = Math.max(0, inVal - agentAmt);
                            $('#modal_agent_preview_payout').text('₹' + agentAmt.toFixed(2) + ' (' + outVal.toFixed(2) + '%)');
                            $('#modal_agent_preview_profit').text('₹' + profit.toFixed(2));
                        } else {
                            const profit = Math.max(0, inVal - outVal);
                            $('#modal_agent_preview_payout').text('₹' + outVal.toFixed(2));
                            $('#modal_agent_preview_profit').text('₹' + profit.toFixed(2));
                        }
                    }
                }

                $('#modal_agent_pay_in_type, #modal_agent_pay_out_type, #modal_agent_pay_out_based_on').on('change', recalculateAgentModalSummary);
                $('#modal_agent_pay_in_value, #modal_agent_pay_out_value').on('input', recalculateAgentModalSummary);

                // Open Agent Modal via AJAX
                $(document).on('click', '.open-agent-commission-modal-btn', function() {
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

                                $('#modal_agent_id').val(aId);
                                $('#modal_user_id').val(aId);
                                $('#modal_agent_name').text(aName);
                                $('#modal_agent_email').text(aEmail);
                                $('#modal_agent_role_badge').text(aRole);
                                $('#modal_agent_pay_in_type').val(data.pay_in_type);
                                $('#modal_agent_pay_in_value').val(data.pay_in_value);
                                $('#modal_agent_pay_out_type').val(data.pay_out_type);
                                $('#modal_agent_pay_out_value').val(data.pay_out_value);
                                $('#modal_agent_pay_out_based_on').val(data.pay_out_based_on);
                                $('#modal_agent_is_active').prop('checked', data.is_active);

                                recalculateAgentModalSummary();
                                $('#agentCommissionModal').modal('show');
                            }
                        })
                        .catch(() => {
                            btn.prop('disabled', false);
                            alert('Unable to load agent commission details. Please try again.');
                        });
                });

                // Submit agent commission form via AJAX
                $('#agentCommissionModalForm').on('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = $('#saveAgentCommissionBtn');
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
                                $('#agentCommissionModal').modal('hide');
                                
                                // Update table badges dynamically
                                $(`#agent_payin_badge_${res.user_id}`).html(`
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                        ${res.pay_in}
                                    </span>
                                `);
                                $(`#agent_payout_badge_${res.user_id}`).html(`
                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                        ${res.pay_out}
                                    </span>
                                `);
                                $(`#agent_basis_${res.user_id}`).html(`
                                    <span class="badge bg-light text-secondary border">
                                        ${res.pay_out_based_on}
                                    </span>
                                `);
                                $(`#agent_status_${res.user_id}`).html(
                                    res.is_active
                                        ? '<span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>'
                                        : '<span class="badge bg-secondary-subtle text-secondary border">Default / Inactive</span>'
                                );
                                $(`#agent_action_${res.user_id}`).html(`
                                    <button type="button" class="btn btn-sm btn-outline-dark px-3 open-agent-commission-modal-btn" data-user-id="${res.user_id}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Rate
                                    </button>
                                `);
                                $(`#agent_updated_${res.user_id}`).text(res.updated_at);

                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message);
                                } else {
                                    alert(res.message);
                                }
                            }
                        },
                        error: function(err) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            const msg = err.responseJSON?.message || 'Failed to save agent commission.';
                            alert(msg);
                        }
                    });
                });

                // ─── Policy Commission Master Modal Logic in Settings ───────────────────
                function recalculatePolicyCommissionSettingsModal() {
                    const premium = parseFloat($('#modal_pol_settings_premium_val').val()) || 0;
                    const inType = $('#modal_pol_pay_in_type').val();
                    const inVal = parseFloat($('#modal_pol_pay_in_value').val()) || 0;
                    const outType = $('#modal_pol_pay_out_type').val();
                    const outVal = parseFloat($('#modal_pol_pay_out_value').val()) || 0;
                    const outBase = $('#modal_pol_pay_out_based_on').val();

                    $('.modal-pol-payin-unit').text(inType === 'percentage' ? '%' : '₹');
                    $('.modal-pol-payout-unit').text(outType === 'percentage' ? '%' : '₹');

                    let payInAmt = 0;
                    if (inType === 'percentage') {
                        payInAmt = (premium * inVal) / 100;
                    } else {
                        payInAmt = inVal;
                    }

                    let payOutAmt = 0;
                    if (outType === 'percentage') {
                        const base = (outBase === 'premium') ? premium : payInAmt;
                        payOutAmt = (base * outVal) / 100;
                    } else {
                        payOutAmt = outVal;
                    }

                    const netProfit = Math.max(0, payInAmt - payOutAmt);

                    $('#modal_pol_preview_payin').text('₹' + payInAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#modal_pol_preview_payout').text('₹' + payOutAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#modal_pol_preview_profit').text('₹' + netProfit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }

                $('#modal_pol_pay_in_type, #modal_pol_pay_out_type, #modal_pol_pay_out_based_on').on('change', recalculatePolicyCommissionSettingsModal);
                $('#modal_pol_pay_in_value, #modal_pol_pay_out_value').on('input', recalculatePolicyCommissionSettingsModal);

                // Open Policy Commission Modal via AJAX
                $(document).on('click', '.open-policy-commission-settings-btn', function() {
                    const policyId = $(this).data('policy-id');
                    const btn = $(this);
                    btn.prop('disabled', true);

                    $.ajax({
                        url: '/commission/get-policy-commission/' + policyId,
                        type: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            btn.prop('disabled', false);
                            if (res.status === 'success') {
                                $('#modal_pol_settings_id').val(res.policy_id);
                                $('#modal_pol_settings_number').text(res.policy_number);
                                $('#modal_pol_settings_subtitle').text('Policy #' + res.policy_number);
                                $('#modal_pol_settings_company').text(res.company_name);
                                $('#modal_pol_settings_holder').text(res.holder_name || 'Policy ID: #' + res.policy_id);
                                $('#modal_pol_settings_agent').text(res.agent_name);
                                $('#modal_pol_settings_premium_val').val(res.premium);
                                $('#modal_pol_settings_premium_disp').text('₹' + parseFloat(res.premium).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                                $('#modal_pol_pay_in_type').val(res.pay_in_type);
                                $('#modal_pol_pay_in_value').val(res.pay_in_value);
                                $('#modal_pol_pay_out_type').val(res.pay_out_type);
                                $('#modal_pol_pay_out_value').val(res.pay_out_value);
                                $('#modal_pol_pay_out_based_on').val(res.pay_out_based_on);

                                recalculatePolicyCommissionSettingsModal();
                                $('#policyCommissionSettingsModal').modal('show');
                            }
                        },
                        error: function() {
                            btn.prop('disabled', false);
                            alert('Unable to load policy commission details. Please try again.');
                        }
                    });
                });

                // Submit Policy Commission Form via AJAX
                $('#policyCommissionSettingsModalForm').on('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = $('#savePolicyCommissionSettingsBtn');
                    const origHtml = submitBtn.html();
                    const policyId = $('#modal_pol_settings_id').val();

                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');

                    $.ajax({
                        url: '/commission/save-policy-commission',
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            $('#policyCommissionSettingsModal').modal('hide');

                            if (res.status === 'success') {
                                $(`#pol_payin_badge_${policyId}`).html(`
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                        ₹${parseFloat(res.pay_in_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </span>
                                `);
                                $(`#pol_payout_badge_${policyId}`).html(`
                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 fw-bold fs-6">
                                        ₹${parseFloat(res.pay_out_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </span>
                                `);
                                $(`#pol_profit_badge_${policyId}`).html(`
                                    <span class="badge bg-emerald-subtle text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                        ₹${parseFloat(res.profit).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </span>
                                `);
                                $(`#pol_status_${policyId}`).html(
                                    '<span class="badge bg-success-subtle text-success border border-success border-opacity-25"><i class="fa fa-circle-check me-1"></i> Active</span>'
                                );
                                $(`#pol_action_${policyId}`).html(`
                                    <button type="button" class="btn btn-sm btn-outline-info text-dark px-3 open-policy-commission-settings-btn" data-policy-id="${policyId}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Rate
                                    </button>
                                `);

                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message);
                                } else {
                                    alert(res.message);
                                }
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(origHtml);
                            const msg = xhr.responseJSON?.message || 'Failed to save policy commission.';
                            alert(msg);
                        }
                    });
                });
            });
        </script>
        <script>
            window.settingsPageConfig = {
                apiSettingsIndex: @json(route('api.settings.index')),
                apiSettingsUpdate: @json(route('api.settings.update')),
                settingsUpdate: @json(route('settings.update')),
            };
        </script>
        <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/setting.js' : '/assets/js/setting.js') }}?v={{ filemtime(public_path('assets/js/setting.js')) }}"></script>
        <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/settings-page.js' : '/js/settings-page.js') }}?v={{ filemtime(public_path('js/settings-page.js')) }}"></script>
    @endpush
@endsection
