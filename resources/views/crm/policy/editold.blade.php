@extends('layouts.app')
@section('content')
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
<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-10 mb-lg-0 mb-4">
            <div class="card ">
                <div class="card-header pb-2 pt-2 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Update Policy</h6>
                        <a href="<?= base_url('all-policies'); ?>" class="btn btn-dark-blue mb-0"><i class="fa-solid fa-angle-left"></i> BACK</a>
                    </div>
                </div>
                <div class="table-responsive px-0 px-lg-5 px-md-5">
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

                        <!-- Step 1: Policy Holder + Policy Details -->
                        <div class="step step-1">
                            <div class="row">
                                <!-- Policy Holder -->
                                  <div class="mb-3 col-lg-6">
                                        <label for="policy_number" class="form-label">Policy Number</label>
                                        <input type="text" class="form-control" name="policy_number" id="policy_number" value="<?= $policy['policy_number_no'] ?? '' ?>">
                                        <div class="invalid-feedback" id="policy_number_err"></div>
                                    </div>
                                <?php
                                $session = session();

                                if ($session->get("user_role") == 3) {  ?>
                                    <div class="mb-3 col-lg-6">
                                        <label class="form-label" for="user_id">It's self / Staff Name</label>
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
                                        <label class="form-label" for="staff_id">Staff Name</label>
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
                                    <label class="form-label" for="family_group_id">Family Group</label>
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
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="family_group_id_err"></div>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="user_id">Policy Holder Name</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex:1;">
                                            <select class="form-control" name="user_id" id="user_id">
                                                <option value="">-- Select Holder --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="user_id_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="policy_type_id">Policy Type</label>
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
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="policy_type_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="sub_type_id">Policy Sub Type</label>
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
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="sub_type_err"></div>
                                </div>

                                <!-- Segment -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="segment_id">Segment</label>
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
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="segment_err"></div>
                                </div>

                                <!-- Company -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="company_id">Insurance Company</label>
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
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="company_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="branch_id">Branch</label>
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
                                        <label for="vehicle_reg_no" class="form-label">Vehicle Registration Number</label>
                                        <input type="text" class="form-control" name="vehicle_reg_no" id="vehicle_reg_no" value="<?= esc($vehicle['vehicle_reg_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="vehicle_reg_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_model" class="form-label">Vehicle Model</label>
                                        <input type="text" class="form-control" name="vehicle_model" id="vehicle_model" value="<?= esc($vehicle['vehicle_model'] ?? '') ?>">
                                        <div class="invalid-feedback" id="vehicle_model_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="engine_no" class="form-label">Engine Number</label>
                                        <input type="text" class="form-control" name="engine_no" id="engine_no" value="<?= esc($vehicle['engine_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="engine_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="chassis_no" class="form-label">Chassis Number</label>
                                        <input type="text" class="form-control" name="chassis_no" id="chassis_no" value="<?= esc($vehicle['chassis_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="chassis_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="fuel_type" class="form-label">Fuel Type</label>
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
                                        <label for="ncb" class="form-label">No Claim Bonus</label>
                                        <input type="text" class="form-control" name="ncb" id="ncb" value="<?= esc($vehicle['ncb'] ?? '') ?>">
                                        <div class="invalid-feedback" id="ncb_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="year_of_manufacture" class="form-label">Year of Manufacture</label>
                                        <input type="number" class="form-control" name="year_of_manufacture" id="year_of_manufacture" value="<?= esc($vehicle['year_of_manufacture'] ?? '') ?>">
                                        <div class="invalid-feedback" id="year_of_manufacture_err"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Policy Mode, Start/End Dates -->
                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="policy_mode">Payment Mode</label>
                                    <select class="form-control" name="policy_mode" id="policy_mode">
                                        <?php $modes = ['Monthly', 'Quarterly', 'Half-Yearly', 'Yearly']; ?>
                                        <?php foreach ($modes as $mode): ?>
                                            <option value="<?= $mode ?>" <?= ($policy['policy_mode'] == $mode) ? 'selected' : ''; ?>><?= $mode ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="start_date">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?= $policy['start_date']; ?>">
                                    <span id="start_date_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="end_date">End Date</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?= $policy['end_date']; ?>">
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
                                    <label class="form-label" for="sum_assured">Sum Assured</label>
                                    <input type="number" step="0.01" class="form-control" name="sum_assured" id="sum_assured" value="<?= esc($policy['sum_assured']); ?>">
                                    <span id="sum_assured_err" class="text-danger"></span>
                                </div>
                                <!-- Vehicle IDV (only for vehicle) -->
                                <div class="mb-3 col-lg-3 vehicle-only-field <?= $isVehiclePolicy ? '' : 'd-none' ?>">
                                    <label class="form-label" for="vehicle_idv">Vehicle IDV</label>
                                    <input type="number" step="0.01" class="form-control" name="vehicle_idv" id="vehicle_idv" value="<?= esc($policy['vehicle_idv'] ?? ''); ?>">
                                    <span id="vehicle_idv_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="premium">Premium Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="premium" id="premium" value="<?= esc($policy['premium_amount']); ?>">
                                    <span id="premium_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="tax_amount">Tax Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="tax_amount" id="tax_amount" value="<?= esc($policy['tax_amount']); ?>">
                                    <span id="tax_amount_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="payment_date">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" id="payment_date" value="<?= $policy['payment_date']; ?>">
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
                                    <label class="form-label" for="payment_mode">Payment Type</label>
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
                                    <label class="form-label" for="amount">Payment Amount</label>
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

                        <!-- Navigation Buttons -->
                        <div class="row mt-3">
                            <div class="col-6">
                                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-dark-blue" id="nextBtn" onclick="nextPrev(1)">Next</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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
        if (n === 0) { $('#prevBtn').hide(); } else { $('#prevBtn').show(); }
        $('#nextBtn').text(n === steps.length - 1 ? 'Submit' : 'Next');
    }

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
    $('#user_id').select2($.extend({}, s2opts, { placeholder: '-- Select Holder --' }));
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
            $holderSelect.html('<option value="">-- Select Holder --</option>');
            $holderSelect.trigger('change.select2');
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
            $container.addClass('select2-error');
            $('#' + errId).text(msg);
            return false;
        }
        $container.removeClass('select2-error');
        $('#' + errId).text('');
        return true;
    }

    function validateStep(step) {
        var isValid = true;
        if (step === 0) {
            if (!validateSelect('family_group_id', 'family_group_id_err', 'Please select a family group.')) isValid = false;
            if (!validateSelect('user_id', 'user_id_err', 'Please select a policy holder.')) isValid = false;
            if (!$('#policy_number').val()) {
                $('#policy_number').addClass('is-invalid');
                $('#policy_number_err').text('Please enter a policy number.');
                isValid = false;
            } else { $('#policy_number').removeClass('is-invalid'); $('#policy_number_err').text(''); }
            if (!validateSelect('policy_type_id', 'policy_type_err', 'Please select a policy type.')) isValid = false;
            if (!validateSelect('sub_type_id', 'sub_type_err', 'Please select a sub type.')) isValid = false;
            if (!validateSelect('segment_id', 'segment_err', 'Please select a segment.')) isValid = false;
            if (!validateSelect('company_id', 'company_err', 'Please select a company.')) isValid = false;
            if (!validateSelect('branch_id', 'branch_err', 'Please select a branch.')) isValid = false;
        }
        if (step === 1) {
            if (!$('#policy_mode').val()) {
                $('#policy_mode').addClass('is-invalid'); $('#policy_mode_err').text('Please select a payment mode.'); isValid = false;
            } else { $('#policy_mode').removeClass('is-invalid'); $('#policy_mode_err').text(''); }
            if (!$('#start_date').val()) {
                $('#start_date').addClass('is-invalid'); $('#start_date_err').text('Please select start date.'); isValid = false;
            } else { $('#start_date').removeClass('is-invalid'); $('#start_date_err').text(''); }
            if (!$('#end_date').val()) {
                $('#end_date').addClass('is-invalid'); $('#end_date_err').text('Please select end date.'); isValid = false;
            } else { $('#end_date').removeClass('is-invalid'); $('#end_date_err').text(''); }
            if (!$('#nominee_name').val()) {
                $('#nominee_name').addClass('is-invalid'); $('#nominee_name_err').text('Please enter nominee name.'); isValid = false;
            } else { $('#nominee_name').removeClass('is-invalid'); $('#nominee_name_err').text(''); }
            if (!$('#vehicle_fields').hasClass('d-none')) {
                var vmap = { 'vehicle_reg_no':'Please enter vehicle registration number.','vehicle_model':'Please enter vehicle model.','engine_no':'Please enter engine number.','chassis_no':'Please enter chassis number.','fuel_type':'Please select fuel type.','ncb':'Please enter no claim bonus.','year_of_manufacture':'Please enter year of manufacture.' };
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
            ['premium','payment_date','payment_mode','amount'].forEach(function(f) {
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
            if ($(this).val()) { $(this).next('.select2-container').removeClass('select2-error'); $('#' + id + '_err').text(''); }
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
                    Swal.fire({ title: 'Success!', text: res.message, icon: 'success', confirmButtonText: 'OK' })
                        .then(function() { window.location.href = '{{ route("policies.legacy.index") }}'; });
                } else {
                    Swal.fire({ title: 'Error!', text: res.message, icon: 'error', confirmButtonText: 'OK' });
                    if (res.errors) { $.each(res.errors, function(k, v) { $('#' + k + '_err').text(v); }); }
                }
            },
            error: function() { Swal.fire({ title: 'Oops!', text: 'Something went wrong!', icon: 'error' }); },
            complete: function() { $('#nextBtn').prop('disabled', false).text('Submit'); }
        });
    }

});
</script>
@endpush
@endsection

