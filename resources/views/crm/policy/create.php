<?= $this->include('layout/header') ?>
<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
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
    .d-flex.gap-2 > div[style*="flex"] {
        min-width: 0;
    }
    .d-flex.gap-2 .btn {
        align-self: flex-start;
        white-space: nowrap;
        height: 37px;
    }

    /* Select2 validation error state */
    .select2-error .select2-selection {
        border: 1px solid #dc3545 !important;
        border-radius: 7px !important;
    }

    @media (max-width: 576px) {
        .card-header {
            height: 92px;
        }

        #issue {
            margin-top: -15px !important;
        }
    }

    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 2),
    only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 2) {
        #issue {
            margin-top: -3px !important;
        }
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

</style>
<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-10 mb-lg-0 mb-4">
            <div class="card ">
                <div class="card-header pb-0 pt-2 py-2 px-2 px-md-4">
                    <div class="row align-items-center">
                        <!-- Title -->
                        <div class="col-lg-6 col-md-6 col-12">
                            <h6 class="mt-2 mb-0 text-center text-md-start">ADD INSURANCE POLICY</h6>
                        </div>

                        <!-- Buttons -->
                        <div class="col-lg-6 col-md-6 col-12 d-flex justify-content-center justify-content-lg-end gap-1 mt-1 mt-lg-0">
                            <input type="file" id="pdf_upload" accept=".pdf" style="display: none;">
                            <button type="button" class="btn btn-light mb-0" onclick="document.getElementById('pdf_upload').click()">
                                <i class="fas fa-upload"></i> Upload Policy PDF
                            </button>

                            <a class="btn btn-light px-md-4 mb-0"
                                href="<?= base_url('all-policies'); ?>">
                                <i class="fa-solid fa-angle-left"></i> BACK
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive px-0 px-lg-5 px-md-5">
                    <form class="m-3" id="add_policy_form">
                        <input type="hidden" name="base_url" id="base_url" value="<?= base_url(); ?>">

                        <!-- Step 1: User Selection -->
                        <div class="step step-1">
                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label for="policy_number" class="form-label">Policy Number</label>
                                    <input type="text" class="form-control" name="policy_number" id="policy_number">
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
                                                    value="<?= $staff['id'] ?>">
                                                    <?= $staff['name'] ?? 'Unkown' ?>
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
                                                    <?= ($staff['id'] == $currentUserId) ? 'selected disabled' : '' ?>>
                                                    <?= $staff['name'] ?? 'Unknown' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback" id="staff_id_err"></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Policy Holder -->


                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="family_group_id">Family Group</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="family_group_id" id="family_group_id">
                                                <option value="">-- Select Family Group --</option>
                                                <?php foreach ($familyGroups as $familyGroup): ?>
                                                    <option
                                                        value="<?= $familyGroup['id'] ?>"
                                                        data-holder-id="<?= $familyGroup['holder_id'] ?? '' ?>">
                                                        <?= $familyGroup['group_name'] ?? 'Unnamed Group' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalFamilyGroup">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="family_group_id_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="user_id">Policy Holder Name</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="user_id" id="user_id">
                                                <option value="">-- Select Holder --</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalPolicyHolder">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="user_id_err"></div>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="policy_type_id">Select Policy Type</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="policy_type_id" id="policy_type_id">
                                                <option value="">-- Select Policy Type --</option>
                                                <?php foreach ($policyTypes as $pt): ?>
                                                    <option value="<?= $pt['id'] ?>"><?= $pt['name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalPolicyType">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="policy_type_err"></div>
                                </div>

                                <!-- Hidden input for storing policy type name -->
                                <input type="hidden" name="policy_type_name" id="policy_type_name">

                                <!-- Policy Sub Type -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="sub_type_id">Policy Sub Type</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="sub_type_id" id="sub_type_id">
                                                <option value="">-- Select Sub Type --</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalPolicySubType">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="sub_type_err"></div>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="segment_id">Segment</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="segment_id" id="segment_id">
                                                <option value="">-- Select Segment --</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalSegment">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="segment_err"></div>
                                </div>
                                <!-- Company -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="company_id">Insurance Company</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="company_id" id="company_id">
                                                <option value="">-- Select Company --</option>
                                                <?php foreach ($companies as $c): ?>
                                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalInsuranceCompany">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="company_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="branch_id">Branch</label>
                                    <div class="d-flex gap-2">
                                        <div style="flex: 1;">
                                            <select class="form-control" name="branch_id" id="branch_id">
                                                <option value="">-- Select Branch --</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalBranch">Add</button>
                                    </div>
                                    <div class="invalid-feedback d-block text-danger small mt-1" id="branch_err"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">



                        </div>

                        <div class="row">


                        </div>



                        <!-- Step 3: Policy Dates & Nominee -->
                        <div class="step step-2 d-none">

                            <!-- Vehicle Insurance Fields -->
                            <div id="vehicle_fields" class="insurance-fields d-none">
                                <div class="row">
                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_reg_no" class="form-label">Vehicle Registration Number</label>
                                        <input type="text" class="form-control" name="vehicle_reg_no" id="vehicle_reg_no">
                                        <div class="invalid-feedback" id="vehicle_reg_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_model" class="form-label">Vehicle Model</label>
                                        <input type="text" class="form-control" name="vehicle_model" id="vehicle_model">
                                        <div class="invalid-feedback" id="vehicle_model_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="engine_no" class="form-label">Engine Number</label>
                                        <input type="text" class="form-control" name="engine_no" id="engine_no">
                                        <div class="invalid-feedback" id="engine_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="chassis_no" class="form-label">Chassis Number</label>
                                        <input type="text" class="form-control" name="chassis_no" id="chassis_no">
                                        <div class="invalid-feedback" id="chassis_no_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="fuel_type" class="form-label">Fuel Type</label>
                                        <select class="form-control" name="fuel_type" id="fuel_type">
                                            <option value="">-- Select Fuel Type --</option>
                                            <option value="Petrol">Petrol</option>
                                            <option value="Diesel">Diesel</option>
                                            <option value="Electric">Electric</option>
                                        </select>
                                        <div class="invalid-feedback" id="fuel_type_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="cc" class="form-label">Engine Capacity (CC)</label>
                                        <input type="number" class="form-control" name="cc" id="cc">
                                        <div class="invalid-feedback" id="cc_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="gvw" class="form-label">Gross Vehicle Weight (GVW)</label>
                                        <input type="number" class="form-control" name="gvw" id="gvw">
                                        <div class="invalid-feedback" id="gvw_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="ncb" class="form-label">No Claim Bonus</label>
                                        <input type="text" class="form-control" name="ncb" id="ncb">
                                        <div class="invalid-feedback" id="ncb_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                        <label for="year_of_manufacture" class="form-label">Year of Manufacture</label>
                                        <input type="number" class="form-control" name="year_of_manufacture" id="year_of_manufacture">
                                        <div class="invalid-feedback" id="year_of_manufacture_err"></div>
                                    </div>
                                </div>
                            </div>



                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="policy_mode">Payment Mode</label>
                                    <select class="form-control" name="policy_mode" id="policy_mode">
                                        <option value="">-- Select Payment Mode --</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Half-Yearly">Half-Yearly</option>
                                        <option value="Yearly">Yearly</option>
                                    </select>
                                    <div class="invalid-feedback" id="policy_mode_err"></div>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="start_date">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date">
                                    <div class="invalid-feedback" id="start_date_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="end_date">End Date</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date">
                                    <div class="invalid-feedback" id="end_date_err"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="nominee_name">Nominee Name</label>
                                    <input type="text" class="form-control" name="nominee_name" id="nominee_name">
                                    <div class="invalid-feedback" id="nominee_name_err"></div>
                                </div>

                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="nominee_contact">Nominee Contact</label>
                                    <input type="text" class="form-control" name="nominee_contact" id="nominee_contact">
                                    <div class="invalid-feedback" id="nominee_contact_err"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Premium, Payment, Sum Assured -->
                        <div class="step step-3 d-none">
                            <div class="row">
                                <!-- Sum Assured (only for NON-Vehicle insurance) -->
                                <div class="mb-3 col-lg-3 non-vehicle-field">
                                    <label class="form-label" for="sum_assured">Sum Assured</label>
                                    <input type="number" step="0.01" class="form-control" name="sum_assured" id="sum_assured">
                                    <span id="sum_assured_err" class="text-danger"></span>
                                </div>
                                
                                <!-- Vehicle IDV (only for Vehicle insurance) -->
                                <div class="mb-3 col-lg-3 vehicle-only-field d-none">
                                    <label class="form-label" for="vehicle_idv">Vehicle IDV</label>
                                    <input type="number" step="0.01" class="form-control" name="vehicle_idv" id="vehicle_idv">
                                    <span id="vehicle_idv_err" class="text-danger"></span>
                                </div>

                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="premium">Premium Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="premium" id="premium">
                                    <span id="premium_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="tax_amount">Tax Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="tax_amount" id="tax_amount">
                                    <span id="tax_amount_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="payment_date">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" id="payment_date">
                                    <span id="payment_date_err" class="text-danger"></span>
                                </div>
                            </div>

                            <!-- CNG/LPG Unit (only for Vehicle insurance) -->
                            <div class="row vehicle-only-field d-none">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="cng_lpg_unit">CNG/LPG Unit</label>
                                    <input type="text" class="form-control" name="cng_lpg_unit" id="cng_lpg_unit" placeholder="Enter CNG/LPG unit details">
                                    <span id="cng_lpg_unit_err" class="text-danger"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="payment_mode">Payment Type</label>
                                    <select class="form-control" name="payment_mode" id="payment_mode">
                                        <option value="">-- Select Payment Type --</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Online">Online</option>
                                        <option value="Bank">Bank</option>
                                        <option value="Card">Card</option>
                                    </select>
                                    <span id="payment_mode_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="amount">Payment Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" id="amount">
                                    <span id="amount_err" class="text-danger"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-lg-12">
                                    <label class="form-label" for="remarks">Remarks</label>
                                    <textarea class="form-control" name="remarks" id="remarks" rows="2"></textarea>
                                </div>
                            </div>
                        </div>


                        <div class="row mt-3">
                            <div class="col-6">
                                <button type="button" class="btn btn-secondary" id="prevBtn">Previous</button>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-dark-blue" id="nextBtn">Next</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->include('layout/footer') ?>
<!-- jQuery (only one version) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<script>
    // console.log('🚀 Policy Create Page Scripts Loading...');
    // console.log('📋 Console logging enabled - check console filters if logs not visible');
    $(document).ready(function() {
        // console.log('✅ Document Ready - Initializing Select2...');
        // Family Group
        $('#family_group_id').select2({
            placeholder: "-- Select Family Group --",
            allowClear: true,
            width: '100%'
        });

        // Policy Holder Name
        $('#user_id').select2({
            placeholder: "-- Select Holder --",
            allowClear: true,
            width: '100%'
        });

        // Policy Type
        $('#policy_type_id').select2({
            placeholder: "-- Select Policy Type --",
            allowClear: true,
            width: '100%'
        });

        // Segment
        $('#segment_id').select2({
            placeholder: "-- Select Segment --",
            allowClear: true,
            width: '100%'
        });

        // Policy Sub Type
        $('#sub_type_id').select2({
            placeholder: "-- Select Sub Type --",
            allowClear: true,
            width: '100%'
        });

        // Insurance Company
        $('#company_id').select2({
            placeholder: "-- Select Company --",
            allowClear: true,
            width: '100%'
        });

        // Branch
        $('#branch_id').select2({
            placeholder: "-- Select Branch --",
            allowClear: true,
            width: '100%'
        });
    });
</script>


<script>
    $(document).ready(function() {
        // Track to prevent duplicate AJAX calls for policy type
        var currentPolicyTypeId = null;
        var isLoadingPolicyOptions = false;
        var policyOptionsLoadedForType = null;

        $('#policy_type_id').on('change', function() {
            var policyTypeId = $(this).val();

            // Prevent duplicate calls for the same policy type ID
            if (policyTypeId === policyOptionsLoadedForType &&
                $('#sub_type_id').find('option').length > 1 &&
                $('#segment_id').find('option').length > 1) {
                // console.log('⏭️ Policy options already loaded for type:', policyTypeId);
                return;
            }

            // Prevent multiple simultaneous AJAX calls
            if (isLoadingPolicyOptions && policyTypeId === currentPolicyTypeId) {
                // console.log('⏳ Already loading policy options for type:', policyTypeId);
                return;
            }

            // Update tracking variables
            currentPolicyTypeId = policyTypeId;
            isLoadingPolicyOptions = true;

            // Select elements
            var $subTypeSelect = $('#sub_type_id');
            var $segmentSelect = $('#segment_id');

            // Show loading in both dropdowns
            $subTypeSelect.html('<option value="">Loading...</option>');
            $segmentSelect.html('<option value="">Loading...</option>');

            if (policyTypeId) {
                // Fetch Policy Sub Types
                $.ajax({
                    url: "<?= base_url('policy-sub-types') ?>/" + policyTypeId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $subTypeSelect.html('<option value="">-- Select Sub Type --</option>');
                        $.each(data, function(index, sub) {
                            $subTypeSelect.append('<option value="' + sub.id + '">' + sub.name + '</option>');
                        });
                        // Trigger change event
                        $subTypeSelect.trigger('change');

                        // Mark as loaded if both requests complete
                        if ($segmentSelect.find('option').length > 1) {
                            isLoadingPolicyOptions = false;
                            policyOptionsLoadedForType = policyTypeId;
                        }
                    },
                    error: function(xhr, status, error) {
                        isLoadingPolicyOptions = false;
                        $subTypeSelect.html('<option value="">-- Select Sub Type --</option>');
                        $subTypeSelect.trigger('change.select2');
                        console.error('Error fetching sub-types:', error);
                    }
                });

                // Fetch Segments
                $.ajax({
                    url: "<?= base_url('segments-by-type') ?>/" + policyTypeId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $segmentSelect.html('<option value="">-- Select Segment --</option>');
                        if (data.length > 0) {
                            $.each(data, function(index, segment) {
                                $segmentSelect.append('<option value="' + segment.id + '">' + segment.name + '</option>');
                            });
                        } else {
                            $segmentSelect.html('<option value="">No segments available</option>');
                        }
                        // Trigger change event
                        $segmentSelect.trigger('change');

                        // Mark as loaded if both requests complete
                        if ($subTypeSelect.find('option').length > 1) {
                            isLoadingPolicyOptions = false;
                            policyOptionsLoadedForType = policyTypeId;
                        }
                    },
                    error: function(xhr, status, error) {
                        isLoadingPolicyOptions = false;
                        $segmentSelect.html('<option value="">-- Select Segment --</option>');
                        $segmentSelect.trigger('change');
                        console.error('Error fetching segments:', error);
                    }
                });

            } else {
                // Reset tracking variables
                currentPolicyTypeId = null;
                isLoadingPolicyOptions = false;
                policyOptionsLoadedForType = null;

                // Reset dropdowns if no policy type selected
                $subTypeSelect.html('<option value="">-- Select Sub Type --</option>');
                $segmentSelect.html('<option value="">-- Select Segment --</option>');
            }
        });
        // Track to prevent duplicate AJAX calls
        var currentCompanyId = null;
        var isLoadingBranches = false;
        var branchesLoadedForCompany = null;

        $('#company_id').on('change', function() {
            var companyId = $(this).val();
            var baseUrl = '<?= base_url() ?>';
            var $branchSelect = $('#branch_id');

            // Prevent duplicate calls for the same company ID
            if (companyId === branchesLoadedForCompany && $branchSelect.find('option').length > 1) {
                // console.log('⏭️ Branches already loaded for company:', companyId);
                return;
            }

            // Prevent multiple simultaneous AJAX calls
            if (isLoadingBranches && companyId === currentCompanyId) {
                // console.log('⏳ Already loading branches for company:', companyId);
                return;
            }

            // Update tracking variables
            currentCompanyId = companyId;
            isLoadingBranches = true;

            // Show loading while fetching branches
            $branchSelect.html('<option value="">Loading...</option>');

            if (companyId) {
                $.ajax({
                    url: baseUrl + '/get-branches/' + companyId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        isLoadingBranches = false;
                        branchesLoadedForCompany = companyId;

                        if (data.length > 0) {
                            $branchSelect.html('<option value="">-- Select Branch --</option>');
                            $.each(data, function(index, branch) {
                                $branchSelect.append('<option value="' + branch.id + '">' + branch.name + '</option>');
                            });
                        } else {
                            $branchSelect.html('<option value="">No branches available</option>');
                        }
                        // Trigger change event
                        $branchSelect.trigger('change');
                    },
                    error: function() {
                        isLoadingBranches = false;
                        $branchSelect.html('<option value="">-- Select Branch --</option>');
                        $branchSelect.trigger('change.select2');
                        Swal.fire('Error', 'Could not fetch branches', 'error');
                    }
                });
            } else {
                isLoadingBranches = false;
                branchesLoadedForCompany = null;
                $branchSelect.html('<option value="">-- Select Branch --</option>');
                $branchSelect.trigger('change.select2');
            }
        });

        // Track to prevent duplicate AJAX calls for family group
        var currentFamilyGroupId = null;
        var isLoadingHolders = false;
        var holdersLoadedForGroup = null;

        $('#family_group_id').on('change', function() {
            var $selectedOption = $(this).find(':selected');
            var familyGroupId = $(this).val(); // get selected family group
            var baseUrl = $('#base_url').val();
            var $holderSelect = $('#user_id'); // target dropdown
            var holderId = $selectedOption.data('holder-id');

            // Prevent duplicate calls for the same family group ID
            if (familyGroupId === holdersLoadedForGroup && $holderSelect.find('option').length > 1) {
                // console.log('⏭️ Holders already loaded for family group:', familyGroupId);
                return;
            }

            // Prevent multiple simultaneous AJAX calls
            if (isLoadingHolders && familyGroupId === currentFamilyGroupId) {
                // console.log('⏳ Already loading holders for family group:', familyGroupId);
                return;
            }

            // Update tracking variables
            currentFamilyGroupId = familyGroupId;
            isLoadingHolders = true;

            // Show loading message
            $holderSelect.html('<option value="">Loading...</option>');

            if (familyGroupId) {
                $.ajax({
                    url: baseUrl + 'family-groups/get-family-holder/' + holderId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        isLoadingHolders = false;
                        holdersLoadedForGroup = familyGroupId;

                        if (data.length > 0) {
                            $holderSelect.html('<option value="">-- Select Holder --</option>');
                            $.each(data, function(index, holder) {
                                $holderSelect.append(
                                    '<option value="' + holder.id + '">' +
                                    holder.name +
                                    (holder.email ? ' (' + holder.email + ')' : '') +
                                    '</option>'
                                );
                            });
                        } else {
                            $holderSelect.html('<option value="">No holders available</option>');
                        }
                        // Trigger change event
                        $holderSelect.trigger('change');
                    },
                    error: function() {
                        isLoadingHolders = false;
                        $holderSelect.html('<option value="">-- Select Holder --</option>');
                        $holderSelect.trigger('change.select2');
                        Swal.fire('Error', 'Could not fetch holders', 'error');
                    }
                });
            } else {
                isLoadingHolders = false;
                holdersLoadedForGroup = null;
                $holderSelect.html('<option value="">-- Select Holder --</option>');
                $holderSelect.trigger('change.select2');
            }
        });


    });
