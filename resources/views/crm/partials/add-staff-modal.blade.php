<!-- Modal: Add Staff -->
<div class="modal fade" id="modalAddStaff" tabindex="-1" aria-labelledby="modalAddStaffLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalAddStaffLabel">Add Staff</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddStaff" action="/api/users" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" placeholder="Email address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" placeholder="10 digit phone number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" placeholder="Minimum 8 characters" required minlength="8">
                    </div>
                    <input type="hidden" name="role" value="staff">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
