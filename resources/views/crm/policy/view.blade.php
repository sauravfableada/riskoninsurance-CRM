@extends('layouts.app')
@section('page_title', 'Policy Details')
@section('content')


<style>
      label.form-label span {

        border-bottom: none;

    }

    .customer_details {
        border-right: 1px solid #00000012;
    }

    #example_table_length {

        padding-top: 13px;

    }

    div.dataTables_wrapper div.dataTables_paginate ul.pagination {

        margin: 2px 7px;

    }



    .description_div {

        display: inline-block;

        width: 205px;

        white-space: normal;

        overflow-wrap: break-word;

        word-break: keep-all;

        height: 110px;

        overflow-y: auto;

    }



    .nav-link.active.show {

        background: #fff !important;

        color: #013A83 !important;

    }



    #productDetails .tab-content table th {

        width: 25%;

    }

    #productDetails .tab-content table td {

        width: 75%;

    }

    .policy-field-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        min-width: 0;
    }

    .policy-field-label i {
        width: 18px;
        text-align: center;
        flex-shrink: 0;
    }

    .policy-view-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .policy-view-header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .policy-view-header-actions .btn {
        margin: 0 !important;
        white-space: nowrap;
    }


    @media (max-width: 576px) {

        .policy-view-container {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        .policy-view-row {
            margin-top: 0 !important;
        }

        .policy-view-card > .card-header {
            height: auto;
            padding: 1rem !important;
        }

        .policy-view-header {
            display: block;
        }

        .policy-view-header-title {
            margin-bottom: 0.75rem;
        }

        .policy-view-header-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: 0.5rem;
        }

        .policy-view-header-actions .btn-group,
        .policy-view-header-actions .btn {
            width: 100%;
        }

        .policy-view-invoice-action {
            display: none;
        }

        #issue {

            margin-top: 0 !important;

        }

    }

    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 2),

    only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 2) {

        #issue {

            margin-top: -3px !important;

        }

    }
