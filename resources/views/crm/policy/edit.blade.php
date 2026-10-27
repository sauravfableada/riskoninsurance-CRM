@extends('layouts.masters')
@section('page_title', 'Edit Insurance Policy')
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
    @media (max-width: 576px) {
        .card-header {
            height: auto;
            min-height: 60px;
        }
    }
</style>

<style>
    span.select2-selection.select2-selection--single.select2-selection--clearable {
        height: 38px !important;
        border: 1px solid #d2d6da !important;
        border-radius: 7px !important;
    }
    span#select2-family_group_id-container,
    span#select2-user_id-container,
    span#select2-policy_type_id-container,
    span#select2-sub_type_id-container,
    span#select2-segment_id-container,
    span#select2-company_id-container,
    span#select2-branch_id-container {
        color: #495057 !important;
        line-height: 34px !important;
        font-size: 0.875rem !important;
    }
    button.select2-selection__clear {
        height: 35px !important;
    }
    span.select2-selection.select2-selection--single {
        height: 37px;
        border: 1px solid #d2d6da !important;
        border-radius: 7px !important;
        color: #495057 !important;
    }
    span.select2-selection__placeholder {
        color: #495057 !important;
    }
    /* Keep Select2 + Add button on same row */
    .d-flex.gap-2 .select2-container {
        flex: 1 !important;
        width: 100% !important;
    }
    .d-flex.gap-2 > div[style*="flex"] { min-width: 0; }
    .d-flex.gap-2 .btn { align-self: flex-start; white-space: nowrap; height: 37px; }
    /* Select2 validation error state */
    .select2-error .select2-selection {
        border: 1px solid #dc3545 !important;
        border-radius: 7px !important;
    }
