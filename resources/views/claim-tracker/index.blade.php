@extends('layouts.app')
@section('page_title', 'Claim Tracker')
@section('content')
<div class="container-fluid p-0"><div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div><h4 class="fw-bold mb-0">Claim Tracker</h4><p class="text-muted small mb-0">Manage and track insurance claims.</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('claim-tracker.export', request()->query()) }}" class="btn btn-outline-dark-blue"><i class="fa-solid fa-download me-1"></i>Export</a>
                <a href="{{ route('claim-tracker.create') }}" class="btn btn-dark-blue"><i class="fa fa-plus me-1"></i>Add Claim</a>
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width:250px"><span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span><input type="text" id="claimsSearch" class="form-control border-start-0" placeholder="Search claims..."></div>
                <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#claimFilterCollapse" aria-expanded="true"><i class="bi bi-funnel"></i> Filters</button>
            </div>
            <div class="d-flex align-items-center gap-2 small"><span class="text-muted">Show per page:</span><select id="claimsPerPage" class="form-select form-select-sm" style="width:70px"><option>10</option><option>25</option><option>50</option><option>100</option></select></div>
        </div>
        <div class="collapse mt-3 show" id="claimFilterCollapse">
            <div class="card card-body bg-light border-0 rounded-3"><form method="GET" action="{{ route('claim-tracker.index') }}" id="claimFiltersForm"><div class="row g-3 align-items-end">
                <div class="col-md-2"><label class="form-label small text-muted mb-1">Start Date</label><input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}"></div>
                <div class="col-md-2"><label class="form-label small text-muted mb-1">End Date</label><input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}"></div>
                <div class="col-md-2"><label class="form-label small text-muted mb-1">Policy Number</label><select name="policy_number" class="form-select form-select-sm claim-filter-select2" data-placeholder="Select policy"><option value=""></option>@foreach($filterPolicies as $policy)<option value="{{ $policy->policy_number_no }}" @selected(request('policy_number') === $policy->policy_number_no)>{{ $policy->policy_number_no }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small text-muted mb-1">Proposer Name</label><select name="proposer_name" class="form-select form-select-sm claim-filter-select2" data-placeholder="Select proposer"><option value=""></option>@foreach($filterCustomers as $customer)<option value="{{ $customer->name }}" @selected(request('proposer_name') === $customer->name)>{{ $customer->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small text-muted mb-1">Status</label><select name="status" class="form-select form-select-sm"><option value="">All Statuses</option>@foreach(['Processed','Query','Closed','Rejected'] as $filterStatus)<option value="{{ $filterStatus }}" @selected(request('status') === $filterStatus)>{{ $filterStatus }}</option>@endforeach</select></div>
                <div class="col-md-2"><a href="{{ route('claim-tracker.index') }}" class="btn btn-sm btn-dark text-white px-3" style="background:#0f172a; border-color:#0f172a">Clear</a></div>
            </div></form></div>
        </div>
    </div>
    <div class="card-body overflow-auto">
        <table id="claimTable" class="table table-flush w-100"><thead><tr><th>Sr.No</th><th>Family Group</th><th>Proposer Name</th><th>Policy No.</th><th>Insured Member</th><th>Amount</th><th>Received</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>@forelse($claims as $claim)<tr>
            <td>{{ $loop->iteration }}</td><td>{{ $claim->familyGroup?->group_name ?? $claim->policy?->familyGroup?->group_name ?? 'N/A' }}</td><td>{{ $claim->proposer?->name ?? $claim->policy?->holder?->name ?? 'N/A' }}</td><td>{{ $claim->policy?->policy_number_no ?? 'N/A' }}</td><td>{{ $claim->insuredMember?->member_name ?? 'N/A' }}</td>
            <td>{{ number_format((float)$claim->claim_amount, 2) }}</td><td>{{ $claim->claim_file_received_date?->format('d-m-Y') }}</td><td class="claim-submitted-cell">@if($claim->claim_file_submit_date)<span>{{ $claim->claim_file_submit_date->format('d-m-Y') }}</span>@else<form method="POST" action="{{ route('claim-tracker.submitted-date', $claim) }}" class="m-0">@csrf @method('PATCH')<input type="date" name="claim_file_submit_date" class="form-control form-control-sm claim-submitted-date" min="{{ $claim->claim_file_received_date?->format('Y-m-d') }}" aria-label="Claim submitted date" onchange="if (this.value && this.checkValidity()) { this.form.submit(); }"></form>@endif</td>
            <td><form method="POST" action="{{ route('claim-tracker.status', $claim) }}" class="m-0">@csrf @method('PATCH')<select name="claim_status" class="form-select form-select-sm claim-status-select" onchange="this.form.submit()">
                @foreach(['Processed', 'Query', 'Closed', 'Rejected'] as $status)<option value="{{ $status }}" @selected($claim->claim_status === $status)>{{ $status }}</option>@endforeach
            </select></form></td>
            <td class="text-nowrap"><a href="{{ route('claim-tracker.show', $claim) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-eye"></i></a> <a href="{{ route('claim-tracker.edit', $claim) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-edit"></i></a>
            <form action="{{ route('claim-tracker.destroy', $claim) }}" method="POST" class="d-inline delete-claim">@csrf @method('DELETE')<button type="submit" class="btn btn-sm crm-action-btn" title="Delete"><i class="fa fa-trash"></i></button></form></td>
        </tr>@empty
            <tr><td colspan="10" class="text-center py-5">
                <div style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                    <i class="fa-solid fa-clipboard-list d-block" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                    <p class="mb-3">No claims found.</p>
                    <a href="{{ route('claim-tracker.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Claim</a>
                </div>
            </td></tr>
        @endforelse</tbody></table>
    </div>
</div></div>
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    #claimTable th { font-size: 13px; }
    #claimTable td { font-size: 15px; vertical-align: middle; }
    .claim-status-select {
        min-width: 118px;
        height: 34px;
        border: 1px solid #d2d6da;
        border-radius: 6px;
        background-color: #fff;
        color: #344767;
        font-size: 13px;
        font-weight: 600;
        padding: .3rem 2rem .3rem .65rem;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }
    .claim-status-select:hover { border-color: #a8b3c5; }
    .claim-status-select:focus { border-color: #5e72e4; box-shadow: 0 0 0 2px rgba(94, 114, 228, .14); }
    .claim-status-select:disabled { background-color: #f8f9fa; cursor: wait; opacity: .75; }
    .claim-submitted-date { min-width: 142px; height: 34px; border-color: #d2d6da; color: #344767; font-size: 13px; cursor: pointer; }
    .claim-submitted-date:focus { border-color: #5e72e4; box-shadow: 0 0 0 2px rgba(94, 114, 228, .14); }
    #claimFilterCollapse .select2-container { width: 100% !important; }
    #claimFilterCollapse .select2-selection--single { height: 31px; border-color: #d2d6da; font-size: .875rem; }
    #claimFilterCollapse .select2-selection__rendered { line-height: 29px; padding-left: .5rem; }
    #claimFilterCollapse .select2-selection__arrow { height: 29px; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    const toast = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2500, timerProgressBar:true });
    @if(session('success')) toast.fire({icon:'success', title:@json(session('success'))}); @endif
    @if($claims->isNotEmpty())
    const claimTable = $('#claimTable').DataTable({pageLength:10,dom:'rtip',columnDefs:[{targets:[0,9],orderable:false}]});
    $('#claimsSearch').on('input', function(){ claimTable.search(this.value).draw(); });
    $('#claimsPerPage').on('change', function(){ claimTable.page.len(Number(this.value)).draw(); });
    @endif
    $('.claim-filter-select2').each(function() {
        $(this).select2({width:'100%', placeholder:$(this).data('placeholder'), allowClear:true});
    });
    $('#claimFiltersForm').on('change', 'input, select', function() {
        this.form.submit();
    });

    $(document).on('submit', '.delete-claim', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush
@endsection
