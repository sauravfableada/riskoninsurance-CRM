@extends('layouts.app')

@section('page_title', 'Edit Life Insurance Policy')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-form.css' : '/css/users-form.css') }}?v={{ filemtime(public_path('css/users-form.css')) }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .step-wizard { position: relative; }
        .wizard-steps { --wizard-circle-size: 40px; position: relative; display: flex; justify-content: space-between; align-items: flex-start; width: 100%; }
        .wizard-connector { position: absolute; left: 12.5%; right: 12.5%; top: calc(var(--wizard-circle-size) / 2); transform: translateY(-50%); height: 2px; z-index: 1; pointer-events: none; }
        .wizard-connector-bg, .wizard-connector-fill { position: absolute; left: 0; top: 0; height: 100%; border-radius: 1px; }
        .wizard-connector-bg { right: 0; background: #e9ecef; }
        .wizard-connector-fill { width: 0%; max-width: 100%; background: var(--crm-dark-blue, #1f2a7a); transition: width 0.3s ease; }
        .wizard-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
        .step-circle { width: var(--wizard-circle-size); height: var(--wizard-circle-size); border-radius: 50%; background-color: #fff; color: #212529; display: flex; align-items: center; justify-content: center; font-weight: 600; margin: 0 auto 10px; transition: all 0.3s ease; border: 2px solid #dee2e6; font-size: 14px; position: relative; z-index: 2; box-sizing: border-box; }
        .step-label { display: block; font-size: 11px; color: #495057; white-space: normal; max-width: 90px; margin: 0 auto; line-height: 1.2; font-weight: 500; }
        .wizard-step.active .step-circle { background-color: var(--crm-dark-blue, #1f2a7a) !important; color: #fff !important; border-color: var(--crm-dark-blue, #1f2a7a) !important; }
        .wizard-step.active .step-label { color: var(--crm-dark-blue, #1f2a7a) !important; font-weight: 700 !important; }
        .wizard-step.completed .step-circle { background-color: var(--crm-dark-blue, #1f2a7a); color: #fff; border-color: var(--crm-dark-blue, #1f2a7a); }
        .wizard-step.completed .step-circle .step-circle-num { display: none; }
        .wizard-step.completed .step-circle::after { content: "\f00c"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 14px; }
        .wizard-step.completed .step-label { color: var(--crm-dark-blue, #1f2a7a); }

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #d8dde6;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            padding: 0 0.25rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 0.6rem;
            padding-right: 2rem;
            color: #111827;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 6px;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #9fb8d9;
            box-shadow: 0 0 0 0.2rem rgba(31, 42, 122, 0.08);
        }

        .inline-select2-group {
            flex-wrap: nowrap;
            align-items: stretch;
        }

        .inline-select2-group .select2-container {
            flex: 1 1 auto;
            min-width: 0;
        }

        .inline-select2-group .btn {
            flex: 0 0 auto;
        }

        .select2-dropdown {
            border-color: #d8dde6;
            border-radius: 0.5rem;
            overflow: hidden;
            z-index: 9999;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden staff-form-card">

        {{-- Header --}}
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Edit Life Insurance Policy</h1>
                    <p class="text-muted small mb-0">Update the details and save changes to this life insurance policy.</p>
                </div>
                <a href="{{ route('life-insurance.index') }}" class="btn btn-dark-blue customer-back-btn">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- Body --}}
        <div class="card-body p-3 p-md-4">

            {{-- Step Wizard --}}
            <div class="step-wizard mb-5">
                <div class="wizard-steps">
                    <div class="wizard-connector" aria-hidden="true">
                        <span class="wizard-connector-bg"></span>
                        <span class="wizard-connector-fill" id="wizardConnectorFill"></span>
                    </div>
                    <div class="wizard-step active" data-step="1">
                        <div class="step-circle mx-auto"><span class="step-circle-num">1</span></div>
                        <span class="step-label">Personal Info</span>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="step-circle mx-auto"><span class="step-circle-num">2</span></div>
                        <span class="step-label">Policy Details</span>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="step-circle mx-auto"><span class="step-circle-num">3</span></div>
                        <span class="step-label">Agency & Payment</span>
                    </div>
                    <div class="wizard-step" data-step="4">
                        <div class="step-circle mx-auto"><span class="step-circle-num">4</span></div>
                        <span class="step-label">Documents</span>
                    </div>
                </div>
            </div>

            <form id="lifeInsuranceForm" enctype="multipart/form-data" novalidate>
                        @csrf
                        
                        <!-- Step 1: Personal Information -->
                        <div class="wizard-content active" id="step1">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="transaction_date">
                                        <i class="bi bi-calendar-event-fill"></i> Transaction Date <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    <div class="invalid-feedback staff-validation" id="transaction_date_error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="document_number">
                                        <i class="bi bi-hash"></i> Document Number
                                    </label>
                                    <input type="text" name="document_number" id="document_number" class="form-control bg-light" placeholder="Auto-generated" readonly>
                                    <div class="invalid-feedback staff-validation" id="document_number_error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="family_group_id">
                                        <i class="bi bi-people-fill"></i> Family Group <span class="text-danger ms-1">*</span>
                                    </label>
                                    <div class="input-group inline-select2-group">
                                        <select name="family_group_id" id="family_group_id" class="form-select select2" required>
                                            <option value="">Select Group</option>
                                            @foreach($familyGroups as $group)
                                                <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addFamilyGroupBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="invalid-feedback staff-validation" id="family_group_id_error"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="policy_holder_id">
                                        <i class="bi bi-person-fill"></i> Policy Holder Name <span class="text-danger ms-1">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select name="policy_holder_id" id="policy_holder_id" class="form-select" required>
                                            <option value="">Select Holder</option>
                                            @foreach($policyHolders as $holder)
                                                <option value="{{ $holder->id }}" data-email="{{ $holder->email }}" data-contact="{{ $holder->phone }}" data-address="{{ $holder->address }}" data-dob="{{ $holder->dob }}" data-city-id="{{ $holder->city_id }}">{{ $holder->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addPolicyHolderBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="invalid-feedback staff-validation" id="policy_holder_id_error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="gender">
                                        <i class="bi bi-gender-ambiguous"></i> Gender
                                    </label>
                                    <select name="gender" id="gender" class="form-select">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="first_name">
                                        <i class="bi bi-person"></i> First Name
                                    </label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Enter first name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="middle_name">
                                        <i class="bi bi-person"></i> Middle Name
                                    </label>
                                    <input type="text" name="middle_name" id="middle_name" class="form-control" placeholder="Enter middle name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="last_name">
                                        <i class="bi bi-person"></i> Last Name
                                    </label>
                                    <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Enter last name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="mobile_number">
                                        <i class="bi bi-telephone-fill"></i> Mobile Number
                                    </label>
                                    <input type="tel" name="mobile_number" id="mobile_number" class="form-control" placeholder="Enter mobile number" maxlength="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="email_address">
                                        <i class="bi bi-envelope-fill"></i> Email Address
                                    </label>
                                    <input type="email" name="email_address" id="email_address" class="form-control" placeholder="Enter email address">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="dob">
                                        <i class="bi bi-calendar-fill"></i> Date of Birth
                                    </label>
                                    <input type="date" name="dob" id="dob" class="form-control" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="relation_with_head">
                                        <i class="bi bi-diagram-3-fill"></i> Relation With Head
                                    </label>
                                    <select name="relation_with_head" id="relation_with_head" class="form-select select2">
                                        <option value="Self" selected>Self</option>
                                        <option value="Spouse">Spouse</option>
                                        <option value="Son">Son</option>
                                        <option value="Daughter">Daughter</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="address_line1">
                                        <i class="bi bi-geo-alt-fill"></i> Address Line 1
                                    </label>
                                    <input type="text" name="address_line1" id="address_line1" class="form-control" placeholder="Enter building, apartment, street">
                                </div>

                                {{-- Address Line 2 --}}
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="address_line2">
                                        <i class="bi bi-geo-alt"></i> Address Line 2
                                    </label>
                                    <input type="text" name="address_line2" id="address_line2" class="form-control" placeholder="Address line 2">
                                </div>

                                {{-- City --}}
                                <div class="col-12">
                                    <label class="form-label d-flex align-items-center gap-2" for="city_id">
                                        <i class="bi bi-building-fill"></i> City
                                    </label>
                                    <select name="city_id" id="city_id" class="form-select select2">
                                        <option value="">Select City</option>
                                        @foreach($cities as $city)
                                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Policy Details -->
                        <div class="wizard-content d-none" id="step2">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="insurance_company_id">
                                        <i class="bi bi-building"></i> Insurance Company <span class="text-danger ms-1">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select name="insurance_company_id" id="insurance_company_id" class="form-select" required>
                                            <option value="">Select Company</option>
                                            @foreach($insuranceCompanies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addCompanyBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="invalid-feedback staff-validation" id="insurance_company_id_error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="branch_id">
                                        <i class="bi bi-diagram-2-fill"></i> Branch <span class="text-danger ms-1">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select name="branch_id" id="branch_id" class="form-select" required>
                                            <option value="">Select Company First</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addBranchBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="invalid-feedback staff-validation" id="branch_id_error"></div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="policy_type_id">
                                        <i class="bi bi-card-checklist"></i> Zone    
                                    </label>
                                    <select name="zone" id="zone" class="form-select">
                                            <option value="">Search for zones</option>
                                            <option value="North">North</option>
                                            <option value="South">South</option>
                                            <option value="East">East</option>
                                            <option value="West">West</option>
                                            <option value="Central">Central</option>
                                        </select>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_policy_received" id="is_policy_received" value="1" checked>
                                        <label class="form-check-label fw-semibold" for="is_policy_received">Is Policy Received</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="policy_number">
                                        <i class="bi bi-file-earmark-text-fill"></i> Policy Number
                                    </label>
                                    <input type="text" name="policy_number" id="policy_number" class="form-control" placeholder="Enter policy number">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="insurance_plan_name">
                                        <i class="bi bi-card-list"></i> Plan Name
                                    </label>
                                    <div class="input-group">
                                        <select name="insurance_plan_name" id="insurance_plan_name" class="form-select">
                                            <option value="">Select Plan</option>
                                            @foreach($insurancePlanNames as $plan)
                                                <option value="{{ $plan->name }}">{{ $plan->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addPlanNameBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center gap-2" for="insurance_plan_type">
                                        <i class="bi bi-card-list"></i> Plan Type
                                    </label>
                                    <div class="input-group">
                                        <select name="insurance_plan_type" id="insurance_plan_type" class="form-select">
                                            <option value="">Select Plan Type</option>
                                            @foreach($insurancePlanTypes as $type)
                                                <option value="{{ $type->name }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addPlanTypeBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label d-flex align-items-center gap-2" for="policy_documents">
                                        <i class="bi bi-card-list"></i> Policy Documents (JPG, PNG, JPEG Or PDF) <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="file" name="policy_documents[]" id="policy_documents" class="form-control @error('policy_documents') is-invalid @enderror" multiple>
                                    <div class="invalid-feedback staff-validation" id="policy_documents_error"></div>
                                    @php
                                        $documents = is_string($policy->policy_documents) ? json_decode($policy->policy_documents, true) : ($policy->policy_documents ?? []);
                                    @endphp
                                    <div id="policy_documents_preview" class="d-flex flex-wrap gap-2 mt-2">
                                        @if(!empty($documents) && is_array($documents))
                                            @foreach($documents as $doc)
                                                @if(is_string($doc) && trim($doc) !== '')
                                                    @php $extension = strtolower(pathinfo($doc, PATHINFO_EXTENSION)); @endphp
                                                    @if(in_array($extension, ['jpg','jpeg','png','gif','webp','bmp']))
                                                        <div class="border rounded p-1">
                                                            <a href="{{ asset($doc) }}" target="_blank">
                                                                <img src="{{ asset($doc) }}" width="120" height="120" style="object-fit: cover;" alt="doc">
                                                            </a>
                                                        </div>
                                                    @else
                                                        <div class="border rounded p-2">
                                                            <a href="{{ asset($doc) }}" target="_blank">{{ basename($doc) }}</a>
                                                        </div>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="premium_mode">
                                        <i class="bi bi-arrow-repeat"></i> Premium Mode <span class="text-danger ms-1">*</span>
                                    </label>
                                    <select name="premium_mode" id="premium_mode" class="form-select">
                                        <option value="Yearly" selected>Yearly</option>
                                        <option value="Half-Yearly">Half-Yearly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Single">Single</option>
                                    </select>
                                    <div class="invalid-feedback staff-validation" id="premium_mode_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="sum_assured">
                                        <i class="bi bi-currency-rupee"></i> Sum Assured <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="number" name="sum_assured" id="sum_assured" class="form-control" placeholder="Enter Sum Assured">
                                    <div class="invalid-feedback staff-validation" id="sum_assured_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="risk_term">
                                        <i class="bi bi-hourglass-split"></i> Risk Term <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="text" name="risk_term" id="risk_term" class="form-control" placeholder="Enter Risk Term">
                                    <div class="invalid-feedback staff-validation" id="risk_term_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="premium_paying_term">
                                        <i class="bi bi-hourglass"></i> Premium Paying Term <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="text" name="premium_paying_term" id="premium_paying_term" class="form-control" placeholder="Enter Premium Paying Term">
                                    <div class="invalid-feedback staff-validation" id="premium_paying_term_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="risk_start_date">
                                        <i class="bi bi-calendar-check-fill"></i> Risk Start Date (Doc) <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="date" name="risk_start_date" id="risk_start_date" class="form-control">
                                    <div class="invalid-feedback staff-validation" id="risk_start_date_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="maturity_date">
                                        <i class="bi bi-calendar-x-fill"></i> Maturity Date
                                    </label>
                                    <input type="date" name="maturity_date" id="maturity_date" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="last_premium_payment_date">
                                        <i class="bi bi-cash"></i> Last Premium Payment Date
                                    </label>
                                    <input type="date" name="last_premium_payment_date" id="last_premium_payment_date" class="form-control" placeholder="0.00">
                                    <div class="invalid-feedback staff-validation" id="last_premium_payment_date_error"></div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="next_premium_due_date">
                                        <i class="bi bi-cash-stack"></i> Next Premium Due Date (FUP)
                                    </label>
                                    <input type="date" name="next_premium_due_date" id="next_premium_due_date" class="form-control bg-light" placeholder="0.00">
                                    <div class="invalid-feedback staff-validation" id="next_premium_due_date_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="total_premium_amount">
                                        <i class="bi bi-cash-stack"></i> Basic Premium Amount <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="number" name="basic_premium_amount" id="basic_premium_amount" class="form-control bg-light" placeholder="0.00">
                                    <div class="invalid-feedback staff-validation" id="basic_premium_amount_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="total_premium_amount">
                                        <i class="bi bi-cash-stack"></i> Tax Amount
                                    </label>
                                    <input type="number" name="tax_amount" id="tax_amount" class="form-control bg-light" placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="total_premium_amount">
                                        <i class="bi bi-cash-stack"></i> Total Premium Amount <span class="text-danger ms-1">*</span>
                                    </label>
                                    <input type="number" name="total_premium_amount" id="total_premium_amount" class="form-control bg-light" placeholder="0.00">
                                    <div class="invalid-feedback staff-validation" id="total_premium_amount_error"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="total_premium_amount">
                                        <i class="bi bi-cash-stack"></i> Commissionable Amount
                                    </label>
                                    <input type="number" name="commissionable_amount" id="commissionable_amount" class="form-control bg-light" placeholder="0.00">
                                </div>
                                <div class="col-12">
                                    <div class="card card-body bg-light rounded-4 mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0 fw-semibold">Riders</h6>
                                            <small class="text-muted">Select riders and enter rider values</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width: 40px;"></th>
                                                        <th>Rider Name</th>
                                                        <th style="width: 170px;">Sum Assured</th>
                                                        <th style="width: 170px;">Risk Term</th>
                                                        <th style="width: 170px;">Premium Paying Term</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $riderNames = [
                                                            'Premium Waiver Benefit',
                                                            'Accident Cover',
                                                            'Double Accident Cover',
                                                            'Term Riders Benefit',
                                                            'Critical Illness Cover',
                                                            'New Critical Illness Cover',
                                                            'New Term Riders Benefit',
                                                            'Inflation And Education Costs',
                                                            'Liquidity And Riders',
                                                            'Premium Payment Frequency',
                                                            'Regular Payout Option',
                                                            'Associated Charges In The Policy',
                                                            'Income Benefit Rider',
                                                            'Waiver Of Premium On Disability Rider',
                                                            'Hospital Cash Benefit Rider',
                                                            'Accelerated Death Benefit',
                                                        ];
                                                    @endphp
                                                    @foreach($riderNames as $index => $riderName)
                                                        <tr>
                                                            <td class="text-center">
                                                                <div class="form-check">
                                                                    <input class="form-check-input rider-checkbox" type="checkbox" id="rider_checkbox_{{ $index }}" name="riders[{{ $index }}][selected]" value="1">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <label class="form-check-label" for="rider_checkbox_{{ $index }}">{{ $riderName }}</label>
                                                                <input type="hidden" name="riders[{{ $index }}][name]" value="{{ $riderName }}">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" name="riders[{{ $index }}][sum_assured]" class="form-control form-control-sm rider-input" disabled placeholder="Sum Assured">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="1" min="0" name="riders[{{ $index }}][risk_term]" class="form-control form-control-sm rider-input" disabled placeholder="Risk Term">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="1" min="0" name="riders[{{ $index }}][premium_paying_term]" class="form-control form-control-sm rider-input" disabled placeholder="Premium Paying Term">
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Agency & Payment -->
                        <div class="wizard-content d-none" id="step3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="policy_status">
                                        <i class="bi bi-toggle-on"></i> Policy Status <span class="text-danger ms-1">*</span>
                                    </label>
                                    <select name="policy_status" id="policy_status" class="form-select">
                                        <option value="Inforce" selected>Inforce</option>
                                        <option value="Lapsed">Lapsed</option>
                                        <option value="Surrendered">Surrendered</option>
                                        <option value="Matured">Matured</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                    <div class="invalid-feedback staff-validation" id="policy_status_error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="broker_id">
                                        <i class="bi bi-briefcase-fill"></i> Broker <span class="text-danger ms-1">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select name="broker_id" id="broker_id" class="form-select">
                                            <option value="">Select Broker</option>
                                            @foreach($brokers as $broker)
                                                <option value="{{ $broker->id }}">{{ $broker->broker_name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addBrokerBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="invalid-feedback staff-validation" id="broker_id_error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="agency_level_id">
                                        <i class="bi bi-person-badge-fill"></i> Agency Level <span class="text-danger ms-1">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select name="agency_level_id" id="agency_level_id" class="form-select">
                                            <option value="">Select Agency Level</option>
                                            @foreach($agencyLevels as $level)
                                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addAgencyLevelBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="invalid-feedback staff-validation" id="agency_level_id_error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="sub_agent_id">
                                        <i class="bi bi-person-badge-fill"></i> Agency
                                    </label>
                                    <div class="input-group">
                                        <select name="sub_agent_id" id="sub_agent_id" class="form-select">
                                            <option value="">Select Agency Level First</option>
                                            @foreach($subAgents as $agent)
                                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addAgencyBtn"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="nominee_name">
                                        <i class="bi bi-person-heart"></i> Nominee Name
                                    </label>
                                    <input type="text" name="nominee_name" id="nominee_name" class="form-control" placeholder="Enter nominee name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="nominee_name">
                                        <i class="bi bi-person-heart"></i> Nominee Contact No
                                    </label>
                                    <input type="text" name="nominee_contact_no" id="nominee_contact_no" class="form-control" placeholder="Enter nominee contact number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="paid_by_agent">
                                        <i class="bi bi-person-badge-fill"></i> Paid By Agent
                                    </label>
                                    <div class="input-group">
                                        <select name="paid_by_agent" id="paid_by_agent" class="form-select">
                                            <option value="">Select Payment Method</option>
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3 addPaymentMethodBtn" data-target="#paid_by_agent"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="agent_amount">
                                        <i class="bi bi-person-heart"></i> Amount
                                    </label>
                                    <input type="number" step="0.01" min="0" name="agent_amount" id="agent_amount" class="form-control" placeholder="Enter amount">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="paid_by_client">
                                        <i class="bi bi-person-badge-fill"></i> Paid By Client
                                    </label>
                                    <div class="input-group">
                                        <select name="paid_by_client" id="paid_by_client" class="form-select">
                                            <option value="">Select Payment Method</option>
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-dark-blue px-3 addPaymentMethodBtn" data-target="#paid_by_client"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center gap-2" for="client_amount">
                                        <i class="bi bi-person-heart"></i> Amount
                                    </label>
                                    <input type="number" step="0.01" min="0" name="client_amount" id="client_amount" class="form-control" placeholder="Enter amount">
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-flex align-items-center gap-2" for="remarks">
                                        <i class="bi bi-chat-left-text-fill"></i> Remarks
                                    </label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter any additional notes"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Documents -->
                        <div class="wizard-content d-none" id="step4">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <h6 class="fw-semibold mb-0">Policy Documents</h6>
                                <button type="button" class="btn btn-dark-blue btn-sm" id="addDocumentRowBtn">
                                    <i class="bi bi-plus-lg me-1"></i> Add More
                                </button>
                            </div>

                            <div id="documentRows" class="d-flex flex-column gap-3">
                                <div class="document-row border rounded-3 p-3" data-index="0">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold" for="document_type_0">Document Type</label>
                                            <select name="documents[0][document_type_id]" id="document_type_0" class="form-select bg-white document-type-select">
                                                <option value="">Select Document Type</option>
                                                @foreach($documentTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->document_type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold" for="document_name_0">Document Name</label>
                                            <select name="documents[0][document_id]" id="document_name_0" class="form-select bg-white document-name-select">
                                                <option value="">Select Document Type First</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold" for="nos_0">Nos</label>
                                            <input type="text" name="documents[0][nos]" id="nos_0" class="form-control" placeholder="Enter Nos">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold" for="policy_documents_0">Upload</label>
                                            <input type="file" name="documents[0][files][]" id="policy_documents_0" class="form-control document-file-input" multiple accept=".jpg,.jpeg,.png,.pdf">
                                        </div>
                                        <div class="col-md-1 text-md-end">
                                            <button type="button" class="btn crm-action-btn remove-document-row" disabled>
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted small mt-3 mb-0">JPG, PNG, JPEG, and PDF are allowed. Max size 5MB per file.</p>
                            <div id="document_list" class="mt-3"></div>
                        </div>

                        <div class="mt-4 pt-4 border-top row g-2 customer-form-actions">
                            <div class="col-6 col-md-auto">
                                <a href="{{ route('life-insurance.index') }}" class="btn btn-outline-dark-blue w-100 px-4" id="cancelBtn">Cancel</a>
                                <button type="button" class="btn btn-outline-dark-blue w-100 px-4 d-none" id="prevBtn">Previous</button>
                            </div>
                            <div class="col-6 col-md-auto ms-auto text-end">
                                <button type="button" class="btn btn-dark-blue w-100 px-4" id="nextBtn">Next</button>
                                <button type="submit" class="btn btn-dark-blue w-100 px-4 d-none" id="submitBtn">Submit</button>
                            </div>
                        </div>
                    </form>
        </div>
    </div>
</div>

@include('life-insurance.modals')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        const policy = @json($policy);
        const existingDbDocs = Array.isArray(policy?.policy_document_rows) ? policy.policy_document_rows : [];
        const existingColumnDocs = Array.isArray(policy?.policy_documents)
            ? policy.policy_documents
            : (typeof policy?.policy_documents === 'string' && policy.policy_documents
                ? (JSON.parse(policy.policy_documents || '[]') || [])
                : []);
        const existingPolicyDocumentCount = existingDbDocs.length + existingColumnDocs.filter(function(item) {
            return typeof item === 'string' && item.trim() !== '';
        }).length;

        if ($.fn.select2) {
            $('#family_group_id').select2({
                width: '100%',
                placeholder: 'Select Group',
                allowClear: true
            });

            $('#relation_with_head').select2({
                width: '100%',
                placeholder: 'Select Relation',
                allowClear: true
            });

            $('#city_id').select2({
                width: '100%',
                placeholder: 'Select City',
                allowClear: true
            });

            $('#area_id').select2({
                width: '100%',
                placeholder: 'Search Area...',
                allowClear: true
            });
        }

        Object.keys(policy || {}).forEach(function(key) {
            const $field = $('[name="' + key + '"]');
            if (!$field.length) return;
            if ($field.is(':checkbox')) {
                $field.prop('checked', !!policy[key]);
            } else {
                $field.val(policy[key]);
            }
        });

        if (policy && policy.city_id) {
            $('#city_id').val(policy.city_id);
            loadAreasByCity(policy.city_id, policy.area_id || '');
        }

        if (policy && policy.agency_level_id) {
            $('#agency_level_id').val(policy.agency_level_id);
            loadAgenciesByLevel(policy.agency_level_id, policy.sub_agent_id || '');
        }

        function prefillRidersFromPolicy() {
            const riderRows = Array.isArray(policy?.policy_riders) ? policy.policy_riders : [];
            if (!riderRows.length) return;
            const normalizeRiderName = function(name) {
                return String(name || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9 ]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            };

            riderRows.forEach(function(rider) {
                const riderName = normalizeRiderName(rider.rider_name);
                let $nameInput = $('input[type="hidden"][name^="riders["][name$="[name]"]').filter(function() {
                    const formName = normalizeRiderName($(this).val());
                    return formName === riderName;
                }).first();

                if (!$nameInput.length) return;
                const baseName = $nameInput.attr('name').replace('[name]', '');
                const $checkbox = $('input[name="' + baseName + '[selected]"]');
                const $sum = $('input[name="' + baseName + '[sum_assured]"]');
                const $risk = $('input[name="' + baseName + '[risk_term]"]');
                const $ppt = $('input[name="' + baseName + '[premium_paying_term]"]');

                $checkbox.prop('checked', true);
                $checkbox.trigger('change');
                $sum.val(rider.sum_assured || '');
                $risk.val(rider.risk_term || '');
                $ppt.val(rider.premium_paying_term || '');
            });
        }

        if (existingDbDocs.length || existingColumnDocs.length) {
            const dbList = existingDbDocs.map(function(doc, idx) {
                const file = doc.document_file || '';
                const name = file ? file.split('/').pop() : ('Document ' + (idx + 1));
                return `<div class="small mb-1"><a href="/${file}" target="_blank">${name}</a></div>`;
            });
            const colList = existingColumnDocs
                .filter(function(file) { return typeof file === 'string' && file.trim() !== ''; })
                .map(function(file, idx) {
                    const name = file.split('/').pop() || ('Policy Document ' + (idx + 1));
                    return `<div class="small mb-1"><a href="/${file}" target="_blank">${name}</a></div>`;
                });
            const existing = dbList.concat(colList).join('');
            $('#document_list').html(
                `<div class="alert alert-light border mb-0"><strong>Existing Uploaded Documents:</strong><div class="mt-2">${existing}</div></div>`
            );
        }

        function renderTopPolicyPreview() {
            const $preview = $('#policy_documents_preview');
            $preview.empty();
            const files = $('#policy_documents')[0]?.files || [];
            Array.from(files).forEach(function(file) {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext)) {
                    const url = URL.createObjectURL(file);
                    $preview.append(`<img src="${url}" alt="${file.name}" style="width:56px;height:56px;object-fit:cover;border:1px solid #ddd;border-radius:6px;">`);
                } else {
                    $preview.append(`<span class="badge bg-light text-dark border">${file.name}</span>`);
                }
            });
        }

        function renderExistingTopPolicyPreview() {
            const $preview = $('#policy_documents_preview');
            const fromColumn = existingColumnDocs
                .filter(function(file) { return typeof file === 'string' && file.trim() !== ''; })
                .map(function(file) {
                    const ext = (file.split('.').pop() || '').toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext)) {
                        return `<a href="/${file}" target="_blank"><img src="/${file}" alt="" style="width:56px;height:56px;object-fit:cover;border:1px solid #ddd;border-radius:6px;"></a>`;
                    }
                    const name = file.split('/').pop() || 'Document';
                    return `<a href="/${file}" target="_blank" class="badge bg-light text-dark border text-decoration-none">${name}</a>`;
                });

            if (fromColumn.length) {
                $preview.html(fromColumn.join(''));
            }
        }

        renderExistingTopPolicyPreview();
        $('#policy_documents').on('change', renderTopPolicyPreview);
        let currentStep = 1;
        const totalSteps = 4;

        // Keep existing document number on edit; only generate if missing
        if (!$('#document_number').val()) {
            const docNum = 'LI-' + Date.now().toString().slice(-8) + Math.floor(Math.random() * 100).toString().padStart(2, '0');
            $('#document_number').val(docNum);
        }

        function updateWizardUI() {
            $('.wizard-step').each(function() {
                const step = $(this).data('step');
                if (step < currentStep) {
                    $(this).addClass('completed').removeClass('active');
                } else if (step === currentStep) {
                    $(this).addClass('active').removeClass('completed');
                } else {
                    $(this).removeClass('active completed');
                }
            });

            $('.wizard-content').addClass('d-none');
            $('#step' + currentStep).removeClass('d-none');

            if (currentStep === 1) {
                $('#prevBtn').addClass('d-none');
                $('#cancelBtn').removeClass('d-none');
            } else {
                $('#prevBtn').removeClass('d-none');
                $('#cancelBtn').addClass('d-none');
            }

            if (currentStep === totalSteps) {
                $('#nextBtn').addClass('d-none');
                $('#submitBtn').removeClass('d-none');
            } else {
                $('#nextBtn').removeClass('d-none');
                $('#submitBtn').addClass('d-none');
            }

            const connectorPct = totalSteps <= 1
                ? 100
                : ((currentStep - 0.5) / (totalSteps - 0.5)) * 100;
            $('#wizardConnectorFill').css('width', connectorPct + '%');
        }

        updateWizardUI();

        function clearFieldValidation(fieldId) {
            const $input = $('#' + fieldId);
            $input.removeClass('is-invalid is-valid');
            const $error = $('#' + fieldId + '_error');
            $error.text('').removeClass('d-block').addClass('d-none');
        }

        function showFieldError(fieldId, message) {
            const $input = $('#' + fieldId);
            $input.removeClass('is-valid').addClass('is-invalid');
            const $error = $('#' + fieldId + '_error');
            $error.text(message).removeClass('d-none').addClass('d-block');
        }

        function showFieldValid(fieldId) {
            const $input = $('#' + fieldId);
            $input.removeClass('is-invalid').addClass('is-valid');
            const $error = $('#' + fieldId + '_error');
            $error.text('').removeClass('d-block').addClass('d-none');
        }

        function validateStep(step) {
            if (step === 1) {
                clearFieldValidation('transaction_date');
                clearFieldValidation('document_number');
                clearFieldValidation('family_group_id');
                clearFieldValidation('policy_holder_id');

                if (!$('#transaction_date').val()) {
                    showFieldError('transaction_date', 'Transaction date is required.');
                    return { step: 1 };
                }
                showFieldValid('transaction_date');

                if (!$('#family_group_id').val()) {
                    showFieldError('family_group_id', 'Please select a family group.');
                    return { step: 1 };
                }
                showFieldValid('family_group_id');

                if (!$('#policy_holder_id').val()) {
                    showFieldError('policy_holder_id', 'Please select a policy holder.');
                    return { step: 1 };
                }
                showFieldValid('policy_holder_id');

                if (!$('#document_number').val()) {
                    showFieldError('document_number', 'Document number is required.');
                    return { step: 1 };
                }
                showFieldValid('document_number');
            }
            if (step === 2) {
                const fields = [
                    'insurance_company_id', 'branch_id', 'premium_mode', 
                    'premium_paying_term', 'risk_term', 'sum_assured', 
                    'risk_start_date', 'basic_premium_amount', 'total_premium_amount'
                ];
                
                let hasError = false;

                fields.forEach(field => {
                    clearFieldValidation(field);
                    if (!$('#' + field).val()) {
                        showFieldError(field, 'This field is required.');
                        hasError = true;
                    } else {
                        showFieldValid(field);
                    }
                });

                // Policy Documents validation - only if policy is received
                clearFieldValidation('policy_documents');
                if ($('#is_policy_received').is(':checked')) {
                    const hasTopLevelUpload = ($('#policy_documents')[0]?.files?.length || 0) > 0;
                    const hasRowUpload = $('.document-file-input').toArray().some(input => (input.files?.length || 0) > 0);
                    const hasExistingUploads = existingPolicyDocumentCount > 0;
                    const hasRenderedPreview = $('#policy_documents_preview').find('img,a,.badge,.border').length > 0;
                    const hasExistingList = $('#document_list').text().toLowerCase().includes('existing');
                    if (!hasTopLevelUpload && !hasRowUpload && !hasExistingUploads && !hasRenderedPreview && !hasExistingList) {
                        showFieldError('policy_documents', 'Please upload at least one document.');
                        hasError = true;
                    } else {
                        showFieldValid('policy_documents');
                    }
                }

                // Rider Validation
                $('.rider-checkbox').each(function() {
                    const $row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        $row.find('.rider-input').each(function() {
                            if (!$(this).val()) {
                                $(this).addClass('is-invalid');
                                hasError = true;
                            } else {
                                $(this).removeClass('is-invalid').addClass('is-valid');
                            }
                        });
                    } else {
                        $row.find('.rider-input').removeClass('is-invalid is-valid');
                    }
                });

                if (hasError) return { step: 2 };
            }
            if (step === 3) {
                const fields = ['policy_status', 'broker_id', 'agency_level_id'];
                let hasError = false;

                fields.forEach(field => {
                    clearFieldValidation(field);
                    if (!$('#' + field).val()) {
                        showFieldError(field, 'This field is required.');
                        hasError = true;
                    } else {
                        showFieldValid(field);
                    }
                });

                if (hasError) return { step: 3 };
            }
            return null;
        }

        function validateAllStepsForSubmit() {
            for (let s = 1; s <= totalSteps; s++) {
                const err = validateStep(s);
                if (err) return err;
            }
            return null;
        }

        $('#nextBtn').click(function() {
            const err = validateStep(currentStep);
            if (err) {
                const $firstInvalid = $('.is-invalid').first();
                if ($firstInvalid.length) {
                    $('html, body').animate({ scrollTop: $firstInvalid.offset().top - 80 }, 300);
                    $firstInvalid.focus();
                }
                return;
            }
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizardUI();
            }
        });

        $('#prevBtn').click(function() {
            if (currentStep > 1) {
                currentStep--;
                updateWizardUI();
            }
        });

        // Modal Triggers
        $('#addFamilyGroupBtn').click(function() {
            $('#familyGroupModal').modal('show');
        });

        $('#addPolicyHolderBtn').click(function() {
            $('#policyHolderModal').modal('show');
        });

        $('#addCompanyBtn').click(function() {
            $('#insuranceCompanyModal').modal('show');
        });

        $('#addBranchBtn').click(function() {
            const selectedCompanyId = $('#insurance_company_id').val();
            if (selectedCompanyId) {
                $('#branch_company_id').val(selectedCompanyId);
            }
            $('#branchModal').modal('show');
        });

        function setBranchOptions(message) {
            $('#branch_id').html(`<option value="">${message}</option>`);
        }

        function loadBranchesByCompany(companyId, selectedBranchId = '') {
            if (!companyId) {
                setBranchOptions('Select Company First');
                return;
            }

            setBranchOptions('Loading...');

            $.get("{{ route('api.masters.branches.index') }}", {
                company_id: companyId,
                per_page: 1000
            }).done(function(res) {
                const branches = res.data?.data || res.data || [];

                if (!branches.length) {
                    setBranchOptions('No branches available');
                    return;
                }

                const $branch = $('#branch_id');
                $branch.html('<option value="">Select Branch</option>');

                branches.forEach(function(branch) {
                    $branch.append(`<option value="${branch.id}">${branch.name}</option>`);
                });

                if (selectedBranchId) {
                    $branch.val(selectedBranchId);
                }
            }).fail(function() {
                setBranchOptions('Select Branch');
                Swal.fire('Error', 'Could not fetch branches for selected company', 'error');
            });
        }

        $('#insurance_company_id').on('change', function() {
            const companyId = $(this).val();
            $('#branch_company_id').val(companyId);
            loadBranchesByCompany(companyId);
        });

        $('#addPlanNameBtn').click(function() {
            $('#insurancePlanNameModal').modal('show');
        });

        $('#addPlanTypeBtn').click(function() {
            $('#insurancePlanTypeModal').modal('show');
        });

        $('#addBrokerBtn').click(function() {
            $('#brokerModal').modal('show');
        });

        $('#addAgencyLevelBtn').click(function() {
            $('#agencyLevelModal').modal('show');
        });

        $('#addAgencyBtn').click(function() {
            const selectedLevelId = $('#agency_level_id').val();
            if (selectedLevelId) {
                $('#new_agency_level_id').val(selectedLevelId);
            }
            $('#agencyModal').modal('show');
        });

        $('.addPaymentMethodBtn').click(function() {
            $('#payment_method_target').val($(this).data('target'));
            $('#paymentMethodModal').modal('show');
        });

        function resetAgencyOptions(message) {
            $('#sub_agent_id').html($('<option>', { value: '', text: message }));
        }

        function appendOption($select, value, text, selected = false) {
            const option = new Option(text, value, selected, selected);
            $select.append(option);
        }

        function loadAgenciesByLevel(levelId, selectedAgencyId = '') {
            if (!levelId) {
                resetAgencyOptions('Select Agency Level First');
                return;
            }

            resetAgencyOptions('Loading...');

            $.get("{{ route('api.masters.agencies.index') }}", {
                agency_levels_id: levelId,
                active_only: 1,
                per_page: 1000
            }).done(function(res) {
                const agencies = res.data?.data || res.data || [];
                const $agency = $('#sub_agent_id');

                if (!agencies.length) {
                    resetAgencyOptions('No agencies available');
                    return;
                }

                $agency.html($('<option>', { value: '', text: 'Select Agency' }));
                agencies.forEach(function(agency) {
                    appendOption($agency, agency.id, agency.name, String(agency.id) === String(selectedAgencyId));
                });
            }).fail(function() {
                resetAgencyOptions('Select Agency');
                Swal.fire('Error', 'Could not fetch agencies for selected level', 'error');
            });
        }

        $('#agency_level_id').on('change', function() {
            const levelId = $(this).val();
            $('#new_agency_level_id').val(levelId);
            loadAgenciesByLevel(levelId);
        });

        const documentTypes = @json($documentTypes->map(fn($type) => [
            'id' => $type->id,
            'document_type' => $type->document_type,
        ])->values());
        let documentRowIndex = 1;
        const existingStep4Docs = existingDbDocs.filter(function(doc) {
            return !!doc.document_file;
        });

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function buildDocumentTypeOptions() {
            let options = '<option value="">Select Document Type</option>';
            documentTypes.forEach(function(type) {
                options += `<option value="${type.id}">${escapeHtml(type.document_type)}</option>`;
            });
            return options;
        }

        function loadDocumentNamesByType($row, typeId, selectedDocumentId = '') {
            const $documentName = $row.find('.document-name-select');

            if (!typeId) {
                $documentName.html('<option value="">Select Document Type First</option>');
                return;
            }

            $documentName.html('<option value="">Loading...</option>').prop('disabled', true);

            $.get("{{ route('upload-document.get-documents-by-type') }}", {
                document_type_id: typeId
            }).done(function(res) {
                const documents = res.data || [];
                $documentName.prop('disabled', false);

                if (!res.status || !documents.length) {
                    $documentName.html('<option value="">No documents found</option>');
                    return;
                }

                $documentName.html('<option value="">Select Document Name</option>');
                documents.forEach(function(document) {
                    const selected = String(document.id) === String(selectedDocumentId);
                    $documentName.append(new Option(document.document_name, document.id, selected, selected));
                });
            }).fail(function() {
                $documentName.html('<option value="">Error loading documents</option>').prop('disabled', false);
            });
        }

        function refreshDocumentRemoveButtons() {
            const canRemove = $('.document-row').length > 1;
            $('.remove-document-row').prop('disabled', !canRemove);
        }

        function renderDocumentFileList() {
            const files = [];

            $('.document-file-input').each(function() {
                const rowNumber = $(this).closest('.document-row').index() + 1;
                Array.from(this.files || []).forEach(function(file) {
                    files.push(`<li>Row ${rowNumber}: ${file.name}</li>`);
                });
            });

            $('#document_list').html(files.length
                ? `<div class="alert alert-light border mb-0"><ul class="mb-0 ps-3">${files.join('')}</ul></div>`
                : ''
            );
        }

        function renderExistingStep4Docs() {
            if (!existingStep4Docs.length) return;
            const items = existingStep4Docs.map(function(doc, idx) {
                const file = doc.document_file || '';
                const name = file ? file.split('/').pop() : ('Document ' + (idx + 1));
                const type = doc.documentType?.document_type || '--';
                const dname = doc.documentName?.document_name || '--';
                const ext = (name.split('.').pop() || '').toLowerCase();
                const preview = ['jpg','jpeg','png','gif','webp','bmp'].includes(ext)
                    ? `<a href="/${file}" target="_blank"><img src="/${file}" style="width:56px;height:56px;object-fit:cover;border:1px solid #ddd;border-radius:6px;"></a>`
                    : `<a href="/${file}" target="_blank" class="badge bg-light text-dark border text-decoration-none">${name}</a>`;
                return `<li class="d-flex align-items-center gap-2 mb-2">${preview}<span>${name} (Type: ${type}, Name: ${dname}, Nos: ${doc.nos || '--'})</span></li>`;
            });
            $('#document_list').append(`<div class="alert alert-light border mt-2"><strong>Step 4 Existing Documents:</strong><ul class="mb-0 ps-3 mt-2">${items.join('')}</ul></div>`);
        }

        function prefillStep4Rows() {
            if (!existingStep4Docs.length) return;
            const first = existingStep4Docs[0];
            if (first.document_type_id) {
                $('#document_type_0').val(String(first.document_type_id));
                loadDocumentNamesByType($('.document-row[data-index="0"]'), first.document_type_id, first.document_id || '');
            }
            $('#nos_0').val(first.nos || '');
            if (first.document_file) {
                $('#documentRows .document-row[data-index="0"] .col-md-3:last').append(
                    `<div class="small mt-2"><a href="/${first.document_file}" target="_blank">View existing file</a></div>`
                );
            }

            for (let i = 1; i < existingStep4Docs.length; i++) {
                const doc = existingStep4Docs[i];
                $('#addDocumentRowBtn').trigger('click');
                const idx = documentRowIndex - 1;
                const $row = $(`.document-row[data-index="${idx}"]`);
                if (doc.document_type_id) {
                    $row.find('.document-type-select').val(String(doc.document_type_id));
                    loadDocumentNamesByType($row, doc.document_type_id, doc.document_id || '');
                }
                $row.find(`input[name="documents[${idx}][nos]"]`).val(doc.nos || '');
                if (doc.document_file) {
                    $row.find('.col-md-3:last').append(
                        `<div class="small mt-2"><a href="/${doc.document_file}" target="_blank">View existing file</a></div>`
                    );
                }
            }
        }

        $('#addDocumentRowBtn').on('click', function() {
            const index = documentRowIndex++;
            const rowHtml = `
                <div class="document-row border rounded-3 p-3" data-index="${index}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="document_type_${index}">Document Type</label>
                            <select name="documents[${index}][document_type_id]" id="document_type_${index}" class="form-select bg-white document-type-select">
                                ${buildDocumentTypeOptions()}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="document_name_${index}">Document Name</label>
                            <select name="documents[${index}][document_id]" id="document_name_${index}" class="form-select bg-white document-name-select">
                                <option value="">Select Document Type First</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold" for="nos_${index}">Nos</label>
                            <input type="text" name="documents[${index}][nos]" id="nos_${index}" class="form-control" placeholder="Enter Nos">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="policy_documents_${index}">Upload</label>
                            <input type="file" name="documents[${index}][files][]" id="policy_documents_${index}" class="form-control document-file-input" multiple accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="col-md-1 text-md-end">
                            <button type="button" class="btn crm-action-btn remove-document-row">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('#documentRows').append(rowHtml);
            refreshDocumentRemoveButtons();
        });

        $(document).on('change', '.document-type-select', function() {
            const $row = $(this).closest('.document-row');
            loadDocumentNamesByType($row, $(this).val());
        });

        $(document).on('click', '.remove-document-row', function() {
            $(this).closest('.document-row').remove();
            refreshDocumentRemoveButtons();
            renderDocumentFileList();
        });

        $(document).on('change', '.document-file-input', renderDocumentFileList);
        prefillStep4Rows();
        renderExistingStep4Docs();

        // Update Policy Holder
        $('#savePolicyHolderBtn').click(function() {
            const name    = $('#new_holder_name').val().trim();
            const email   = $('#new_holder_email').val().trim();
            const contact = $('#new_holder_contact').val().trim();
            const countryCode = $('#new_holder_country_code').val().trim() || '+91';
            const address = $('#new_holder_address').val().trim();

            if (!name) return Swal.fire('Error', 'Please enter the holder name', 'error');

            $.post("{{ url('save-customer') }}", {
                _token:  "{{ csrf_token() }}",
                name:    name,
                email:   email,
                contact: contact,
                country_code: countryCode,
                address: address
            }, function(res) {
                if (res.status || res.success || res.id) {
                    const id    = res.id    || res.data?.id;
                    const label = res.name  || res.data?.name || name;
                    const holderEmail = res.email || res.data?.email || email;
                    const holderContact = res.contact || res.data?.contact || contact;
                    const holderAddress = res.address || res.data?.address || address;
                    
                    // Add to main Policy Holder dropdown
                    const $option = $(`<option value="${id}" data-email="${holderEmail}" data-contact="${holderContact}" data-address="${holderAddress}">${label}</option>`);
                    $('#policy_holder_id').append($option).val(id);
                    
                    // Also add to Family Group modal dropdown
                    $('#family_group_policy_holder').append(`<option value="${id}" selected>${label}</option>`).val(id);
                    
                    // Trigger change event to pre-fill holder details
                    $('#policy_holder_id').trigger('change');
                    
                    // Clear form
                    $('#new_holder_name').val('');
                    $('#new_holder_email').val('');
                    $('#new_holder_contact').val('');
                    $('#new_holder_country_code').val('+91');
                    $('#new_holder_address').val('');
                    
                    $('#policyHolderModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Success', text: 'Policy holder added.', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Error', res.message || 'Failed to Update Policy holder', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Server error. Please try again.', 'error');
            });
        });

        // Modal Submissions
        $('#savePlanNameBtn').click(function() {
            const name = $('#new_insurance_plan_name').val().trim();
            if(!name) return Swal.fire('Error', 'Please enter plan name', 'error');
            
            $.post("{{ route('api.masters.insurance_plan_names.store') }}", {
                _token: "{{ csrf_token() }}",
                name: name
            }, function(res) {
                if(res.status && res.data) {
                    $('#insurance_plan_name').append(`<option value="${res.data.name}">${res.data.name}</option>`).val(res.data.name);
                    $('#insurancePlanNameModal').modal('hide');
                    $('#new_insurance_plan_name').val('');
                    Swal.fire('Success', 'Plan name added successfully', 'success');
                } else {
                    Swal.fire('Error', res.message || 'Failed to save plan name', 'error');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save plan name';
                Swal.fire('Error', message, 'error');
            });
        });

        $('#savePlanTypeBtn').click(function() {
            const name = $('#new_insurance_plan_type').val().trim();
            if(!name) return Swal.fire('Error', 'Please enter plan type', 'error');

            $.post("{{ route('api.masters.insurance_plan_types.store') }}", {
                _token: "{{ csrf_token() }}",
                name: name
            }, function(res) {
                if(res.status && res.data) {
                    $('#insurance_plan_type').append(`<option value="${res.data.name}">${res.data.name}</option>`).val(res.data.name);
                    $('#insurancePlanTypeModal').modal('hide');
                    $('#new_insurance_plan_type').val('');
                    Swal.fire('Success', 'Plan type added successfully', 'success');
                } else {
                    Swal.fire('Error', res.message || 'Failed to save plan type', 'error');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save plan type';
                Swal.fire('Error', message, 'error');
            });
        });

        $('#saveCompanyModalBtn').click(function() {
            const name = $('#new_insurance_company_name').val().trim();
            if(!name) return Swal.fire('Error', 'Please enter company name', 'error');

            $.post("/api/masters/company-master", {
                _token: "{{ csrf_token() }}",
                name: name
            }, function(res) {
                if(res.success && res.data) {
                    const company = res.data;
                    const option = `<option value="${company.id}">${company.name}</option>`;
                    $('#insurance_company_id').append(option).val(company.id);
                    $('#branch_company_id').append(option);
                    loadBranchesByCompany(company.id);
                    $('#insuranceCompanyModal').modal('hide');
                    $('#new_insurance_company_name').val('');
                    Swal.fire('Success', 'Company added successfully', 'success');
                } else {
                    Swal.fire('Error', res.message || 'Failed to save company', 'error');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Server error. Please try again.';
                Swal.fire('Error', message, 'error');
            });
        });

        $('#saveBranchBtn').click(function() {
            const name = $('#new_branch_name').val();
            const company_id = $('#branch_company_id').val();
            if(!name || !company_id) return Swal.fire('Error', 'Please fill all required fields', 'error');
            
            $.post("{{ route('api.masters.branches.store') }}", {
                _token: "{{ csrf_token() }}",
                name: name,
                company_id: company_id
            }, function(res) {
                if(res.success && res.data) {
                    const branch = res.data;
                    if ($('#insurance_company_id').val() !== String(company_id)) {
                        $('#insurance_company_id').val(company_id);
                    }
                    loadBranchesByCompany(company_id, branch.id);
                    $('#branchModal').modal('hide');
                    $('#new_branch_name').val('');
                    Swal.fire('Success', 'Branch added successfully', 'success');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save branch';
                Swal.fire('Error', message, 'error');
            });
        });

        $('#saveBrokerBtn').click(function() {
            const name = $('#new_broker_name').val();
            if(!name) return Swal.fire('Error', 'Please enter broker name', 'error');
            
            $.post("{{ route('api.masters.brokers.store') }}", { broker_name: name }, function(res) {
                if(res.status) {
                    $('#broker_id').append(`<option value="${res.data.id}">${res.data.broker_name}</option>`).val(res.data.id);
                    $('#brokerModal').modal('hide');
                    $('#new_broker_name').val('');
                    Swal.fire('Success', 'Broker added successfully', 'success');
                }
            });
        });

        $('#saveAgencyLevelBtn').click(function() {
            const name = $('#new_agency_level_name').val().trim();
            if(!name) return Swal.fire('Error', 'Please enter agency level', 'error');

            $.post("{{ route('api.masters.agency_levels.store') }}", {
                _token: "{{ csrf_token() }}",
                name: name
            }, function(res) {
                if(res.success && res.data) {
                    const level = res.data;
                    appendOption($('#agency_level_id'), level.id, level.name, true);
                    appendOption($('#new_agency_level_id'), level.id, level.name, true);
                    loadAgenciesByLevel(level.id);
                    $('#agencyLevelModal').modal('hide');
                    $('#new_agency_level_name').val('');
                    Swal.fire('Success', 'Agency level added successfully', 'success');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save agency level';
                Swal.fire('Error', message, 'error');
            });
        });

        $('#saveAgencyBtn').click(function() {
            const name = $('#new_agency_name').val().trim();
            const levelId = $('#new_agency_level_id').val();
            if(!name || !levelId) return Swal.fire('Error', 'Please fill all required fields', 'error');

            $.post("{{ route('api.masters.agencies.store') }}", {
                _token: "{{ csrf_token() }}",
                name: name,
                agency_levels_id: levelId
            }, function(res) {
                if(res.success && res.data) {
                    const agency = res.data;
                    if ($('#agency_level_id').val() !== String(levelId)) {
                        $('#agency_level_id').val(levelId);
                    }
                    loadAgenciesByLevel(levelId, agency.id);
                    $('#agencyModal').modal('hide');
                    $('#new_agency_name').val('');
                    Swal.fire('Success', 'Agency added successfully', 'success');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save agency';
                Swal.fire('Error', message, 'error');
            });
        });

        $('#savePaymentMethodBtn').click(function() {
            const name = $('#new_payment_method_name').val().trim();
            const target = $('#payment_method_target').val();
            if(!name) return Swal.fire('Error', 'Please enter payment method', 'error');

            $.post("{{ route('masters.payment_method.store') }}", {
                _token: "{{ csrf_token() }}",
                name: name
            }, function(res) {
                if(res.status && res.data) {
                    const method = res.data;
                    ['#paid_by_agent', '#paid_by_client'].forEach(function(selector) {
                        const $select = $(selector);
                        if (!$select.find(`option[value="${method.id}"]`).length) {
                            appendOption($select, method.id, method.name, selector === target);
                        } else if (selector === target) {
                            $select.val(method.id);
                        }
                    });
                    $('#paymentMethodModal').modal('hide');
                    $('#new_payment_method_name').val('');
                    Swal.fire('Success', 'Payment method added successfully', 'success');
                } else {
                    Swal.fire('Error', res.message || 'Failed to save payment method', 'error');
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save payment method';
                Swal.fire('Error', message, 'error');
            });
        });

        const initialPolicyHolderOptions = $('#policy_holder_id').html();

        function setPolicyHolderOptions(message) {
            $('#policy_holder_id').html(`<option value="">${message}</option>`);
        }

        function appendPolicyHolderOption(holder, selected = false) {
            const option = new Option(holder.name, holder.id, selected, selected);
            $(option)
                .attr('data-email', holder.email || '')
                .attr('data-contact', holder.phone || holder.contact || '')
                .attr('data-address', holder.address || '')
                .attr('data-dob', holder.dob || '')
                .attr('data-city-id', holder.city_id || '');

            $('#policy_holder_id').append(option);
        }

        function loadPolicyHoldersByFamilyGroup(familyGroupId) {
            if (!familyGroupId) {
                $('#policy_holder_id').html(initialPolicyHolderOptions).val('');
                return;
            }

            setPolicyHolderOptions('Loading...');

            $.get("{{ url('family-groups') }}/" + familyGroupId + "/holders")
                .done(function(res) {
                    const holders = res.data || [];

                    if (!holders.length) {
                        setPolicyHolderOptions('No policy holder found');
                        return;
                    }

                    setPolicyHolderOptions('Select Holder');
                    holders.forEach(function(holder, index) {
                        appendPolicyHolderOption(holder, index === 0);
                    });

                    $('#policy_holder_id').trigger('change');
                })
                .fail(function() {
                    setPolicyHolderOptions('Select Holder');
                    Swal.fire('Error', 'Could not fetch policy holder for selected family group', 'error');
                });
        }

        function setAreaOptions(message) {
            $('#area_id').html(`<option value="">${message}</option>`).trigger('change.select2');
        }

        function loadAreasByCity(cityId, selectedAreaId = '') {
            if (!cityId) {
                setAreaOptions('Select City First');
                return;
            }

            setAreaOptions('Loading...');

            $.get("{{ url('cities') }}/" + cityId + "/areas")
                .done(function(res) {
                    const areas = res.data || [];

                    if (!areas.length) {
                        setAreaOptions('No areas found');
                        return;
                    }

                    const $area = $('#area_id');
                    $area.html('<option value="">Select Area</option>');
                    areas.forEach(function(area) {
                        $area.append(new Option(area.area_name, area.id, false, String(area.id) === String(selectedAreaId)));
                    });
                    $area.trigger('change.select2');
                })
                .fail(function() {
                    setAreaOptions('Select Area');
                    Swal.fire('Error', 'Could not fetch areas for selected city', 'error');
                });
        }

        $('#family_group_id').on('change', function() {
            loadPolicyHoldersByFamilyGroup($(this).val());
        });

        $('#city_id').on('change', function() {
            loadAreasByCity($(this).val());
        });

        setAreaOptions('Select City First');

        // Pre-fill holder details
        $('#policy_holder_id').change(function() {
            const selected = $(this).find('option:selected');
            if (selected.val()) {
                const name = selected.text().split(' ');
                $('#first_name').val(name[0] || '');
                $('#last_name').val(name[name.length - 1] || '');
                if (name.length > 2) $('#middle_name').val(name[1]);
                
                $('#email_address').val(selected.data('email') || '');
                $('#mobile_number').val(selected.data('contact') || '');
                $('#address_line1').val(selected.data('address') || '');
                $('#dob').val(selected.data('dob') || '');

                const cityId = selected.data('city-id');
                if (cityId) {
                    $('#city_id').val(cityId).trigger('change.select2');
                    loadAreasByCity(cityId);
                }
            }
        });

        // Premium Calculation
        $('#basic_premium_amount, #tax_amount').on('input', function() {
            const basic = parseFloat($('#basic_premium_amount').val()) || 0;
            const tax = parseFloat($('#tax_amount').val()) || 0;
            const total = (basic + tax).toFixed(2);
            $('#total_premium_amount').val(total);
            
            if (basic > 0) showFieldValid('basic_premium_amount');
            if (parseFloat(total) > 0) showFieldValid('total_premium_amount');
        });

        // Rider row enable / disable
        $(document).on('change', '.rider-checkbox', function() {
            const row = $(this).closest('tr');
            const enabled = $(this).is(':checked');
            row.find('.rider-input').prop('disabled', !enabled);
            if (!enabled) {
                row.find('.rider-input').val('');
            }
        });
        prefillRidersFromPolicy();

        // Family Group Modal - Load Customers
        $('#familyGroupModal').on('show.bs.modal', function() {
            $.ajax({
                url: "{{ url('get-customers-json') }}",
                type: 'GET',
                success: function(res) {
                    const select = $('#family_group_policy_holder');
                    select.empty().append('<option value="">Select Policy Holder</option>');
                    const customers = Array.isArray(res) ? res : (res.data || []);
                    if (customers.length > 0) {
                        customers.forEach(function(customer) {
                            select.append('<option value="' + customer.id + '">' + customer.name + '</option>');
                        });
                    }
                },
                error: function() {
                    console.error('Failed to load customers');
                }
            });
        });

        // Add More Members
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

        // Save Family Group
        $('#saveFamilyGroupBtn').on('click', function() {
            const formData = new FormData($('#familyGroupForm')[0]);
            $.ajax({
                url: "{{ url('family-groups/store') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if (res.status === 'success' || res.id) {
                        const id = res.id || res.data?.id;
                        const label = res.group_name || res.data?.group_name || $('#new_family_group_name').val();
                        
                        // Add to Family Group dropdown in main form
                        $('#family_group_id').append(`<option value="${id}">${label}</option>`).val(id).trigger('change');
                        
                        // Clear form
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
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Family Group Created!',
                            text: 'Family group has been saved successfully.',
                            showConfirmButton: true,
                            confirmButtonColor: '#5e72e4'
                        }).then(() => {
                            $('#familyGroupModal').modal('hide');
                        });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to save family group', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Something went wrong. Please check your data.', 'error');
                }
            });
        });

        // Form Submission (novalidate on form: native validation cannot focus fields hidden in other wizard steps)
        $('#lifeInsuranceForm').on('submit', function(e) {
            e.preventDefault();

            const validationError = validateAllStepsForSubmit();
            if (validationError) {
                currentStep = validationError.step;
                updateWizardUI();
                const $firstInvalid = $('.is-invalid').first();
                if ($firstInvalid.length) {
                    $('html, body').animate({ scrollTop: $firstInvalid.offset().top - 80 }, 300);
                    $firstInvalid.focus();
                }
                return;
            }

            let formData = new FormData(this);
            
            $.ajax({
                url: "{{ route('life-insurance.update', $policy->id) }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-HTTP-Method-Override': 'PUT'
                },
                beforeSend: function() {
                    $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');
                },
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Policy Created!',
                            text: 'Life insurance policy has been saved successfully.',
                            showConfirmButton: true,
                            confirmButtonColor: '#5e72e4'
                        }).then(() => {
                            window.location.href = "{{ route('life-insurance.index') }}";
                        });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to Update Policy', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Something went wrong. Please check your data.', 'error');
                },
                complete: function() {
                    $('#submitBtn').prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Update Policy');
                }
            });
        });
    });
</script>
@endpush
