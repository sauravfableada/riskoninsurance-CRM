<?= $this->include('layout/header') ?>
<style>
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
</style>



<div class="container-fluid py-4">
    <div class="row mt-4">
        <div class="col-lg-10 mb-lg-0">
        </div>
    </div>
    <div class="row mt-4 mb-4">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card ">
                <div class="card-header pb-0 pt-3 p-4">
                    <div class="row">
                        <div
                            class="col-12 col-lg-6 col-md-6 d-flex justify-content-center justify-content-lg-start justify-content-md-start mb-1 mb-lg-0">
                            <h6 class="mb-2">INSURANCE POLICY</h6>
                        </div>

                        <?php
                        $session = session();
                        if ($session->get("user_role") == 1) {
                            $db = \Config\Database::connect();
                            $builders = $db->table('users_permission');
                            $builders->select('*');
                            $builders->where(['user_id' => session()->get('id'), 'module_id' => 17]);
                            $permission = $builders->get()->getRowArray();

                            if ($permission['module_id'] == 17 && $permission['create'] == 1) {
                        ?>
                                <div class="col-12 col-lg-6 col-md-6 d-flex justify-content-center justify-content-lg-end justify-content-md-end align-items-center"
                                    id="issue">

                                    <!-- Add Policy Type -->
                                    <div class="btn-group ">
                                        <a class="btn btn-light px-2 px-md-3" aria-expanded="false" style="margin-top:-9px;margin-left:10px;"
                                            href="<?php echo base_url(); ?>add-policy">
                                            <i class="fa-solid fa-plus"></i> ADD POLICY
                                        </a>
                                    </div>
                                </div>
                            <?php
                            }
                        } else { ?>
                            <div class="col-12 col-lg-6 col-md-6 d-flex justify-content-center justify-content-lg-end justify-content-md-end align-items-center"
                                id="issue">

                                <!-- Add Policy Type -->
                                <div class="btn-group ">
                                    <a class="btn btn-light px-2 px-md-3" aria-expanded="false" style="margin-top:-9px;margin-left:10px;"
                                        href="<?php echo base_url(); ?>add-policy">
                                        <i class="fa-solid fa-plus"></i> ADD POLICY
                                    </a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
                <?php
                $session = session();
                $userRole = $session->get('user_role');

                if ($userRole == 3): ?>
                    <div class="p-4 overflow-scroll">

                        <!-- Main Tabs -->
                        <ul class="nav nav-tabs mb-3" id="policyTabs" role="tablist">
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
                                <ul class="nav nav-pills mb-3 custom-pills" id="myPoliciesInnerTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active"
                                            id="currentMyPolicies-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#currentMyPolicies"
                                            type="button" role="tab"
                                            aria-controls="currentMyPolicies"
                                            aria-selected="true"><i class="fa fa-file-text-o me-2"></i>
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
                                <div class="row mb-3">
                                    <div class="col-lg-3">
                                        <label for="filter_holder_me" class="form-label">Filter by Holder</label>
                                        <select id="filter_holder_me" class="form-control">
                                            <option value="">-- All Holders --</option>
                                            <?php if (!empty($holders)): ?>
                                                <?php foreach ($holders as $holder): ?>
                                                    <option value="<?= esc($holder['name']) ?>"><?= esc($holder['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="filter_policy_type_me" class="form-label">Filter by Policy Type</label>
                                        <select id="filter_policy_type_me" class="form-control">
                                            <option value="">-- All Policy Types --</option>
                                            <?php if (!empty($policyTypes)): ?>
                                                <?php foreach ($policyTypes as $type): ?>
                                                    <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="filter_company_me" class="form-label">Filter by Company</label>
                                        <select id="filter_company_me" class="form-control">
                                            <option value="">-- All Companies --</option>
                                            <?php if (!empty($companies)): ?>
                                                <?php foreach ($companies as $company): ?>
                                                    <option value="<?= esc($company['name']) ?>"><?= esc($company['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <label for="filter_start_date" class="form-label">Start Date</label>
                                                <input type="date" id="filter_start_date" class="form-control">
                                            </div>
                                            <div class="col-lg-6">
                                                <label for="filter_end_date" class="form-label">End Date</label>
                                                <input type="date" id="filter_end_date" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content overflow-scroll" id="myPoliciesInnerContent">
                                    <!-- Current My Policies -->
                                    <div class="tab-pane fade show active" id="currentMyPolicies" role="tabpanel"
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
                                                        <tr data-id="<?= $policy['id']; ?>">
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

                                                            <td class="font_size mobile-hide"><?= date("d-m-Y", strtotime($policy['start_date'])); ?></td>
                                                            <td class="font_size mobile-hide"><?= date("d-m-Y", strtotime($policy['end_date'])); ?></td>
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

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
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
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>

                                                                </div>


                                                                <button class="mobile-only mobile-expand-btn clr-btn" onclick="toggleMobileDetails(<?= $policy['id']; ?>)">
                                                                    <span class="expand-icon">+</span>
                                                                </button>

                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                        <tr class="mobile-details mobile-only" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>">
                                                            <td colspan="8" class="mobile-details-row">
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Policy Type:</span>
                                                                    <span class="detail-value"><?= esc($policy['policy_type_name']); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Company:</span>
                                                                    <span class="detail-value"><?= esc($policy['company_name']); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Start Date:</span>
                                                                    <span class="detail-value"><?= date("d-m-Y", strtotime($policy['start_date'])); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">End Date:</span>
                                                                    <span class="detail-value"><?= date("d-m-Y", strtotime($policy['end_date'])); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Premium:</span>
                                                                    <span class="detail-value"><?= esc($policy['premium']); ?></span>
                                                                </div>
                                                                <div class="action-buttons">
                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-sm btn-secondary view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-dark-blue" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-sm btn-success" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
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
                                                        <tr data-id="<?= $policy['id']; ?>">
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
                                                            <td class="font_size mobile-hide"><?= date("d-m-Y", strtotime($policy['start_date'])); ?></td>
                                                            <td class="font_size mobile-hide"><?= date("d-m-Y", strtotime($policy['end_date'])); ?></td>
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

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
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
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
                                                                </div>

                                                                <button class="mobile-only mobile-expand-btn clr-btn" onclick="toggleMobileDetails(<?= $policy['id']; ?>)">
                                                                    <span class="expand-icon">+</span>
                                                                </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                        <tr class="mobile-details mobile-only" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>">
                                                            <td colspan="8" class="mobile-details-row">
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Policy Type:</span>
                                                                    <span class="detail-value"><?= esc($policy['policy_type_name']); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Company:</span>
                                                                    <span class="detail-value"><?= esc($policy['company_name']); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Start Date:</span>
                                                                    <span class="detail-value"><?= date("d-m-Y", strtotime($policy['start_date'])); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">End Date:</span>
                                                                    <span class="detail-value"><?= date("d-m-Y", strtotime($policy['end_date'])); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Premium:</span>
                                                                    <span class="detail-value"><?= esc($policy['premium']); ?></span>
                                                                </div>
                                                                <div class="action-buttons">
                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-sm btn-secondary view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>


                                                                    <button type="button" class="btn btn-sm btn-dark-blue" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-sm btn-success" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>
                                                                    <!-- <a href="<? //= base_url('download-policy-invoice/' . $policy['id']); 
                                                                                    ?>" target="_blank" class="btn btn-sm btn-warning" title="Download Invoice PDF">
                                                                        <i class="fa fa-file-pdf-o"></i> Invoice
                                                                    </a> -->
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
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
                                <ul class="nav nav-pills mb-3 custom-pills" id="staffPoliciesInnerTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="currentStaffPolicies-tab" data-bs-toggle="tab"
                                            data-bs-target="#currentStaffPolicies" type="button" role="tab"
                                            aria-controls="currentStaffPolicies" aria-selected="true">
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
                                <div class="row mb-3">
                                    <div class="col-lg-3">
                                        <label for="filter_holder_staff" class="form-label">Filter by Holder</label>
                                        <select id="filter_holder_staff" class="form-control">
                                            <option value="">-- All Holders --</option>
                                            <?php if (!empty($holders)): ?>
                                                <?php foreach ($holders as $holder): ?>
                                                    <option value="<?= esc($holder['name']) ?>"><?= esc($holder['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="filter_staff" class="form-label">Filter by Staff</label>
                                        <select id="filter_staff" class="form-control">
                                            <option value="">-- All Staff --</option>
                                            <?php if (!empty($staffList)): ?>
                                                <?php foreach ($staffList as $staff): ?>
                                                    <option value="<?= esc($staff['name']) ?>"><?= esc($staff['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="filter_policy_type_staff" class="form-label">Filter by Policy Type</label>
                                        <select id="filter_policy_type_staff" class="form-control">
                                            <option value="">-- All Policy Types --</option>
                                            <?php if (!empty($policyTypes)): ?>
                                                <?php foreach ($policyTypes as $type): ?>
                                                    <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="tab-content" id="staffPoliciesInnerContent">
                                    <!-- Current Staff Policies -->
                                    <div class="tab-pane fade show active" id="currentStaffPolicies" role="tabpanel"
                                        aria-labelledby="currentStaffPolicies-tab">
                                        <table id="currentStaffPoliciesTable" class="table policy-table">
                                            <thead>
                                                <tr>
                                                    <th class="sr-no text-center">Sr.No</th>
                                                    <th class="policy-holder">Policy Holder</th>
                                                    <th class="company mobile-hide">Staff Name</th>
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
                                                        <tr data-id="<?= $policy['id']; ?>">
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

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
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
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
                                                                </div>

                                                                <button class="mobile-only mobile-expand-btn clr-btn" onclick="toggleMobileDetails(<?= $policy['id']; ?>)">
                                                                    <span class="expand-icon">+</span>
                                                                </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                        <tr class="mobile-details mobile-only" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>">
                                                            <td colspan="8" class="mobile-details-row">
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Staff Name:</span>
                                                                    <span class="detail-value"><?= esc($policy['staff_name'] ?? ''); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Policy Type:</span>
                                                                    <span class="detail-value"><?= esc($policy['policy_type_name'] ?? ''); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Start Date:</span>
                                                                    <span class="detail-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">End Date:</span>
                                                                    <span class="detail-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Premium:</span>
                                                                    <span class="detail-value"><?= esc($policy['premium'] ?? ''); ?></span>
                                                                </div>
                                                                <div class="action-buttons">

                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-sm btn-secondary view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>


                                                                    <button type="button" class="btn btn-sm btn-dark-blue" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-sm btn-success" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>
                                                                    <!-- <a href="<? //= base_url('download-policy-invoice/' . $policy['id']); 
                                                                                    ?>" target="_blank" class="btn btn-sm btn-warning" title="Download Invoice PDF">
                                                                        <i class="fa fa-file-pdf-o"></i> Invoice
                                                                    </a> -->
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
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
                                                    <th class="company mobile-hide">Staff Name</th>
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
                                                        <tr data-id="<?= $policy['id']; ?>">
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

                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning p-2 view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
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
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
                                                                </div>

                                                                <button class="mobile-only mobile-expand-btn clr-btn" onclick="toggleMobileDetails(<?= $policy['id']; ?>)">
                                                                    <span class="expand-icon">+</span>
                                                                </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Mobile Details Row -->
                                                        <tr class="mobile-details mobile-only" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>">
                                                            <td colspan="8" class="mobile-details-row">
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Staff Name:</span>
                                                                    <span class="detail-value"><?= esc($policy['staff_name'] ?? ''); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Policy Type:</span>
                                                                    <span class="detail-value"><?= esc($policy['policy_type_name'] ?? ''); ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Start Date:</span>
                                                                    <span class="detail-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">End Date:</span>
                                                                    <span class="detail-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></span>
                                                                </div>
                                                                <div class="detail-item">
                                                                    <span class="detail-label">Premium:</span>
                                                                    <span class="detail-value"><?= esc($policy['premium'] ?? ''); ?></span>
                                                                </div>
                                                                <div class="action-buttons">

                                                                    <a href="javascript:void(0);"
                                                                        class="btn btn-secondary view-kyc-btn"
                                                                        data-policy-id="<?= $policy['id']; ?>"
                                                                        title="View KYC Details">
                                                                        <i class="fa fa-id-card"></i>
                                                                    </a>

                                                                    <button type="button" class="btn btn-sm btn-dark-blue" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>
                                                                    <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-sm btn-success" title="Renew Policy">
                                                                        <i class="fa fa-refresh"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                        class="btn btn-warning view-document-btn"
                                                                        data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                        title="View Policy Document">
                                                                        <i class="fa fa-file-pdf-o"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                        <i class="fa fa-eye"></i>
                                                                    </a>
                                                                    <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                        <i class="fa fa-trash-o"></i>
                                                                    </a>
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
                    <div class="p-4 overflow-scroll">
                        <!-- ================= Staff Policies ================= -->
                        <!-- Inner Tabs -->
                        <ul class="nav nav-pills mb-3 custom-pills" id="staffPoliciesInnerTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="currentStaffPolicies-tab" data-bs-toggle="tab"
                                    data-bs-target="#currentStaffPoliciesStaff" type="button" role="tab"
                                    aria-controls="currentStaffPoliciesStaff" aria-selected="true">
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
                        <div class="row mb-3">
                            <div class="col-lg-3">
                                <label for="filter_holder_staff" class="form-label">Filter by Holder</label>
                                <select id="filter_holder_staff" class="form-control">
                                    <option value="">-- All Holders --</option>
                                    <?php if (!empty($holders)): ?>
                                        <?php foreach ($holders as $holder): ?>
                                            <option value="<?= esc($holder['name']) ?>"><?= esc($holder['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <!-- <div class="col-lg-3">
                                <label for="filter_staff" class="form-label">Filter by Staff</label>
                                <select id="filter_staff" class="form-control">
                                    <option value="">-- All Staff --</option>
                                    <? //php if (!empty($staffList)): 
                                    ?>
                                        <? //php foreach ($staffList as $staff): 
                                        ?>
                                            <option value="<? //= esc($staff['name']) 
                                                            ?>"><? //= esc($staff['name']) 
                                                                ?></option>
                                        <? //php endforeach; 
                                        ?>
                                    <? //php endif; 
                                    ?>
                                </select>
                            </div> -->
                            <div class="col-lg-3">
                                <label for="filter_policy_type_staff" class="form-label">Filter by Policy Type</label>
                                <select id="filter_policy_type_staff" class="form-control">
                                    <option value="">-- All Policy Types --</option>
                                    <?php if (!empty($policyTypes)): ?>
                                        <?php foreach ($policyTypes as $type): ?>
                                            <option value="<?= esc($type['name']) ?>"><?= esc($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="tab-content overflow-scroll" id="staffPoliciesInnerContentStaff">
                            <!-- Current Staff Policies -->
                            <div class="tab-pane fade show active" id="currentStaffPoliciesStaff" role="tabpanel"
                                aria-labelledby="currentStaffPolicies-tab">
                                <table id="currentStaffPoliciesTableStaff" class="table policy-table">
                                    <thead>
                                        <tr>
                                            <th class="sr-no text-center">Sr.No</th>
                                            <th class="policy-holder">Policy Holder</th>
                                            <th class="company mobile-hide">Staff Name</th>
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
                                                <tr data-id="<?= $policy['id']; ?>">
                                                    <td class="text-center"><?= $i++; ?></td>
                                                    <td><?= esc($policy['policy_holder_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['staff_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['policy_type_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['premium'] ?? ''); ?></td>
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

                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                class="btn btn-warning p-2 view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="fa fa-file-pdf-o"></i>
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
                                                                <i class="fa fa-trash-o"></i>
                                                            </a>
                                                        </div>

                                                        <button class="mobile-only mobile-expand-btn clr-btn" onclick="toggleMobileDetails(<?= $policy['id']; ?>)">
                                                            <span class="expand-icon">+</span>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Mobile Details Row -->
                                                <tr class="mobile-details mobile-only" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>">
                                                    <td colspan="8" class="mobile-details-row">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Staff Name:</span>
                                                            <span class="detail-value"><?= esc($policy['staff_name'] ?? ''); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Policy Type:</span>
                                                            <span class="detail-value"><?= esc($policy['policy_type_name'] ?? ''); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Start Date:</span>
                                                            <span class="detail-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">End Date:</span>
                                                            <span class="detail-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Premium:</span>
                                                            <span class="detail-value"><?= esc($policy['premium'] ?? ''); ?></span>
                                                        </div>
                                                        <div class="action-buttons">
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-secondary view-kyc-btn"
                                                                data-policy-id="<?= $policy['id']; ?>"
                                                                title="View KYC Details">
                                                                <i class="fa fa-id-card"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-dark-blue" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                <i class="fa fa-upload"></i>
                                                            </button>
                                                            <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-sm btn-success" title="Renew Policy">
                                                                <i class="fa fa-refresh"></i>
                                                            </a>
                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                class="btn btn-warning view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="fa fa-file-pdf-o"></i>
                                                            </a>
                                                            <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                <i class="fa fa-trash-o"></i>
                                                            </a>
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
                                            <th class="company mobile-hide">Staff Name</th>
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
                                                <tr data-id="<?= $policy['id']; ?>">
                                                    <td class="text-center"><?= $i++; ?></td>
                                                    <td><?= esc($policy['policy_holder_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['staff_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['policy_type_name'] ?? ''); ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></td>
                                                    <td class="mobile-hide"><?= esc($policy['premium'] ?? ''); ?></td>
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
                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                class="btn btn-warning p-2 view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="fa fa-file-pdf-o"></i>
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
                                                                <i class="fa fa-trash-o"></i>
                                                            </a>
                                                        </div>

                                                        <button class="mobile-only mobile-expand-btn clr-btn" onclick="toggleMobileDetails(<?= $policy['id']; ?>)">
                                                            <span class="expand-icon">+</span>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Mobile Details Row -->
                                                <tr class="mobile-details mobile-only" id="details-<?= $policy['id']; ?>" data-id="<?= $policy['id']; ?>">
                                                    <td colspan="8" class="mobile-details-row">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Staff Name:</span>
                                                            <span class="detail-value"><?= esc($policy['staff_name'] ?? ''); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Policy Type:</span>
                                                            <span class="detail-value"><?= esc($policy['policy_type_name'] ?? ''); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Start Date:</span>
                                                            <span class="detail-value"><?= !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : ''; ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">End Date:</span>
                                                            <span class="detail-value"><?= !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : ''; ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Premium:</span>
                                                            <span class="detail-value"><?= esc($policy['premium'] ?? ''); ?></span>
                                                        </div>
                                                        <div class="action-buttons">
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-secondary view-kyc-btn"
                                                                data-policy-id="<?= $policy['id']; ?>"
                                                                title="View KYC Details">
                                                                <i class="fa fa-id-card"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-dark-blue" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="<?= $policy['id']; ?>" title="Upload / Download">
                                                                <i class="fa fa-upload"></i>
                                                            </button>
                                                            <a href="<?= base_url('renew-policy/' . $policy['id']); ?>" class="btn btn-sm btn-success" title="Renew Policy">
                                                                <i class="fa fa-refresh"></i>
                                                            </a>
                                                            <a href="<?= base_url('policy-document-view/' . $policy['id']); ?>"
                                                                class="btn btn-warning view-document-btn"
                                                                data-has-doc="<?= !empty($policy['document']) ? '1' : '0' ?>"
                                                                title="View Policy Document">
                                                                <i class="fa fa-file-pdf-o"></i>
                                                            </a>
                                                            <a href="<?= base_url('view-policy/' . $policy['id']); ?>" class="btn btn-sm btn-info" title="View Policy">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <a href="<?= base_url('edit-policy/' . $policy['id']); ?>" class="btn btn-sm btn-light" title="Edit Policy">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger delete-policy" data-id="<?= $policy['id']; ?>" title="Delete Policy">
                                                                <i class="fa fa-trash-o"></i>
                                                            </a>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="uploadDownloadForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadDownloadModalLabel">Upload / Download Documents</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="policy_id" id="policy_id">

                        <!-- Upload Section -->
                        <div class="mb-3">
                            <label for="document" class="form-label">Upload Document</label>
                            <input type="file" name="document" id="document" class="form-control">
                        </div>

                        <!-- Download Section -->
                        <div class="mb-3">
                            <label class="form-label">Available Documents:</label>
                            <ul id="documentList" class="list-group">
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

                        <div id="kyc-errors" class="alert alert-danger mt-3" style="display:none;"></div>
                        <input type="hidden" name="policy_id" id="policy_id">

                        <div class="tab-content mt-3" id="kycTabsContent">

                            <!-- ================= ID PROOF TAB ================= -->
                            <div class="tab-pane fade show active" id="idproof" role="tabpanel">
                                <h5 class="mt-2">ID Proof</h5>

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label>Aadhaar Card</label>
                                        <div class="d-flex align-items-center">
                                            <input type="file" name="aadhaar_card[]" class="form-control" multiple>
                                            <a href="#" class="ms-2" target="_blank" id="view_aadhaar_card" style="display:none;">
                                                <i class="fa fa-eye text-primary"></i>
                                            </a>
                                        </div>
                                        <!-- Individual Submit/Update Button for Aadhaar Card -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="aadhaar_card">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="aadhaar_card" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>PAN Card</label>
                                        <input type="file" name="pan_card[]" class="form-control" multiple>
                                        <!-- Individual Submit/Update Button for PAN Card -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="pan_card">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="pan_card" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Driving Licence</label>
                                        <input type="file" name="driving_licence[]" class="form-control" multiple>
                                        <!-- Individual Submit/Update Button for Driving Licence -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="driving_licence">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="driving_licence" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
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
                                        <!-- Individual Submit/Update Button for Address Aadhaar -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="addr_aadhaar">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="addr_aadhaar" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Driving Licence</label>
                                        <input type="file" name="addr_driving_licence[]" class="form-control" multiple>
                                        <!-- Individual Submit/Update Button for Address Driving Licence -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="addr_driving_licence">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="addr_driving_licence" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
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
                                        <!-- Individual Submit/Update Button for Photo -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="photo">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="photo" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Signature</label>
                                        <input type="file" name="signature[]" class="form-control" multiple>
                                        <!-- Individual Submit/Update Button for Signature -->
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark-blue submit-single-btn" data-field="signature">
                                                <i class="fa fa-upload"></i> Submit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success update-single-btn" data-field="signature" style="display:none;">
                                                <i class="fa fa-refresh"></i> Update
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <!-- Buttons - Only on the last tab -->
                                <div class="mt-4 text-end">

                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="clearKycErrors()">
                                        Cancel
                                    </button>

                                    <button type="submit" class="btn btn-success" id="btnSaveKyc">
                                        <i class="fa fa-save"></i> Save All Document
                                    </button>

                                    <button type="button" class="btn btn-dark-blue" id="btnUpdateKyc" style="display:none;">
                                        <i class="fa fa-edit"></i> Update All Document
                                    </button>

                                </div>
                            </div>

                        </div>

                    </form>


                </div>





            </div>
        </div>
    </div>


    <?= $this->include('layout/footer') ?>

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

    <!-- jQuery is already loaded globally in layout/header.php -->
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>
    <script>
        // Function to clear KYC form errors
        function clearKycErrors() {
            $('#kycForm').find('.error-text, .invalid-feedback').remove();
            $('#kycForm').find('.is-invalid').removeClass('is-invalid');
            $('#kyc-errors').hide().text('');
            $('#kycForm').removeData('is-update');
            $('#kycForm').data('is-update', false);
        }

        // Also clear errors when modal is hidden
        $('#kycModal').on('hidden.bs.modal', function() {
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
                                <div class="mb-3">
                                    <h6>${fileName}</h6>
                                    <img src="${file}" class="img-fluid mb-2 gallery-image" alt="${fileName}" style="max-height: 400px; cursor: pointer;" onclick="window.open('${file}', '_blank')">
                                </div>
                            `);
                        } else {
                            // For non-image files (PDF, etc.), show a link
                            container.append(`
                                <div class="mb-3">
                                    <h6>${fileName}</h6>
                                    <p><a href="${file}" target="_blank" class="btn btn-dark-blue">View Document</a></p>
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
            $('#kycForm').find('.error-text, .invalid-feedback').remove();
            $('#kycForm').find('.is-invalid').removeClass('is-invalid');

            $('#kycForm .view-icon').remove();
            $('#kycForm .download-icon').remove();

            // Clear update flag
            $('#kycForm').removeData('is-update');
            $('#kycForm').data('is-update', false);

            // Set policy ID
            $("#policy_id").val(policyId);

            // Show modal
            $("#kycModal").modal("show");

            let isUpdate = false;

            // AJAX to fetch existing KYC
            $.ajax({
                url: $('#base_url').val() + '/view-kyc',
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
                            if ($input.parent().hasClass('d-flex')) {
                                $input.parent().after(iconHtml);
                            } else {
                                // For fields without .d-flex wrapper, add the icons directly after the input
                                $input.after(iconHtml);
                            }

                            // Show update button and hide submit button for this field
                            const fieldClass = inputSelector.replace('[name="', '').replace('[]"]', '').replace('"]', '');
                            $(`.submit-single-btn[data-field="${fieldClass}"]`).hide();
                            $(`.update-single-btn[data-field="${fieldClass}"]`).show();
                        };

                        // Aadhaar (can have multiple files)
                        if (data.aadhaar_card && data.aadhaar_card.length > 0) {
                            addDocumentIcons('input[name="aadhaar_card[]"]', data.aadhaar_card, 'Aadhaar Card');
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
            const policyId = $('#policy_id').val();

            // Get the file input for this field
            let fileInput;
            if (field === 'aadhaar_card' && $('input[name="aadhaar_card[]"]').parent().hasClass('d-flex')) {
                fileInput = $('input[name="aadhaar_card[]"]')[0];
            } else {
                fileInput = $(`input[name="${field}[]"]`)[0] || $(`input[name="${field}"]`)[0];
            }

            // Check if there's already an existing document for this field
            const hasExistingDocument = $(this).closest('.mb-3').find('.view-icon').length > 0;

            // Only show error if no file is selected AND no existing document
            if ((!fileInput || fileInput.files.length === 0) && !hasExistingDocument) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a file to upload for ' + field.replace('_', ' ')
                });
                return;
            }

            // If no new file is selected but document exists, do nothing
            if (!fileInput || fileInput.files.length === 0) {
                return;
            }

            // Create FormData for this single field
            const formData = new FormData();
            formData.append('policy_id', policyId);

            // Append all files for this field
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append(`${field}[]`, fileInput.files[i]);
            }

            // Disable button during upload
            const button = $(this);
            const originalText = button.html();
            button.html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
            button.prop('disabled', true);

            // Submit via AJAX
            $.ajax({
                url: $('#base_url').val() + '/submit-kyc-single',
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
            const policyId = $('#policy_id').val();

            // Get the file input for this field
            let fileInput;
            if (field === 'aadhaar_card' && $('input[name="aadhaar_card[]"]').parent().hasClass('d-flex')) {
                fileInput = $('input[name="aadhaar_card[]"]')[0];
            } else {
                fileInput = $(`input[name="${field}[]"]`)[0] || $(`input[name="${field}"]`)[0];
            }

            // Check if there's already an existing document for this field
            const hasExistingDocument = $(this).closest('.mb-3').find('.view-icon').length > 0;

            // Only show error if no file is selected AND document exists (update scenario)
            if ((!fileInput || fileInput.files.length === 0) && hasExistingDocument) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a file to update for ' + field.replace('_', ' ')
                });
                return;
            }

            // If no new file is selected, do nothing
            if (!fileInput || fileInput.files.length === 0) {
                return;
            }

            // Create FormData for this single field
            const formData = new FormData();
            formData.append('policy_id', policyId);

            // Append all files for this field
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append(`${field}[]`, fileInput.files[i]);
            }

            // Disable button during upload
            const button = $(this);
            const originalText = button.html();
            button.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
            button.prop('disabled', true);

            // Submit via AJAX
            $.ajax({
                url: $('#base_url').val() + '/submit-kyc-single',
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
                    // Aadhaar card validation (1-2 files required)
                    let aadhaarFiles = $('input[name="aadhaar_card[]"]')[0].files;
                    if (aadhaarFiles.length === 0 || aadhaarFiles.length > 2) {
                        showError('input[name="aadhaar_card[]"]', 'Please upload 1 or 2 Aadhaar card files.');
                    } else {
                        // Validate file types for Aadhaar
                        for (let i = 0; i < aadhaarFiles.length; i++) {
                            let ext = aadhaarFiles[i].name.split('.').pop().toLowerCase();
                            if (!allowedDocTypes.includes(ext)) {
                                showError('input[name="aadhaar_card[]"]', 'Aadhaar card files must be jpg, png, gif or pdf.');
                                break;
                            }
                        }
                    }

                    // PAN card validation (exactly 1 file required)
                    let panFiles = $('input[name="pan_card[]"]')[0].files;
                    if (panFiles.length !== 1) {
                        showError('input[name="pan_card[]"]', 'Please upload exactly 1 PAN card file.');
                    } else {
                        let ext = panFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedDocTypes.includes(ext)) {
                            showError('input[name="pan_card[]"]', 'PAN card file must be jpg, png, gif or pdf.');
                        }
                    }

                    // Photo validation (exactly 1 file required)
                    let photoFiles = $('input[name="photo[]"]')[0].files;
                    if (photoFiles.length !== 1) {
                        showError('input[name="photo[]"]', 'Please upload exactly 1 Passport Size Photo.');
                    } else {
                        let ext = photoFiles[0].name.split('.').pop().toLowerCase();
                        if (!allowedImageTypes.includes(ext)) {
                            showError('input[name="photo[]"]', 'Photo must be jpg, png, or gif.');
                        }
                    }

                    // Signature validation (exactly 1 file required)
                    let signFiles = $('input[name="signature[]"]')[0].files;
                    if (signFiles.length !== 1) {
                        showError('input[name="signature[]"]', 'Please upload exactly 1 Signature.');
                    } else {
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
                formData.set('policy_id', $('#policy_id').val());

                $.ajax({
                    url: $('#base_url').val() + '/submit-kyc-details',
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
        function toggleMobileDetails(id) {
            const detailsRow = document.getElementById('details-' + id);
            const mainRow = document.querySelector(`tr[data-id="${id}"]`);
            const expandBtn = mainRow ? mainRow.querySelector('.mobile-expand-btn .expand-icon') : null;

            if (detailsRow && expandBtn) {
                if (detailsRow.classList.contains('show')) {
                    detailsRow.classList.remove('show');
                    expandBtn.textContent = '+';
                } else {
                    // Close all other open details
                    document.querySelectorAll('.mobile-details.show').forEach(row => {
                        row.classList.remove('show');
                        const rowId = row.getAttribute('data-id');
                        const btnRow = document.querySelector(`tr[data-id="${rowId}"]`);
                        if (btnRow) {
                            const btn = btnRow.querySelector('.mobile-expand-btn .expand-icon');
                            if (btn) btn.textContent = '+';
                        }
                    });

                    detailsRow.classList.add('show');
                    expandBtn.textContent = '−';
                }
            }
        }

        $(document).on('click', '.delete-policy', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var originalBtnText = $btn.html();
            var policyId = $btn.data('id');
            var baseUrl = $('#base_url').val();
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
                        url: baseUrl + '/delete-policy/' + policyId,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id: policyId
                        },
                        success: function(res) {
                            if (res.status === true) {
                                Swal.fire(
                                    'Deleted!',
                                    res.message,
                                    'success'
                                );
                                // Remove both main row and mobile details row if exists
                                $row.next('.mobile-details').remove();
                                $row.fadeOut(500, function() {
                                    $(this).remove();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    res.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Something went wrong while deleting the policy.',
                                'error'
                            );
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
                modal.find('#policy_id').val(policyId);
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

                        if (res.status === 'success' && res.document) {

                            var fileUrl = "<?= base_url('uploads/policies/'); ?>" + res.document;

                            list.append(
                                '<div class="row p-2   align-items-center">' +

                                // LEFT SIDE → Document Name (10 cols)
                                '<div class="col-10 d-flex align-items-center border rounded mb-3 p-2">' +
                                res.document +
                                '</div>' +
                                // RIGHdT SIDE → Download Button (2 cols)
                                '<div class="col-2 text-end">' +
                                '<a href="' + fileUrl + '" class="btn btn-success" download>' +
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
                        Swal.fire('Error', 'Failed to fetch document.', 'error');
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

                        if (res.status == 'success') {

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
                    error: function() {
                        $('#uploadDownloadForm button[type="submit"]').prop('disabled', false).text('Upload');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Upload failed'
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
            $('#currentMyPoliciesTable tbody tr.mobile-details, #renewedMyPoliciesTable tbody tr.mobile-details, #currentStaffPoliciesTable tbody tr.mobile-details, #renewedStaffPoliciesTable tbody tr.mobile-details, #currentStaffPoliciesTableStaff tbody tr.mobile-details, #renewedStaffPoliciesTableStaff tbody tr.mobile-details').each(function() {
                mobileDetailsRows.push($(this).detach());
            });

            // Initialize DataTables for all existing policy tables
            var tables = {};

            // My policies
            if ($('#currentMyPoliciesTable').length) {
                tables.currentMyPolicies = $('#currentMyPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100]
                });
            }

            if ($('#renewedMyPoliciesTable').length) {
                tables.renewedMyPolicies = $('#renewedMyPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100]
                });
            }

            // Staff policies (admin view)
            if ($('#currentStaffPoliciesTable').length) {
                tables.currentStaffPolicies = $('#currentStaffPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100]
                });
            }

            if ($('#renewedStaffPoliciesTable').length) {
                tables.renewedStaffPolicies = $('#renewedStaffPoliciesTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100]
                });
            }

            // Staff policies (Created by Staff section)
            if ($('#currentStaffPoliciesTableStaff').length) {
                tables.currentStaffPoliciesStaff = $('#currentStaffPoliciesTableStaff').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100]
                });
            }

            if ($('#renewedStaffPoliciesTableStaff').length) {
                tables.renewedStaffPoliciesStaff = $('#renewedStaffPoliciesTableStaff').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100]
                });
            }

            // Existing filters...
            $('#filter_holder_me').on('change', function() {
                if (tables.currentMyPolicies) tables.currentMyPolicies.column(1).search(this.value).draw();
                if (tables.renewedMyPolicies) tables.renewedMyPolicies.column(1).search(this.value).draw();
            });
            $('#filter_policy_type_me').on('change', function() {
                if (tables.currentMyPolicies) tables.currentMyPolicies.column(2).search(this.value).draw();
                if (tables.renewedMyPolicies) tables.renewedMyPolicies.column(2).search(this.value).draw();
            });
            $('#filter_company_me').on('change', function() {
                if (tables.currentMyPolicies) tables.currentMyPolicies.column(3).search(this.value).draw();
                if (tables.renewedMyPolicies) tables.renewedMyPolicies.column(3).search(this.value).draw();
            });

            // Staff filters (applied to both admin and staff views)
            $('#filter_holder_staff').on('change', function() {
                if (tables.currentStaffPolicies) tables.currentStaffPolicies.column(1).search(this.value).draw();
                if (tables.renewedStaffPolicies) tables.renewedStaffPolicies.column(1).search(this.value).draw();
                if (tables.currentStaffPoliciesStaff) tables.currentStaffPoliciesStaff.column(1).search(this.value).draw();
                if (tables.renewedStaffPoliciesStaff) tables.renewedStaffPoliciesStaff.column(1).search(this.value).draw();
            });
            $('#filter_staff').on('change', function() {
                if (tables.currentStaffPolicies) tables.currentStaffPolicies.column(2).search(this.value).draw();
                if (tables.renewedStaffPolicies) tables.renewedStaffPolicies.column(2).search(this.value).draw();
                if (tables.currentStaffPoliciesStaff) tables.currentStaffPoliciesStaff.column(2).search(this.value).draw();
                if (tables.renewedStaffPoliciesStaff) tables.renewedStaffPoliciesStaff.column(2).search(this.value).draw();
            });
            $('#filter_policy_type_staff').on('change', function() {
                if (tables.currentStaffPolicies) tables.currentStaffPolicies.column(3).search(this.value).draw();
                if (tables.renewedStaffPolicies) tables.renewedStaffPolicies.column(3).search(this.value).draw();
                if (tables.currentStaffPoliciesStaff) tables.currentStaffPoliciesStaff.column(3).search(this.value).draw();
                if (tables.renewedStaffPoliciesStaff) tables.renewedStaffPoliciesStaff.column(3).search(this.value).draw();
            });

            // Custom Date Range Filter for DataTables
            $.fn.dataTable.ext.search.push(function(settings, data) {
                var allowedTables = [
                    'currentMyPoliciesTable',
                    'renewedMyPoliciesTable',
                    'currentStaffPoliciesTable',
                    'renewedStaffPoliciesTable',
                    'currentStaffPoliciesTableStaff',
                    'renewedStaffPoliciesTableStaff'
                ];

                if ($.inArray(settings.nTable.id, allowedTables) === -1) {
                    return true;
                }

                var startDate = $('#filter_start_date').val();
                var endDate = $('#filter_end_date').val();

                var policyStart = data[4];
                var policyEnd = data[5];

                if (!startDate && !endDate) {
                    return true;
                }

                var start = new Date(startDate);
                var end = new Date(endDate);
                var policyStartDate = new Date(policyStart.split('-').reverse().join('-'));
                var policyEndDate = new Date(policyEnd.split('-').reverse().join('-'));

                if ((isNaN(start) || policyStartDate >= start) && (isNaN(end) || policyEndDate <= end)) {
                    return true;
                }
                return false;
            });

            $('#filter_start_date, #filter_end_date').on('change', function() {
                if (tables.currentMyPolicies) tables.currentMyPolicies.draw();
                if (tables.renewedMyPolicies) tables.renewedMyPolicies.draw();
                if (tables.currentStaffPolicies) tables.currentStaffPolicies.draw();
                if (tables.renewedStaffPolicies) tables.renewedStaffPolicies.draw();
                if (tables.currentStaffPoliciesStaff) tables.currentStaffPoliciesStaff.draw();
                if (tables.renewedStaffPoliciesStaff) tables.renewedStaffPoliciesStaff.draw();
            });

            // Re-insert mobile details rows after DataTables initialization
            mobileDetailsRows.forEach(function(row) {
                var rowId = row.attr('data-id');
                var mainRow = $('#currentMyPoliciesTable tbody tr[data-id="' + rowId + '"], #renewedMyPoliciesTable tbody tr[data-id="' + rowId + '"], #currentStaffPoliciesTable tbody tr[data-id="' + rowId + '"], #renewedStaffPoliciesTable tbody tr[data-id="' + rowId + '"], #currentStaffPoliciesTableStaff tbody tr[data-id="' + rowId + '"], #renewedStaffPoliciesTableStaff tbody tr[data-id="' + rowId + '"]');
                if (mainRow.length) {
                    row.insertAfter(mainRow);
                }
            });
        });
    </script>
    <script>
        $(document).on('click', '.view-document-btn', function(e) {

            if ($(this).data('has-doc') == "0") {

                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'No Document Found',
                    text: 'This policy does not have any document uploaded.',
                    confirmButtonColor: '#d33'
                });

                return false;
            }

        });
    </script>
