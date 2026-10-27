@extends('layouts.app')
@section('content')


<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-10 mb-lg-0 mb-4">
            <div class="card ">
                <div class="card-header pb-2 pt-2 p-3">
                    <div class="row">
                        <div class="col-lg-6">
                            <h6 class="mt-2">RENEW INSURANCE POLICY</h6>
                        </div>
                        <div class="col-lg-6 mb-lg-0 text-end">
                            <a href="<?= base_url('all-policies'); ?>" class="btn btn-dark-blue"><i class="fa-solid fa-arrow-left me-1"></i>BACK</a>
                        </div>
                    </div>
                </div>

                
                <div class="table-responsive px-0 px-lg-5 px-md-5">
                    <form class="m-3" id="edit_policy_form">
                        <input type="hidden" name="base_url" id="base_url" value="<?= base_url(); ?>">
                        <input type="hidden" name="id" id="policy_id" value="<?= $policy['id']; ?>">
                        <?php
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
                                        <input type="text" class="form-control" name="policy_number" id="policy_number" value="<?= esc($policy['policy_number_no'] ?? $policy['policy_number'] ?? '') ?>" disabled>
                                        <div class="invalid-feedback" id="policy_number_err"></div>
                                    </div>
                                    <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="staff_id">Staff Name</label>
                                    <select class="form-control" name="staff_id" id="staff_id" disabled>
                                        <option value="">-- Select Staff --</option>
                                        <?php foreach ($staffs as $staff): ?>
                                            <option value="<?= $staff['id'] ?>" <?= ($policy['user_id'] == $staff['id']) ? 'selected' : ''; ?>>
                                                <?= $staff['name'] ?><?= !empty($staff['email']) ? ' (' . $staff['email'] . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="staff_id_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="family_group_id">Family Group</label>
                                    <select class="form-control" name="family_group_id" id="family_group_id"disabled>
                                        <option value="">-- Select Family Group --</option>
                                        <?php foreach ($familyGroups as $group): ?>
                                            <option value="<?= $group['id']; ?>" data-holder-id="<?= $group['holder_id'] ?? '' ?>" <?= ($policy['family_group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($group['group_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback" id="family_group_id_err"></div>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="user_id">Policy Holder Name</label>
                                    <select class="form-control" name="user_id" id="user_id" disabled>
                                        <option value="">-- Select User --</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>" <?= ($policy['holder_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                <?= $user['name'] ?><?= !empty($user['email']) ? ' (' . $user['email'] . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="user_id_err" class="text-danger"></span>
                                </div>

                                <!-- Policy Type -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="policy_type_id">Policy Type</label>
                                    <select class="form-control" name="policy_type_id" id="policy_type_id" disabled>
                                        <option value="">-- Select Policy Type --</option>
                                        <?php foreach ($policyTypes as $pt): ?>
                                            <option value="<?= $pt['id'] ?>" <?= ($policy['policy_type_id'] == $pt['id']) ? 'selected' : ''; ?>>
                                                <?= $pt['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="policy_type_err" class="text-danger"></span>
                                </div>
                                <!-- Policy Sub Type -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="sub_type_id">Policy Sub Type</label>
                                    <select class="form-control" name="sub_type_id" id="sub_type_id" disabled>
                                        <option value="">-- Select Sub Type --</option>
                                        <?php foreach ($subTypes as $st): ?>
                                            <option value="<?= $st['id'] ?>" <?= (($policy['policy_sub_type_id'] ?? $policy['sub_type_id'] ?? '') == $st['id']) ? 'selected' : ''; ?>>
                                                <?= $st['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="sub_type_err" class="text-danger"></span>
                                </div>

                                <!-- Segment -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="segment_id">Segment</label>
                                    <select class="form-control" name="segment_id" id="segment_id" disabled>
                                        <option value="">-- Select Segment --</option>
                                        <?php foreach ($segments as $seg): ?>
                                            <option value="<?= $seg['id'] ?>" <?= ($policy['segment_id'] == $seg['id']) ? 'selected' : ''; ?>>
                                                <?= $seg['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="segment_err" class="text-danger"></span>
                                </div>
                                 <!-- Company -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="company_id">Insurance Company</label>
                                    <select class="form-control" name="company_id" id="company_id" disabled>
                                        <option value="">-- Select Company --</option>
                                        <?php foreach ($companies as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= ($policy['company_id'] == $c['id']) ? 'selected' : ''; ?>>
                                                <?= $c['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="company_err" class="text-danger"></span>
                                </div>

                                <!-- Branch -->
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="branch_id">Branch</label>
                                    <select class="form-control" name="branch_id" id="branch_id" disabled>
                                        <option value="">-- Select Branch --</option>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?= $branch['id'] ?>" <?= ($policy['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                                <?= $branch['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="branch_err" class="text-danger"></span>
                                </div>
                            </div>
                            </div>

                            <div class="row">
                                
                            </div>

                            <div class="row">
                               
                        </div>


                        <!-- Step 2: Policy Dates & Nominee -->
                        <div class="step step-2 d-none">

                            <div id="vehicle_fields" class="insurance-fields <?= $isVehiclePolicy ? '' : 'd-none'; ?>">
                                <div class="row">
                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_reg_no" class="form-label">Vehicle Registration Number</label>
                                        <input type="text" class="form-control" name="vehicle_reg_no" id="vehicle_reg_no"
                                            value="<?= old('vehicle_reg_no', $vehicle['vehicle_reg_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="vehicle_reg_no_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="vehicle_model" class="form-label">Vehicle Model</label>
                                        <input type="text" class="form-control" name="vehicle_model" id="vehicle_model"
                                            value="<?= old('vehicle_model', $vehicle['vehicle_model'] ?? '') ?>">
                                        <div class="invalid-feedback" id="vehicle_model_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="engine_no" class="form-label">Engine Number</label>
                                        <input type="text" class="form-control" name="engine_no" id="engine_no"
                                            value="<?= old('engine_no', $vehicle['engine_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="engine_no_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="chassis_no" class="form-label">Chassis Number</label>
                                        <input type="text" class="form-control" name="chassis_no" id="chassis_no"
                                            value="<?= old('chassis_no', $vehicle['chassis_no'] ?? '') ?>">
                                        <div class="invalid-feedback" id="chassis_no_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="fuel_type" class="form-label">Fuel Type</label>
                                        <select class="form-control" name="fuel_type" id="fuel_type">
                                            <option value="">-- Select Fuel Type --</option>
                                            <option value="Petrol" <?= old('fuel_type', $vehicle['fuel_type'] ?? '') == 'Petrol' ? 'selected' : '' ?>>Petrol</option>
                                            <option value="Diesel" <?= old('fuel_type', $vehicle['fuel_type'] ?? '') == 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                                            <option value="Electric" <?= old('fuel_type', $vehicle['fuel_type'] ?? '') == 'Electric' ? 'selected' : '' ?>>Electric</option>
                                        </select>
                                        <div class="invalid-feedback" id="fuel_type_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="registration_date" class="form-label">Registration Date</label>
                                        <input type="date" class="form-control" name="registration_date" id="registration_date"
                                            value="<?= old('registration_date', $vehicle['registration_date'] ?? '') ?>">
                                        <div class="invalid-feedback" id="registration_date_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="cc" class="form-label">Engine Capacity (CC)</label>
                                        <input type="number" class="form-control" name="cc" id="cc"
                                            value="<?= old('cc', $vehicle['cc'] ?? '') ?>">
                                        <div class="invalid-feedback" id="cc_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="gvw" class="form-label">Gross Vehicle Weight (GVW)</label>
                                        <input type="number" class="form-control" name="gvw" id="gvw"
                                            value="<?= old('gvw', $vehicle['gvw'] ?? '') ?>">
                                        <div class="invalid-feedback" id="gvw_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="ncb" class="form-label">No Claim Bonus</label>
                                        <input type="text" class="form-control" name="ncb" id="ncb"
                                            value="<?= old('ncb', $vehicle['ncb'] ?? '') ?>">
                                        <div class="invalid-feedback" id="ncb_err"></div>
                                    </div>

                                    <div class="mb-3 col-lg-6">
                                        <label for="year_of_manufacture" class="form-label">Year of Manufacture</label>
                                        <input type="number" class="form-control" name="year_of_manufacture" id="year_of_manufacture"
                                            value="<?= old('year_of_manufacture', $vehicle['year_of_manufacture'] ?? '') ?>">
                                        <div class="invalid-feedback" id="year_of_manufacture_err"></div>
                                    </div>
                                </div>

                            </div>



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
                                <div class="mb-3 col-lg-3 non-vehicle-field <?= $isVehiclePolicy ? 'd-none' : '' ?>">
                                    <label class="form-label" for="sum_assured">Sum Assured</label>
                                    <input type="number" step="0.01" class="form-control" name="sum_assured" id="sum_assured" value="<?= esc($policy['sum_assured'] ?? ''); ?>">
                                    <span id="sum_assured_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3 vehicle-only-field <?= $isVehiclePolicy ? '' : 'd-none' ?>">
                                    <label class="form-label" for="vehicle_idv">Vehicle IDV</label>
                                    <input type="number" step="0.01" class="form-control" name="vehicle_idv" id="vehicle_idv" value="<?= esc($policy['vehicle_idv'] ?? ''); ?>">
                                    <span id="vehicle_idv_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-3">
                                    <label class="form-label" for="premium">Premium Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="premium" id="premium" value="<?= esc($policy['premium_amount'] ?? ''); ?>">
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

                            <div class="row vehicle-only-field <?= $isVehiclePolicy ? '' : 'd-none' ?>">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="cng_lpg_unit">CNG/LPG Unit</label>
                                    <input type="text" class="form-control" name="cng_lpg_unit" id="cng_lpg_unit" value="<?= esc($policy['cng_lpg_unit'] ?? $policy['CNG_LPG'] ?? ''); ?>" placeholder="Enter CNG/LPG unit details">
                                    <span id="cng_lpg_unit_err" class="text-danger"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="payment_mode">Payment Type</label>
                                    <select class="form-control" name="payment_mode" id="payment_mode">
                                        <?php $pmodes = ['Cash', 'Online', 'Bank', 'Card']; ?>
                                        <?php foreach ($pmodes as $pm): ?>
                                            <option value="<?= $pm ?>" <?= (($policy['payment_type'] ?? '') == $pm) ? 'selected' : ''; ?>><?= $pm ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span id="payment_mode_err" class="text-danger"></span>
                                </div>
                                <div class="mb-3 col-lg-6">
                                    <label class="form-label" for="amount">Payment Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="<?= esc($policy['payment_amount'] ?? ''); ?>">
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
                                <button type="button" class="btn btn-secondary" id="prevBtn"><i class="fa-solid fa-angle-left me-1"></i>Previous</button>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-dark-blue" id="nextBtn">Next <i class="fa-solid fa-angle-right ms-1"></i></button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $('#policy_type_id').on('change', function() {
            var policyTypeId = $(this).val();

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
                    },
                    error: function(xhr, status, error) {
                        $subTypeSelect.html('<option value="">-- Select Sub Type --</option>');
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
                    },
                    error: function(xhr, status, error) {
                        $segmentSelect.html('<option value="">-- Select Segment --</option>');
                        console.error('Error fetching segments:', error);
                    }
                });

            } else {
                // Reset dropdowns if no policy type selected
                $subTypeSelect.html('<option value="">-- Select Sub Type --</option>');
                $segmentSelect.html('<option value="">-- Select Segment --</option>');
            }
        });
        $('#company_id').on('change', function() {
            var companyId = $(this).val();
            var baseUrl = $('#base_url').val();
            var $branchSelect = $('#branch_id');

            // Show loading while fetching branches
            $branchSelect.html('<option value="">Loading...</option>');

            if (companyId) {
                var branchUrl = baseUrl.replace(/\/+$/, '') + '/get-branches/' + companyId;
                $.ajax({
                    url: branchUrl,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.length > 0) {
                            $branchSelect.html('<option value="">-- Select Branch --</option>');
                            $.each(data, function(index, branch) {
                                $branchSelect.append('<option value="' + branch.id + '">' + branch.name + '</option>');
                            });
                        } else {
                            $branchSelect.html('<option value="">No branches available</option>');
                        }
                    },
                    error: function() {
                        $branchSelect.html('<option value="">-- Select Branch --</option>');
                        window.crmAlert('Could not fetch branches');
                    }
                });
            } else {
                $branchSelect.html('<option value="">-- Select Branch --</option>');
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
            $('#nextBtn').text(n === $('.step').length - 1 ? 'Renew' : 'Next');
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

        // Live error removal
        $('#edit_policy_form').on('change input', 'select, input, textarea', function() {
            var fieldId = $(this).attr('id');
            $('#' + fieldId + '_err').text('');
            $(this).removeClass('is-invalid');
        });

        function validateStep(step) {
            var isValid = true;
            $('.text-danger').text('');
            $('.is-invalid').removeClass('is-invalid');

            // ✅ On renew, skip policy holder / type / company selection (already fixed)
            if (step === 1) {
                if (!$('#start_date').val()) {
                    $('#start_date').addClass('is-invalid');
                    $('#start_date_err').text('Please select start date.');
                    isValid = false;
                }
                if (!$('#end_date').val()) {
                    $('#end_date').addClass('is-invalid');
                    $('#end_date_err').text('Please select end date.');
                    isValid = false;
                }
                if (!$('#nominee_name').val()) {
                    $('#nominee_name').addClass('is-invalid');
                    $('#nominee_name_err').text('Please enter nominee name.');
                    isValid = false;
                }
            }

            if (step === 1 && !$('#vehicle_fields').hasClass('d-none')) {
                var vmap = { 'vehicle_reg_no':'Please enter vehicle registration number.','vehicle_model':'Please enter vehicle model.','engine_no':'Please enter engine number.','chassis_no':'Please enter chassis number.','fuel_type':'Please select fuel type.','ncb':'Please enter no claim bonus.','year_of_manufacture':'Please enter year of manufacture.','registration_date':'Please enter registration date.' };
                $.each(vmap, function(f, m) {
                    if (!$('#'+f).val()) { $('#'+f).addClass('is-invalid'); $('#'+f+'_err').text(m); isValid = false; }
                    else { $('#'+f).removeClass('is-invalid'); $('#'+f+'_err').text(''); }
                });
            }

            if (step === 2) {
                if (!$('.non-vehicle-field').hasClass('d-none') && !$('#sum_assured').val()) {
                    $('#sum_assured').addClass('is-invalid');
                    $('#sum_assured_err').text('Please enter sum assured.');
                    isValid = false;
                }
                if (!$('.vehicle-only-field').hasClass('d-none') && !$('#vehicle_idv').val()) {
                    $('#vehicle_idv').addClass('is-invalid');
                    $('#vehicle_idv_err').text('Please enter vehicle IDV.');
                    isValid = false;
                }
                if (!$('#premium').val()) {
                    $('#premium').addClass('is-invalid');
                    $('#premium_err').text('Please enter premium amount.');
                    isValid = false;
                }
                if (!$('#tax_amount').val()) {
                    $('#tax_amount').addClass('is-invalid');
                    $('#tax_amount_err').text('Please enter tax amount.');
                    isValid = false;
                }
                if (!$('#payment_date').val()) {
                    $('#payment_date').addClass('is-invalid');
                    $('#payment_date_err').text('Please select payment date.');
                    isValid = false;
                }
                if (!$('#payment_mode').val()) {
                    $('#payment_mode').addClass('is-invalid');
                    $('#payment_mode_err').text('Please select payment mode.');
                    isValid = false;
                }
                if (!$('#amount').val()) {
                    $('#amount').addClass('is-invalid');
                    $('#amount_err').text('Please enter payment amount.');
                    isValid = false;
                }
            }

            return isValid;
        }

        function submitForm() {
            var $form = $('#edit_policy_form');

            // Temporarily enable disabled fields so they are included
            $form.find(':disabled').prop('disabled', false);

            var formData = new FormData($form[0]);

            // Re-disable again if you want to keep UI as is
            $form.find('[data-keep-disabled="true"]').prop('disabled', true);

            var baseUrl = $('#base_url').val();
            var policyId = $('#policy_id').val();

            $.ajax({
                url: baseUrl + 'renew-policy/' + policyId,
                type: 'POST',
                data: formData,
                processData: false, // ✅ Required for FormData
                contentType: false, // ✅ Required for FormData
                dataType: 'json',
                beforeSend: function() {
                    $('#nextBtn').prop('disabled', true).text('Renewing...');
                },
                success: function(response) {
                    if (response.status === 'success' || response.status === true) {
                        Swal.fire({
                            title: 'Renewed!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = baseUrl + 'all-policies';
                        });
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(key, val) {
                                $('#' + key + '_err').text(val);
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
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

            // Show vehicle fields if selected
            if (selectedText.includes('vehicle')) {
                $('#vehicle_fields').removeClass('d-none');
            }
        });
    });
</script>
<script>
    var policyTypeSelect = document.getElementById('policy_type_id');
    var policyTypeName = document.getElementById('policy_type_name');
    if (policyTypeSelect && policyTypeName) {
        policyTypeSelect.addEventListener('change', function() {
            let selectedText = this.options[this.selectedIndex].text;
            policyTypeName.value = selectedText !== "-- Select Policy Type --" ? selectedText : "";
        });
    }
</script>
<script>
    $(document).ready(function() {
        function toggleInsuranceFields() {
            var selectedText = $("#policy_type_id option:selected").text().toLowerCase();

            // Hide all insurance-specific fields first
            $('.insurance-fields').addClass('d-none');

            // Show vehicle fields if selected
            if (selectedText.includes('vehicle')) {
                $('#vehicle_fields').removeClass('d-none');
                $('.vehicle-only-field').removeClass('d-none');
                $('.non-vehicle-field').addClass('d-none');
            } else {
                $('.vehicle-only-field').addClass('d-none');
                $('.non-vehicle-field').removeClass('d-none');
            }

            // Later you can add conditions for Life / Health insurance
            // if (selectedText.includes('life')) { $('#life_fields').removeClass('d-none'); }
            // if (selectedText.includes('health')) { $('#health_fields').removeClass('d-none'); }
        }

        // Run when dropdown changes
        $('#policy_type_id').on('change', toggleInsuranceFields);

        // Run once on page load (important for edit form)
        toggleInsuranceFields();
    });
</script>
@endsection