</style>

        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Update Policy</h1>
                    <p class="text-muted small mb-0">Modify an existing insurance policy.</p>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= base_url('all-policies'); ?>" class="btn btn-dark-blue mb-0">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>


                    <form class="m-3" id="edit_policy_form">
                        <input type="hidden" name="base_url" id="base_url" value="<?= base_url(); ?>">
                        <input type="hidden" name="id" value="<?= $policy['id']; ?>">
                        <?php
                        // Determine if this is a vehicle policy on the server side
                        $isVehiclePolicy = false;
                        foreach ($policyTypes as $pt) {
                            if ($pt['id'] == $policy['policy_type_id'] && stripos($pt['name'], 'vehicle') !== false) {
                                $isVehiclePolicy = true;
                                break;
                            }
                        }
                        ?>

                        <div class="mb-4">
                            <div class="d-flex flex-wrap gap-2" id="policyFormSteps">
                                <button type="button" class="btn btn-outline-dark-blue active flex-grow-1 flex-md-grow-0" data-step="0">Policy Details</button>
                                <button type="button" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0" data-step="1">Vehicle/Nominee Details</button>
                                <button type="button" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0" data-step="2">Payment & Premium</button>
                            </div>
                        </div>                        <!-- Step 1: Policy Holder + Policy Details -->
                        <div class="step step-1">
                            <div class="row">
                                <!-- Policy Holder -->
                                  <div class="mb-3 col-lg-6">
                                        <label for="policy_number" class="form-label">Policy Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="policy_number" id="policy_number" value="<?= $policy['policy_number_no'] ?? '' ?>" placeholder="Enter policy number">
                                        <div class="invalid-feedback" id="policy_number_err"></div>
                                    </div>
                                <?php
                                $session = session();

                                if ($session->get("user_role") == 3) {  ?>
                                    <div class="mb-3 col-lg-6">
                                        <label class="form-label" for="user_id">Self / Sub Agent</label>
                                        <select class="form-control" name="staff_id" id="staff_id">
                                            <!-- <option value="">-- Select Staff --</option> -->
                                            <?php foreach ($staffs as $staff): ?>
                                                <option
                                                    value="<?= $staff['id'] ?>"
                                                    <?= ($staff['id'] == ($policy['user_id'] ?? '')) ? 'selected' : '' ?>>
                                                    <?= $staff['name'] ?? 'Unknown' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback" id="staff_id_err"></div>
                                    </div>
                                <?php } ?>
                                <?php if ($session->get("user_role") == 1): ?>
                                    <?php $currentUserId = $session->get('id'); ?>
                                    <div class="mb-3 col-lg-6">
                                        <label class="form-label" for="staff_id">Sub Agent Name</label>
                                        <select class="form-control" name="staff_id" id="staff_id">
                                            <?php foreach ($staffs as $staff): ?>
                                                <option
                                                    value="<?= $staff['id'] ?>"
                                                    <?= ($staff['id'] == ($policy['user_id'] ?? $currentUserId)) ? 'selected' : '' ?>>
                                                    <?= $staff['name'] ?? 'Unknown' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback" id="staff_id_err"></div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="family_group_id">Family Group <span class="text-muted small">(Optional)</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="family_group_id" id="family_group_id">
                                                <option value="">-- Select Family Group --</option>
                                                <?php foreach ($familyGroups as $group): ?>
                                                    <option value="<?= $group['id']; ?>"
                                                        data-holder-id="<?= $group['holder_id'] ?? '' ?>"
                                                        <?= ($policy['family_group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($group['group_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalFamilyGroup"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="family_group_id_err"></div>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="user_id">Policy Holder Name <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="user_id" id="user_id">
                                                <option value="">-- Select Holder --</option>
                                                <?php foreach ($users as $u): ?>
                                                    <option value="<?= $u['id'] ?>" <?= ($policy['holder_id'] == $u['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($u['name']) ?> <?= !empty($u['email']) ? '('.htmlspecialchars($u['email']).')' : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalPolicyHolder"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="user_id_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="policy_type_id">Policy Type <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="policy_type_id" id="policy_type_id">
                                                <option value="">-- Select Policy Type --</option>
                                                <?php foreach ($policyTypes as $pt): ?>
                                                    <option value="<?= $pt['id'] ?>" <?= ($policy['policy_type_id'] == $pt['id']) ? 'selected' : ''; ?>>
                                                        <?= $pt['name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalPolicyType"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="policy_type_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="sub_type_id">Policy Sub Type <span class="text-muted small">(Optional)</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="sub_type_id" id="sub_type_id">
                                                <option value="">-- Select Sub Type --</option>
                                                <?php foreach ($subTypes as $st): ?>
                                                    <option value="<?= $st['id'] ?>" <?= ($policy['policy_sub_type_id'] == $st['id']) ? 'selected' : ''; ?>>
                                                        <?= $st['name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalPolicySubType"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="sub_type_err"></div>
                                </div>

                                <!-- Segment -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="segment_id">Segment <span class="text-muted small">(Optional)</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="segment_id" id="segment_id">
                                                <option value="">-- Select Segment --</option>
                                                <?php foreach ($segments as $seg): ?>
                                                    <option value="<?= $seg['id'] ?>" <?= ($policy['segment_id'] == $seg['id']) ? 'selected' : ''; ?>>
                                                        <?= $seg['name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalSegment"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="segment_err"></div>
                                </div>

                                <!-- Company -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="company_id">Insurance Company <span class="text-muted small">(Optional)</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="company_id" id="company_id">
                                                <option value="">-- Select Company --</option>
                                                <?php foreach ($companies as $c): ?>
                                                    <option value="<?= $c['id'] ?>" <?= ($policy['company_id'] == $c['id']) ? 'selected' : ''; ?>>
                                                        <?= $c['name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalInsuranceCompany"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="company_err"></div>
                                </div>

                                <div class="mb-3 col-lg-12">
                                    <label class="form-label" for="branch_id">Branch <span class="text-muted small">(Optional)</span></label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="branch_id" id="branch_id">
                                                <option value="">-- Select Branch --</option>
                                                <?php foreach ($branches as $branch): ?>
                                                    <option value="<?= $branch['id'] ?>" <?= ($policy['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                                        <?= $branch['name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalBranch"><i class="bi bi-plus-lg me-1"></i> Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="branch_err"></div>
                                </div>

                            </div>
                            </div>

                            <div class="row">
                                <!-- Policy Type -->
                                


                            </div>

                            <div class="row">

                                
                        </div>

                        <!-- Step 2: Policy Dates & Nominee -->
                        <div class="step step-2 d-none">


                        <!-- Vehicle Details -->
                            <div id="vehicle_fields" class="insurance-fields <?= $isVehiclePolicy ? '' : 'd-none' ?>">
                                <div class="row">
                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_reg_no" class="form-label">Vehicle Registration Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="vehicle_reg_no" id="vehicle_reg_no" value="<?= esc($vehicle['vehicle_reg_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="vehicle_reg_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_model" class="form-label">Vehicle Model <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="vehicle_model" id="vehicle_model" value="<?= esc($vehicle['vehicle_model'] ?? '') ?>">
                                        <div class="invalid-feedback" id="vehicle_model_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="engine_no" class="form-label">Engine Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="engine_no" id="engine_no" value="<?= esc($vehicle['engine_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="engine_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="chassis_no" class="form-label">Chassis Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="chassis_no" id="chassis_no" value="<?= esc($vehicle['chassis_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="chassis_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="fuel_type" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                                        <select class="form-control" name="fuel_type" id="fuel_type">
                                            <option value="">-- Select Fuel Type --</option>
                                            <option value="Petrol" <?= (isset($vehicle['fuel_type']) && $vehicle['fuel_type'] == 'Petrol') ? 'selected' : '' ?>>Petrol</option>
                                            <option value="Diesel" <?= (isset($vehicle['fuel_type']) && $vehicle['fuel_type'] == 'Diesel') ? 'selected' : '' ?>>Diesel</option>
                                            <option value="Electric" <?= (isset($vehicle['fuel_type']) && $vehicle['fuel_type'] == 'Electric') ? 'selected' : '' ?>>Electric</option>
                                        </select>
                                        <div class="invalid-feedback" id="fuel_type_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="cc" class="form-label">Engine Capacity (CC)</label>
                                        <input type="number" class="form-control" name="cc" id="cc" value="<?= esc($vehicle['cc'] ?? '') ?>">
                                        <div class="invalid-feedback" id="cc_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="gvw" class="form-label">Gross Vehicle Weight (GVW)</label>
                                        <input type="number" class="form-control" name="gvw" id="gvw" value="<?= esc($vehicle['gvw'] ?? '') ?>">
                                        <div class="invalid-feedback" id="gvw_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="ncb" class="form-label">No Claim Bonus <span class="text-muted small">(Optional)</span></label>
                                        <input type="text" class="form-control" name="ncb" id="ncb" value="<?= esc($vehicle['ncb'] ?? '') ?>">
                                        <div class="invalid-feedback" id="ncb_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="year_of_manufacture" class="form-label">Year of Manufacture <span class="text-muted small">(Optional)</span></label>
                                        <input type="number" class="form-control" name="year_of_manufacture" id="year_of_manufacture" value="<?= esc($vehicle['year_of_manufacture'] ?? '') ?>">
                                        <div class="invalid-feedback" id="year_of_manufacture_err"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Policy Mode, Start/End Dates -->
                            <div class="row">
                                <div class="mb-3 col-lg-4">
                                    <label class="form-label" for="policy_mode">Payment Mode <span class="text-muted small">(Optional)</span></label>
                                    <select class="form-control" name="policy_mode" id="policy_mode">
                                        <?php $modes = ['Monthly', 'Quarterly', 'Half-Yearly', 'Yearly']; ?>
                                        <?php foreach ($modes as $mode): ?>
                                            <option value="<?= $mode ?>" <?= ($policy['policy_mode'] == $mode) ? 'selected' : ''; ?>><?= $mode ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-lg-4">
                                    <label class="form-label" for="start_date">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?= $policy['start_date']; ?>" data-allow-past-date="true">
                                    <span id="start_date_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-4">
                                    <label class="form-label" for="end_date">End Date</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?= $policy['end_date']; ?>" data-allow-past-date="true">
                                    <span id="end_date_err" class="text-danger"></span>
                                </div>
                            </div>

                            <!-- Nominee Details -->
                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="nominee_name">Nominee Name</label>
                                    <input type="text" class="form-control" name="nominee_name" id="nominee_name" value="<?= esc($policy['nominee_name']); ?>">
                                    <span id="nominee_name_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="nominee_contact">Nominee Contact No.</label>
                                    <input type="text" class="form-control" name="nominee_contact" id="nominee_contact" value="<?= esc($policy['nominee_contact']); ?>">
                                    <span id="nominee_contact_err" class="text-danger"></span>
                                </div>
                            </div>
                        </div>


                        <!-- Step 3: Premium, Payment, Sum Assured -->
                        <div class="step step-3 d-none">
                            <div class="row">
                                <!-- Sum Assured (hidden for vehicle) -->
                                <div class="mb-3 col-lg-3 non-vehicle-field <?= $isVehiclePolicy ? 'd-none' : '' ?>">
                                    <label class="form-label" for="sum_assured">Sum Assured <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="sum_assured" id="sum_assured" value="<?= esc($policy['sum_assured']); ?>">
                                    <span id="sum_assured_err" class="text-danger"></span>
                                </div>
                                <!-- Vehicle IDV (only for vehicle) -->
                                <div class="mb-3 col-lg-3 vehicle-only-field <?= $isVehiclePolicy ? '' : 'd-none' ?>">
                                    <label class="form-label" for="vehicle_idv">Vehicle IDV <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="vehicle_idv" id="vehicle_idv" value="<?= esc($policy['vehicle_idv'] ?? ''); ?>">
                                    <span id="vehicle_idv_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="premium">Premium Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="premium" id="premium" value="<?= esc($policy['premium_amount']); ?>">
                                    <span id="premium_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="tax_amount">Tax Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="tax_amount" id="tax_amount" value="<?= esc($policy['tax_amount']); ?>">
                                    <span id="tax_amount_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="payment_date">Payment Date <span class="text-muted small">(Optional)</span></label>
                                    <input type="date" class="form-control" name="payment_date" id="payment_date" value="<?= $policy['payment_date']; ?>" data-allow-past-date="true">
                                    <span id="payment_date_err" class="text-danger"></span>
                                </div>
                            </div>

                            <!-- CNG/LPG Unit (only for vehicle) -->
                            <div class="row vehicle-only-field <?= $isVehiclePolicy ? '' : 'd-none' ?>">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="cng_lpg_unit">CNG/LPG Unit</label>
                                    <input type="text" class="form-control" name="cng_lpg_unit" id="cng_lpg_unit" value="<?= esc($policy['CNG_LPG'] ?? ''); ?>" placeholder="Enter CNG/LPG unit details">
                                    <span id="cng_lpg_unit_err" class="text-danger"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="payment_mode">Payment Type <span class="text-muted small">(Optional)</span></label>
                                    <select class="form-control" name="payment_mode" id="payment_mode">
                                        <option value="">-- Select Payment Type --</option>
                                        <?php $pmodes = ['Cash', 'Online', 'Bank', 'Card']; ?>
                                        <?php foreach ($pmodes as $pm): ?>
                                            <option value="<?= $pm ?>" <?= ($policy['payment_type'] == $pm) ? 'selected' : ''; ?>><?= $pm ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="payment_mode_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="amount">Payment Amount <span class="text-muted small">(Optional)</span></label>
                                    <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="<?= esc($policy['payment_amount']); ?>">
                                    <span id="amount_err" class="text-danger"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-lg-12">
                                    <label class="form-label" for="remarks">Remarks</label>
                                    <textarea class="form-control" name="remarks" id="remarks" rows="2"><?= esc($policy['remarks']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-top d-flex justify-content-between align-items-center gap-2">
                            <a href="{{ route('policies.legacy.index') }}" class="btn btn-outline-dark-blue px-4">Cancel</a>
                            <div class="d-flex gap-2 ms-auto">
                                <button type="button" class="btn btn-outline-dark-blue d-none px-4" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                                <button type="button" class="btn btn-dark-blue px-4" id="nextBtn" onclick="nextPrev(1)">Next</button>
                            </div>
                        </div>
                    </form>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var currentStep = 0;
    showStep(currentStep);

    function showStep(n) {
        var steps = $('.step');
        steps.addClass('d-none');
        $(steps[n]).removeClass('d-none');
        
        $('#prevBtn').toggleClass('d-none', n === 0);
        $('#nextBtn').text(n === steps.length - 1 ? 'Submit' : 'Next');

        // Update tab buttons
        $('#policyFormSteps button').removeClass('active');
        $('#policyFormSteps button[data-step="' + n + '"]').addClass('active');
    }

    $('#policyFormSteps button').click(function() {
        var targetStep = parseInt($(this).data('step'));
        
        if (targetStep < currentStep) {
            currentStep = targetStep;
            showStep(currentStep);
            return;
        }

        if (targetStep > currentStep) {
            if (!validateStep(currentStep)) return false;
            currentStep = targetStep;
            showStep(currentStep);
        }
    });

    window.nextPrev = function(n) {
        var steps = $('.step');
        if (n === 1 && !validateStep(currentStep)) return false;
        currentStep += n;
        if (currentStep >= steps.length) { submitForm(); return false; }
        showStep(currentStep);
    };

    // ── Select2 init ─────────────────────────────────────────────────────────
    var s2opts = { allowClear: true, width: '100%' };
    $('#family_group_id').select2($.extend({}, s2opts, { placeholder: '-- Select Family Group --' }));
    $('#staff_id').select2($.extend({}, s2opts, { placeholder: '-- Select Self / Sub Agent --' }));
    $('#user_id').select2($.extend({}, s2opts, { 
        placeholder: '-- Select Holder --',
        templateResult: function (user) {
            if (!user.id) {
                return user.text;
            }
            var $container = $(
                '<div class="d-flex justify-content-between align-items-center w-100">' +
                '<span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%;">' + user.text + '</span>' +
                '<button type="button" class="btn btn-sm text-danger delete-user-btn" style="padding: 0 5px;" title="Delete Policy Holder">' +
                '<i class="bi bi-x-circle-fill"></i>' +
                '</button>' +
                '</div>'
            );
            
            $container.find('.delete-user-btn').on('mouseup', function(e) {
                e.stopPropagation(); // prevent select2 from selecting option
                window.crmConfirm('Are you sure you want to delete this policy holder?', { confirmButtonColor: '#0f172a' }).then(function(result) {
                    if (result.isConfirmed) {
                        var baseUrl = $('#base_url').val() || '<?= base_url() ?>';
                        $.ajax({
                            url: baseUrl + 'delete-policy-customer/' + user.id,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(res) {
                                if (res.success) {
                                    $('#user_id').find('option[value="' + user.id + '"]').remove();
                                    $('#user_id').select2('close');
                                    window.crmAlert(res.message, 'success');
                                } else {
                                    window.crmAlert(res.message, 'error');
                                }
                            },
                            error: function() {
                                window.crmAlert('An error occurred.', 'error');
                            }
                        });
                    }
                });
            });
            return $container;
        }
    }));
    $('#policy_type_id').select2($.extend({}, s2opts, { placeholder: '-- Select Policy Type --' }));
    $('#sub_type_id').select2($.extend({}, s2opts, { placeholder: '-- Select Sub Type --' }));
    $('#segment_id').select2($.extend({}, s2opts, { placeholder: '-- Select Segment --' }));
    $('#company_id').select2($.extend({}, s2opts, { placeholder: '-- Select Company --' }));
    $('#branch_id').select2($.extend({}, s2opts, { placeholder: '-- Select Branch --' }));

    // ── Family Group → Policy Holder (identical to create page) ─────────────
    var currentFamilyGroupId  = null;
    var isLoadingHolders      = false;
    var holdersLoadedForGroup  = null;

    $('#family_group_id').on('change', function() {
        var familyGroupId = $(this).val();
        var baseUrl       = $('#base_url').val();
        var $holderSelect = $('#user_id');

        if (familyGroupId === holdersLoadedForGroup && $holderSelect.find('option').length > 1 && !window._editInitialHolderId) {
            return;
        }
        if (isLoadingHolders && familyGroupId === currentFamilyGroupId) { return; }

        currentFamilyGroupId = familyGroupId;
        isLoadingHolders     = true;
        $holderSelect.html('<option value="">Loading...</option>');
        $holderSelect.trigger('change.select2');

        if (familyGroupId) {
            $.ajax({
                url: baseUrl + 'family-groups/get-family-holder/' + familyGroupId,
                type: 'GET', dataType: 'json',
                success: function(data) {
                    isLoadingHolders      = false;
                    holdersLoadedForGroup  = familyGroupId;
                    if (data.length > 0) {
                        $holderSelect.html('<option value="">-- Select Holder --</option>');
                        $.each(data, function(i, holder) {
                            $holderSelect.append('<option value="' + holder.id + '">' + holder.name + (holder.email ? ' (' + holder.email + ')' : '') + '</option>');
                        });
                        if (window._editInitialHolderId) {
                            $holderSelect.val(String(window._editInitialHolderId));
                            window._editInitialHolderId = null;
                        }
                    } else {
                        $holderSelect.html('<option value="">No holders available</option>');
                    }
                    $holderSelect.trigger('change.select2');
                },
                error: function() {
                    isLoadingHolders = false;
                    $holderSelect.html('<option value="">-- Select Holder --</option>');
                    $holderSelect.trigger('change.select2');
                    window.crmAlert('Could not fetch holders');
                }
            });
        } else {
            isLoadingHolders = false; holdersLoadedForGroup = null;
            $holderSelect.html('<option value="">Loading...</option>');
            $holderSelect.trigger('change.select2');

            var customersUrl = baseUrl.replace(/\/+$/, '') + '/get-policy-customers-json';
            $.ajax({
                url: customersUrl,
                type: 'GET',
                dataType: 'json',
                success: function(customers) {
                    $holderSelect.html('<option value="">-- Select Holder --</option>');
                    $.each(customers, function(i, c) {
                        $holderSelect.append('<option value="' + c.id + '">' + c.name + (c.email ? ' (' + c.email + ')' : '') + '</option>');
                    });
                    
                    // If we have an initial holder ID, select it
                    if (window._editInitialHolderId) {
                        $holderSelect.val(String(window._editInitialHolderId));
                        window._editInitialHolderId = null;
                    }
                    
                    $holderSelect.trigger('change.select2');
                },
                error: function() {
                    $holderSelect.html('<option value="">-- Select Holder --</option>');
                    $holderSelect.trigger('change.select2');
                }
            });
        }
    });

    // On page load: store saved holder_id then trigger family group to load holders
    window._editInitialHolderId = '<?= $policy->holder_id ?>';
    if ($('#family_group_id').val()) { $('#family_group_id').trigger('change'); }

    // ── Vehicle toggle ───────────────────────────────────────────────────────
    function toggleVehicleFields() {
        var isVehicle = $('#policy_type_id option:selected').text().toLowerCase().indexOf('vehicle') !== -1;
        if (isVehicle) {
            $('#vehicle_fields').removeClass('d-none');
            $('.vehicle-only-field').removeClass('d-none');
            $('.non-vehicle-field').addClass('d-none');
        } else {
            $('#vehicle_fields').addClass('d-none');
            $('.vehicle-only-field').addClass('d-none');
            $('.non-vehicle-field').removeClass('d-none');
        }
    }
    toggleVehicleFields();
    $('#policy_type_id').on('change', toggleVehicleFields);

    // ── Policy Type → Sub Types + Segments ───────────────────────────────────
    $('#policy_type_id').on('change', function() {
        var policyTypeId = $(this).val();
        var $sub = $('#sub_type_id'), $seg = $('#segment_id');
        $sub.html('<option value="">Loading...</option>');
        $seg.html('<option value="">Loading...</option>');
        if (policyTypeId) {
            $.getJSON('<?= base_url('policy-sub-types') ?>/' + policyTypeId, function(data) {
                $sub.html('<option value="">-- Select Sub Type --</option>');
                $.each(data, function(i, s) { $sub.append('<option value="' + s.id + '">' + s.name + '</option>'); });
                $sub.trigger('change.select2');
            });
            $.getJSON('<?= base_url('segments-by-type') ?>/' + policyTypeId, function(data) {
                $seg.html(data.length ? '<option value="">-- Select Segment --</option>' : '<option value="">No segments available</option>');
                $.each(data, function(i, s) { $seg.append('<option value="' + s.id + '">' + s.name + '</option>'); });
                $seg.trigger('change.select2');
            });
        } else {
            $sub.html('<option value="">-- Select Sub Type --</option>');
            $seg.html('<option value="">-- Select Segment --</option>');
        }
    });

    // ── Company → Branches ───────────────────────────────────────────────────
    $('#company_id').on('change', function() {
        var companyId = $(this).val();
        var $branch   = $('#branch_id');
        $branch.html('<option value="">Loading...</option>');
        if (companyId) {
            $.getJSON('<?= base_url('get-branches') ?>/' + companyId, function(data) {
                $branch.html(data.length ? '<option value="">-- Select Branch --</option>' : '<option value="">No branches available</option>');
                $.each(data, function(i, b) { $branch.append('<option value="' + b.id + '">' + b.name + '</option>'); });
                $branch.trigger('change.select2');
            });
        } else {
            $branch.html('<option value="">-- Select Branch --</option>');
        }
    });

    // ── Validation ───────────────────────────────────────────────────────────
    function validateSelect(id, errId, msg) {
        var $sel = $('#' + id);
        var $container = $sel.next('.select2-container');
        if (!$sel.val()) {
            $sel.addClass('is-invalid');
            $container.addClass('select2-error');
            $('#' + errId).text(msg);
            return false;
        }
        $sel.removeClass('is-invalid');
        $container.removeClass('select2-error');
        $('#' + errId).text('');
        return true;
    }

    function validateStep(step) {
        var isValid = true;
        if (step === 0) {
            // if (!validateSelect('family_group_id', 'family_group_id_err', 'Please select a family group.')) isValid = false;
            if (!validateSelect('user_id', 'user_id_err', 'Please select a policy holder.')) isValid = false;
            if (!$('#policy_number').val()) {
                $('#policy_number').addClass('is-invalid');
                $('#policy_number_err').text('Please enter a policy number.');
                isValid = false;
            } else { $('#policy_number').removeClass('is-invalid'); $('#policy_number_err').text(''); }
            if (!validateSelect('policy_type_id', 'policy_type_err', 'Please select a policy type.')) isValid = false;
            // if (!validateSelect('sub_type_id', 'sub_type_err', 'Please select a sub type.')) isValid = false;
            // if (!validateSelect('company_id', 'company_err', 'Please select a company.')) isValid = false;
        }
        if (step === 1) {
            // Start Date, End Date, Nominee Name, and Nominee Contact No. are optional.
            if (!$('#vehicle_fields').hasClass('d-none')) {
                var vmap = { 'vehicle_reg_no':'Please enter vehicle registration number.','vehicle_model':'Please enter vehicle model.','engine_no':'Please enter engine number.','chassis_no':'Please enter chassis number.','fuel_type':'Please select fuel type.' };
                $.each(vmap, function(f, m) {
                    if (!$('#'+f).val()) { $('#'+f).addClass('is-invalid'); $('#'+f+'_err').text(m); isValid = false; }
                    else { $('#'+f).removeClass('is-invalid'); $('#'+f+'_err').text(''); }
                });
            }
        }
        if (step === 2) {
            if (!$('.non-vehicle-field').hasClass('d-none') && !$('#sum_assured').val()) {
                $('#sum_assured').addClass('is-invalid'); $('#sum_assured_err').text('Please enter sum assured.'); isValid = false;
            } else { $('#sum_assured').removeClass('is-invalid'); $('#sum_assured_err').text(''); }
            if (!$('.vehicle-only-field').hasClass('d-none') && !$('#vehicle_idv').val()) {
                $('#vehicle_idv').addClass('is-invalid'); $('#vehicle_idv_err').text('Please enter vehicle IDV.'); isValid = false;
            } else { $('#vehicle_idv').removeClass('is-invalid'); $('#vehicle_idv_err').text(''); }
            ['premium'].forEach(function(f) {
                if (!$('#'+f).val()) { $('#'+f).addClass('is-invalid'); $('#'+f+'_err').text('Please fill in this field.'); isValid = false; }
                else { $('#'+f).removeClass('is-invalid'); $('#'+f+'_err').text(''); }
            });
        }
        return isValid;
    }

    // Live error clearing
    $('#edit_policy_form').on('change input', 'select, input, textarea', function() {
        var id = $(this).attr('id');
        if ($(this).val()) $(this).removeClass('is-invalid');
        if (id) $('#' + id + '_err').text('');
    });
    ['family_group_id','user_id','policy_type_id','sub_type_id','segment_id','company_id','branch_id'].forEach(function(id) {
        $('#' + id).on('change', function() {
            if ($(this).val()) { $(this).removeClass('is-invalid'); $(this).next('.select2-container').removeClass('select2-error'); $('#' + id + '_err').text(''); }
        });
    });

    // ── Submit ───────────────────────────────────────────────────────────────
    function submitForm() {
        var baseUrl = $('#base_url').val();
        $.ajax({
            url: '{{ route("policies.legacy.update") }}',
            type: 'POST',
            data: $('#edit_policy_form').serialize(),
            dataType: 'json',
            beforeSend: function() { $('#nextBtn').prop('disabled', true).text('Updating...'); },
            success: function(res) {
                if (res.status) {
                    Swal.fire({ title: 'Success!', text: res.message, icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#0F172A' })
                        .then(function() { window.location.href = '{{ route("policies.legacy.index") }}'; });
                } else {
                    Swal.fire({ title: 'Error!', text: res.message, icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#0F172A' });
                    if (res.errors) { $.each(res.errors, function(k, v) { $('#' + k + '_err').text(v); }); }
                }
            },
            error: function() { Swal.fire({ title: 'Oops!', text: 'Something went wrong!', icon: 'error', confirmButtonColor: '#0F172A' }); },
            complete: function() { $('#nextBtn').prop('disabled', false).text('Submit'); }
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    // AUTO-CALCULATE END DATE
    // ════════════════════════════════════════════════════════════════════════
    function calculateEndDate() {
        var startDateStr = $('#start_date').val();
        var mode = $('#policy_mode').val();
        
        if (!startDateStr) return;
        
        var startDate = new Date(startDateStr);
        if (isNaN(startDate.getTime())) return;
        
        var endDate = new Date(startDate);
        
        if (mode === 'Monthly') {
            endDate.setMonth(endDate.getMonth() + 1);
        } else if (mode === 'Quarterly') {
            endDate.setMonth(endDate.getMonth() + 3);
        } else if (mode === 'Half-Yearly') {
            endDate.setMonth(endDate.getMonth() + 6);
        } else if (mode === 'Yearly') {
            endDate.setFullYear(endDate.getFullYear() + 1);
        } else {
            // Default to 1 year
            endDate.setFullYear(endDate.getFullYear() + 1);
        }
        
        // Subtract 1 day for policy duration
        endDate.setDate(endDate.getDate());
        
        var yyyy = endDate.getFullYear();
        var mm = String(endDate.getMonth() + 1).padStart(2, '0');
        var dd = String(endDate.getDate()).padStart(2, '0');
        
        $('#end_date').val(yyyy + '-' + mm + '-' + dd);
    }

    $('#start_date, #policy_mode').on('change', function() {
        calculateEndDate();
    });

});
</script>
@endpush
<!-- ===================== ADD MODALS ===================== -->

<!-- Modal: Add Family Group -->
<div class="modal fade" id="modalFamilyGroup" tabindex="-1" aria-labelledby="modalFamilyGroupLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalFamilyGroupLabel">Add Family Group</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formFamilyGroup">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Family Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fg_group_name" name="group_name" placeholder="Enter group name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Policy Holder <span class="text-danger">*</span></label>
                            <select class="form-control" id="fg_holder_id" name="holder_id">
                                <option value="">Select Policy Holder</option>
                            </select>
                        </div>
                    </div>
                    <h6 class="fw-bold mt-2">Members</h6>
                    <div id="fg_members_container">
                        <div class="fg-member-row d-flex gap-2 mb-2 align-items-center">
                                <input type="text" class="form-control" name="member_name[]" placeholder="Member Name">
                                <input type="text" class="form-control" name="member_relation[]" placeholder="Relation">
                                <input type="text" class="form-control" name="member_contact[]" placeholder="Contact">
                                <input type="date" class="form-control" name="member_dob[]" placeholder="DOB" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">
                                <input type="date" class="form-control" name="member_anniversary[]" placeholder="Anniversary">
                            <button type="button" class="btn btn-link text-danger p-0 fg-remove-member"><i class="bi bi-trash text-danger"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary mt-1" id="fg_add_member">Add More Members</button>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea class="form-control" name="remarks" id="fg_remarks" rows="3" placeholder="Any remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Policy Holder (Customer) -->
<div class="modal fade" id="modalPolicyHolder" tabindex="-1" aria-labelledby="modalPolicyHolderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalPolicyHolderLabel">Add Policy Holder</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolicyHolder">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email address">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" list="policyHolderCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone number" maxlength="15" inputmode="numeric">
                            </div>
                            <datalist id="policyHolderCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">WhatsApp</label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="whatsapp_country_code" value="+91" list="policyHolderCountryCodes" maxlength="5" style="width:95px" aria-label="WhatsApp country code">
                                <input type="tel" class="form-control" name="whatsapp" placeholder="WhatsApp number" maxlength="15" inputmode="numeric">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Company Name</label>
                            <input type="text" class="form-control" name="company_name" placeholder="Company name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Address</label>
                            <input type="text" class="form-control" name="address" placeholder="Address">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Policy Type -->
<div class="modal fade" id="modalPolicyType" tabindex="-1" aria-labelledby="modalPolicyTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalPolicyTypeLabel">Add Policy Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolicyType">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="policy_type_name" placeholder="Enter policy type name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="policy_description" rows="3" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Policy Sub Type -->
<div class="modal fade" id="modalPolicySubType" tabindex="-1" aria-labelledby="modalPolicySubTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalPolicySubTypeLabel">Add Policy Sub Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolicySubType">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="type_id" id="pst_type_id">
                            <option value="">-- Select Policy Type --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sub Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter sub type name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Segment -->
<div class="modal fade" id="modalSegment" tabindex="-1" aria-labelledby="modalSegmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalSegmentLabel">Add Segment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSegment">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="insurance_type_id" id="seg_type_id">
                            <option value="">-- Select Policy Type --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Segment Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter segment name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Insurance Company -->
<div class="modal fade" id="modalInsuranceCompany" tabindex="-1" aria-labelledby="modalInsuranceCompanyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalInsuranceCompanyLabel">Add Insurance Company</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formInsuranceCompany">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter company name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Branch -->
<div class="modal fade" id="modalBranch" tabindex="-1" aria-labelledby="modalBranchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalBranchLabel">Add Branch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBranch">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Insurance Company <span class="text-danger">*</span></label>
                        <select class="form-control" name="company_id" id="branch_company_id">
                            <option value="">-- Select Company --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="branch_name" placeholder="Enter branch name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL JS ===================== -->
@push('scripts')
<script>
$(document).ready(function () {
    var baseUrl = '<?= base_url() ?>';

    // ════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════════

    /** Append a new <option> to a select, select it, and trigger change */
    function appendAndSelect(selectId, id, label, extraAttrs) {
        var $sel = $(selectId);
        // Remove duplicate if already exists
        $sel.find('option[value="' + id + '"]').remove();
        var $opt = $('<option>', { value: id, text: label });
        if (extraAttrs) {
            $.each(extraAttrs, function (attr, val) {
                $opt.attr('data-' + attr, val);
            });
        }
        $sel.append($opt).val(id);
        // Trigger both change and Select2-specific change
        $sel.trigger('change');
        if ($sel.data('select2')) {
            $sel.trigger('change.select2');
        }
    }

    /** Populate a <select> from a JSON endpoint */
    function populateSelect(selectId, url, valueFn, labelFn, placeholder) {
        var $sel = $(selectId);
        $sel.html('<option value="">Loading...</option>');
        $.getJSON(url, function (data) {
            $sel.html('<option value="">' + (placeholder || '-- Select --') + '</option>');
            $.each(data, function (i, row) {
                $sel.append('<option value="' + valueFn(row) + '">' + labelFn(row) + '</option>');
            });
            if ($sel.data('select2')) { $sel.trigger('change.select2'); }
        }).fail(function () {
            $sel.html('<option value="">' + (placeholder || '-- Select --') + '</option>');
            if ($sel.data('select2')) { $sel.trigger('change.select2'); }
        });
    }

    /** Toastr or fallback alert */
    function notify(type, msg) {
        if (typeof toastr !== 'undefined') { toastr[type](msg); }
        else { window.crmAlert(msg); }
    }

    /** Generic AJAX form submit handler */
    function handleModalSubmit(formId, url, onSuccess) {
        $(formId).on('submit', function (e) {
            e.preventDefault();
            var $btn = $(formId + ' [type=submit]').prop('disabled', true).text('Saving...');
            $.ajax({
                url: baseUrl + url,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).text('Submit');
                    var ok = res.status === 'success' || res.status === true || res.status === 'warning';
                    if (ok) {
                        notify('success', res.message || 'Saved successfully.');
                        onSuccess(res);
                    } else {
                        notify('error', res.message || 'Failed. Please try again.');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Submit');
                    var msg = 'Server error.';
                    try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                    notify('error', msg);
                }
            });
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    // 1. FAMILY GROUP
    // ════════════════════════════════════════════════════════════════════════
    $('#modalFamilyGroup').on('show.bs.modal', function () {
        populateSelect('#fg_holder_id', baseUrl + 'get-policy-customers-json',
            function (r) { return r.id; },
            function (r) { return r.name + (r.email ? ' (' + r.email + ')' : ''); },
            'Select Policy Holder'
        );
    });

    // Dynamic member rows
    $('#fg_add_member').on('click', function () {
        $('#fg_members_container').append(
            '<div class="fg-member-row d-flex gap-2 mb-2 align-items-center">' +
            '<input type="text" class="form-control" name="member_name[]" placeholder="Member Name">' +
            '<input type="text" class="form-control" name="member_relation[]" placeholder="Relation">' +
            '<input type="text" class="form-control" name="member_contact[]" placeholder="Contact">' +
            '<input type="date" class="form-control" name="member_dob[]" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">' +
            '<input type="date" class="form-control" name="member_anniversary[]">' +
            '<button type="button" class="btn btn-link text-danger p-0 fg-remove-member"><i class="bi bi-trash text-danger"></i></button>' +
            '</div>'
        );
    });
    $(document).on('click', '.fg-remove-member', function () {
        if ($('.fg-member-row').length > 1) $(this).closest('.fg-member-row').remove();
    });

    handleModalSubmit('#formFamilyGroup', 'family-groups/store', function (res) {
        appendAndSelect('#family_group_id', res.id, res.group_name, { 'holder-id': res.holder_id || '' });
        //$('#modalFamilyGroup').modal('hide');
        $('#formFamilyGroup')[0].reset();
        $('#fg_members_container').html(
            '<div class="fg-member-row d-flex gap-2 mb-2 align-items-center">' +
            '<input type="text" class="form-control" name="member_name[]" placeholder="Member Name">' +
            '<input type="text" class="form-control" name="member_relation[]" placeholder="Relation">' +
            '<input type="text" class="form-control" name="member_contact[]" placeholder="Contact">' +
            '<input type="date" class="form-control" name="member_dob[]" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">' +
            '<input type="date" class="form-control" name="member_anniversary[]">' +
            '<button type="button" class="btn btn-link text-danger p-0 fg-remove-member"><i class="bi bi-trash text-danger"></i></button>' +
            '</div>'
        );
    });

    // ════════════════════════════════════════════════════════════════════════
    // 2. POLICY HOLDER (Customer)
    // ════════════════════════════════════════════════════════════════════════
    handleModalSubmit('#formPolicyHolder', 'save-customer', function (res) {
        var label = res.name || $('[name="name"]', '#formPolicyHolder').val();
        appendAndSelect('#user_id', res.id, label);
        //$('#modalPolicyHolder').modal('hide');
        $('#formPolicyHolder')[0].reset();
    });

    // ════════════════════════════════════════════════════════════════════════
    // 3. POLICY TYPE
    // ════════════════════════════════════════════════════════════════════════
    handleModalSubmit('#formPolicyType', 'policy-type/store', function (res) {
        var label = res.name || $('[name="policy_type_name"]', '#formPolicyType').val();
        appendAndSelect('#policy_type_id', res.id, label);
        // Also refresh the policy type dropdowns inside sub-type & segment modals
        populateSelect('#pst_type_id', baseUrl + 'all-policy-type-json',
            function (r) { return r.id; }, function (r) { return r.name; }, '-- Select Policy Type --');
        populateSelect('#seg_type_id', baseUrl + 'all-policy-type-json',
            function (r) { return r.id; }, function (r) { return r.name; }, '-- Select Policy Type --');
        //$('#modalPolicyType').modal('hide');
        $('#modalPolicyType').modal('hide');
        $('#formPolicyType')[0].reset();
    });

    // ════════════════════════════════════════════════════════════════════════
    // 4. POLICY SUB TYPE
    // ════════════════════════════════════════════════════════════════════════
    $('#modalPolicySubType').on('show.bs.modal', function () {
        populateSelect('#pst_type_id', baseUrl + 'all-policy-type-json',
            function (r) { return r.id; }, function (r) { return r.name; }, '-- Select Policy Type --');
    });

    handleModalSubmit('#formPolicySubType', 'policy-sub-type/store', function (res) {
        var label = res.name || $('[name="name"]', '#formPolicySubType').val();
        // Only add to sub_type_id if the selected policy type matches
        var selectedType = $('#policy_type_id').val();
        if (!selectedType || selectedType == res.type_id) {
            appendAndSelect('#sub_type_id', res.id, label);
        } else {
            notify('info', 'Sub type saved. Select the matching policy type to see it in the list.');
        }
        //$('#modalPolicySubType').modal('hide');
        $('#formPolicySubType')[0].reset();
    });

    // ════════════════════════════════════════════════════════════════════════
    // 5. SEGMENT
    // ════════════════════════════════════════════════════════════════════════
    $('#modalSegment').on('show.bs.modal', function () {
        populateSelect('#seg_type_id', baseUrl + 'all-policy-type-json',
            function (r) { return r.id; }, function (r) { return r.name; }, '-- Select Policy Type --');
    });

    handleModalSubmit('#formSegment', 'store-segment', function (res) {
        var label = res.name || $('[name="name"]', '#formSegment').val();
        var selectedType = $('#policy_type_id').val();
        if (!selectedType || selectedType == res.insurance_type_id) {
            appendAndSelect('#segment_id', res.id, label);
        } else {
            notify('info', 'Segment saved. Select the matching policy type to see it in the list.');
        }
        //$('#modalSegment').modal('hide');
        $('#formSegment')[0].reset();
    });

    // ════════════════════════════════════════════════════════════════════════
    // 6. INSURANCE COMPANY
    // ════════════════════════════════════════════════════════════════════════
    handleModalSubmit('#formInsuranceCompany', 'store-company', function (res) {
        var label = res.name || $('[name="name"]', '#formInsuranceCompany').val();
        appendAndSelect('#company_id', res.id, label);
        // Refresh company list in Branch modal
        populateSelect('#branch_company_id', baseUrl + 'get-companies-json',
            function (r) { return r.id; }, function (r) { return r.name; }, '-- Select Company --');
        $('#modalInsuranceCompany').modal('hide');
        $('#formInsuranceCompany')[0].reset();
    });

    // ════════════════════════════════════════════════════════════════════════
    // 7. BRANCH
    // ════════════════════════════════════════════════════════════════════════
    $('#modalBranch').on('show.bs.modal', function () {
        populateSelect('#branch_company_id', baseUrl + 'get-companies-json',
            function (r) { return r.id; }, function (r) { return r.name; }, '-- Select Company --');
    });

    handleModalSubmit('#formBranch', 'branch/store', function (res) {
        var label = res.name || $('[name="branch_name"]', '#formBranch').val();
        appendAndSelect('#branch_id', res.id, label);
        //$('#modalBranch').modal('hide');
        $('#formBranch')[0].reset();
    });

    // ════════════════════════════════════════════════════════════════════════
    // 8. AUTO-CALCULATE END DATE
    // ════════════════════════════════════════════════════════════════════════
    function calculateEndDate() {
        var startDateStr = $('#start_date').val();
        var mode = $('#policy_mode').val();
        
        if (!startDateStr) return;
        
        var startDate = new Date(startDateStr);
        if (isNaN(startDate.getTime())) return;
        
        var endDate = new Date(startDate);
        
        if (mode === 'Monthly') {
            endDate.setMonth(endDate.getMonth() + 1);
        } else if (mode === 'Quarterly') {
            endDate.setMonth(endDate.getMonth() + 3);
        } else if (mode === 'Half-Yearly') {
            endDate.setMonth(endDate.getMonth() + 6);
        } else if (mode === 'Yearly') {
            endDate.setFullYear(endDate.getFullYear() + 1);
        } else {
            // Default to 1 year
            endDate.setFullYear(endDate.getFullYear() + 1);
        }
        
        // Subtract 1 day for policy duration
        endDate.setDate(endDate.getDate());
        
        var yyyy = endDate.getFullYear();
        var mm = String(endDate.getMonth() + 1).padStart(2, '0');
        var dd = String(endDate.getDate()).padStart(2, '0');
        
        $('#end_date').val(yyyy + '-' + mm + '-' + dd);
    }

    $('#start_date, #policy_mode').on('change', function() {
        calculateEndDate();
    });

});
</script>
@endpush

@endsection
