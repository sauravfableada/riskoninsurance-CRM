<?= $this->include('layout/header') ?>

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
</style>
<div class="container-fluid py-4">
    <div class="row mt-4 justify-content-center mb-4">
        <div class="col-lg-12 mb-lg-0">
            <div class="card w-90 m-auto">
                <div class="card-header pb-0 p-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-lg-between">
                        <div
                            class="col-12 col-lg-6 col-md-6 d-flex justify-content-center justify-content-lg-start justify-content-md-start mb-3 mb-lg-0 mb-sm-3">

                            <h6 class="mb-2">POLICY DETAILS</h6>

                        </div>



                        <div class="col-12 col-lg-6 col-md-6 d-flex justify-content-center justify-content-lg-end justify-content-md-end align-items-center"
                            id="issue">

                            <div class="btn-group">

                                <a href="<?= base_url('download-policy-invoice/' . $policy['id']); ?>"
                                target="_blank"
                                class="btn btn-light text-dark"
                                style="border-radius:8px;margin-top:-9px;margin-right:10px; text-decoration: none;">

                                    <i class="fa fa-file-pdf-o" style="font-size:13px;"></i>

                                </a>

                            </div>


                            <?php
                            $session = session();
                            if ($session->get("user_role") == 1) {
                                $db = \Config\Database::connect();
                                $builders = $db->table('users_permission');
                                $builders->select('*');
                                $builders->where(['user_id' => $session->get('id'), 'module_id' => 15]);
                                $permission = $builders->get()->getRowArray();
                                if ($permission && $permission['update'] == 1) {
                            ?>

                                    <div class="btn-group ">

                                        <a href="<?= base_url('edit-policy/' . $policy['id']); ?>"
                                            class="btn btn-light text-dark"
                                            style="border-radius:8px;margin-top:-9px;margin-right:10px; text-decoration: none;">

                                            <i class="fa fa-edit" style="font-size: 13px;"></i> EDIT

                                        </a>

                                    </div>

                                <?php }
                            } else if ($session->get("user_role") == 3) {

                                ?>



                                <div class="btn-group ">

                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>"
                                        class="btn btn-light text-dark"
                                        style="border-radius:8px;margin-top:-9px;margin-right:10px; text-decoration: none;">

                                        <i class="fa fa-edit" style="font-size: 13px;"></i> EDIT

                                    </a>

                                </div>

                            <?php } ?>

                            <div class="btn-group ">

                                <a href="<?= base_url(); ?>all-policies" class="btn btn-light text-dark"
                                    style="border-radius:8px; margin-top:-9px; text-decoration: none;">

                                    <i class="fa-solid fa-chevron-left" style="font-size: 13px;"></i> BACK

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
                                    "Staff / Agent" => ['icon' => 'fa-solid fa-user-tie', 'value' => $readValue($assignedStaff ?? null, 'name')],
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


        <?= $this->include('layout/footer') ?>
