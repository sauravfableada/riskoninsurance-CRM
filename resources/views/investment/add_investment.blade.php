@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($investment);
    $inv = $investment ?? [];
    $formattedDates = [];
    if($isEdit) {
        $dateFields = ['transaction_date', 'date_of_birth', 'mf_start_date', 'bank_deposit_date', 'bank_mature_date', 'po_opening_date', 'po_mature_date', 'bond_issue_date', 'bond_purchase_date', 'bond_maturity_date', 'other_deposit_date', 'other_mature_date', 'other_next_due_date'];
        foreach($dateFields as $df) {
            $val = $inv[$df] ?? null;
            if(!empty($val)) {
                try {
                    $formattedDates[$df] = \Carbon\Carbon::parse($val)->format('Y-m-d');
                } catch (\Exception $e) {
                    $formattedDates[$df] = substr((string)$val, 0, 10);
                }
            }
        }
    }
@endphp

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
@endpush

<style>
    .form-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 2rem;
    }

    .step-item {
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        z-index: 1;
        transition: all 0.3s ease;
    }

    .step-item.active .step-circle {
        background: #5e72d8;
        color: #fff;
        box-shadow: 0 4px 10px rgba(94, 114, 216, 0.3);
    }

    .step-item.completed .step-circle {
        background: #2dce89;
        color: #fff;
    }

    .step-line {
        width: 80px;
        height: 2px;
        background: #e9ecef;
        transition: all 0.3s ease;
    }

    .step-item.completed .step-line {
        background: #2dce89;
    }

    .step-item.active .step-line {
        background: #5e72d8;
    }

    .step-label {
        position: absolute;
        top: 45px;
        font-size: 12px;
        color: #6c757d;
        left: 20px; /* Center of the 40px circle */
        transform: translateX(-50%);
        white-space: nowrap;
    }

    .step-item.active .step-label {
        color: #5e72d8;
        font-weight: 600;
    }

    .step-item.completed .step-label {
        color: #2dce89;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #344767;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
    }

    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(94, 114, 216, 0.1);
        color: #5e72d8;
        margin-right: 10px;
    }

    .investment-type-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .investment-type-card:hover {
        border-color: #5e72d8;
        background-color: rgba(94, 114, 216, 0.02);
    }

    .investment-type-card.active {
        border-color: #5e72d8;
        background-color: rgba(94, 114, 216, 0.05);
        box-shadow: 0 4px 12px rgba(94, 114, 216, 0.1);
    }

    .investment-type-icon {
        font-size: 24px;
        margin-bottom: 0.75rem;
        color: #5e72d8;
    }

    .investment-type-label {
        font-size: 14px;
        font-weight: 600;
        color: #344767;
        margin-bottom: 0;
    }

    .required-star {
        color: #fd5c70;
        margin-left: 2px;
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #d2d6da;
        border-radius: 0.5rem;
        height: 40px;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        padding-left: 12px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }

    @media (max-width: 768px) {
        .step-line {
            width: 40px;
        }
        .step-label {
            font-size: 10px;
            left: 16px; /* Center of the 32px circle */
            white-space: nowrap;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }
    }
    /* Validation Styles */
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #fd5c70 !important;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23fd5c70'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23fd5c70' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #fd5c70;
    }

    .is-invalid ~ .invalid-feedback {
        display: block;
    }

    /* Select2 Validation */
    .select2-container.is-invalid .select2-selection {
        border-color: #fd5c70 !important;
    }
    
    .input-group .form-control.is-invalid, 
    .input-group .form-select.is-invalid,
    .input-group .select2-container.is-invalid .select2-selection {
        z-index: 3;
    }
</style>

