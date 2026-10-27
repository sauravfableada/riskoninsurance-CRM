<!-- Insurance Plan Name Modal -->
<div class="modal fade" id="insurancePlanNameModal" tabindex="-1" aria-labelledby="insurancePlanNameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="insurancePlanNameModalLabel">Add Insurance Plan Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="insurancePlanNameForm">
                    <div class="mb-3">
                        <label for="new_insurance_plan_name" class="form-label small fw-bold text-uppercase">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_insurance_plan_name" name="name" placeholder="Enter plan name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="savePlanNameBtn">Save Plan Name</button>
            </div>
        </div>
    </div>
</div>

<!-- Insurance Plan Type Modal -->
<div class="modal fade" id="insurancePlanTypeModal" tabindex="-1" aria-labelledby="insurancePlanTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="insurancePlanTypeModalLabel">Add Insurance Plan Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="insurancePlanTypeForm">
                    <div class="mb-3">
                        <label for="new_insurance_plan_type" class="form-label small fw-bold text-uppercase">Plan Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_insurance_plan_type" name="name" placeholder="Enter plan type" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="savePlanTypeBtn">Save Plan Type</button>
            </div>
        </div>
    </div>
</div>

<!-- Insurance Company Modal -->
<div class="modal fade" id="insuranceCompanyModal" tabindex="-1" aria-labelledby="insuranceCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="insuranceCompanyModalLabel">Add Insurance Company</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="insuranceCompanyForm">
                    <div class="mb-3">
                        <label for="new_insurance_company_name" class="form-label small fw-bold text-uppercase">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_insurance_company_name" name="name" placeholder="Enter company name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="saveCompanyModalBtn">Save Company</button>
            </div>
        </div>
    </div>
</div>

<!-- Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1" aria-labelledby="branchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="branchModalLabel">Add Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="branchForm">
                    <div class="mb-3">
                        <label for="branch_company_id" class="form-label small fw-bold text-uppercase">Company <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="branch_company_id" name="company_id" required>
                            <option value="">Select Company</option>
                            @foreach($insuranceCompanies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_branch_name" class="form-label small fw-bold text-uppercase">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_branch_name" name="name" placeholder="Enter branch name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="saveBranchBtn">Save Branch</button>
            </div>
        </div>
    </div>
</div>

<!-- Broker Modal -->
<div class="modal fade" id="brokerModal" tabindex="-1" aria-labelledby="brokerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="brokerModalLabel">Add Broker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="brokerForm">
                    <div class="mb-3">
                        <label for="new_broker_name" class="form-label small fw-bold text-uppercase">Broker Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_broker_name" name="broker_name" placeholder="Enter broker name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="saveBrokerBtn">Save Broker</button>
            </div>
        </div>
    </div>
</div>

<!-- Agency Level Modal -->
<div class="modal fade" id="agencyLevelModal" tabindex="-1" aria-labelledby="agencyLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="agencyLevelModalLabel">Add Agency Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="agencyLevelForm">
                    <div class="mb-3">
                        <label for="new_agency_level_name" class="form-label small fw-bold text-uppercase">Agency Level <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_agency_level_name" name="name" placeholder="Enter agency level" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="saveAgencyLevelBtn">Save Level</button>
            </div>
        </div>
    </div>
</div>

<!-- Agency Modal -->
<div class="modal fade" id="agencyModal" tabindex="-1" aria-labelledby="agencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="agencyModalLabel">Add Agency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="agencyForm">
                    <div class="mb-3">
                        <label for="new_agency_level_id" class="form-label small fw-bold text-uppercase">Agency Level <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="new_agency_level_id" name="agency_levels_id" required>
                            <option value="">Select Agency Level</option>
                            @foreach($agencyLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_agency_name" class="form-label small fw-bold text-uppercase">Agency Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_agency_name" name="name" placeholder="Enter agency name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="saveAgencyBtn">Save Agency</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Modal -->
<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-labelledby="paymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="paymentMethodModalLabel">Add Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="paymentMethodForm">
                    <input type="hidden" id="payment_method_target">
                    <div class="mb-3">
                        <label for="new_payment_method_name" class="form-label small fw-bold text-uppercase">Payment Method <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="new_payment_method_name" name="name" placeholder="Enter payment method" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="savePaymentMethodBtn">Save Payment Method</button>
            </div>
        </div>
    </div>
</div>

<!-- Add more modals as needed (Family Group, Policy Holder) -->

<!-- Family Group Modal -->
<div class="modal fade" id="familyGroupModal" tabindex="-1" aria-labelledby="familyGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="familyGroupModalLabel">Add Family Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="familyGroupForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="new_family_group_name" class="form-label small fw-bold text-uppercase">Family Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="new_family_group_name" name="group_name" placeholder="Enter family group name" required>
                        </div>
                        <div class="col-md-12">
                            <label for="family_group_policy_holder" class="form-label small fw-bold text-uppercase">Policy Holder <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="family_group_policy_holder" name="policy_holder_id" required>
                                <option value="">Select Policy Holder</option>
                                @isset($policyHolders)
                                    @foreach($policyHolders as $holder)
                                        <option value="{{ $holder->id }}">{{ $holder->name }}</option>
                                    @endforeach
                                @endisset
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
                            <button type="button" class="btn btn-sm crm-action-btn mt-2" id="addMoreMembersBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add More Members
                            </button>
                        </div>
                        <div class="col-md-12">
                            <label for="family_group_remarks" class="form-label small fw-bold text-uppercase">Remarks</label>
                            <textarea class="form-control rounded-3" id="family_group_remarks" name="remarks" rows="3" placeholder="Enter remarks (optional)"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="saveFamilyGroupBtn">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Policy Holder (Customer) Modal -->
<div class="modal fade" id="policyHolderModal" tabindex="-1" aria-labelledby="policyHolderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="policyHolderModalLabel">Add Policy Holder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
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
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="savePolicyHolderBtn">Save Holder</button>
            </div>
        </div>
    </div>
</div>