</script>
<script>
    $(document).ready(function() {
        var currentStep = 0;
        showStep(currentStep);

        function showStep(n) {
            $('.step').addClass('d-none');
            $('.step').eq(n).removeClass('d-none'); // show current step
            $('#prevBtn').toggle(n !== 0);
            $('#nextBtn').text(n === $('.step').length - 1 ? 'Submit' : 'Next');
        }

        $('#nextBtn').click(function() {
            if (!validateStep(currentStep)) return false;

            currentStep++;
            if (currentStep >= $('.step').length) {
                submitForm();
                return false;
            }
            showStep(currentStep);
        });

        $('#prevBtn').click(function() {
            currentStep--;
            if (currentStep < 0) currentStep = 0;
            showStep(currentStep);
        });

        // Live error removal — regular fields
        $('#add_policy_form').on('change input', 'select, input, textarea', function() {
            var fieldId = $(this).attr('id');
            if ($(this).val()) {
                $(this).removeClass('is-invalid');
            }
            $('#' + fieldId + '_err').text('');
        });

        // Live error removal — Select2 fields (clear red border + message on selection)
        var select2Fields = ['family_group_id', 'user_id', 'policy_type_id', 'sub_type_id', 'segment_id', 'company_id', 'branch_id'];
        $.each(select2Fields, function(i, id) {
            $('#' + id).on('change', function() {
                if ($(this).val()) {
                    $(this).next('.select2-container').removeClass('select2-error');
                    $('#' + id + '_err').text('');
                }
            });
        });

        function validateField(fieldId, errorMsg) {
            var field = $('#' + fieldId);
            if (!field.val()) {
                field.addClass('is-invalid');
                $('#' + fieldId + '_err').text(errorMsg);
                return false;
            } else {
                field.removeClass('is-invalid');
                $('#' + fieldId + '_err').text('');
                return true;
            }
        }

        function validateStep(step) {
            var isValid = true;
            $('.text-danger').text('');

            // Helper: validate a Select2 or regular select field
            function validateSelect(id, errId, msg) {
                var $sel = $('#' + id);
                var $container = $sel.next('.select2-container');
                if (!$sel.val()) {
                    $container.addClass('select2-error');
                    $('#' + errId).text(msg);
                    isValid = false;
                } else {
                    $container.removeClass('select2-error');
                    $('#' + errId).text('');
                }
            }

            if (step === 0) {
                // Family Group
                validateSelect('family_group_id', 'family_group_id_err', 'Please select a family group.');
                // Policy Holder Name
                validateSelect('user_id', 'user_id_err', 'Please select a policy holder.');
                // Policy Number
                if (!$('#policy_number').val()) {
                    $('#policy_number').addClass('is-invalid');
                    $('#policy_number_err').text('Please enter a policy number.');
                    isValid = false;
                } else {
                    $('#policy_number').removeClass('is-invalid');
                    $('#policy_number_err').text('');
                }
                // Policy Type
                validateSelect('policy_type_id', 'policy_type_err', 'Please select a policy type.');
                // Policy Sub Type
                validateSelect('sub_type_id', 'sub_type_err', 'Please select a sub type.');
                // Segment
                validateSelect('segment_id', 'segment_err', 'Please select a segment.');
                // Company
                validateSelect('company_id', 'company_err', 'Please select a company.');
                // Branch
                validateSelect('branch_id', 'branch_err', 'Please select a branch.');
            }

            // On change, update hidden field
            $('#policy_type_id').on('change', function() {
                var selectedText = $('#policy_type_id option:selected').text();
                $('#policy_type_name').val(selectedText);
            });

            // Step 1 validation
            if (step === 1) {

                // Payment Mode
                if (!$('#policy_mode').val()) {
                    $('#policy_mode').addClass('is-invalid');
                    $('#policy_mode_err').text('Please select a payment mode.');
                    isValid = false;
                } else {
                    $('#policy_mode').removeClass('is-invalid');
                    $('#policy_mode_err').text('');
                }

                if (!$('#start_date').val()) {
                    $('#start_date').addClass('is-invalid');
                    $('#start_date_err').text('Please select start date.');
                    isValid = false;
                } else {
                    $('#start_date').removeClass('is-invalid');
                    $('#start_date_err').text('');
                }

                if (!$('#end_date').val()) {
                    $('#end_date').addClass('is-invalid');
                    $('#end_date_err').text('Please select end date.');
                    isValid = false;
                } else {
                    $('#end_date').removeClass('is-invalid');
                    $('#end_date_err').text('');
                }


                // ✅ Vehicle fields validation only if vehicle_fields section is visible
                if (!$('#vehicle_fields').hasClass('d-none')) {
                    var vehicleFieldMap = {
                        'vehicle_reg_no'     : 'Please enter vehicle registration number.',
                        'vehicle_model'      : 'Please enter vehicle model.',
                        'engine_no'          : 'Please enter engine number.',
                        'chassis_no'         : 'Please enter chassis number.',
                        'fuel_type'          : 'Please select fuel type.',
                        'ncb'                : 'Please enter no claim bonus.',
                        'year_of_manufacture': 'Please enter year of manufacture.'
                    };
                    $.each(vehicleFieldMap, function(field, msg) {
                        if (!$('#' + field).val()) {
                            $('#' + field).addClass('is-invalid');
                            $('#' + field + '_err').text(msg);
                            isValid = false;
                        } else {
                            $('#' + field).removeClass('is-invalid');
                            $('#' + field + '_err').text('');
                        }
                    });
                }
            }



            if (step === 2) {
                // Sum Assured (only for non-vehicle insurance)
                if (!$('.non-vehicle-field').hasClass('d-none')) {
                    if (!$('#sum_assured').val()) {
                        $('#sum_assured').addClass('is-invalid');
                        $('#sum_assured_err').text('Please enter sum assured.');
                        isValid = false;
                    } else {
                        $('#sum_assured').removeClass('is-invalid');
                        $('#sum_assured_err').text('');
                    }
                }

                // Vehicle IDV (only for vehicle insurance)
                if (!$('.vehicle-only-field').hasClass('d-none')) {
                    if (!$('#vehicle_idv').val()) {
                        $('#vehicle_idv').addClass('is-invalid');
                        $('#vehicle_idv_err').text('Please enter vehicle IDV.');
                        isValid = false;
                    } else {
                        $('#vehicle_idv').removeClass('is-invalid');
                        $('#vehicle_idv_err').text('');
                    }
                }

                if (!$('#premium').val()) {
                    $('#premium').addClass('is-invalid');
                    $('#premium_err').text('Please enter premium amount.');
                    isValid = false;
                } else {
                    $('#premium').removeClass('is-invalid');
                    $('#premium_err').text('');
                }

                if (!$('#payment_date').val()) {
                    $('#payment_date').addClass('is-invalid');
                    $('#payment_date_err').text('Please select payment date.');
                    isValid = false;
                } else {
                    $('#payment_date').removeClass('is-invalid');
                    $('#payment_date_err').text('');
                }

                if (!$('#payment_mode').val()) {
                    $('#payment_mode').addClass('is-invalid');
                    $('#payment_mode_err').text('Please select payment mode.');
                    isValid = false;
                } else {
                    $('#payment_mode').removeClass('is-invalid');
                    $('#payment_mode_err').text('');
                }

                if (!$('#amount').val()) {
                    $('#amount').addClass('is-invalid');
                    $('#amount_err').text('Please enter payment amount.');
                    isValid = false;
                } else {
                    $('#amount').removeClass('is-invalid');
                    $('#amount_err').text('');
                }
            }

            return isValid;
        }

        function submitForm() {
            var $form = $('#add_policy_form');
            var baseUrl = $('#base_url').val();

            // Use FormData so the optional PDF file is included
            var formData = new FormData($form[0]);

            // Attach the PDF file if one was selected
            var pdfFile = $('#pdf_upload')[0].files[0];
            if (pdfFile) {
                formData.append('policy_pdf', pdfFile);
            }

            $.ajax({
                url: baseUrl + 'store-policy',
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#nextBtn').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.status === true) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '<?= base_url("all-policies") ?>';
                        });
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(key, val) {
                                $('#' + key + '_err').text(val);
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    }
                    $('#nextBtn').prop('disabled', false).text('Next');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    Swal.fire('Error', 'Something went wrong! Please try again.', 'error');
                    $('#nextBtn').prop('disabled', false).text('Next');
                }
            });
        }
    });
