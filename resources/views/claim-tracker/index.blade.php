@extends('layouts.app')
@section('page_title', 'Claim Tracker')
@section('content')
<div class="container-fluid p-0"><div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div><h4 class="fw-bold mb-0">Claim Tracker</h4><p class="text-muted small mb-0">Manage and track insurance claims.</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('claim-tracker.export', request()->query()) }}" class="btn btn-outline-dark-blue"><i class="fa-solid fa-download me-1"></i>Export</a>
                <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#selectPoliciesModal">
                    <i class="fa-solid fa-square-check me-1"></i>Select Policies
                </button>
                <a href="{{ route('claim-tracker.create') }}" class="btn btn-outline-dark-blue"><i class="fa fa-plus me-1"></i>Add Claim</a>
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
        <table id="claimTable" class="table table-flush w-100">
            <thead>
                <tr>
                    <th>Sr.No</th>
                    <th>Family Group</th>
                    <th>Proposer Name</th>
                    <th>Policy No.</th>
                    <th>Insured Member</th>
                    <th>Amount</th>
                    <th>Received</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
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

<!-- Modal: Select Policies for Claim Tracker -->
<div class="modal fade" id="selectPoliciesModal" tabindex="-1" aria-labelledby="selectPoliciesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4" style="background-color: #0f172a;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-1" id="selectPoliciesModalLabel">
                        <i class="fa-solid fa-square-check me-2 text-primary"></i>Select Policies for Claim Tracker
                    </h5>
                    <p class="text-white-50 small mb-0">Directly tick or untick any policy below to include or remove it from Claim Tracker.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div class="input-group" style="max-width: 400px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="modalPolicySearch" class="form-control border-start-0" placeholder="Search by policy number, holder, company...">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                            <i class="fa fa-clipboard-check me-1"></i><span id="trackedCountDisplay">{{ count($trackedPolicyIds ?? []) }}</span> Policies in Claim Tracker
                        </span>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive" style="max-height: 480px;">
                        <table class="table table-hover align-middle mb-0" id="modalPoliciesTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <input type="checkbox" class="form-check-input" id="modalBulkCheck" title="Select / Deselect visible" style="cursor: pointer; width: 18px; height: 18px;">
                                    </th>
                                    <th>Policy No.</th>
                                    <th>Policy Holder</th>
                                    <th>Policy Type</th>
                                    <th>Insurance Company</th>
                                    <th>Validity</th>
                                    <th>Premium</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($availablePolicies ?? []) as $p)
                                    @php($isTracked = in_array($p->id, $trackedPolicyIds ?? []))
                                    <tr data-policy-id="{{ $p->id }}" class="modal-policy-row {{ $isTracked ? 'table-success-subtle' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox"
                                                class="form-check-input modal-policy-checkbox claim-tracker-toggle-checkbox"
                                                data-policy-id="{{ $p->id }}"
                                                data-policy-number="{{ $p->policy_number_no ?: $p->policy_number }}"
                                                {{ $isTracked ? 'checked' : '' }}
                                                title="Tick to show in Claim Tracker"
                                                style="cursor: pointer; width: 18px; height: 18px;">
                                        </td>
                                        <td class="fw-bold font-monospace text-primary">{{ $p->policy_number_no ?: $p->policy_number }}</td>
                                        <td class="fw-semibold">{{ $p->holder?->name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $p->policyType?->name ?? 'N/A' }}</span></td>
                                        <td>{{ $p->company?->name ?? 'N/A' }}</td>
                                        <td class="small text-muted">
                                            {{ $p->start_date ? date('d-m-Y', strtotime($p->start_date)) : '--' }}
                                            <i class="fa-solid fa-arrow-right-long text-muted mx-1" style="font-size: 10px;"></i>
                                            {{ $p->end_date ? date('d-m-Y', strtotime($p->end_date)) : '--' }}
                                        </td>
                                        <td class="fw-semibold">₹{{ number_format((float) ($p->premium_amount ?? 0), 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge modal-status-badge {{ $isTracked ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-1">
                                                {{ $isTracked ? 'Tracked' : 'Not Tracked' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No policies available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="fa fa-info-circle me-1 text-primary"></i>Ticking any policy updates Claim Tracker in real-time.</span>
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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

    let claimTrackerChanged = false;

    // Toggle single policy checkbox (both in main table and modal)
    $(document).on('change', '.claim-tracker-toggle-checkbox', function(e) {
        e.stopPropagation();
        const $checkbox = $(this);
        const policyId = $checkbox.data('policy-id');
        const policyNumber = $checkbox.data('policy-number') || ('#' + policyId);
        const isChecked = $checkbox.is(':checked');

        $checkbox.prop('disabled', true);

        $.ajax({
            url: '{{ route("claim-tracker.toggle-policy") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                policy_id: policyId,
                track: isChecked ? 1 : 0
            },
            success: function(res) {
                $checkbox.prop('disabled', false);
                claimTrackerChanged = true;

                // Sync all checkboxes matching this policyId
                $('.claim-tracker-toggle-checkbox[data-policy-id="' + policyId + '"]').prop('checked', res.tracked);

                // Update modal row styling & badge if present
                const $modalRow = $('.modal-policy-row[data-policy-id="' + policyId + '"]');
                if ($modalRow.length) {
                    const $badge = $modalRow.find('.modal-status-badge');
                    if (res.tracked) {
                        $badge.removeClass('bg-secondary').addClass('bg-success').text('Tracked');
                        $modalRow.addClass('table-success-subtle');
                    } else {
                        $badge.removeClass('bg-success').addClass('bg-secondary').text('Not Tracked');
                        $modalRow.removeClass('table-success-subtle');
                    }
                }

                // Update count badge
                const curCount = parseInt($('#trackedCountDisplay').text()) || 0;
                $('#trackedCountDisplay').text(res.tracked ? curCount + 1 : Math.max(0, curCount - 1));

                toast.fire({
                    icon: res.tracked ? 'success' : 'info',
                    title: res.message || (res.tracked ? 'Policy added to Claim Tracker' : 'Policy removed from Claim Tracker')
                });
            },
            error: function(xhr) {
                $checkbox.prop('disabled', false);
                $checkbox.prop('checked', !isChecked);
                Swal.fire('Error', xhr.responseJSON?.message || 'Could not update Claim Tracker status.', 'error');
            }
        });
    });

    // Modal search filtering
    $('#modalPolicySearch').on('input', function() {
        const term = $(this).val().toLowerCase().trim();
        $('#modalPoliciesTable tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(term) > -1);
        });
    });

    // Modal bulk check
    $('#modalBulkCheck').on('change', function() {
        const isChecked = $(this).is(':checked');
        const policyIds = [];
        $('#modalPoliciesTable tbody tr:visible .modal-policy-checkbox').each(function() {
            policyIds.push($(this).data('policy-id'));
        });

        if (policyIds.length === 0) return;

        $('#modalBulkCheck').prop('disabled', true);
        $.ajax({
            url: '{{ route("claim-tracker.bulk-toggle-policies") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                policy_ids: policyIds,
                track: isChecked ? 1 : 0
            },
            success: function(res) {
                $('#modalBulkCheck').prop('disabled', false);
                claimTrackerChanged = true;
                policyIds.forEach(function(pid) {
                    const $row = $('.modal-policy-row[data-policy-id="' + pid + '"]');
                    $row.find('.modal-policy-checkbox').prop('checked', isChecked);
                    const $badge = $row.find('.modal-status-badge');
                    if (isChecked) {
                        $badge.removeClass('bg-secondary').addClass('bg-success').text('Tracked');
                        $row.addClass('table-success-subtle');
                    } else {
                        $badge.removeClass('bg-success').addClass('bg-secondary').text('Not Tracked');
                        $row.removeClass('table-success-subtle');
                    }
                });
                toast.fire({ icon: 'success', title: res.message });
            },
            error: function(xhr) {
                $('#modalBulkCheck').prop('disabled', false).prop('checked', !isChecked);
                Swal.fire('Error', xhr.responseJSON?.message || 'Bulk update failed.', 'error');
            }
        });
    });

    // Reload Claim Tracker table if policies were added or removed in modal
    $('#selectPoliciesModal').on('hidden.bs.modal', function () {
        if (claimTrackerChanged) {
            window.location.reload();
        }
    });
});
</script>
@endpush
@endsection
