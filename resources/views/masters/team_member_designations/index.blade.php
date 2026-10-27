@extends('layouts.masters')

@section('page_title', 'Team Member Designations')

@section('masters_content')
<div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
    <div><h4 class="fw-bold mb-1">Team Member Designations</h4><p class="text-muted small mb-0">Create and manage selectable staff designations.</p></div>
    <button class="btn btn-dark-blue" id="addDesignation"><i class="fa-solid fa-plus me-1"></i>Add Designation</button>
</div>
<div class="card-body p-0">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4">Sr.No</th><th>Designation</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead><tbody id="designationRows"></tbody></table></div>
</div>

<div class="modal fade" id="designationModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" id="designationForm">@csrf
    <div class="modal-header"><h5 class="modal-title">Add Designation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><input type="hidden" id="designationId"><label class="form-label">Designation <span class="text-danger">*</span></label><input class="form-control" id="designationName" maxlength="100" required><div class="mt-3" id="statusWrap" style="display:none"><label class="form-label">Status</label><select class="form-select" id="designationStatus"><option value="1">Active</option><option value="0">Inactive</option></select></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-dark-blue" type="submit">Save</button></div>
</form></div></div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('designationModal'));
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let records = [];
    function load() { $.get(@json(route('masters.team_member_designations.list')), function (res) { records = res.data || []; $('#designationRows').html(records.length ? records.map((r,i) => `<tr><td class="ps-4">${i+1}</td><td>${$('<div>').text(r.name).html()}</td><td><span class="badge ${r.is_active ? 'bg-success' : 'bg-secondary'}">${r.is_active ? 'Active' : 'Inactive'}</span></td><td class="text-end pe-4"><button class="btn crm-action-btn btn-sm edit-designation" data-id="${r.id}"><i class="bi bi-pencil"></i></button> <button class="btn crm-action-btn btn-sm text-danger delete-designation" data-id="${r.id}"><i class="bi bi-trash text-danger"></i></button></td></tr>`).join('') : '<tr><td colspan="4" class="text-center py-5 text-muted">No designations found.</td></tr>'); }); }
    $('#addDesignation').on('click', function () { $('#designationId').val(''); $('#designationName').val(''); $('#statusWrap').hide(); $('.modal-title').text('Add Designation'); modal.show(); });
    $(document).on('click', '.edit-designation', function () { const r = records.find(x => Number(x.id) === Number(this.dataset.id)); $('#designationId').val(r.id); $('#designationName').val(r.name); $('#designationStatus').val(r.is_active ? '1' : '0'); $('#statusWrap').show(); $('.modal-title').text('Edit Designation'); modal.show(); });
    $('#designationForm').on('submit', function(e) { e.preventDefault(); const id=$('#designationId').val(); $.ajax({url:id ? @json(url('/masters/team-member-designations')).replace(/\/$/,'')+'/'+id : @json(route('masters.team_member_designations.store')), type:id?'PUT':'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}, data:{name:$('#designationName').val(),is_active:$('#designationStatus').val()}, success:function(res){modal.hide(); Swal.fire({icon:'success',text:res.message,timer:1400,showConfirmButton:false});load();}, error:function(xhr){Swal.fire('Error',xhr.responseJSON?.message || 'Unable to save designation.','error');}}); });
    $(document).on('click', '.delete-designation', function () { const id=this.dataset.id; Swal.fire({title:'Delete designation?',icon:'warning',showCancelButton:true,confirmButtonText:'Delete'}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:@json(url('/masters/team-member-designations')).replace(/\/$/,'')+'/'+id,type:'DELETE',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},success:function(res){Swal.fire({icon:'success',text:res.message,timer:1200,showConfirmButton:false});load();},error:function(xhr){Swal.fire('Error',xhr.responseJSON?.message || 'Unable to delete designation.','error');}});}); });
    load();
});
</script>
@endpush