</script>
<script>
    $(document).ready(function() {
        $('#policy_type_id').on('change', function() {
            var selectedText = $("#policy_type_id option:selected").text().toLowerCase();

            // Hide all insurance-specific fields first
            $('.insurance-fields').addClass('d-none');
            $('.vehicle-only-field').addClass('d-none');
            $('.non-vehicle-field').removeClass('d-none');

            // Show vehicle fields if selected
            if (selectedText.includes('vehicle')) {
                $('#vehicle_fields').removeClass('d-none');
                $('.vehicle-only-field').removeClass('d-none');
                $('.non-vehicle-field').addClass('d-none'); // Hide Sum Assured for vehicle
            }
        });
    });
</script>
<script>
    document.getElementById('policy_type_id').addEventListener('change', function() {
        let selectedText = this.options[this.selectedIndex].text;
        document.getElementById('policy_type_name').value = selectedText !== "-- Select Policy Type --" ? selectedText : "";
    });
</script>

<script>
    // PDF Upload and Auto-fill Functionality
    $(document).ready(function() {
        // console.log('🔵 PDF Upload handler initialized');
        console.log('🔵 Available dropdowns:', {
            policy_type: $('#policy_type_id').length,
            company: $('#company_id').length,
            branch: $('#branch_id').length,
            segment: $('#segment_id').length,
            sub_type: $('#sub_type_id').length,
            family_group: $('#family_group_id').length,
            user: $('#user_id').length
        });

        $('#pdf_upload').on('change', function(e) {
            // console.log('📎 PDF file selected:', e.target.files[0]?.name);
            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            if (file.type !== 'application/pdf') {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Please upload a valid PDF file.', 'Invalid File Type');
                    // Clear status div after showing toast
                    setTimeout(function() {
                        $('#pdf_status').html('');
                    }, 500);
                } else {
                    Swal.fire('Error', 'Please upload a valid PDF file.', 'error');
                    $('#pdf_status').html('<span class="text-danger">Invalid file type</span>');
                }
                $(this).val('');
                return;
            }

            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('File size must be less than 10MB.', 'File Too Large');
                    // Clear status div after showing toast
                    setTimeout(function() {
                        $('#pdf_status').html('');
                    }, 500);
                } else {
                    Swal.fire('Error', 'File size must be less than 10MB.', 'error');
                    $('#pdf_status').html('<span class="text-danger">File too large</span>');
                }
                $(this).val('');
                return;
            }

            // Show loading toast only (no status div update)
            if (typeof toastr !== 'undefined') {
                toastr.info('Processing PDF file...', 'Please wait', {
                    timeOut: 0,
                    extendedTimeOut: 0
                });
            } else {
                $('#pdf_status').html('<i class="fas fa-spinner fa-spin"></i> Processing PDF...');
            }

            // Create FormData
            const formData = new FormData();
            formData.append('pdf_file', file);

            // Upload and extract data
            $.ajax({
                url: '<?= base_url("extract-policy-pdf") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    // console.log('📄 PDF Upload Response:', response);
                    // Clear loading toast
                    if (typeof toastr !== 'undefined') {
                        toastr.clear();
                    }

                    if (response.status === true) {
                        // Prepare data object - include raw_text for frontend extraction
                        const formData = response.data || {};
                        if (response.raw_text) {
                            formData.raw_text = response.raw_text;
                            // console.log('📄 Raw text available for extraction, length:', response.raw_text.length);
                        }

                        // Show extracted data count
                        const dataCount = Object.keys(formData).filter(k => formData[k] && k !== 'raw_text').length;
                        // console.log('📊 Extracted Data Fields:', dataCount, formData);
                        // Auto-fill form fields (will extract from raw_text if needed)
                        // console.log('🚀 Starting form fill from PDF data...');
                        fillFormFromPdfData(formData);

                        // Final refresh of all dropdowns after form filling completes
                        setTimeout(function() {
                            const dropdowns = [
                                '#policy_type_id',
                                '#company_id',
                                '#branch_id',
                                '#segment_id',
                                '#sub_type_id',
                                '#family_group_id',
                                '#user_id',
                                '#policy_mode',
                                '#payment_mode',
                                '#fuel_type'
                            ];

                            dropdowns.forEach(function(selector) {
                                const $select = $(selector);
                                const value = $select.val();
                                if (value) {
                                    absolutelyForceSelect(selector, value);
                                }
                            });
                        }, 6500); // Wait 6.5 seconds for all dependent dropdowns to load

                        // Show success toast and clear status div
                        if (typeof toastr !== 'undefined') {
                            const message = 'PDF processed successfully! Found ' + dataCount + ' data fields.' +
                                (response.raw_text ? ' Additional extraction from raw text will be attempted.' : '');
                            toastr.success(message, 'Success!', {
                                timeOut: 5000
                            });
                            // Clear status div after showing toast
                            setTimeout(function() {
                                $('#pdf_status').html('');
                            }, 500);
                        } else {
                            $('#pdf_status').html('<span class="text-success"><i class="fas fa-check"></i> PDF processed successfully!</span>');
                            Swal.fire({
                                title: 'Success!',
                                html: 'PDF processed successfully.<br><small>Found ' + dataCount + ' data fields. ' +
                                    (response.raw_text ? 'Additional extraction from raw text will be attempted.' : '') + '</small>',
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    } else {
                        // Show error toast and clear status div
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.message || 'Failed to extract data from PDF.', 'Error');
                            // Clear status div after showing toast
                            setTimeout(function() {
                                $('#pdf_status').html('');
                            }, 500);
                        } else {
                            $('#pdf_status').html('<span class="text-danger">Failed to extract data</span>');
                            Swal.fire('Error', response.message || 'Failed to extract data from PDF.', 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Clear loading toast
                    if (typeof toastr !== 'undefined') {
                        toastr.clear();
                    }

                    console.error('❌ PDF Upload Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        statusCode: xhr.status
                    });

                    // Show error toast and clear status div
                    let errorMessage = 'Failed to process PDF. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            if (errorData.message) {
                                errorMessage = errorData.message;
                            }
                        } catch (e) {
                            // Keep default message
                        }
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage, 'Upload Failed');
                        // Clear status div after showing toast
                        setTimeout(function() {
                            $('#pdf_status').html('');
                        }, 500);
                    } else {
                        $('#pdf_status').html('<span class="text-danger">Upload failed</span>');
                        Swal.fire('Error', errorMessage, 'error');
                    }
                }
            });
        });
    });

    // Helper function to force Select2 to update its display
    function updateSelect2Display($select, value, text) {
        if (!$select.data('select2')) return;

        // Set value and trigger Select2 update
        $select.val(value).trigger('change.select2');

        // Force update the display text
        setTimeout(function() {
            const $container = $select.next('.select2-container');
            if ($container.length) {
                const $rendered = $container.find('.select2-selection__rendered');
                if ($rendered.length) {
                    $rendered.text(text);
                    $rendered.html(text);
                    $rendered.attr('title', text);
                    $rendered.removeClass('select2-selection__placeholder');
                    $rendered.css('color', '#495057');
                }
            }
        }, 50);
    }

    // Helper function to force regular select to update its display
    function updateRegularSelectDisplay($select, value) {
        if ($select.data('select2')) return; // Skip if Select2

        const $option = $select.find('option[value="' + value + '"]');
        if ($option.length === 0) {
            // console.log('⚠️ Option not found for value:', value, 'in selector:', $select.attr('id'));
            return;
        }

        // Remove selected from all options
        $select.find('option').prop('selected', false).removeAttr('selected');
        // Set selected on the target option
        $option.prop('selected', true).attr('selected', 'selected');
        // Set value multiple ways to ensure it sticks
        $select.val(value);
        $select[0].value = value;
        $select[0].selectedIndex = $option.index();

        // Trigger change event to ensure any dependent logic runs
        $select.trigger('change');

        // Force browser refresh
        const selectElement = $select[0];
        selectElement.blur();
        setTimeout(function() {
            selectElement.focus();
            setTimeout(function() {
                selectElement.blur();
            }, 10);
        }, 10);

        // console.log('✅ Updated regular select display:', $select.attr('id'), 'Value:', value, 'Text:', $option.text());
    }

    // Function to absolutely force a dropdown to show selected value AND display text immediately
    function absolutelyForceSelect(selector, value) {
        // console.log('🔵 absolutelyForceSelect called:', selector, 'Value:', value);
        const $select = $(selector);
        if ($select.length === 0) {
            console.error('❌ Selector not found:', selector);
            return false;
        }
        if (!value) {
            console.warn('⚠️ No value provided for:', selector);
            return false;
        }

        // Wait for option to be available (for dynamically loaded dropdowns)
        const checkAndSet = function(attempts) {
            attempts = attempts || 0;
            if (attempts > 20) {
                console.error('❌ Option not found after 20 attempts:', selector, value);
                console.log('Available options:', $select.find('option').map(function() {
                    return {
                        value: $(this).val(),
                        text: $(this).text()
                    };
                }).get());
                return false;
            }

            if (attempts > 0) {
                // console.log('⏳ Waiting for options... Attempt:', attempts, selector);
            }

            const $option = $select.find('option[value="' + value + '"]');
            if ($option.length === 0) {
                setTimeout(function() {
                    checkAndSet(attempts + 1);
                }, 100);
                return false;
            }

            const optionText = $option.text().trim();
            const optionIndex = $option.index();

            // Check if value is already set to prevent unnecessary change events
            const currentValue = $select.val();
            const shouldTriggerChange = currentValue !== value;

            // Set the value first
            $select.val(value);

            // If Select2 is initialized, use its API
            if ($select.data('select2')) {
                // Only trigger change event if value actually changed
                if (shouldTriggerChange) {
                    $select.trigger('change.select2');
                }

                // Force update the display
                setTimeout(function() {
                    const $container = $select.next('.select2-container');
                    if ($container.length) {
                        const $rendered = $container.find('.select2-selection__rendered');
                        if ($rendered.length) {
                            $rendered.text(optionText);
                            $rendered.html(optionText);
                            $rendered.attr('title', optionText);
                            $rendered.removeClass('select2-selection__placeholder');
                            $rendered.css('color', '#495057');
                        }
                    }
                }, 50);
            } else {
                // Regular select - use helper function to ensure display updates
                updateRegularSelectDisplay($select, value);

                // Trigger change event if value changed
                if (shouldTriggerChange) {
                    $select.trigger('change');
                }

                // Double-check the value is set after a short delay
                setTimeout(function() {
                    if ($select.val() !== value) {
                        updateRegularSelectDisplay($select, value);
                    }
                }, 50);
            }

            // console.log('✅ ABSOLUTELY FORCED:', selector, 'Value:', value, 'Text:', optionText);
            return true;
        };

        return checkAndSet(0);
    }

    // Helper function to select dropdown option by ID or text and force display
    function selectDropdownOption(selector, searchValue, exactMatch = false, isId = false) {
        const $select = $(selector);
        if ($select.length === 0) {
            // console.log('❌ Selector not found:', selector);
            return false;
        }

        let found = false;
        let optionValue = null;
        let optionText = '';

        // Wait for options to be available (for dynamically loaded dropdowns)
        const findAndSelect = function(attempts) {
            attempts = attempts || 0;
            if (attempts > 20) {
                // console.log('❌ Options not loaded after 20 attempts:', selector);
                return false;
            }

            // If it's an ID, directly set the value
            if (isId && searchValue) {
                optionValue = String(searchValue);
                const $option = $select.find('option[value="' + optionValue + '"]');

                if ($option.length > 0) {
                    optionText = $option.text().trim();
                    found = true;
                } else {
                    setTimeout(function() {
                        findAndSelect(attempts + 1);
                    }, 100);
                    return false;
                }
            } else {
                // Try text matching
                const searchText = String(searchValue).toLowerCase().trim();

                $select.find('option').each(function() {
                    const $option = $(this);
                    const optText = $option.text().toLowerCase().trim();
                    const optValue = $option.val();

                    // Skip empty values
                    if (!optValue) return true;

                    let matches = false;
                    if (exactMatch) {
                        matches = optText === searchText;
                    } else {
                        matches = optText.includes(searchText) || searchText.includes(optText);
                    }

                    if (matches) {
                        optionValue = optValue;
                        optionText = $option.text().trim();
                        found = true;
                        return false; // Break loop
                    }
                });

                if (!found) {
                    setTimeout(function() {
                        findAndSelect(attempts + 1);
                    }, 100);
                    return false;
                }
            }

            if (!found || !optionValue) {
                return false;
            }

            // NOW FORCE THE SELECTION AND DISPLAY using absolutelyForceSelect
            absolutelyForceSelect(selector, optionValue);

            // console.log('✅ Selected:', selector, 'Value:', optionValue, 'Text:', optionText);
            return true;
        };

        return findAndSelect(0);
    }

    // Function to force dropdown to show selected value
    function forceDropdownDisplay(selector) {
        const $select = $(selector);
        if ($select.length === 0) return;

        const value = $select.val();
        if (!value) return;

        const $option = $select.find('option[value="' + value + '"]');
        if ($option.length === 0) return;

        const optionText = $option.text().trim();

        // For regular select - force DOM update
        $select[0].selectedIndex = $option.index();
        $select[0].value = value;

        // Force native events
        $select[0].dispatchEvent(new Event('input', {
            bubbles: true
        }));
        $select[0].dispatchEvent(new Event('change', {
            bubbles: true
        }));

        // For Select2 - update display directly
        if ($select.hasClass('select2-hidden-accessible') || $select.data('select2')) {
            updateSelect2Display($select, value, optionText);
            $select.trigger('change.select2');
        }

        // Trigger jQuery change
        $select.trigger('change');
    }

    // Helper function to extract field from raw_text if not in parsed data
    function extractFromRawText(rawText, fieldPatterns) {
        if (!rawText) return null;

        for (let i = 0; i < fieldPatterns.length; i++) {
            const pattern = new RegExp(fieldPatterns[i], 'i');
            const match = rawText.match(pattern);
            if (match && match[1]) {
                return match[1].trim().replace(/[:;]\s*$/, '').trim();
            }
        }
        return null;
    }

    // Helper function to fill vehicle fields
    function fillVehicleFields(data) {
        if (data.vehicle_reg_no) {
            // console.log('🚗 Setting Vehicle Registration Number:', data.vehicle_reg_no);
            $('#vehicle_reg_no').val(data.vehicle_reg_no).trigger('input');
        }
        if (data.vehicle_model) {
            // console.log('🚗 Setting Vehicle Model:', data.vehicle_model);
            $('#vehicle_model').val(data.vehicle_model).trigger('input');
        }
        if (data.engine_no) {
            // console.log('🚗 Setting Engine Number:', data.engine_no);
            $('#engine_no').val(data.engine_no).trigger('input');
        }
        if (data.chassis_no) {
            // console.log('🚗 Setting Chassis Number:', data.chassis_no);
            $('#chassis_no').val(data.chassis_no).trigger('input');
        }
        if (data.fuel_type) {
            // console.log('🚗 Setting Fuel Type:', data.fuel_type);
            $('#fuel_type').val(data.fuel_type).trigger('change');
        }
        if (data.registration_date) {
            // console.log('🚗 Setting Registration Date:', data.registration_date);
            $('#registration_date').val(data.registration_date);
        }
        if (data.cc) {
            // console.log('🚗 Setting Engine Capacity (CC):', data.cc);
            $('#cc').val(data.cc).trigger('input');
        }
        if (data.gvw) {
            // console.log('🚗 Setting Gross Vehicle Weight (GVW):', data.gvw);
            $('#gvw').val(data.gvw).trigger('input');
        }
        if (data.ncb) {
            // console.log('🚗 Setting No Claim Bonus (NCB):', data.ncb);
            $('#ncb').val(data.ncb).trigger('input');
        }
        if (data.year_of_manufacture) {
            // console.log('🚗 Setting Year of Manufacture:', data.year_of_manufacture);
            $('#year_of_manufacture').val(data.year_of_manufacture).trigger('input');
        }
    }

    // Function to auto-fill form from extracted PDF data
    function fillFormFromPdfData(data) {
        // console.log('🚀 Filling form with PDF data:', data);
        // If raw_text is available, use it as fallback for missing fields
        if (data.raw_text) {
            // console.log('📄 Raw text available for fallback extraction');
            // Extract Policy Type from raw_text if not found or seems wrong
            if (!data.policy_type_id && (!data.policy_type || data.policy_type === 'Sub Type')) {
                const rawPolicyType = extractFromRawText(data.raw_text, [
                    'policy\\s*type[\\s:]*([^\\n\\r:]+?)(?:\\s*\\n|\\s*Sub\\s*Type|\\s*Segment|$)',
                    'policy\\s*type[\\s:]*([A-Za-z0-9\\s]{1,50})'
                ]);
                if (rawPolicyType && rawPolicyType.length > 0) {
                    data.policy_type = rawPolicyType;
                    // console.log('📄 Extracted Policy Type from raw_text:', rawPolicyType);
                }
            }

            // Extract Company from raw_text if not found
            if (!data.company_id && !data.company_name) {
                // Try Go Digit format: "Go Digit General Insurance Ltd"
                let rawCompany = null;
                if (data.raw_text.includes('Go Digit')) {
                    const goDigitMatch = data.raw_text.match(/go\s+digit\s+general\s+insurance\s+ltd\.?/i);
                    if (goDigitMatch) {
                        rawCompany = 'Go Digit General Insurance Ltd';
                    }
                }
                if (!rawCompany) {
                    rawCompany = extractFromRawText(data.raw_text, [
                        'company[\\s:]*([A-Za-z0-9\\s]+?)(?:\\s*Branch|$)',
                        'insurance\\s+company[\\s:]*([A-Za-z0-9\\s]+?)(?:\\s*Branch|$)',
                        'go\\s+digit[\\s\w]*ltd/i'
                    ]);
                }
                if (rawCompany) {
                    data.company_name = rawCompany.replace(/\s*Branch\s*$/i, '').trim();
                    // console.log('📄 Extracted Company from raw_text:', data.company_name);
                }
            }

            // Extract Branch from raw_text if not found
            if (!data.branch_id && !data.branch_name) {
                const rawBranch = extractFromRawText(data.raw_text, [
                    'branch[\\s:]*([A-Za-z0-9\\s]+?)(?:\\s*Policy|$)',
                    'branch[\\s:]*([A-Za-z0-9\\s]{2,50})'
                ]);
                if (rawBranch) {
                    data.branch_name = rawBranch;
                    // console.log('📄 Extracted Branch from raw_text:', rawBranch);
                }
            }

            // Extract Sub Type from raw_text if not found
            if (!data.sub_type_id && !data.sub_type_name) {
                const rawSubType = extractFromRawText(data.raw_text, [
                    'sub\\s*type[\\s:]*([A-Za-z0-9\\s]+?)(?:\\s*Segment|$)',
                    'sub\\s*type[\\s:]*([A-Za-z0-9\\s]{2,50})'
                ]);
                if (rawSubType) {
                    data.sub_type_name = rawSubType;
                    // console.log('📄 Extracted Sub Type from raw_text:', rawSubType);
                }
            }

            // Extract Segment from raw_text if not found
            if (!data.segment_id && !data.segment_name) {
                const rawSegment = extractFromRawText(data.raw_text, [
                    'segment[\\s:]*([A-Za-z0-9\\s]+?)(?:\\s*Company|$)',
                    'segment[\\s:]*([A-Za-z0-9\\s]{2,50})'
                ]);
                if (rawSegment) {
                    data.segment_name = rawSegment;
                    // console.log('📄 Extracted Segment from raw_text:', rawSegment);
                }
            }

            // Extract Policy Holder Name from raw_text if not found (Go Digit format: "Name: PRAVINKUMAR...")
            if ((!data.user_name && !data.holder_name) && data.raw_text) {
                const nameMatch = data.raw_text.match(/name[\s:]*([A-Z][A-Z\s]{5,50}?)(?:\s*Address|\s*Mobile|\s*Email|$)/i);
                if (nameMatch && nameMatch[1]) {
                    const extractedName = nameMatch[1].trim();
                    // Check if it looks like a name (contains uppercase letters and spaces)
                    if (extractedName.match(/^[A-Z][A-Z\s]{4,}$/)) {
                        data.user_name = extractedName;
                        data.holder_name = extractedName;
                        // console.log('📄 Extracted Policy Holder Name from raw_text:', extractedName);
                    }
                }
            }
        }

        // Extract Policy Number from raw_text if not found
        if ((!data.policy_number || String(data.policy_number).trim() === '') && data.raw_text) {
            // console.log('📄 Extracting Policy Number from raw_text...');
            // Try Go Digit format: "Policy No.: D238619471/25112025"
            let policyMatch = data.raw_text.match(/policy\s*no\.?[\s:]*([A-Z0-9\-\/\s]+?)(?:\s*Policy\s*Issue|\s*Period|\s*From|$)/i);
            if (!policyMatch) {
                // Try standard format
                policyMatch = data.raw_text.match(/policy\s*(?:number|no)[\s:]*([A-Z0-9\-\/\s]+?)(?:\s*Policy\s*Issue|\s*Period|\s*From|$)/i);
            }
            if (!policyMatch) {
                // Try to find policy number pattern in text (e.g., "D238619471" or "D238619471/25112025")
                policyMatch = data.raw_text.match(/([A-Z]\d{8,}(?:\/\d+)?)/);
            }
            if (policyMatch && policyMatch[1]) {
                data.policy_number = policyMatch[1].trim().replace(/\s+/g, '');
                // console.log('📄 Extracted Policy Number from raw_text:', data.policy_number);
            }
        }

        // Policy Number - Set default "Policy No." if empty or not exist
        if (data.policy_number && String(data.policy_number).trim() !== '') {
            $('#policy_number').val(data.policy_number).trigger('input');
            // console.log('📝 Policy Number set:', data.policy_number);
        } else {
            // Set default value "Policy No." if empty or not exist
            const currentValue = $('#policy_number').val();
            if (!currentValue || currentValue.trim() === '') {
                $('#policy_number').val('Policy No.').trigger('input');
                // console.log('📝 Policy Number not found, set default: Policy No.');
            }
        }

        // Policy Type - SET IMMEDIATELY AT UPLOAD TIME - try to match by ID first, then by text
        if (data.policy_type_id) {
            // console.log('Setting Policy Type by ID:', data.policy_type_id);
            absolutelyForceSelect('#policy_type_id', data.policy_type_id);

            // Get and set the name after a short delay
            setTimeout(function() {
                const $policyTypeSelect = $('#policy_type_id');
                const $policyTypeOption = $policyTypeSelect.find('option[value="' + data.policy_type_id + '"]');
                if ($policyTypeOption.length > 0) {
                    const selectedText = $policyTypeOption.text().trim();
                    $('#policy_type_name').val(selectedText);
                    // console.log('✅ Policy Type set:', selectedText);
                    // Show/hide vehicle fields based on policy type
                    if (selectedText.toLowerCase().includes('vehicle')) {
                        $('#vehicle_fields').removeClass('d-none');
                    }
                }
            }, 200);
        } else if (data.policy_type) {
            const policyTypeText = String(data.policy_type).trim();
            // console.log('🔍 Trying to match Policy Type by text:', policyTypeText);
            // Try exact match first
            let found = selectDropdownOption('#policy_type_id', policyTypeText, true);

            // If exact match fails, try partial match
            if (!found) {
                // console.log('⚠️ Exact match failed, trying partial match...');
                found = selectDropdownOption('#policy_type_id', policyTypeText, false);
            }

            // If still not found and raw_text is available, try extracting from raw_text
            if (!found && data.raw_text) {
                // console.log('📄 Trying to extract Policy Type from raw_text...');
                const rawTextMatch = data.raw_text.match(/policy\s*type[\s:]*([^\n\r:]+?)(?:\s*\n|\s*Sub\s*Type|\s*Segment|$)/i);
                if (rawTextMatch && rawTextMatch[1]) {
                    const extractedType = rawTextMatch[1].trim().replace(/[:;]\s*$/, '').trim();
                    if (extractedType && extractedType !== policyTypeText) {
                        // console.log('📄 Extracted from raw_text:', extractedType);
                        found = selectDropdownOption('#policy_type_id', extractedType, true);
                        if (!found) {
                            found = selectDropdownOption('#policy_type_id', extractedType, false);
                        }
                    }
                }
            }

            if (found) {
                setTimeout(function() {
                    const selectedText = $('#policy_type_id option:selected').text();
                    $('#policy_type_name').val(selectedText);
                    // console.log('✅ Policy Type matched:', selectedText);
                    // Show/hide vehicle fields based on policy type
                    if (selectedText.toLowerCase().includes('vehicle')) {
                        $('#vehicle_fields').removeClass('d-none');
                    }
                }, 200);
            } else {
                // console.log('❌ Policy Type not found for:', policyTypeText);
                console.log('Available options:', $('#policy_type_id').find('option').map(function() {
                    return {
                        value: $(this).val(),
                        text: $(this).text()
                    };
                }).get());
            }
        }

        // Company - SET IMMEDIATELY AT UPLOAD TIME - try to match by ID first, then by text
        if (data.company_id) {
            // console.log('Setting Company by ID:', data.company_id);
            absolutelyForceSelect('#company_id', data.company_id);
        } else if (data.company_name) {
            let companyName = String(data.company_name).replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
            companyName = companyName.replace(/\s*Branch\s*$/i, '').trim();

            // console.log('Trying to match Company by text:', companyName);
            selectDropdownOption('#company_id', companyName);
        }

        // Sub Type - Wait for policy_type to load options first
        if (data.sub_type_id || data.sub_type_name) {
            // Wait for policy type change to complete and populate sub_type options
            setTimeout(function() {
                if (data.sub_type_id) {
                    // console.log('Setting Sub Type by ID:', data.sub_type_id);
                    absolutelyForceSelect('#sub_type_id', data.sub_type_id);
                } else if (data.sub_type_name) {
                    const subTypeName = String(data.sub_type_name).trim();
                    // console.log('Trying to match Sub Type by text:', subTypeName);
                    selectDropdownOption('#sub_type_id', subTypeName);
                }
            }, 1000); // Wait 1 second for policy type to load sub types
        }

        // Segment - Wait for policy_type to load options first
        if (data.segment_id || data.segment_name) {
            // Wait for policy type change to complete and populate segment options
            setTimeout(function() {
                if (data.segment_id) {
                    // console.log('Setting Segment by ID:', data.segment_id);
                    absolutelyForceSelect('#segment_id', data.segment_id);
                } else if (data.segment_name) {
                    const segmentName = String(data.segment_name).trim();
                    // console.log('Trying to match Segment by text:', segmentName);
                    selectDropdownOption('#segment_id', segmentName);
                }
            }, 1000); // Wait 1 second for policy type to load segments
        }

        // Branch - Wait for company to load branches first
        if (data.branch_id || data.branch_name) {
            // Wait for company change to complete and populate branch options
            setTimeout(function() {
                if (data.branch_id) {
                    // console.log('Setting Branch by ID:', data.branch_id);
                    absolutelyForceSelect('#branch_id', data.branch_id);
                } else if (data.branch_name) {
                    const branchName = String(data.branch_name).trim();
                    // console.log('Trying to match Branch by text:', branchName);
                    selectDropdownOption('#branch_id', branchName);
                }
            }, 1000); // Wait 1 second for company to load branches
        }

        // Family Group - SET IMMEDIATELY AT UPLOAD TIME - by ID first, then by name
        if (data.family_group_id) {
            // console.log('🔵 Setting Family Group by ID:', data.family_group_id);
            absolutelyForceSelect('#family_group_id', data.family_group_id);

            // Trigger change event to ensure AJAX loads holders
            $('#family_group_id').trigger('change');

            // After Family Group is set, wait for holders to load, then set Policy Holder
            if (data.user_id || data.holder_id || data.user_name || data.holder_name) {
                // Try multiple times with increasing delays
                setTimeout(function() {
                    setPolicyHolder(data);
                }, 1000); // First attempt after 1 second

                setTimeout(function() {
                    setPolicyHolder(data);
                }, 2000); // Second attempt after 2 seconds

                setTimeout(function() {
                    setPolicyHolder(data);
                }, 3000); // Third attempt after 3 seconds
            }
        } else if (data.family_group_name) {
            const familyGroupName = String(data.family_group_name).trim();
            // console.log('🔵 Trying to match Family Group by text:', familyGroupName);
            const found = selectDropdownOption('#family_group_id', familyGroupName);

            if (found) {
                // Trigger change event to ensure AJAX loads holders
                $('#family_group_id').trigger('change');

                if (data.user_id || data.holder_id || data.user_name || data.holder_name) {
                    // Wait for family group to load holders
                    setTimeout(function() {
                        setPolicyHolder(data);
                    }, 1500);

                    setTimeout(function() {
                        setPolicyHolder(data);
                    }, 2500);
                }
            }
        } else {
            // No family group, but still try to set policy holder if we have the data
            if (data.user_id || data.holder_id || data.user_name || data.holder_name) {
                setTimeout(function() {
                    setPolicyHolder(data);
                }, 2000);
            }
        }

        // Helper function to set Policy Holder
        function setPolicyHolder(data) {
            const $userSelect = $('#user_id');

            // ── If the dropdown is empty (no family group selected), load all customers first ──
            function ensureHoldersLoaded(callback) {
                if ($userSelect.find('option').length > 1) {
                    callback();
                    return;
                }
                // Load all customers directly
                $.getJSON('<?= base_url('get-customers-json') ?>', function(customers) {
                    $userSelect.html('<option value="">-- Select Holder --</option>');
                    $.each(customers, function(i, c) {
                        $userSelect.append('<option value="' + c.id + '">' + c.name + (c.email ? ' (' + c.email + ')' : '') + '</option>');
                    });
                    if ($userSelect.data('select2')) $userSelect.trigger('change.select2');
                    callback();
                }).fail(function() {
                    callback(); // proceed anyway
                });
            }

            if (data.user_id || data.holder_id) {
                const userId = String(data.user_id || data.holder_id);
                ensureHoldersLoaded(function() {
                    absolutelyForceSelect('#user_id', userId);
                    setTimeout(function() {
                        if ($userSelect.val() !== userId) {
                            absolutelyForceSelect('#user_id', userId);
                        }
                    }, 300);
                });
            } else if (data.user_name || data.holder_name) {
                const name = String(data.user_name || data.holder_name).trim();
                ensureHoldersLoaded(function() {
                    selectDropdownOption('#user_id', name);
                    setTimeout(function() {
                        const currentValue = $userSelect.val();
                        if (currentValue) {
                            updateRegularSelectDisplay($userSelect, currentValue);
                        }
                    }, 300);
                });
            }
        }

        // Extract dates from raw_text if missing
        if (data.raw_text) {
            // Extract Start Date from raw_text if missing
            if (!data.start_date) {
                // Try Go Digit format: "From: 26-Nov-2025" or "Period of Policy... From: 26-Nov-2025"
                let dateMatch = data.raw_text.match(/period\s*of\s*policy.*?from[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i);
                if (!dateMatch) {
                    dateMatch = data.raw_text.match(/from[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i);
                }
                if (!dateMatch) {
                    dateMatch = data.raw_text.match(/from[:\s]+(\d{4}-\d{2}-\d{2})/i);
                }
                if (dateMatch && dateMatch[1]) {
                    // Normalize the date format
                    const dateStr = dateMatch[1].trim();
                    // If it's in format like "26-Nov-2025", convert to YYYY-MM-DD
                    if (dateStr.match(/^\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4}$/i)) {
                        const parts = dateStr.split(/[\/\-]/);
                        if (parts.length === 3) {
                            const monthNames = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                            const monthIndex = monthNames.findIndex(m => parts[1].toLowerCase().startsWith(m));
                            if (monthIndex !== -1) {
                                const month = String(monthIndex + 1).padStart(2, '0');
                                const day = parts[0].padStart(2, '0');
                                const year = parts[2];
                                data.start_date = year + '-' + month + '-' + day;
                                // console.log('📄 Extracted Start Date from raw_text:', data.start_date);
                            }
                        }
                    } else if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        data.start_date = dateStr;
                        // console.log('📄 Extracted Start Date from raw_text:', data.start_date);
                    }
                }
            }

            // Extract End Date from raw_text if missing
            if (!data.end_date) {
                // Try Go Digit format: "To: 25-Nov-2026"
                let dateMatch = data.raw_text.match(/period\s*of\s*policy.*?to[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i);
                if (!dateMatch) {
                    dateMatch = data.raw_text.match(/to[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i);
                }
                if (!dateMatch) {
                    dateMatch = data.raw_text.match(/to[:\s]+(\d{4}-\d{2}-\d{2})/i);
                }
                if (dateMatch && dateMatch[1]) {
                    // Normalize the date format
                    const dateStr = dateMatch[1].trim();
                    // If it's in format like "25-Nov-2026", convert to YYYY-MM-DD
                    if (dateStr.match(/^\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4}$/i)) {
                        const parts = dateStr.split(/[\/\-]/);
                        if (parts.length === 3) {
                            const monthNames = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                            const monthIndex = monthNames.findIndex(m => parts[1].toLowerCase().startsWith(m));
                            if (monthIndex !== -1) {
                                const month = String(monthIndex + 1).padStart(2, '0');
                                const day = parts[0].padStart(2, '0');
                                const year = parts[2];
                                data.end_date = year + '-' + month + '-' + day;
                                // console.log('📄 Extracted End Date from raw_text:', data.end_date);
                            }
                        }
                    } else if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        data.end_date = dateStr;
                        // console.log('📄 Extracted End Date from raw_text:', data.end_date);
                    }
                }
            }
        }

        // Dates
        if (data.start_date) {
            $('#start_date').val(data.start_date);
        }
        if (data.end_date) {
            $('#end_date').val(data.end_date);
        }
        if (data.payment_date) {
            $('#payment_date').val(data.payment_date);
        }

        // Premium and Amounts
        if (data.premium) {
            $('#premium').val(data.premium);
        }
        if (data.sum_assured) {
            $('#sum_assured').val(data.sum_assured);
        }
        if (data.tax_amount) {
            $('#tax_amount').val(data.tax_amount);
        }
        if (data.amount) {
            $('#amount').val(data.amount);
        }

        // Payment Mode
        if (data.payment_mode) {
            $('#payment_mode').val(data.payment_mode).trigger('change');
        }
        if (data.policy_mode) {
            $('#policy_mode').val(data.policy_mode).trigger('change');
        }

        // Fuel Type
        if (data.fuel_type) {
            $('#fuel_type').val(data.fuel_type).trigger('change');
        }

        // Nominee — reject values that look like PDF table headers/labels
        var badNomineePatterns = /relationship|policyholder|share|nominee\s*name|contact|percentage|%/i;
        if (data.nominee_name && !badNomineePatterns.test(data.nominee_name)) {
            $('#nominee_name').val(data.nominee_name);
        }
        if (data.nominee_contact) {
            $('#nominee_contact').val(data.nominee_contact);
        }

        // Check if Vehicle Registration Number exists - if yes, ensure Policy Type is set to Vehicle
        if (data.vehicle_reg_no && data.vehicle_reg_no.trim() !== '') {
            // console.log('🚗 Vehicle Registration Number found:', data.vehicle_reg_no);
            // Check if policy type is already set to Vehicle
            const currentPolicyType = $('#policy_type_name').val().toLowerCase();
            if (!currentPolicyType || !currentPolicyType.includes('vehicle')) {
                // console.log('🚗 Setting Policy Type to Vehicle based on Vehicle Registration Number');
                // Try to set policy type to Vehicle
                const vehicleTypeFound = selectDropdownOption('#policy_type_id', 'Vehicle', false);
                if (vehicleTypeFound) {
                    setTimeout(function() {
                        const selectedText = $('#policy_type_id option:selected').text();
                        $('#policy_type_name').val(selectedText);
                        // console.log('✅ Policy Type set to Vehicle:', selectedText);
                        $('#policy_type_id').trigger('change');
                    }, 200);
                }
            }
        }

        // Extract missing vehicle fields from raw_text if available
        if (data.raw_text && (!data.vehicle_reg_no || !data.vehicle_model || !data.engine_no || !data.chassis_no || !data.gvw || !data.ncb)) {
            // console.log('📄 Extracting missing vehicle fields from raw_text...');
            // Vehicle Registration Number - try multiple patterns
            if (!data.vehicle_reg_no) {
                // Try Go Digit format: "Vehicle Registration No.: GJ05RZ1544"
                let regMatch = data.raw_text.match(/vehicle\s*registration\s*no\.?[\s:]*([A-Z0-9\s\-]+?)(?:\s*Partner|\s*Policy|\s*Name|$)/i);
                if (!regMatch) {
                    // Try AKP Insurance format: "Registration Number: Vehicle test"
                    regMatch = data.raw_text.match(/registration\s*number[\s:]*([^\n\r]+?)(?:\s*Vehicle\s*Model|\s*Engine|\s*Chassis|$)/i);
                }
                if (!regMatch) {
                    // Try generic format
                    regMatch = data.raw_text.match(/([A-Z]{2}\d{1,2}[A-Z]{1,2}\d{1,4})/i);
                }
                if (regMatch && regMatch[1]) {
                    data.vehicle_reg_no = regMatch[1].trim().replace(/\s+/g, '').toUpperCase();
                    // console.log('📄 Extracted Vehicle Registration Number from raw_text:', data.vehicle_reg_no);
                    // Set policy type to Vehicle if not already set
                    const currentPolicyType = $('#policy_type_name').val().toLowerCase();
                    if (!currentPolicyType || !currentPolicyType.includes('vehicle')) {
                        const vehicleTypeFound = selectDropdownOption('#policy_type_id', 'Vehicle', false);
                        if (vehicleTypeFound) {
                            setTimeout(function() {
                                const selectedText = $('#policy_type_id option:selected').text();
                                $('#policy_type_name').val(selectedText);
                                $('#policy_type_id').trigger('change');
                            }, 200);
                        }
                    }
                }
            }

            // Vehicle Model
            if (!data.vehicle_model) {
                const modelMatch = data.raw_text.match(/vehicle\s*model[\s:]*([^\n\r]+?)(?:\s*Fuel|\s*Engine|\s*Year|$)/i);
                if (modelMatch && modelMatch[1]) {
                    data.vehicle_model = modelMatch[1].trim();
                    // console.log('📄 Extracted Vehicle Model from raw_text:', data.vehicle_model);
                }
            }

            // Engine Number
            if (!data.engine_no || data.engine_no === 'NUMBER') {
                const engineMatch = data.raw_text.match(/engine\s*(?:no|number)[\s:]*([^\n\r]+?)(?:\s*Chassis|\s*Registration|\s*Fuel|$)/i);
                if (engineMatch && engineMatch[1]) {
                    data.engine_no = engineMatch[1].trim();
                    // console.log('📄 Extracted Engine Number from raw_text:', data.engine_no);
                }
            }

            // Chassis Number
            if (!data.chassis_no) {
                const chassisMatch = data.raw_text.match(/chassis\s*(?:no|number)[\s:]*([^\n\r]+?)(?:\s*Registration|\s*Fuel|\s*Year|$)/i);
                if (chassisMatch && chassisMatch[1]) {
                    data.chassis_no = chassisMatch[1].trim();
                    // console.log('📄 Extracted Chassis Number from raw_text:', data.chassis_no);
                }
            }

            // Gross Vehicle Weight (GVW)
            if (!data.gvw) {
                const gvwMatch = data.raw_text.match(/gross\s*vehicle\s*weight[\s\(]*gvw[\s\)]*[\s:]*(\d+(?:[.,]\d+)?)/i) ||
                    data.raw_text.match(/gvw[\s:]*(\d+(?:[.,]\d+)?)/i);
                if (gvwMatch && gvwMatch[1]) {
                    data.gvw = gvwMatch[1].trim();
                    // console.log('📄 Extracted GVW from raw_text:', data.gvw);
                }
            }

            // No Claim Bonus (NCB)
            if (!data.ncb) {
                const ncbMatch = data.raw_text.match(/no\s*claim\s*bonus[\s\(]*ncb[\s\)]*[\s:]*(\d+(?:[.,]\d+)?)/i) ||
                    data.raw_text.match(/ncb[\s:]*(\d+(?:[.,]\d+)?)/i);
                if (ncbMatch && ncbMatch[1]) {
                    data.ncb = ncbMatch[1].trim();
                    // console.log('📄 Extracted NCB from raw_text:', data.ncb);
                }
            }
        }

        // Vehicle fields (if applicable) - Show vehicle fields section if vehicle data exists
        const hasVehicleData = data.vehicle_reg_no || data.vehicle_model || data.engine_no || data.chassis_no ||
            data.fuel_type || data.registration_date || data.cc || data.gvw || data.ncb || data.year_of_manufacture;

        if (hasVehicleData) {
            // console.log('🚗 Vehicle data detected, showing vehicle fields section');
            // Make sure policy type is set to Vehicle if not already set
            const currentPolicyType = $('#policy_type_name').val().toLowerCase();
            if (!currentPolicyType || !currentPolicyType.includes('vehicle')) {
                // Try to set policy type to Vehicle
                const vehicleTypeFound = selectDropdownOption('#policy_type_id', 'Vehicle', false);
                if (vehicleTypeFound) {
                    setTimeout(function() {
                        const selectedText = $('#policy_type_id option:selected').text();
                        $('#policy_type_name').val(selectedText);
                        // console.log('✅ Policy Type set to Vehicle:', selectedText);
                        // Trigger change event to show vehicle fields
                        $('#policy_type_id').trigger('change');

                        // Show vehicle fields section
                        $('#vehicle_fields').removeClass('d-none');

                        // Fill vehicle fields after a short delay to ensure fields are visible
                        fillVehicleFields(data);
                    }, 300);
                } else {
                    // If vehicle type not found, just show the fields anyway
                    $('#vehicle_fields').removeClass('d-none');
                    setTimeout(function() {
                        fillVehicleFields(data);
                    }, 500);
                }
            } else {
                // Policy type already set to Vehicle, just show and fill fields
                $('#vehicle_fields').removeClass('d-none');
                setTimeout(function() {
                    fillVehicleFields(data);
                }, 500);
            }
        }

        // Remarks
        if (data.remarks) {
            $('#remarks').val(data.remarks);
        }

        // Final refresh of all regular selects after form filling completes
        setTimeout(function() {
            // Refresh Policy Type, Sub Type, Segment, and User displays
            const selectsToRefresh = ['#policy_type_id', '#sub_type_id', '#segment_id', '#user_id', '#company_id', '#branch_id'];
            selectsToRefresh.forEach(function(selector) {
                const $select = $(selector);
                if ($select.length && !$select.data('select2')) {
                    const currentValue = $select.val();
                    if (currentValue) {
                        updateRegularSelectDisplay($select, currentValue);
                        // console.log('🔄 Refreshed display for:', selector, 'Value:', currentValue);
                    }
                }
            });

            // Final check for Policy Holder - try one more time if still not set
            if (data.user_id || data.holder_id) {
                const userId = data.user_id || data.holder_id;
                const $userSelect = $('#user_id');
                const currentValue = $userSelect.val();

                if (currentValue !== userId) {
                    // console.log('🔄 Final attempt to set Policy Holder:', userId);
                    const $option = $userSelect.find('option[value="' + userId + '"]');
                    if ($option.length > 0) {
                        absolutelyForceSelect('#user_id', userId);
                        updateRegularSelectDisplay($userSelect, userId);
                    }
                }
            }
        }, 3000); // Wait 3 seconds for all AJAX calls to complete (increased from 2 seconds)
    }

    // Toastr Configuration
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    }
</script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- ===================== ADD MODALS ===================== -->

<!-- Modal: Add Family Group -->
<div class="modal fade" id="modalFamilyGroup" tabindex="-1" aria-labelledby="modalFamilyGroupLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#3d4db7;">
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
                                <input type="date" class="form-control" name="member_dob[]" placeholder="DOB">
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
            <div class="modal-header" style="background:#3d4db7;">
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
                            <input type="text" class="form-control" name="phone" placeholder="Phone number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">WhatsApp</label>
                            <input type="text" class="form-control" name="whatsapp" placeholder="WhatsApp number">
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
            <div class="modal-header" style="background:#3d4db7;">
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
            <div class="modal-header" style="background:#3d4db7;">
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
            <div class="modal-header" style="background:#3d4db7;">
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
            <div class="modal-header" style="background:#3d4db7;">
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
            <div class="modal-header" style="background:#3d4db7;">
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
        else { Swal.fire('Error', msg, 'error'); }
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
        populateSelect('#fg_holder_id', baseUrl + 'get-customers-json',
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
            '<input type="date" class="form-control" name="member_dob[]">' +
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
            '<input type="date" class="form-control" name="member_dob[]">' +
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

});
</script>