<div class="container-fluid p-0">
    <div class="card form-card">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">{{ $isEdit ? 'Update' : 'Add' }} Investment</h1>
                    <p class="text-muted small mb-0">{{ $isEdit ? 'Update existing investment details.' : 'Create a new investment record.' }}</p>
                </div>
                <a href="{{ route('investment.index') }}" class="btn btn-dark-blue customer-back-btn">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step-item active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-line"></div>
                    <span class="step-label">Personal Details</span>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-line"></div>
                    <span class="step-label">Investment Details</span>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-circle">3</div>
                    <span class="step-label">Upload Documents</span>
                </div>
            </div>

            <form action="{{ $isEdit ? route('investment.update', $inv['id']) : route('investment.store') }}" method="POST" enctype="multipart/form-data" id="investmentForm" novalidate>
                @csrf
                @if($isEdit)
                    @method('POST') {{-- Or PUT if you prefer --}}
                @endif

                <!-- STEP 1: Personal Details -->
                <div class="investment-step" data-step="1">
                    <div class="form-section-title">
                        <div class="d-flex align-items-center">
                            <div class="section-icon">
                                <i class="fa-solid fa-user-tag"></i>
                            </div>
                            Personal & Transaction Details
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" value="{{ old('transaction_date', $formattedDates['transaction_date'] ?? date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Document Number</label>
                            <input type="text" class="form-control" name="document_number" placeholder="Enter document number" value="{{ old('document_number', $inv['document_number'] ?? '') }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Select Family Group <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="family_group_id" data-placeholder="Search for family group" required>
                                    <option value="">Search for family group</option>
                                    @foreach($familyGroups ?? [] as $group)
                                        <option value="{{ $group['id'] }}" {{ (old('family_group_id', $inv['family_group_id'] ?? '') == $group['id']) ? 'selected' : '' }}>{{ $group['group_name'] }}</option>
                                    @endforeach
                                </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('familyGroupModal')" ><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Select Policy Holder Name <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="investment_holder_id" data-placeholder="Search for policy holder name" data-selected-holder="{{ $inv['investment_holder_id'] ?? '' }}" required>
                                    <option value="">Search for policy holder name</option>
                                    {{-- Holders will be loaded via AJAX --}}
                                </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('holderModal')" ><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-title mt-4">
                        <div class="d-flex align-items-center">
                            <div class="section-icon">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                            Contact & Identity Information
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" placeholder="Enter first name" value="{{ old('first_name', $inv['first_name'] ?? '') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" placeholder="Enter middle name" value="{{ old('middle_name', $inv['middle_name'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" placeholder="Enter last name" value="{{ old('last_name', $inv['last_name'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" name="address_line_1" placeholder="Enter address line 1" value="{{ old('address_line_1', $inv['address_line_1'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" name="address_line_2" placeholder="Enter address line 2" value="{{ old('address_line_2', $inv['address_line_2'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" placeholder="Enter city" value="{{ old('city', $inv['city'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control" name="area" placeholder="Enter area" value="{{ old('area', $inv['area'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mobile_number" placeholder="Enter mobile number" value="{{ old('mobile_number', $inv['mobile_number'] ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email_address" placeholder="Enter email address" value="{{ old('email_address', $inv['email_address'] ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">Select gender</option>
                                <option value="Male" {{ (strtolower(old('gender', $inv['gender'] ?? '')) == 'male') ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ (strtolower(old('gender', $inv['gender'] ?? '')) == 'female') ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ (strtolower(old('gender', $inv['gender'] ?? '')) == 'other') ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth', $formattedDates['date_of_birth'] ?? '') }}" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Family Head Name</label>
                            <input type="text" class="form-control" name="family_head_name" placeholder="Enter family head name" value="{{ old('family_head_name', $inv['family_head_name'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Relation with Head</label>
                            <input type="text" class="form-control" name="relation_with_head" placeholder="Enter relation" value="{{ old('relation_with_head', $inv['relation_with_head'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Investment Type Selection & Details -->
                <div class="investment-step d-none" data-step="2">
                    <div class="form-section-title">
                        <div class="d-flex align-items-center">
                            <div class="section-icon">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            Select Investment Category
                        </div>
                    </div>

                    <div class="row g-3 mb-5">
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="investment-type-card {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'mutual_fund' || !isset($inv['investment_type_main'])) ? 'active' : '' }}" onclick="selectInvestmentType('mutual_fund')">
                                <i class="fa-solid fa-chart-line investment-type-icon"></i>
                                <p class="investment-type-label">Mutual Fund</p>
                                <input type="radio" name="investment_type_main" value="mutual_fund" class="d-none" {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'mutual_fund' || !isset($inv['investment_type_main'])) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="investment-type-card {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'bank') ? 'active' : '' }}" onclick="selectInvestmentType('bank')">
                                <i class="fa-solid fa-building-columns investment-type-icon"></i>
                                <p class="investment-type-label">Bank</p>
                                <input type="radio" name="investment_type_main" value="bank" class="d-none" {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'bank') ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="investment-type-card {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'post_office') ? 'active' : '' }}" onclick="selectInvestmentType('post_office')">
                                <i class="fa-solid fa-envelope-open-text investment-type-icon"></i>
                                <p class="investment-type-label">Post Office</p>
                                <input type="radio" name="investment_type_main" value="post_office" class="d-none" {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'post_office') ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="investment-type-card {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'govt_bonds') ? 'active' : '' }}" onclick="selectInvestmentType('govt_bonds')">
                                <i class="fa-solid fa-certificate investment-type-icon"></i>
                                <p class="investment-type-label">Bond</p>
                                <input type="radio" name="investment_type_main" value="govt_bonds" class="d-none" {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'govt_bonds') ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="investment-type-card {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'other') ? 'active' : '' }}" onclick="selectInvestmentType('other')">
                                <i class="fa-solid fa-sack-dollar investment-type-icon"></i>
                                <p class="investment-type-label">Other</p>
                                <input type="radio" name="investment_type_main" value="other" class="d-none" {{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'other') ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <!-- Mutual Fund Fields -->
                    <div id="mutualFundFields" class="{{ (old('investment_type_main', $inv['investment_type_main'] ?? 'mutual_fund') == 'mutual_fund') ? '' : 'd-none' }}">
                        <div class="form-section-title">
                            <div class="d-flex align-items-center">
                                <div class="section-icon">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </div>
                                Mutual Fund Details
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select class="form-select select2" name="mf_transaction_type_id" data-placeholder="Search for transaction type">
                                            <option value="">Search for transaction type</option>
                                            @foreach($investmentTypes ?? [] as $it)
                                                @if($it['category'] == 'Mutual Fund')
                                                    <option value="{{ $it['id'] }}" {{ (old('mf_transaction_type_id', $inv['mf_transaction_type_id'] ?? '') == $it['id']) ? 'selected' : '' }}>{{ $it['type_name'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-dark-blue" type="button" onclick="activeTypeSelect = 'mf_transaction_type_id'; showModal('investmentTypeModal')"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Folio Number <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="mf_folio_number_id" data-placeholder="Search for folio number">
                                        <option value="">Search for folio number</option>
                                        @foreach($folios ?? [] as $folio)
                                            <option value="{{ $folio['id'] }}" {{ (old('mf_folio_number_id', $inv['mf_folio_number_id'] ?? '') == $folio['id']) ? 'selected' : '' }}>{{ $folio['folio_number'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('folioModal')" ><i class="fa-solid fa-plus"></i></button>
                            </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Type <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select class="form-select select2" name="mf_investment_type_id" data-placeholder="Search for investment type">
                                            <option value="">Search for investment type</option>
                                            @foreach($investmentTypes ?? [] as $it)
                                                @if($it['category'] == 'Mutual Fund')
                                                    <option value="{{ $it['id'] }}" {{ (old('mf_investment_type_id', $inv['mf_investment_type_id'] ?? '') == $it['id']) ? 'selected' : '' }}>{{ $it['type_name'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-dark-blue" type="button" onclick="activeTypeSelect = 'mf_investment_type_id'; showModal('investmentTypeModal')"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Fund Name <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="mf_fund_name_id" data-placeholder="Search for fund name">
                                        <option value="">Search for fund name</option>
                                        @foreach($fundNames ?? [] as $fund)
                                            <option value="{{ $fund['id'] }}" {{ (old('mf_fund_name_id', $inv['mf_fund_name_id'] ?? '') == $fund['id']) ? 'selected' : '' }}>{{ $fund['fund_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('fundNameModal')" ><i class="fa-solid fa-plus"></i></button>
                            </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Fund Type <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="mf_fund_type_id" data-placeholder="Search for fund type">
                                        <option value="">Search for fund type</option>
                                        @foreach($fundTypes ?? [] as $fType)
                                            <option value="{{ $fType['id'] }}" {{ (old('mf_fund_type_id', $inv['mf_fund_type_id'] ?? '') == $fType['id']) ? 'selected' : '' }}>{{ $fType['name'] ?? $fType['fund_type'] ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('fundTypeModal')" ><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Select Broker <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="mf_broker_id" data-placeholder="Search for broker">
                                        <option value="">Search for broker</option>
                                        @foreach($brokers ?? [] as $broker)
                                            <option value="{{ $broker['id'] }}" {{ (old('mf_broker_id', $inv['mf_broker_id'] ?? '') == $broker['id']) ? 'selected' : '' }}>{{ $broker['name'] ?? $broker['broker_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('brokerModal')" ><i class="fa-solid fa-plus"></i></button>
                            </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Select Mode <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select class="form-select select2" name="mf_mode_id" data-placeholder="Search for mode">
                                            <option value="">Search for mode</option>
                                            @foreach($modes ?? [] as $mode)
                                                <option value="{{ $mode['id'] }}" {{ (old('mf_mode_id', $inv['mf_mode_id'] ?? '') == $mode['id']) ? 'selected' : '' }}>{{ $mode['mode_name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-dark-blue" type="button" onclick="showModal('modeModal')" ><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="mf_start_date" value="{{ old('mf_start_date', $formattedDates['mf_start_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Number Of Unit Purchase</label>
                                <input type="text" class="form-control" name="mf_units_purchase" placeholder="Enter units purchase" value="{{ old('mf_units_purchase', $inv['mf_units_purchase'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Unit Price (₹)</label>
                                <input type="text" class="form-control" name="mf_unit_price" placeholder="Enter unit price" value="{{ old('mf_unit_price', $inv['mf_unit_price'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Amount (₹) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="mf_investment_amount" placeholder="Enter investment amount" value="{{ old('mf_investment_amount', $inv['mf_investment_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Balance Unit</label>
                                <input type="text" class="form-control" name="mf_total_balance_unit" placeholder="Total balance unit" value="{{ old('mf_total_balance_unit', $inv['mf_total_balance_unit'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Fund Value (₹)</label>
                                <input type="text" class="form-control" name="mf_total_fund_value" placeholder="Total fund value" value="{{ old('mf_total_fund_value', $inv['mf_total_fund_value'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Count</label>
                                <input type="text" class="form-control" name="mf_total_count" placeholder="Total count" value="{{ old('mf_total_count', $inv['mf_total_count'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Investment Balance Amount (₹)</label>
                                <input type="text" class="form-control" name="mf_total_investment_balance" placeholder="Total investment balance" value="{{ old('mf_total_investment_balance', $inv['mf_total_investment_balance'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Payment Bank</label>
                                <select class="form-select select2" name="mf_payment_bank_id" data-placeholder="Search for payment bank">
                                    <option value="">Search for payment bank</option>
                                    @foreach($banks ?? [] as $bank)
                                        <option value="{{ $bank['id'] }}" {{ (old('mf_payment_bank_id', $inv['mf_payment_bank_id'] ?? '') == $bank['id']) ? 'selected' : '' }}>{{ $bank['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 col-lg-8">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="mf_remarks" rows="2" placeholder="Enter remarks">{{ old('mf_remarks', $inv['mf_remarks'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Investment Document</label>
                                <input type="file" class="form-control" name="mf_document">
                                @if($isEdit && !empty($inv['mf_document']))
                                    <small class="text-success">Current: {{ $inv['mf_document'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Bank Fields -->
                    <div id="bankFields" class="{{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'bank') ? '' : 'd-none' }}">
                        <div class="form-section-title">
                            <div class="d-flex align-items-center">
                                <div class="section-icon">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>
                                Bank Details
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="bank_transaction_type_id" data-placeholder="Search for transaction type">
                                    <option value="">Search for transaction type</option>
                                    {{-- Bank transaction types --}}
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="bank_account_type">
                                    <option value="">Select account type</option>
                                    @foreach($accountTypes ?? [] as $at)
                                        <option value="{{ $at['value'] }}" {{ (old('bank_account_type', $inv['bank_account_type'] ?? '') == $at['value']) ? 'selected' : '' }}>{{ $at['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select select2" name="bank_name_id" data-placeholder="Search for bank">
                                        <option value="">Search for bank</option>
                                        @foreach($banks ?? [] as $bank)
                                            <option value="{{ $bank['id'] }}" {{ (old('bank_name_id', $inv['bank_name_id'] ?? '') == $bank['id']) ? 'selected' : '' }}>{{ $bank['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-dark-blue" type="button" onclick="showModal('bankModal')" ><i class="fa-solid fa-plus"></i></button>
                            </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Product <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="bank_investment_product_id" data-placeholder="Search for product">
                                    <option value="">Search for product</option>
                                    {{-- Investment products --}}
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Customer No.</label>
                                <input type="text" class="form-control" name="bank_customer_no" placeholder="Enter customer number" value="{{ old('bank_customer_no', $inv['bank_customer_no'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Certificate No.</label>
                                <input type="text" class="form-control" name="bank_certificate_no" placeholder="Enter certificate number" value="{{ old('bank_certificate_no', $inv['bank_certificate_no'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Amount <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="bank_investment_amount" placeholder="Enter investment amount" value="{{ old('bank_investment_amount', $inv['bank_investment_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Invested Amount</label>
                                <input type="text" class="form-control" name="bank_total_invested_amount" placeholder="Total invested amount" value="{{ old('bank_total_invested_amount', $inv['bank_total_invested_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Select Mode <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select class="form-select select2" name="bank_mode_id" data-placeholder="Search for mode">
                                            <option value="">Search for mode</option>
                                            @foreach($modes ?? [] as $mode)
                                                <option value="{{ $mode['id'] }}" {{ (old('bank_mode_id', $inv['bank_mode_id'] ?? '') == $mode['id']) ? 'selected' : '' }}>{{ $mode['mode_name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-dark-blue" type="button" onclick="showModal('modeModal')" ><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Deposit Tenure Mode</label>
                                <select class="form-select" name="bank_tenure_mode">
                                    <option value="">Select tenure mode</option>
                                    @foreach($tenureModes ?? [] as $tm)
                                        <option value="{{ $tm['value'] }}" {{ (old('bank_tenure_mode', $inv['bank_tenure_mode'] ?? '') == $tm['value']) ? 'selected' : '' }}>{{ $tm['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Deposit Tenure</label>
                                <input type="text" class="form-control" name="bank_tenure" placeholder="Enter tenure" value="{{ old('bank_tenure', $inv['bank_tenure'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Deposit Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="bank_deposit_date" value="{{ old('bank_deposit_date', $formattedDates['bank_deposit_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Mature Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="bank_mature_date" value="{{ old('bank_mature_date', $formattedDates['bank_mature_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Interest (%)</label>
                                <input type="text" class="form-control" name="bank_interest_rate" placeholder="Enter interest rate" value="{{ old('bank_interest_rate', $inv['bank_interest_rate'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Interest Amount</label>
                                <input type="text" class="form-control" name="bank_interest_amount" placeholder="Enter interest amount" value="{{ old('bank_interest_amount', $inv['bank_interest_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Interest Frequency</label>
                                <select class="form-select" name="bank_interest_frequency">
                                    <option value="">Select frequency</option>
                                    @foreach($interestFrequencies ?? [] as $if)
                                        <option value="{{ $if['value'] }}" {{ (old('bank_interest_frequency', $inv['bank_interest_frequency'] ?? '') == $if['value']) ? 'selected' : '' }}>{{ $if['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Payment Bank</label>
                                <select class="form-select select2" name="bank_payment_bank_id" data-placeholder="Search for payment bank">
                                    <option value="">Search for payment bank</option>
                                    @foreach($banks ?? [] as $bank)
                                        <option value="{{ $bank['id'] }}" {{ (old('bank_payment_bank_id', $inv['bank_payment_bank_id'] ?? '') == $bank['id']) ? 'selected' : '' }}>{{ $bank['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Client (₹)</label>
                                <input type="text" class="form-control" name="bank_paid_by_client" placeholder="Enter amount" value="{{ old('bank_paid_by_client', $inv['bank_paid_by_client'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Agent (₹)</label>
                                <input type="text" class="form-control" name="bank_paid_by_agent" placeholder="Enter amount" value="{{ old('bank_paid_by_agent', $inv['bank_paid_by_agent'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-8">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="bank_remarks" placeholder="Enter remarks" value="{{ old('bank_remarks', $inv['bank_remarks'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Bank Document</label>
                                <input type="file" class="form-control" name="bank_document">
                                @if($isEdit && !empty($inv['bank_document']))
                                    <small class="text-success">Current: {{ $inv['bank_document'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Post Office Fields -->
                    <div id="postOfficeFields" class="{{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'post_office') ? '' : 'd-none' }}">
                        <div class="form-section-title">
                            <div class="d-flex align-items-center">
                                <div class="section-icon">
                                    <i class="fa-solid fa-envelope-open-text"></i>
                                </div>
                                Post Office Details
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="po_transaction_type_id" data-placeholder="Search for transaction type">
                                    <option value="">Search for transaction type</option>
                                    @foreach($poTransactionTypes ?? [] as $tt)
                                        <option value="{{ $tt['id'] }}" {{ (old('po_transaction_type_id', $inv['po_transaction_type_id'] ?? '') == $tt['id']) ? 'selected' : '' }}>{{ $tt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="po_account_type">
                                    <option value="">Select account type</option>
                                    @foreach($accountTypes ?? [] as $at)
                                        <option value="{{ $at['value'] }}" {{ (old('po_account_type', $inv['po_account_type'] ?? '') == $at['value']) ? 'selected' : '' }}>{{ $at['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Certificate / Account No.</label>
                                <input type="text" class="form-control" name="po_certificate_account_no" placeholder="Enter number" value="{{ old('po_certificate_account_no', $inv['po_certificate_account_no'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="po_customer_name" placeholder="Enter customer name" value="{{ old('po_customer_name', $inv['po_customer_name'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Amount <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="po_investment_amount" placeholder="Enter investment amount" value="{{ old('po_investment_amount', $inv['po_investment_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Invested Amount</label>
                                <input type="text" class="form-control" name="po_total_invested_amount" placeholder="Total invested amount" value="{{ old('po_total_invested_amount', $inv['po_total_invested_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Select Mode <span class="text-danger">*</span></label>
                                <select class="form-select" name="po_mode">
                                    <option value="">Select mode</option>
                                    <option value="monthly" {{ (old('po_mode', $inv['po_mode'] ?? '') == 'monthly') ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ (old('po_mode', $inv['po_mode'] ?? '') == 'quarterly') ? 'selected' : '' }}>Quarterly</option>
                                    <option value="annually" {{ (old('po_mode', $inv['po_mode'] ?? '') == 'annually') ? 'selected' : '' }}>Annually</option>
                                    <option value="one-time" {{ (old('po_mode', $inv['po_mode'] ?? '') == 'one-time') ? 'selected' : '' }}>One Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Tenure Mode</label>
                                <select class="form-select" name="po_tenure_mode">
                                    <option value="">Select tenure mode</option>
                                    @foreach($tenureModes ?? [] as $tm)
                                        <option value="{{ $tm['value'] }}" {{ (old('po_tenure_mode', $inv['po_tenure_mode'] ?? '') == $tm['value']) ? 'selected' : '' }}>{{ $tm['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Tenure</label>
                                <input type="text" class="form-control" name="po_tenure" placeholder="Enter tenure" value="{{ old('po_tenure', $inv['po_tenure'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="po_opening_date" value="{{ old('po_opening_date', $formattedDates['po_opening_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Mature Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="po_mature_date" value="{{ old('po_mature_date', $formattedDates['po_mature_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Interest (%)</label>
                                <input type="text" class="form-control" name="po_interest_rate" placeholder="Enter interest rate" value="{{ old('po_interest_rate', $inv['po_interest_rate'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Interest Amount</label>
                                <input type="text" class="form-control" name="po_interest_amount" placeholder="Enter interest amount" value="{{ old('po_interest_amount', $inv['po_interest_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Interest Frequency</label>
                                <select class="form-select" name="po_interest_frequency">
                                    <option value="">Select frequency</option>
                                    @foreach($interestFrequencies ?? [] as $if)
                                        <option value="{{ $if['value'] }}" {{ (old('po_interest_frequency', $inv['po_interest_frequency'] ?? '') == $if['value']) ? 'selected' : '' }}>{{ $if['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Payment Bank</label>
                                <select class="form-select select2" name="po_payment_bank_id" data-placeholder="Search for payment bank">
                                    <option value="">Search for payment bank</option>
                                    @foreach($banks ?? [] as $bank)
                                        <option value="{{ $bank['id'] }}" {{ (old('po_payment_bank_id', $inv['po_payment_bank_id'] ?? '') == $bank['id']) ? 'selected' : '' }}>{{ $bank['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Client (₹)</label>
                                <input type="text" class="form-control" name="po_paid_by_client" placeholder="Enter amount" value="{{ old('po_paid_by_client', $inv['po_paid_by_client'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Agent (₹)</label>
                                <input type="text" class="form-control" name="po_paid_by_agent" placeholder="Enter amount" value="{{ old('po_paid_by_agent', $inv['po_paid_by_agent'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-8">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="po_remarks" placeholder="Enter remarks" value="{{ old('po_remarks', $inv['po_remarks'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Post Office Document</label>
                                <input type="file" class="form-control" name="po_document">
                                @if($isEdit && !empty($inv['po_document']))
                                    <small class="text-success">Current: {{ $inv['po_document'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Government Bonds Fields -->
                    <div id="govtBondsFields" class="{{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'govt_bonds') ? '' : 'd-none' }}">
                        <div class="form-section-title">
                            <div class="d-flex align-items-center">
                                <div class="section-icon">
                                    <i class="fa-solid fa-certificate"></i>
                                </div>
                                Bond Details
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="bond_transaction_type_id" data-placeholder="Search for transaction type">
                                    <option value="">Search for transaction type</option>
                                    @foreach($bondTransactionTypes ?? [] as $tt)
                                        <option value="{{ $tt['id'] }}" {{ (old('bond_transaction_type_id', $inv['bond_transaction_type_id'] ?? '') == $tt['id']) ? 'selected' : '' }}>{{ $tt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Bond ID / ISIN</label>
                                <input type="text" class="form-control" name="bond_id_isin" placeholder="Enter Bond ID or ISIN" value="{{ old('bond_id_isin', $inv['bond_id_isin'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Bond Name</label>
                                <input type="text" class="form-control" name="bond_name" placeholder="Enter bond name" value="{{ old('bond_name', $inv['bond_name'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Bond Issuer</label>
                                <select class="form-select" name="bond_issuer">
                                    <option value="">Select issuer</option>
                                    @foreach($bondIssuers ?? [] as $bi)
                                        <option value="{{ $bi['value'] }}" {{ (old('bond_issuer', $inv['bond_issuer'] ?? '') == $bi['value']) ? 'selected' : '' }}>{{ $bi['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Bond Type</label>
                                <select class="form-select select2" name="bond_type" id="bond_type_select" data-placeholder="Search for bond type">
                                    <option value="">Search for bond type</option>
                                    @foreach($bonds ?? [] as $b)
                                        <option value="{{ $b['id'] }}" {{ (old('bond_type', $inv['bond_type'] ?? '') == $b['id']) ? 'selected' : '' }}>{{ $b['bond_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Sub Bond Type</label>
                                <select class="form-select select2" name="bond_sub_type_id" id="bond_sub_type_id" data-placeholder="Search for sub bond type">
                                    <option value="">Search for sub bond type</option>
                                    @foreach($subBondTypes ?? [] as $sbt)
                                        <option value="{{ $sbt['id'] }}" data-bond-id="{{ $sbt['bond_id'] ?? '' }}" {{ (old('bond_sub_type_id', $inv['bond_sub_type_id'] ?? '') == $sbt['id']) ? 'selected' : '' }}>{{ $sbt['sub_bond_type'] }}{{ !empty($sbt['bond_name']) ? ' (' . $sbt['bond_name'] . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Issue Date</label>
                                <input type="date" class="form-control" name="bond_issue_date" value="{{ old('bond_issue_date', $formattedDates['bond_issue_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Face Value (₹)</label>
                                <input type="text" class="form-control" name="bond_face_value" placeholder="Enter face value" value="{{ old('bond_face_value', $inv['bond_face_value'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" name="bond_purchase_date" value="{{ old('bond_purchase_date', $formattedDates['bond_purchase_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Purchase Price (₹)</label>
                                <input type="text" class="form-control" name="bond_purchase_price" placeholder="Enter purchase price" value="{{ old('bond_purchase_price', $inv['bond_purchase_price'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Quantity</label>
                                <input type="text" class="form-control" name="bond_quantity" placeholder="Enter quantity" value="{{ old('bond_quantity', $inv['bond_quantity'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Tax Status</label>
                                <select class="form-select" name="bond_tax_status">
                                    <option value="">Select tax status</option>
                                    @foreach($taxStatuses ?? [] as $ts)
                                        <option value="{{ $ts['value'] }}" {{ (old('bond_tax_status', $inv['bond_tax_status'] ?? '') == $ts['value']) ? 'selected' : '' }}>{{ $ts['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Callable or Non-Callable</label>
                                <select class="form-select" name="bond_callable_status">
                                    <option value="">Select status</option>
                                    @foreach($callableStatuses ?? [] as $cs)
                                        <option value="{{ $cs['value'] }}" {{ (old('bond_callable_status', $inv['bond_callable_status'] ?? '') == $cs['value']) ? 'selected' : '' }}>{{ $cs['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Maturity Date</label>
                                <input type="date" class="form-control" name="bond_maturity_date" value="{{ old('bond_maturity_date', $formattedDates['bond_maturity_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Return (₹)</label>
                                <input type="text" class="form-control" name="bond_total_return" placeholder="Enter total return" value="{{ old('bond_total_return', $inv['bond_total_return'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Yield to Maturity (YTM)</label>
                                <input type="text" class="form-control" name="bond_ytm" placeholder="Enter YTM" value="{{ old('bond_ytm', $inv['bond_ytm'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Coupon Rate (%)</label>
                                <input type="text" class="form-control" name="bond_coupon_rate" placeholder="Enter coupon rate" value="{{ old('bond_coupon_rate', $inv['bond_coupon_rate'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Coupon Frequency</label>
                                <select class="form-select" name="bond_coupon_frequency">
                                    <option value="">Select frequency</option>
                                    @foreach($interestFrequencies ?? [] as $if)
                                        <option value="{{ $if['value'] }}" {{ (old('bond_coupon_frequency', $inv['bond_coupon_frequency'] ?? '') == $if['value']) ? 'selected' : '' }}>{{ $if['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Credit Rating</label>
                                <input type="text" class="form-control" name="bond_credit_rating" placeholder="Enter credit rating" value="{{ old('bond_credit_rating', $inv['bond_credit_rating'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Amount <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="bond_investment_amount" placeholder="Enter investment amount" value="{{ old('bond_investment_amount', $inv['bond_investment_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Total Invested Amount</label>
                                <input type="text" class="form-control" name="bond_total_invested_amount" placeholder="Total invested amount" value="{{ old('bond_total_invested_amount', $inv['bond_total_invested_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Payment Bank</label>
                                <select class="form-select select2" name="bond_payment_bank_id" data-placeholder="Search for payment bank">
                                    <option value="">Search for payment bank</option>
                                    @foreach($banks ?? [] as $bank)
                                        <option value="{{ $bank['id'] }}" {{ (old('bond_payment_bank_id', $inv['bond_payment_bank_id'] ?? '') == $bank['id']) ? 'selected' : '' }}>{{ $bank['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Client (₹)</label>
                                <input type="text" class="form-control" name="bond_paid_by_client" placeholder="Enter amount" value="{{ old('bond_paid_by_client', $inv['bond_paid_by_client'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Agent (₹)</label>
                                <input type="text" class="form-control" name="bond_paid_by_agent" placeholder="Enter amount" value="{{ old('bond_paid_by_agent', $inv['bond_paid_by_agent'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-12">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="bond_remarks" placeholder="Enter remarks" value="{{ old('bond_remarks', $inv['bond_remarks'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Bond Document</label>
                                <input type="file" class="form-control" name="bond_document">
                                @if($isEdit && !empty($inv['bond_document']))
                                    <small class="text-success">Current: {{ $inv['bond_document'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Other Investment Fields -->
                    <div id="otherInvestmentFields" class="{{ (old('investment_type_main', $inv['investment_type_main'] ?? '') == 'other') ? '' : 'd-none' }}">
                        <div class="form-section-title">
                            <div class="d-flex align-items-center">
                                <div class="section-icon">
                                    <i class="fa-solid fa-sack-dollar"></i>
                                </div>
                                Other Investment Details
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="other_transaction_type_id" data-placeholder="Search for transaction type">
                                    <option value="">Search for transaction type</option>
                                    @foreach($otherTransactionTypes ?? [] as $tt)
                                        <option value="{{ $tt['id'] }}" {{ (old('other_transaction_type_id', $inv['other_transaction_type_id'] ?? '') == $tt['id']) ? 'selected' : '' }}>{{ $tt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment To Place <span class="text-danger">*</span></label>
                                <select class="form-select" name="other_investment_to_place">
                                    <option value="">Select investment to place</option>
                                    @foreach($otherInvestmentTypes ?? [] as $oit)
                                        <option value="{{ $oit['value'] }}" {{ (old('other_investment_to_place', $inv['other_investment_to_place'] ?? '') == $oit['value']) ? 'selected' : '' }}>{{ $oit['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Invested Party Name</label>
                                <input type="text" class="form-control" name="other_invested_party_name" placeholder="Enter party name" value="{{ old('other_invested_party_name', $inv['other_invested_party_name'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Type</label>
                                <select class="form-select select2" name="other_investment_type_id" data-placeholder="Search for investment type">
                                    <option value="">Search for investment type</option>
                                    @foreach($investmentTypes ?? [] as $it)
                                        <option value="{{ $it['id'] }}" {{ (old('other_investment_type_id', $inv['other_investment_type_id'] ?? '') == $it['id']) ? 'selected' : '' }}>{{ $it['type_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Document No.</label>
                                <input type="text" class="form-control" name="other_document_no" placeholder="Enter document number" value="{{ old('other_document_no', $inv['other_document_no'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Select Mode <span class="text-danger">*</span></label>
                                <select class="form-select" name="other_mode">
                                    <option value="">Select mode</option>
                                    <option value="monthly" {{ (old('other_mode', $inv['other_mode'] ?? '') == 'monthly') ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ (old('other_mode', $inv['other_mode'] ?? '') == 'quarterly') ? 'selected' : '' }}>Quarterly</option>
                                    <option value="annually" {{ (old('other_mode', $inv['other_mode'] ?? '') == 'annually') ? 'selected' : '' }}>Annually</option>
                                    <option value="one-time" {{ (old('other_mode', $inv['other_mode'] ?? '') == 'one-time') ? 'selected' : '' }}>One Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Deposit Tenure Mode</label>
                                <select class="form-select" name="other_tenure_mode">
                                    <option value="">Select tenure mode</option>
                                    @foreach($tenureModes ?? [] as $tm)
                                        <option value="{{ $tm['value'] }}" {{ (old('other_tenure_mode', $inv['other_tenure_mode'] ?? '') == $tm['value']) ? 'selected' : '' }}>{{ $tm['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Deposit Tenure</label>
                                <input type="text" class="form-control" name="other_tenure" placeholder="Enter tenure" value="{{ old('other_tenure', $inv['other_tenure'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Deposit Date</label>
                                <input type="date" class="form-control" name="other_deposit_date" value="{{ old('other_deposit_date', $formattedDates['other_deposit_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Mature Date</label>
                                <input type="date" class="form-control" name="other_mature_date" value="{{ old('other_mature_date', $formattedDates['other_mature_date'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Next Due Date</label>
                                <input type="date" class="form-control" name="other_next_due_date" value="{{ old('other_next_due_date', $formattedDates['other_next_due_date'] ?? '') }}" min="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Investment Amount <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="other_investment_amount" placeholder="Enter investment amount" value="{{ old('other_investment_amount', $inv['other_investment_amount'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Rate Of Interest (%)</label>
                                <input type="text" class="form-control" name="other_interest_rate" placeholder="Enter interest rate" value="{{ old('other_interest_rate', $inv['other_interest_rate'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Payment Bank</label>
                                <select class="form-select select2" name="other_payment_bank_id" data-placeholder="Search for payment bank">
                                    <option value="">Search for payment bank</option>
                                    @foreach($banks ?? [] as $bank)
                                        <option value="{{ $bank['id'] }}" {{ (old('other_payment_bank_id', $inv['other_payment_bank_id'] ?? '') == $bank['id']) ? 'selected' : '' }}>{{ $bank['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Client (₹)</label>
                                <input type="text" class="form-control" name="other_paid_by_client" placeholder="Enter amount" value="{{ old('other_paid_by_client', $inv['other_paid_by_client'] ?? '') }}">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label">Paid By Agent (₹)</label>
                                <input type="text" class="form-control" name="other_paid_by_agent" placeholder="Enter amount" value="{{ old('other_paid_by_agent', $inv['other_paid_by_agent'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-8">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="other_remarks" placeholder="Enter remarks" value="{{ old('other_remarks', $inv['other_remarks'] ?? '') }}">
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Other Investment Document</label>
                                <input type="file" class="form-control" name="other_document">
                                @if($isEdit && !empty($inv['other_document']))
                                    <small class="text-success">Current: {{ $inv['other_document'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Documents -->
                <div class="investment-step d-none" data-step="3">
                    <div class="form-section-title">
                        <div class="d-flex align-items-center">
                            <div class="section-icon">
                                <i class="fa-solid fa-file-arrow-up"></i>
                            </div>
                            Upload Additional Documents
                        </div>
                    </div>

                    <div id="documentContainer">
                        @if($isEdit && isset($extra_documents) && !empty($extra_documents))
                            @foreach($extra_documents as $ed)
                                <div class="row g-3 align-items-end document-row mt-2">
                                    <input type="hidden" name="doc_id[]" value="{{ (int)($ed['id'] ?? 0) }}">
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Document Type</label>
                                        <div class="d-flex gap-2 align-items-start">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-select doc-type-select" name="doc_type[]">
                                                    <option value="">Select document type</option>
                                                    @foreach($documentTypes ?? [] as $dt)
                                                        <option value="{{ $dt['id'] }}" {{ (isset($ed['document_type_id']) && $ed['document_type_id'] == $dt['id']) ? 'selected' : '' }}>{{ $dt['document_type'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button class="btn btn-dark-blue" type="button" onclick="showModal('documentTypeModal')" ><i class="fa-solid fa-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Document Name</label>
                                        <div class="d-flex gap-2 align-items-start">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-select doc-name-select" name="doc_name[]">
                                                    <option value="">Select document name</option>
                                                    @if(!empty($ed['document_name_id']) && !empty($ed['document_name']))
                                                        <option value="{{ (int)$ed['document_name_id'] }}" selected>{{ $ed['document_name'] }}</option>
                                                    @endif
                                                </select>
                                            </div>
                                            <button class="btn btn-dark-blue" type="button" onclick="showModal('documentNameModal')" ><i class="fa-solid fa-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Upload Document <small class="text-muted">(JPG, PNG, JPEG or PDF)</small></label>
                                        @if(!empty($ed['document_file']))
                                            <div class="small text-success mb-1">
                                                @if(!empty($ed['document_image_data']))
                                                    <img src="{{ $ed['document_image_data'] }}" alt="Doc" class="img-thumbnail" style="max-height: 80px;">
                                                @else
                                                    <span>{{ $ed['document_file'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="doc_file[]" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <label class="form-label">Nos</label>
                                        <input type="text" class="form-control" name="doc_no[]" placeholder="Enter nos" value="{{ $ed['document_nos'] ?? '' }}">
                                    </div>
                                    <div class="col-md-12 col-lg-1 text-lg-end">
                                        <button type="button" class="btn crm-action-btn mb-0 w-100" onclick="this.closest('.document-row').remove()">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <div class="row g-3 align-items-end document-row {{ ($isEdit && !empty($extra_documents)) ? 'mt-2' : '' }}">
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label">Document Type</label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select class="form-select doc-type-select" name="doc_type[]">
                                            <option value="">Select document type</option>
                                            @foreach($documentTypes ?? [] as $dt)
                                                <option value="{{ $dt['id'] }}">{{ $dt['document_type'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="btn btn-dark-blue" type="button" onclick="showModal('documentTypeModal')" ><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label">Document Name</label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select class="form-select doc-name-select" name="doc_name[]">
                                            <option value="">Select document name</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-dark-blue" type="button" onclick="showModal('documentNameModal')" ><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label">Upload Document <small class="text-muted">(JPG, PNG, JPEG or PDF)</small></label>
                                <input type="file" class="form-control" name="doc_file[]" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <label class="form-label">Nos</label>
                                <input type="text" class="form-control" name="doc_no[]" placeholder="Enter nos">
                            </div>
                            <div class="col-md-12 col-lg-1 text-lg-end">
                                <button type="button" class="btn crm-action-btn mb-0 w-100" onclick="addDocumentRow()">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="mt-5 pt-3 border-top row g-2">
                    <div class="col-6 col-md-auto">
                        <a href="{{ route('investment.index') }}" class="btn btn-outline-dark-blue w-100 px-4" id="invCancelBtn">Cancel</a>
                        <button type="button" class="btn btn-outline-dark-blue w-100 px-4 d-none" id="invPrevBtn">Previous</button>
                    </div>
                    <div class="col-6 col-md-auto ms-auto text-end">
                        <button type="button" class="btn btn-dark-blue w-100 px-4" id="invNextBtn">Next</button>
                        <button type="submit" class="btn btn-dark-blue w-100 px-4 d-none" id="invSaveBtn">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Modal for adding new Investment Type / Transaction Type -->
<!-- Modal for adding new Investment Type / Transaction Type -->
<div class="modal fade" id="investmentTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Investment Type</h5>
                <button type="button" class="btn-close" onclick="closeModal('investmentTypeModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Investment Type Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newInvestmentTypeName" placeholder="Enter investment type name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Investment Category <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="investmentCategory" id="categoryMutualFund" value="Mutual Fund" checked>
                        <label class="form-check-label" for="categoryMutualFund">Mutual Fund</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="investmentCategory" id="categoryOtherInvestment" value="Other Investment">
                        <label class="form-check-label" for="categoryOtherInvestment">Other Investment</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="investmentTypeStatus">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('investmentTypeModal')">Cancel</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveInvestmentType(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Family Group -->
<div class="modal fade" id="familyGroupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Family Group</h5>
                <button type="button" class="btn-close" onclick="closeModal('familyGroupModal')"></button>
            </div>
            <div class="modal-body">
                <form id="familyGroupForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="new_family_group_name" class="form-label small fw-bold text-uppercase">Family Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="new_family_group_name" name="group_name" placeholder="Enter family group name" required>
                        </div>
                        <div class="col-md-12">
                            <label for="family_group_policy_holder" class="form-label small fw-bold text-uppercase">Policy Holder <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3 select2-modal" id="family_group_policy_holder" name="policy_holder_id" required>
                                <option value="">Select Policy Holder</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-uppercase">Members</label>
                            <div id="familyGroupMembers">
                                <div class="member-row border rounded-3 p-3 mb-3 bg-light">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small">Member Name</label>
                                            <input type="text" class="form-control form-control-sm" name="members[0][name]" placeholder="Enter name">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Relation</label>
                                            <select class="form-select form-select-sm" name="members[0][relation]">
                                                <option value="">Select</option>
                                                <option value="Spouse">Spouse</option>
                                                <option value="Son">Son</option>
                                                <option value="Daughter">Daughter</option>
                                                <option value="Father">Father</option>
                                                <option value="Mother">Mother</option>
                                                <option value="Brother">Brother</option>
                                                <option value="Sister">Sister</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Contact No.</label>
                                            <input type="text" class="form-control form-control-sm" name="members[0][contact]" placeholder="Enter contact" maxlength="10">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Date 1</label>
                                            <input type="date" class="form-control form-control-sm" name="members[0][date1]">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Date 2</label>
                                            <input type="date" class="form-control form-control-sm" name="members[0][date2]">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addMoreMembersBtn">
                                <i class="fa-solid fa-plus me-1"></i> Add More Members
                            </button>
                        </div>
                        <div class="col-md-12">
                            <label for="family_group_remarks" class="form-label small fw-bold text-uppercase">Remarks</label>
                            <textarea class="form-control rounded-3" id="family_group_remarks" name="remarks" rows="3" placeholder="Enter remarks (optional)"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('familyGroupModal')">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="saveFamilyGroupBtn">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Holder -->
<div class="modal fade" id="holderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Policy Holder</h5>
                <button type="button" class="btn-close" onclick="closeModal('holderModal')"></button>
            </div>
            <div class="modal-body">
                <form id="policyHolderForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="new_holder_name" class="form-label small fw-bold text-uppercase">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="new_holder_name" name="name" placeholder="Enter name" required>
                        </div>
                        <div class="col-md-12">
                            <label for="new_holder_email" class="form-label small fw-bold text-uppercase">Email</label>
                            <input type="email" class="form-control rounded-3" id="new_holder_email" name="email" placeholder="Enter email">
                        </div>
                        <div class="col-md-12">
                            <label for="new_holder_contact" class="form-label small fw-bold text-uppercase">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" id="new_holder_country_code" name="country_code" value="+91" list="policyHolderCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" id="new_holder_contact" name="contact" placeholder="Enter phone" required maxlength="15" inputmode="numeric">
                            </div>
                            <datalist id="policyHolderCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        </div>
                        <div class="col-md-12">
                            <label for="new_holder_address" class="form-label small fw-bold text-uppercase">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control rounded-3" id="new_holder_address" name="address" rows="3" placeholder="Enter address" required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('holderModal')">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="savePolicyHolderBtn">Save Holder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Fund Name -->
<div class="modal fade" id="fundNameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Fund Name <span class="text-danger">*</span></h5>
                <button type="button" class="btn-close" onclick="closeModal('fundNameModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Fund Name</label>
                    <input type="text" class="form-control" id="newFundName" placeholder="Enter fund name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('fundNameModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveFundName(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Folio Number -->
<div class="modal fade" id="folioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Folio Number</h5>
                <button type="button" class="btn-close" onclick="closeModal('folioModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Folio Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newFolioNumber" placeholder="Enter folio number">
                </div>
                <div class="mb-3">
                    <label class="form-label">Investor Name</label>
                    <input type="text" class="form-control" id="newInvestorName" placeholder="Enter investor name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('folioModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveFolio(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Broker -->
<div class="modal fade" id="brokerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Broker</h5>
                <button type="button" class="btn-close" onclick="closeModal('brokerModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Broker Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newBrokerName" placeholder="Enter broker name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('brokerModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveBroker(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Fund Type -->
<div class="modal fade" id="fundTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Fund Type</h5>
                <button type="button" class="btn-close" onclick="closeModal('fundTypeModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Fund Type Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newFundTypeName" placeholder="Enter fund type name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('fundTypeModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveFundType(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Bank -->
<div class="modal fade" id="bankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Bank</h5>
                <button type="button" class="btn-close" onclick="closeModal('bankModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newBankName" placeholder="Enter bank name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('bankModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveBank(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Document Type -->
<div class="modal fade" id="documentTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Document Type</h5>
                <button type="button" class="btn-close" onclick="closeModal('documentTypeModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Document Type <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newDocumentType" placeholder="Enter document type">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('documentTypeModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveDocumentType(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Document Name -->
<div class="modal fade" id="documentNameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Document Name</h5>
                <button type="button" class="btn-close" onclick="closeModal('documentNameModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Document Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newDocumentName" placeholder="Enter document name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('documentNameModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveDocumentName(this)">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Mode -->
<div class="modal fade" id="modeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Mode</h5>
                <button type="button" class="btn-close" onclick="closeModal('modeModal')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Mode Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newModeName" placeholder="Enter mode name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modeModal')">Close</button>
                <button type="button" class="btn btn-dark-blue" onclick="saveMode(this)">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let currentStep = 1;
    const totalSteps = 3;
    const documentTypesData = @json($documentTypes ?? []);
    const documentsByTypeUrl = "{{ route('upload-document.get-documents-by-type') }}";

    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: function() {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%'
        });
    });

    function showModal(modalId) {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
    }

    function closeModal(modalId) {
        const modalEl = document.getElementById(modalId);
        clearModalInputErrors(modalEl);
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }

    function showModalInputError(input, message) {
        const $input = $(input);
        $input.addClass('is-invalid');
        
        let $feedback = $input.siblings('.invalid-feedback');
        if ($feedback.length === 0) {
            $feedback = $('<div class="invalid-feedback"></div>');
            $input.after($feedback);
        }
        $feedback.text(message).show();
    }

    function clearModalInputErrors(modal) {
        const $modal = $(modal);
        $modal.find('.is-invalid').removeClass('is-invalid');
        $modal.find('.invalid-feedback').hide();
    }

    $(document).on('input change', '.modal input, .modal select', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').hide();
    });

    function selectInvestmentType(type) {
        // Update radio buttons
        const radio = document.querySelector(`input[name="investment_type_main"][value="${type}"]`);
        if (radio) {
            radio.checked = true;
            // Update card UI
            document.querySelectorAll('.investment-type-card').forEach(card => card.classList.remove('active'));
            radio.closest('.investment-type-card').classList.add('active');
            
            // Toggle visibility of detail sections
            document.getElementById('mutualFundFields').classList.toggle('d-none', type !== 'mutual_fund');
            document.getElementById('bankFields').classList.toggle('d-none', type !== 'bank');
            document.getElementById('postOfficeFields').classList.toggle('d-none', type !== 'post_office');
            document.getElementById('govtBondsFields').classList.toggle('d-none', type !== 'govt_bonds');
            document.getElementById('otherInvestmentFields').classList.toggle('d-none', type !== 'other');
        }
    }

    function addDocumentRow() {
        const container = document.getElementById('documentContainer');
        const newRow = document.createElement('div');
        newRow.className = 'row g-3 align-items-end document-row mt-2';
        
        let docTypeOptions = '<option value="">Select document type</option>';
        documentTypesData.forEach(function(dt) {
            docTypeOptions += `<option value="${dt.id}">${dt.document_type}</option>`;
        });

        newRow.innerHTML = `
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Document Type</label>
                <div class="d-flex gap-2 align-items-start">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <select class="form-select doc-type-select" name="doc_type[]">${docTypeOptions}</select>
                    </div>
                    <button class="btn btn-dark-blue" type="button" onclick="showModal('documentTypeModal')" ><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Document Name</label>
                <div class="d-flex gap-2 align-items-start">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <select class="form-select doc-name-select" name="doc_name[]">
                            <option value="">Select document name</option>
                        </select>
                    </div>
                    <button class="btn btn-dark-blue" type="button" onclick="showModal('documentNameModal')" ><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Upload Document <small class="text-muted">(JPG, PNG, JPEG or PDF)</small></label>
                <input type="file" class="form-control" name="doc_file[]" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Nos</label>
                <input type="text" class="form-control" name="doc_no[]" placeholder="Enter nos">
            </div>
            <div class="col-md-12 col-lg-1 text-lg-end">
                <button type="button" class="btn crm-action-btn mb-0 w-100" onclick="this.closest('.document-row').remove()">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        
        // Initialize select2 on the new row if needed
        $(newRow).find('.select2').select2({ width: '100%' });
    }

    // Load document names when document type changes (Step 3)
    $(document).on('change', '#documentContainer .doc-type-select, .doc-type-select', function() {
        const typeId = $(this).val();
        const row = $(this).closest('.document-row');
        const nameSelect = row.find('.doc-name-select');
        
        nameSelect.html('<option value="">Select document name</option>');
        if (!typeId) return;

        $.ajax({
            url: documentsByTypeUrl,
            type: 'GET',
            data: { document_type_id: typeId },
            dataType: 'json',
            success: function(res) {
                if (res.status && res.data && res.data.length > 0) {
                    res.data.forEach(function(doc) {
                        nameSelect.append(`<option value="${doc.id}">${doc.document_name}</option>`);
                    });
                }
            }
        });
    });

    // Step navigation
    const stepEls = document.querySelectorAll('.investment-step');
    const stepItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('invPrevBtn');
    const nextBtn = document.getElementById('invNextBtn');
    const saveBtn = document.getElementById('invSaveBtn');
    const cancelBtn = document.getElementById('invCancelBtn');

    function updateStepIndicator(step) {
        stepItems.forEach((item, index) => {
            const itemStep = index + 1;
            item.classList.remove('active', 'completed');
            
            if (itemStep < step) {
                item.classList.add('completed');
                item.querySelector('.step-circle').innerHTML = '<i class="fa-solid fa-check"></i>';
            } else if (itemStep === step) {
                item.classList.add('active');
                item.querySelector('.step-circle').innerHTML = itemStep;
            } else {
                item.querySelector('.step-circle').innerHTML = itemStep;
            }
        });
    }

    function showStep(step) {
        currentStep = step;

        stepEls.forEach(el => {
            el.classList.toggle('d-none', parseInt(el.dataset.step, 10) !== step);
        });

        updateStepIndicator(step);

        if (step === 1) {
            if(prevBtn) prevBtn.classList.add('d-none');
            if(cancelBtn) cancelBtn.classList.remove('d-none');
        } else {
            if(prevBtn) prevBtn.classList.remove('d-none');
            if(cancelBtn) cancelBtn.classList.add('d-none');
        }

        if (step === totalSteps) {
            if(nextBtn) nextBtn.classList.add('d-none');
            if(saveBtn) saveBtn.classList.remove('d-none');
        } else {
            if(nextBtn) nextBtn.classList.remove('d-none');
            if(saveBtn) saveBtn.classList.add('d-none');
        }
        
        // Scroll to top of card
        document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth' });
    }

    stepItems.forEach(item => {
        item.addEventListener('click', function () {
            const step = parseInt(this.dataset.step, 10);
            if (step <= currentStep) {
                showStep(step);
            }
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentStep > 1) showStep(currentStep - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) showStep(currentStep + 1);
            }
        });
    }

    // Form submit: validate all steps before allowing save (including hidden steps)
    const investmentForm = document.getElementById('investmentForm');
    if (investmentForm) {
        investmentForm.addEventListener('submit', function (e) {
            for (let s = 1; s <= totalSteps; s++) {
                if (!validateStepForSubmit(s)) {
                    e.preventDefault();
                    showStep(s);
                    return false;
                }
            }
            
            const submitBtn = document.getElementById('invSaveBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                setTimeout(function() {
                    submitBtn.disabled = true;
                }, 10);
            }
        });
    }

    // Validate step including hidden step div (used on form submit)
    // Skip inputs inside conditional d-none (e.g. bank fields when mutual fund selected)
    function validateStepForSubmit(step) {
        let isValid = true;
        const currentStepEl = document.querySelector(`.investment-step[data-step="${step}"]`);
        if (!currentStepEl) return true;
        const requiredInputs = currentStepEl.querySelectorAll('[required]');
        requiredInputs.forEach(input => {
            const hiddenAncestor = input.closest('.d-none');
            if (hiddenAncestor && hiddenAncestor !== currentStepEl) return; // skip conditional hidden sections
            const value = (input.value || '').trim();
            if (!value) {
                isValid = false;
                showError(input);
            } else {
                clearError(input);
            }
        });
        // if (!isValid) {
        //     Swal.fire({icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
        // }
        return isValid;
    }

    // Initialize
    showStep(1);
    
    // --- Validation Logic ---

    // 1. Identify required fields based on the asterisk in the label
    function markRequiredFields() {
        // Find all labels with text-danger span containing '*'
        const labels = document.querySelectorAll('label.form-label');
        labels.forEach(label => {
            if (label.innerHTML.includes('<span class="text-danger">*</span>')) {
                // Find the corresponding input/select
                let input = null;
                let nextElem = label.nextElementSibling;
                
                if (nextElem) {
                    if (nextElem.classList.contains('input-group') || nextElem.classList.contains('d-flex')) {
                        input = nextElem.querySelector('input, select');
                    } else if (nextElem.tagName === 'INPUT' || nextElem.tagName === 'SELECT' || nextElem.tagName === 'TEXTAREA') {
                        input = nextElem;
                    }
                }
                
                if (input) {
                    input.setAttribute('required', 'true');
                    input.classList.add('required-field');
                    
                    // Add invalid-feedback div if not exists
                    if (!input.parentNode.querySelector('.invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.innerText = 'This value is required.';
                        input.parentNode.appendChild(feedback);
                    }
                }
            }
        });
    }

    // Call it once on load
    markRequiredFields();

    // 2. Validate function for a specific step
    function validateStep(step) {
        let isValid = true;
        const currentStepEl = document.querySelector(`.investment-step[data-step="${step}"]`);
        
        if (!currentStepEl) return true;

        // Find all required inputs in this step
        // Note: We need to check visibility because Step 2 has conditional fields
        const requiredInputs = currentStepEl.querySelectorAll('[required]');
        
        requiredInputs.forEach(input => {
            // Check if the input is essentially visible
            if (input.closest('.d-none')) {
                return; // Skip hidden fields
            }

            let value = input.value.trim();
            
            // Check validity
            if (!value) {
                isValid = false;
                showError(input);
            } else {
                clearError(input);
            }
        });

        // if (!isValid) {
        //     Swal.fire({icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
        // }
        return isValid;
    }

    function showError(input) {
        input.classList.add('is-invalid');
        
        // Handle Select2
        if ($(input).hasClass('select2-hidden-accessible')) {
            const container = $(input).next('.select2-container');
            if (container.length) {
                container.find('.select2-selection').css('border-color', '#fd5c70');
                let feedback = container.parent().find('.invalid-feedback');
                if (feedback.length === 0) {
                     feedback = $(input).nextAll('.invalid-feedback');
                }
                feedback.show();
            }
        } else {
            let feedback = $(input).parent().find('.invalid-feedback');
            if (feedback.length === 0) {
                 feedback = $(input).nextAll('.invalid-feedback');
            }
            feedback.show();
        }
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        
        // Handle Select2
        if ($(input).hasClass('select2-hidden-accessible')) {
            const container = $(input).next('.select2-container');
            if (container.length) {
                container.find('.select2-selection').css('border-color', ''); // Reset
                
                let feedback = container.parent().find('.invalid-feedback');
                if (feedback.length === 0) {
                     feedback = $(input).nextAll('.invalid-feedback');
                }
                feedback.hide();
            }
        } else {
            let feedback = $(input).parent().find('.invalid-feedback');
            if (feedback.length === 0) {
                 feedback = $(input).nextAll('.invalid-feedback');
            }
            feedback.hide();
        }
    }
    
    // 3. Real-time validation clearing
    document.addEventListener('input', function(e) {
        if (e.target.hasAttribute('required') && e.target.value.trim() !== '') {
            clearError(e.target);
        }
    });
    
    $(document).on('change', 'select', function(e) {
        if (e.target.hasAttribute('required') && e.target.value.trim() !== '') {
            clearError(e.target);
        }
    });

    // --- Dynamic Holder Loading & Auto-fill ---

    // 1. Fetch family members when Family Group changes
    $('select[name="family_group_id"]').on('change', function() {
        const groupId = this.value;
        const holderSelect = $('select[name="investment_holder_id"]');
        
        // Clear existing options
        holderSelect.empty().append('<option value="">Search for policy holder name</option>');
        
        if (groupId) {
            $.ajax({
                url: "{{ url('api/holders/by-family-group') }}",
                type: 'GET',
                data: { family_group_id: groupId },
                dataType: 'json',
                success: function(response) {
                    if (response && response.data && response.data.length > 0) {
                        response.data.forEach(function(member) {
                            const option = new Option(member.name || member.holder_name, member.id, false, false);
                            $(option).data('details', member);
                            holderSelect.append(option);
                        });
                    }
                    const selectedHolderId = holderSelect.data('selected-holder');
                    if (selectedHolderId) {
                        holderSelect.val(selectedHolderId).trigger('change', [true]);
                    } else {
                        holderSelect.trigger('change');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching family members:', error);
                }
            });
        }
    });

    // On load check for family group id to preload members
    if ($('select[name="family_group_id"]').val()) {
        $('select[name="family_group_id"]').trigger('change');
    }

    // 2. Auto-fill details when Investment Holder is selected
    $('select[name="investment_holder_id"]').on('change', function(e, isInitial) {
        if (isInitial) return;
        
        const selectedOption = $(this).find('option:selected');
        const details = selectedOption.data('details');
        
        if (details) {
            const fullName = details.name || details.holder_name || details.member_name || '';
            const nameParts = fullName.split(' ');
            const firstName = nameParts[0] || '';
            const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';
            const middleName = nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : (nameParts.length === 2 ? '' : '');
            
            $('input[name="first_name"]').val(firstName);
            $('input[name="middle_name"]').val(middleName);
            $('input[name="last_name"]').val(lastName);
            
            $('input[name="mobile_number"]').val(details.mobile_number || details.contact || '');
            $('input[name="email_address"]').val(details.email_address || details.email || '');
            
            if (details.dob || details.date_of_birth) {
                const dob = (details.date_of_birth || details.dob).split(' ')[0];
                $('input[name="date_of_birth"]').val(dob);
            }
            
            $('input[name="relation_with_head"]').val(details.relation_with_head || details.relation || '');
            
            $('input[name="first_name"]').trigger('input');
        } else {
             if ($(this).val() === '') {
                $('input[name="first_name"]').val('');
                $('input[name="middle_name"]').val('');
                $('input[name="last_name"]').val('');
                $('input[name="mobile_number"]').val('');
                $('input[name="email_address"]').val('');
                $('input[name="date_of_birth"]').val('');
                $('input[name="relation_with_head"]').val('');
             }
        }
    });

    // AJAX save functions (Family Group, Holder, etc.)
    // Dynamic loaders and dynamic rows for Family Group modal
    $('#familyGroupModal').on('show.bs.modal', function () {
        $.ajax({
            url: "{{ url('api/masters/family-groups/customers') }}",
            type: 'GET',
            success: function (res) {
                if ((res.success || res.status) && res.data) {
                    const select = $('#family_group_policy_holder');
                    select.empty().append('<option value="">Select Policy Holder</option>');
                    res.data.forEach(function (customer) {
                        select.append(new Option(customer.name, customer.id));
                    });
                }
            }
        });
    });

    let memberCount = 1;
    $('#addMoreMembersBtn').on('click', function() {
        const memberHtml = `
            <div class="member-row border rounded-3 p-3 mb-3 bg-light">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Member Name</label>
                        <input type="text" class="form-control form-control-sm" name="members[${memberCount}][name]" placeholder="Enter name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Relation</label>
                        <select class="form-select form-select-sm" name="members[${memberCount}][relation]">
                            <option value="">Select</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Contact No.</label>
                        <input type="text" class="form-control form-control-sm" name="members[${memberCount}][contact]" placeholder="Enter contact" maxlength="10">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date 1</label>
                        <input type="date" class="form-control form-control-sm" name="members[${memberCount}][date1]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date 2</label>
                        <input type="date" class="form-control form-control-sm" name="members[${memberCount}][date2]">
                    </div>
                </div>
            </div>
        `;
        $('#familyGroupMembers').append(memberHtml);
        memberCount++;
    });

    // Event listener bound to saveFamilyGroupBtn
    $(document).on('click', '#saveFamilyGroupBtn', function() {
        const btn = this;
        const groupName = $('#new_family_group_name').val().trim();
        const holderId = $('#family_group_policy_holder').val();
        
        if (!groupName) {
            showModalInputError('#new_family_group_name', 'Please enter family group name.');
            return;
        }
        if (!holderId) {
            showModalInputError('#family_group_policy_holder', 'Please select a policy holder.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        const formData = new FormData($('#familyGroupForm')[0]);
        
        $.ajax({
            url: "{{ url('family-groups/store') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status === 'success' || res.id) {
                    const id = res.id || res.data?.id;
                    const label = res.group_name || res.data?.group_name || groupName;
                    
                    const newOption = new Option(label, id, true, true);
                    $('select[name="family_group_id"]').append(newOption).trigger('change');
                    
                    // Reset form
                    $('#familyGroupForm')[0].reset();
                    $('#familyGroupMembers').html(`
                        <div class="member-row border rounded-3 p-3 mb-3 bg-light">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small">Member Name</label>
                                    <input type="text" class="form-control form-control-sm" name="members[0][name]" placeholder="Enter name">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Relation</label>
                                    <select class="form-select form-select-sm" name="members[0][relation]">
                                        <option value="">Select</option>
                                        <option value="Spouse">Spouse</option>
                                        <option value="Son">Son</option>
                                        <option value="Daughter">Daughter</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Brother">Brother</option>
                                        <option value="Sister">Sister</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Contact No.</label>
                                    <input type="text" class="form-control form-control-sm" name="members[0][contact]" placeholder="Enter contact" maxlength="10">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Date 1</label>
                                    <input type="date" class="form-control form-control-sm" name="members[0][date1]">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Date 2</label>
                                    <input type="date" class="form-control form-control-sm" name="members[0][date2]">
                                </div>
                            </div>
                        </div>
                    `);
                    memberCount = 1;
                    
                    closeModal('familyGroupModal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Family Group Created!',
                        text: 'Family group has been saved successfully.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    Swal.fire('Error', res.message || 'Failed to save family group', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Server error saving family group', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    });

    $(document).on('click', '#savePolicyHolderBtn', function() {
        const btn = this;
        const name = $('#new_holder_name').val().trim();
        const email = $('#new_holder_email').val().trim();
        const contact = $('#new_holder_contact').val().trim();
        const countryCode = $('#new_holder_country_code').val().trim() || '+91';
        const address = $('#new_holder_address').val().trim();
        const familyGroupId = $('select[name="family_group_id"]').val();
        
        if (!familyGroupId) {
            Swal.fire('Error', 'Please select family group first.', 'error');
            return;
        }
        if (!name) {
            showModalInputError('#new_holder_name', 'Please enter holder name.');
            return;
        }
        if (!contact) {
            showModalInputError('#new_holder_contact', 'Please enter phone number.');
            return;
        }
        if (!address) {
            showModalInputError('#new_holder_address', 'Please enter address.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('api/holders/store') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                name: name,
                email: email,
                contact: countryCode + contact,
                country_code: countryCode,
                address: address,
                family_group_id: familyGroupId
            },
            success: function(res) {
                if (res.status || res.success) {
                    const id = res.id || res.data?.id;
                    const label = res.name || res.data?.name || name;
                    
                    const newOption = new Option(label, id, true, true);
                    $(newOption).data('details', res.data || {
                        id: id,
                        name: label,
                        email_address: email,
                        mobile_number: countryCode + contact,
                        relation_with_head: 'Self'
                    });
                    
                    $('select[name="investment_holder_id"]').append(newOption).trigger('change');
                    
                    // Reset form
                    $('#policyHolderForm')[0].reset();
                    
                    closeModal('holderModal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Policy holder added successfully.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    Swal.fire('Error', res.message || 'Failed to save policy holder', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Server error saving policy holder', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    });

    function saveFundName(btn) {
        const fundName = $('#newFundName').val();
        if (!fundName) {
            showModalInputError('#newFundName', 'Please enter fund name.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('api/investments/fund-name/store') }}",
            type: 'POST',
            data: { 
                fund_name: fundName,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption = new Option(res.data.name, res.data.id, true, true);
                    $('select[name="mf_fund_name_id"]').append(newOption).trigger('change');
                    closeModal('fundNameModal');
                    $('#newFundName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Fund name added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add fund name.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    function saveFolio(btn) {
        const folioNumber = $('#newFolioNumber').val();
        const investorName = $('#newInvestorName').val();
        if (!folioNumber) {
            showModalInputError('#newFolioNumber', 'Please enter folio number.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('api/investments/folio/store') }}",
            type: 'POST',
            data: { 
                folio_number: folioNumber,
                investor_name: investorName,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption = new Option(res.data.name, res.data.id, true, true);
                    $('select[name="mf_folio_number_id"]').append(newOption).trigger('change');
                    closeModal('folioModal');
                    $('#newFolioNumber').val('');
                    $('#newInvestorName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Folio number added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add folio number.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    function saveBroker(btn) {
        const brokerName = $('#newBrokerName').val();
        if (!brokerName) {
            showModalInputError('#newBrokerName', 'Please enter broker name.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('api/masters/brokers') }}",
            type: 'POST',
            data: { 
                broker_name: brokerName,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption = new Option(res.data.broker_name, res.data.id, true, true);
                    $('select[name="mf_broker_id"]').append(newOption).trigger('change');
                    closeModal('brokerModal');
                    $('#newBrokerName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Broker added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add broker.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    function saveFundType(btn) {
        const fundTypeName = $('#newFundTypeName').val();
        if (!fundTypeName) {
            showModalInputError('#newFundTypeName', 'Please enter fund type name.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('masters/fund-type/store') }}",
            type: 'POST',
            data: { 
                name: fundTypeName,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption = new Option(res.data.name, res.data.id, true, true);
                    $('select[name="mf_fund_type_id"]').append(newOption).trigger('change');
                    closeModal('fundTypeModal');
                    $('#newFundTypeName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Fund Type added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add fund type.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    function saveBank(btn) {
        const bankName = $('#newBankName').val();
        if (!bankName) {
            showModalInputError('#newBankName', 'Please enter bank name.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('api/masters/banks') }}",
            type: 'POST',
            data: { 
                name: bankName,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption = new Option(res.data.name, res.data.id, true, true);
                    // Append to multiple potential bank selects
                    $('select[name="mf_payment_bank_id"], select[name="bank_payment_bank_id"], select[name="po_payment_bank_id"], select[name="bond_payment_bank_id"], select[name="other_payment_bank_id"], select[name="bank_name_id"]').append(newOption).trigger('change');
                    
                    closeModal('bankModal');
                    $('#newBankName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Bank added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add bank.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    function saveDocumentType(btn) {
        // Implement save logic for document type
        closeModal('documentTypeModal');
    }

    function saveDocumentName(btn) {
        // Implement save logic for document name
        closeModal('documentNameModal');
    }

    function saveMode(btn) {
        const modeName = $('#newModeName').val();
        if (!modeName) {
            showModalInputError('#newModeName', 'Please enter mode name.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('api/investments/mode/store') }}",
            type: 'POST',
            data: { 
                mode_name: modeName,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption = new Option(res.data.name, res.data.id, true, true);
                    $('select[name="mf_mode_id"], select[name="bank_mode_id"]').append(newOption).trigger('change');
                    closeModal('modeModal');
                    $('#newModeName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Mode added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add mode.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    let activeTypeSelect = null;
    function saveInvestmentType(btn) {
        const typeName = $('#newInvestmentTypeName').val();
        const category = $('input[name="investmentCategory"]:checked').val();
        const status = $('#investmentTypeStatus').val();

        if (!typeName) {
            showModalInputError('#newInvestmentTypeName', 'Please enter type name.');
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        $.ajax({
            url: "{{ url('masters/investment-type/store') }}",
            type: 'POST',
            data: { 
                name: typeName,
                type: category,
                status: status,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.status || res.success) {
                    const newOption1 = new Option(res.data.type_name, res.data.id, false, false);
                    
                    if (category === 'Mutual Fund') {
                        const newOption2 = new Option(res.data.type_name, res.data.id, false, false);
                        $('select[name="mf_transaction_type_id"]').append(newOption1);
                        $('select[name="mf_investment_type_id"]').append(newOption2);
                    } else {
                        $('select[name="other_investment_type_id"]').append(newOption1);
                    }
                    
                    if (activeTypeSelect) {
                        $('select[name="' + activeTypeSelect + '"]').val(res.data.id).trigger('change');
                    }
                    
                    closeModal('investmentTypeModal');
                    $('#newInvestmentTypeName').val('');
                    Swal.fire({icon: 'success', title: 'Success', text: 'Investment type added successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: res.message || 'Failed to add investment type.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    // Bond filtering
    $('select[name="bond_type"]').on('change', function() {
        const bondId = $(this).val();
        const subSelect = $('#bond_sub_type_id');
        subSelect.val('').trigger('change');
        
        if (!bondId) {
            subSelect.find('option').show();
        } else {
            subSelect.find('option').each(function() {
                const optBondId = $(this).data('bond-id');
                if (!optBondId || optBondId == bondId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
</script>
@endpush
@endsection
