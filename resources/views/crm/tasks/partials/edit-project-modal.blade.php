<div class="modal fade" id="modalEditProject" tabindex="-1" aria-labelledby="modalEditProjectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalEditProjectLabel">Edit Project</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditProject" method="POST">
                @csrf
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-bold">Project Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Customer <span class="text-danger">*</span></label><select name="customer_id" class="form-select" required><option value="">Select Customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Assigned User</label><select name="assigned_user_id" class="form-select"><option value="">Select User</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Status <span class="text-danger">*</span></label><select name="status" class="form-select" required><option value="pending">Pending</option><option value="ongoing">Active</option><option value="completed">Completed</option><option value="canceled">Cancelled</option></select></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="start_date" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold">End Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="end_date" required></div>
                    <div class="col-12"><label class="form-label fw-bold">Description <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="3" required></textarea></div>
                    <div class="col-12"><label class="form-label fw-bold">Status Comment</label><textarea name="status_comment" class="form-control" rows="2" placeholder="Optional status update comment"></textarea></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark-blue"><span class="spinner-border spinner-border-sm d-none"></span><span class="btn-text">Update Project</span></button></div>
            </form>
        </div>
    </div>
</div>