</style>
<div class="container-fluid py-4 policy-view-container">
    <div class="row mt-4 justify-content-center mb-4 policy-view-row">
        <div class="col-lg-12 mb-lg-0">
            <div class="card w-90 m-auto policy-view-card">
                <div class="card-header pb-2 p-3">
                    <div class="policy-view-header">
                        <div class="policy-view-header-title">
                            <h5 class="mb-1 fw-bold">Policy Details</h5>
                            <p class="text-muted small mb-0">Complete information about this policy</p>
                        </div>

                        <div class="policy-view-header-actions" id="issue">
                            <div class="btn-group policy-view-invoice-action">
                                <a href="{{ route('policies.legacy.invoice', $policy['id']) }}"
                                target="_blank"
                                class="btn btn-light text-dark"
                                style="border-radius:8px; text-decoration: none;" title="View policy PDF">
                                    <i class="fa fa-file-pdf-o" style="font-size:13px;"></i>
                                </a>
                            </div>

                            @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasMatrixPermission('edit_policies')))
                                <div class="btn-group ">
                                    <a href="{{ route('policies.legacy.edit', $policy['id']) }}"
                                        class="btn btn-outline-dark-blue text-dark"
                                        style="border-radius:8px; text-decoration: none;">
                                        <i class="fa fa-edit" style="font-size: 13px;"></i> Edit
                                    </a>
                                </div>
                            @endif

                            <div class="btn-group ">
                                <a href="{{ url('all-policies') }}" class="btn btn-dark-blue text-white"
                                    style="border-radius:8px; text-decoration: none;">
                                    <i class="fa-solid fa-chevron-left" style="font-size: 13px;"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ----------------------------- -->
                </div>
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-body px-5">
                        <!-- Policy Details -->
                        <div class="mb-4">
                            <h5 class="mb-3 text-secondary">
                                Policy Number
                                <?php if (!empty($policy['policy_number_no'])): ?>
                                    -- <span class="text-primary">#<?= htmlspecialchars($policy['policy_number_no']) ?></span>
                                <?php endif; ?>
                            </h5>

                            <div class="row g-3">
                                <?php
                                $readValue = function ($source, $keys, $default = "--") {
                                    foreach ((array) $keys as $key) {
                                        $value = null;

                                        if (is_array($source) && array_key_exists($key, $source)) {
                                            $value = $source[$key];
                                        } elseif (is_object($source)) {
                                            if (isset($source->{$key})) {
                                                $value = $source->{$key};
                                            } elseif ($source instanceof \ArrayAccess && isset($source[$key])) {
                                                $value = $source[$key];
                                            }
                                        }

                                        if ($value !== null && $value !== '') {
                                            return $value;
                                        }
                                    }

                                    return $default;
                                };

                                $formatDate = function ($value) {
                                    return !empty($value) ? date('j M Y', strtotime($value)) : "--";
                                };

                                $policyTypeName = $readValue($policyTypes, 'name');
                                $isVehicle = stripos((string) $policyTypeName, 'vehicle') !== false;

                                $policyFields = [
                                    "Sub Agent" => ['icon' => 'fa-solid fa-user-tie', 'value' => $readValue($assignedStaff ?? null, 'name')],
                                    "Family Group" => ['icon' => 'fa-solid fa-people-group', 'value' => $readValue($familyGroup ?? null, 'name')],
                                    "Policy Holder" => ['icon' => 'fa-solid fa-user', 'value' => $readValue($policy_holder ?? null, 'name')],
                                    "Policy Type" => ['icon' => 'fa-solid fa-shield-halved', 'value' => $policyTypeName],
                                    "Policy Sub Type" => ['icon' => 'fa-solid fa-layer-group', 'value' => $readValue($subTypes, 'name')],
                                    "Segment" => ['icon' => 'fa-solid fa-diagram-project', 'value' => $readValue($segments, 'name')],
                                    "Insurance Company" => ['icon' => 'fa-solid fa-building-shield', 'value' => $readValue($companies, 'name')],
                                    "Branch" => ['icon' => 'fa-solid fa-code-branch', 'value' => $readValue($branches, 'name')],
                                    "Start Date" => ['icon' => 'fa-solid fa-calendar-day', 'value' => $formatDate($readValue($policy, 'start_date', null))],
                                    "End Date" => ['icon' => 'fa-solid fa-calendar-check', 'value' => $formatDate($readValue($policy, 'end_date', null))],
                                    "Policy Mode" => ['icon' => 'fa-solid fa-repeat', 'value' => $readValue($policy, 'policy_mode')],
                                    "Premium Amount" => ['icon' => 'fa-solid fa-money-bill-wave', 'value' => $readValue($policy, ['premium_amount', 'premium'])],
                                    "Tax Amount" => ['icon' => 'fa-solid fa-receipt', 'value' => $readValue($policy, 'tax_amount')],
                                    "Payment Date" => ['icon' => 'fa-solid fa-calendar-days', 'value' => $formatDate($readValue($policy, 'payment_date', null))],
                                    "Payment Mode" => ['icon' => 'fa-solid fa-credit-card', 'value' => $readValue($policy, ['payment_type', 'payment_mode'])],
                                    "Nominee Name" => ['icon' => 'fa-solid fa-user-check', 'value' => $readValue($policy, 'nominee_name')],
                                    "Nominee Contact Number" => ['icon' => 'fa-solid fa-phone', 'value' => $readValue($policy, 'nominee_contact')],
                                    "Payment Amount" => ['icon' => 'fa-solid fa-indian-rupee-sign', 'value' => $readValue($policy, ['payment_amount', 'amount'])],
                                ];

                                if ($isVehicle) {
                                    $policyFields["Vehicle IDV"] = ['icon' => 'fa-solid fa-car-side', 'value' => $readValue($policy, ['vehicle_idv', 'vehicle_IDV'])];
                                    $policyFields["CNG/LPG Unit"] = ['icon' => 'fa-solid fa-gas-pump', 'value' => $readValue($policy, ['cng_lpg_unit', 'CNG_LPG'])];
                                } else {
                                    $policyFields["Sum Assured"] = ['icon' => 'fa-solid fa-sack-dollar', 'value' => $readValue($policy, 'sum_assured')];
                                }

                                if ($readValue($policy, 'kyc_id_type', null) !== null) {
                                    $policyFields["KYC ID Type"] = ['icon' => 'fa-solid fa-id-card', 'value' => $readValue($policy, 'kyc_id_type')];
                                }
                                if ($readValue($policy, 'kyc_id_number', null) !== null) {
                                    $policyFields["KYC ID Number"] = ['icon' => 'fa-solid fa-fingerprint', 'value' => $readValue($policy, 'kyc_id_number')];
                                }
                                if ($readValue($policy, 'kyc_notes', null) !== null) {
                                    $policyFields["KYC Notes"] = ['icon' => 'fa-solid fa-note-sticky', 'value' => $readValue($policy, 'kyc_notes')];
                                }

                                if ($readValue($policy, 'remarks', null) !== null) {
                                    $policyFields["Remarks"] = ['icon' => 'fa-solid fa-comment-dots', 'value' => $readValue($policy, 'remarks')];
                                }

                                // Render fields
                                foreach ($policyFields as $label => $field):
                                    $value = is_array($field) ? $field['value'] : $field;
                                    $icon = is_array($field) ? $field['icon'] : 'fa-solid fa-circle-info';
                                ?>
                                    <div class="col-md-6 d-flex justify-content-between align-items-center border-bottom py-2">
                                        <span class="policy-field-label"><i class="<?= htmlspecialchars($icon) ?> text-muted"></i><?= $label ?>:</span>
                                        <strong class="text-wrap"><?= htmlspecialchars($value) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        {{-- Insured Members / Family Members Details --}}
                        @php
                            $allMembers = collect();
                            // Collect direct policy members
                            if (!empty($policy->members) && $policy->members->isNotEmpty()) {
                                foreach ($policy->members as $m) {
                                    $allMembers->push([
                                        'name' => $m->name,
                                        'relation' => $m->relation ?: '--',
                                        'dob' => $m->dob ? $m->dob->format('j M Y') : '--',
                                        'gender' => $m->gender ?: '--',
                                        'source' => 'Insured Member',
                                    ]);
                                }
                            }
                            // Collect family group members
                            if (!empty($familyMembers) && $familyMembers->isNotEmpty()) {
                                foreach ($familyMembers as $fm) {
                                    $allMembers->push([
                                        'name' => $fm->member_name,
                                        'relation' => $fm->relation ?: '--',
                                        'dob' => $fm->dob ? date('j M Y', strtotime($fm->dob)) : '--',
                                        'gender' => '--',
                                        'contact' => $fm->contact ?: '--',
                                        'source' => 'Family Group Member',
                                    ]);
                                }
                            }
                        @endphp

                        <div class="bg-light p-3 rounded mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <h5 class="text-dark mb-0">
                                    <i class="fa-solid fa-users me-2 text-primary"></i>Insured / Family Member Details
                                </h5>
                                <span class="badge bg-secondary">{{ $allMembers->count() }} {{ Str::plural('Member', $allMembers->count()) }}</span>
                            </div>
                            @if($allMembers->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm bg-white mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">#</th>
                                                <th>Member Name</th>
                                                <th>Relationship</th>
                                                <th>Date of Birth</th>
                                                <th>Gender / Contact</th>
                                                <th class="pe-3">Category</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allMembers as $idx => $member)
                                                <tr>
                                                    <td class="ps-3">{{ $idx + 1 }}</td>
                                                    <td><strong>{{ $member['name'] }}</strong></td>
                                                    <td><span class="badge bg-light text-dark border">{{ $member['relation'] }}</span></td>
                                                    <td>{{ $member['dob'] }}</td>
                                                    <td>
                                                        @if(!empty($member['contact']) && $member['contact'] !== '--')
                                                            <i class="fa-solid fa-phone small text-muted me-1"></i>{{ $member['contact'] }}
                                                        @else
                                                            {{ $member['gender'] }}
                                                        @endif
                                                    </td>
                                                    <td class="pe-3"><span class="badge bg-info text-white">{{ $member['source'] }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0 small"><i class="fa-solid fa-info-circle me-1"></i>No additional member details linked with this policy.</p>
                            @endif
                        </div>

                        {{-- Policy PDF Sections (Categorized into Original & Renewal) --}}
                        @php
                            $pdfCollection = $policyDocuments ?? collect();
                            $originalPdfs = $pdfCollection->filter(fn($doc) => $doc->document_type !== 'renewal');
                            $renewalPdfs = $pdfCollection->filter(fn($doc) => $doc->document_type === 'renewal');
                        @endphp

                        <div class="bg-light p-3 rounded mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <h5 class="text-dark mb-0">
                                    <i class="fa-solid fa-file-pdf me-2 text-danger"></i>Policy PDF Documents
                                </h5>
                                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasMatrixPermission('edit_policies')))
                                    <button type="button" class="btn btn-dark-blue btn-sm" id="uploadPolicyPdfBtn">
                                        <i class="fa-solid fa-upload me-1"></i>Upload PDF
                                    </button>
                                    <input type="file" id="uploadPolicyPdfInput" class="d-none" accept="application/pdf,.pdf">
                                @endif
                            </div>

                            {{-- Renewal Policies Section --}}
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-success"><i class="fa-solid fa-arrows-rotate me-2"></i>Renewal Policy Documents</span>
                                    <span class="badge bg-success">{{ $renewalPdfs->count() }}</span>
                                </div>
                                <div class="card-body p-3">
                                    @forelse($renewalPdfs as $document)
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom py-2">
                                            <div>
                                                <span class="badge bg-success-subtle text-success me-2">Renewal</span>
                                                <strong>{{ $document->label ?: 'Renewed Policy PDF' }}</strong>
                                                <div class="small text-muted">
                                                    {{ $document->original_file_name ?: $document->file_name }}
                                                    &middot; Uploaded {{ $document->created_at?->format('j M Y, g:i A') ?? '--' }}
                                                    @if($document->uploader)
                                                        by {{ $document->uploader->name }}
                                                    @endif
                                                </div>
                                                @if($document->policy?->policy_number_no || $document->policy?->policy_number || $document->policy?->start_date || $document->policy?->end_date)
                                                    <div class="small text-muted">
                                                        @if($document->policy?->policy_number_no || $document->policy?->policy_number)
                                                            Policy #{{ $document->policy?->policy_number_no ?: $document->policy?->policy_number }}
                                                        @endif
                                                        @if($document->policy?->start_date || $document->policy?->end_date)
                                                            &middot; {{ $document->policy?->start_date ? date('j M Y', strtotime($document->policy->start_date)) : '--' }}
                                                            to {{ $document->policy?->end_date ? date('j M Y', strtotime($document->policy->end_date)) : '--' }}
                                                        @endif
                                                    </div>
                                                @endif
                                                @if(filled($document->comment))
                                                    <div class="small mt-1"><strong>Comment:</strong> {{ $document->comment }}</div>
                                                @endif
                                                @if($document->document_date)
                                                    <div class="small mt-1"><strong>Document Date:</strong> {{ $document->document_date->format('d M Y') }}</div>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                                                <a href="{{ route('policies.legacy.documents.history.view', $document->id) }}" target="_blank"
                                                    rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
                                                    <i class="fa-solid fa-file-pdf me-1"></i>View PDF
                                                </a>
                                                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasMatrixPermission('delete_policies')))
                                                    <button type="button" class="btn btn-danger btn-sm delete-policy-pdf"
                                                        data-document-id="{{ $document->id }}"
                                                        data-file-name="{{ $document->original_file_name ?: $document->file_name }}">
                                                        <i class="fa-solid fa-trash me-1"></i>Delete
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0 small">No renewal policy PDFs uploaded yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Base / Original Policies Section --}}
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary"><i class="fa-solid fa-file-lines me-2"></i>Primary / Base Policy Documents</span>
                                    <span class="badge bg-primary">{{ $originalPdfs->count() }}</span>
                                </div>
                                <div class="card-body p-3">
                                    @forelse($originalPdfs as $document)
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom py-2">
                                            <div>
                                                <span class="badge bg-primary-subtle text-primary me-2">Original</span>
                                                <strong>{{ $document->label ?: 'Policy PDF' }}</strong>
                                                <div class="small text-muted">
                                                    {{ $document->original_file_name ?: $document->file_name }}
                                                    &middot; Uploaded {{ $document->created_at?->format('j M Y, g:i A') ?? '--' }}
                                                    @if($document->uploader)
                                                        by {{ $document->uploader->name }}
                                                    @endif
                                                </div>
                                                @if($document->policy?->policy_number_no || $document->policy?->policy_number || $document->policy?->start_date || $document->policy?->end_date)
                                                    <div class="small text-muted">
                                                        @if($document->policy?->policy_number_no || $document->policy?->policy_number)
                                                            Policy #{{ $document->policy?->policy_number_no ?: $document->policy?->policy_number }}
                                                        @endif
                                                        @if($document->policy?->start_date || $document->policy?->end_date)
                                                            &middot; {{ $document->policy?->start_date ? date('j M Y', strtotime($document->policy->start_date)) : '--' }}
                                                            to {{ $document->policy?->end_date ? date('j M Y', strtotime($document->policy->end_date)) : '--' }}
                                                        @endif
                                                    </div>
                                                @endif
                                                @if(filled($document->comment))
                                                    <div class="small mt-1"><strong>Comment:</strong> {{ $document->comment }}</div>
                                                @endif
                                                @if($document->document_date)
                                                    <div class="small mt-1"><strong>Document Date:</strong> {{ $document->document_date->format('d M Y') }}</div>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                                                <a href="{{ route('policies.legacy.documents.history.view', $document->id) }}" target="_blank"
                                                    rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
                                                    <i class="fa-solid fa-file-pdf me-1"></i>View PDF
                                                </a>
                                                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasMatrixPermission('delete_policies')))
                                                    <button type="button" class="btn btn-danger btn-sm delete-policy-pdf"
                                                        data-document-id="{{ $document->id }}"
                                                        data-file-name="{{ $document->original_file_name ?: $document->file_name }}">
                                                        <i class="fa-solid fa-trash me-1"></i>Delete
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0 small">No base policy PDFs uploaded yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>


                        <?php if ($isVehicle): ?>
                            <div class="bg-light p-3 rounded mb-3 shadow-sm">
                                <h5 class="text-dark mb-3">Vehicle Details</h5>
                                <div class="row g-3">
                                    <?php
                                    $vehicleFields = [
                                        "Vehicle Model" => ['icon' => 'fa-solid fa-car', 'value' => $readValue($vehicleDetails, 'vehicle_model', $readValue($policy, 'vehicle_model'))],
                                        "Registration No" => ['icon' => 'fa-solid fa-id-badge', 'value' => $readValue($vehicleDetails, 'vehicle_reg_no', $readValue($policy, 'vehicle_registration_number'))],
                                        "Engine No" => ['icon' => 'fa-solid fa-gears', 'value' => $readValue($vehicleDetails, 'engine_no', $readValue($policy, 'engine_number'))],
                                        "Chassis No" => ['icon' => 'fa-solid fa-barcode', 'value' => $readValue($vehicleDetails, 'chassis_no', $readValue($policy, 'chassis_number'))],
                                        "Fuel Type" => ['icon' => 'fa-solid fa-gas-pump', 'value' => $readValue($vehicleDetails, 'fuel_type', $readValue($policy, 'fuel_type'))],
                                        "Engine Capacity (CC)" => ['icon' => 'fa-solid fa-gauge-high', 'value' => $readValue($vehicleDetails, 'cc', $readValue($policy, 'engine_capacity'))],
                                        "Gross Vehicle Weight (GVW)" => ['icon' => 'fa-solid fa-weight-hanging', 'value' => $readValue($vehicleDetails, 'gvw', $readValue($policy, 'gross_vehicle_weight'))],
                                        "No Claim Bonus (NCB)" => ['icon' => 'fa-solid fa-award', 'value' => $readValue($vehicleDetails, 'ncb', $readValue($policy, 'no_claim_bonus'))],
                                        "Year of Manufacture" => ['icon' => 'fa-solid fa-calendar', 'value' => $readValue($vehicleDetails, 'year_of_manufacture', $readValue($policy, 'year_of_manufacture'))],
                                    ];

                                    foreach ($vehicleFields as $label => $field):
                                        $value = $field['value'];
                                        $icon = $field['icon'];
                                    ?>
                                        <div class="col-md-6 d-flex justify-content-between align-items-center border-bottom py-2">
                                            <span class="policy-field-label"><i class="<?= htmlspecialchars($icon) ?> text-muted"></i><?= $label ?>:</span>
                                            <strong><?= htmlspecialchars($value) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>




            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const policyId = @json($policy->id);
    const csrfToken = @json(csrf_token());
    const today = @json(now()->toDateString());
    const uploadButton = document.getElementById('uploadPolicyPdfBtn');
    const uploadInput = document.getElementById('uploadPolicyPdfInput');

    if (uploadButton && uploadInput) {
        uploadButton.addEventListener('click', () => uploadInput.click());

        uploadInput.addEventListener('change', async function () {
            const file = this.files && this.files[0];
            if (!file) return;

            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            if (!isPdf || file.size > 20 * 1024 * 1024) {
                Swal.fire('Invalid PDF', 'Select a PDF file up to 20 MB.', 'error');
                this.value = '';
                return;
            }

            const commentPrompt = await Swal.fire({
                title: 'PDF Details',
                html: `
                    <label for="policyPdfDate" class="form-label d-block text-start fw-semibold">Document Date</label>
                    <input type="date" id="policyPdfDate" class="form-control mb-3" value="${today}" min="${today}">
                    <label for="policyPdfComment" class="form-label d-block text-start fw-semibold">Comment <span class="text-muted fw-normal">(Optional)</span></label>
                    <textarea id="policyPdfComment" class="form-control" rows="4" maxlength="2000" placeholder="Enter a note about this policy PDF..."></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Upload PDF',
                focusConfirm: false,
                preConfirm: () => {
                    const documentDate = document.getElementById('policyPdfDate').value;
                    if (!documentDate) {
                        Swal.showValidationMessage('Please select the document date.');
                        return false;
                    }
                    if (documentDate < today) {
                        Swal.showValidationMessage('Past dates cannot be selected.');
                        return false;
                    }
                    return {
                        documentDate,
                        comment: document.getElementById('policyPdfComment').value.trim()
                    };
                }
            });
            if (!commentPrompt.isConfirmed) {
                this.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('policy_id', policyId);
            formData.append('document', file);
            formData.append('comment', commentPrompt.value.comment);
            formData.append('document_date', commentPrompt.value.documentDate);

            Swal.fire({ title: 'Uploading PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const response = await fetch(@json(route('policies.legacy.documents.upload')), {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (!response.ok || result.status !== true) throw new Error(result.message || 'Upload failed.');

                await Swal.fire('Uploaded', result.message || 'Policy PDF uploaded successfully.', 'success');
                window.location.reload();
            } catch (error) {
                Swal.fire('Upload failed', error.message, 'error');
                uploadInput.value = '';
            }
        });
    }

    document.querySelectorAll('.delete-policy-pdf').forEach(function (button) {
        button.addEventListener('click', async function () {
            const confirmation = await Swal.fire({
                title: 'Delete this PDF?',
                text: this.dataset.fileName + ' will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete PDF',
                confirmButtonColor: '#dc3545'
            });
            if (!confirmation.isConfirmed) return;

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('policy_id', policyId);
            formData.append('document_id', this.dataset.documentId);

            try {
                const response = await fetch(@json(route('policies.legacy.documents.delete')), {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Delete failed.');

                await Swal.fire('Deleted', result.message, 'success');
                window.location.reload();
            } catch (error) {
                Swal.fire('Delete failed', error.message, 'error');
            }
        });
    });
});
</script>

@endsection
