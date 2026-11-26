<div class="modal fade" id="crmAccessRequestModal" tabindex="-1" aria-labelledby="crmAccessRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="crmAccessRequestModalLabel">
                    <i class="bi bi-shield-lock text-warning"></i>
                    <span>Request Action Access</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="crmArModule">
                <input type="hidden" id="crmArAction">
                <input type="hidden" id="crmArPermission">
                <input type="hidden" id="crmArRecordId">

                <div class="p-3 bg-light rounded-3 mb-3 small" id="crmArTargetSummary"></div>
                <div class="alert alert-danger py-2 px-3 small d-none" id="crmArPreviousNotice"></div>

                <div class="mb-3 d-none" id="crmArActionsWrapper">
                    <label class="form-label fw-semibold small mb-2">
                        Select Actions to Request <span class="text-danger">*</span>
                    </label>
                    <div id="crmArActionsList" class="d-flex flex-column gap-2"></div>
                </div>

                <div class="mb-2">
                    <label for="crmArReason" class="form-label fw-semibold small">
                        Reason for requesting access <span class="text-danger">*</span>
                    </label>
                    <textarea id="crmArReason" class="form-control" rows="3" maxlength="1000"
                              placeholder="Explain why you need permission to perform this action..."></textarea>
                    <div class="text-danger small mt-1 d-none" id="crmArError"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="crmArSubmitBtn">
                    <i class="bi bi-send me-1"></i>Submit Request
                </button>
            </div>
        </div>
    </div>
</div>
