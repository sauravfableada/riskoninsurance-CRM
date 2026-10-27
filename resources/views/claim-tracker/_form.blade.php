@php($selectedFamilyGroup = old('family_group_id', $claimTracker->family_group_id ?? $claimTracker->policy?->family_group_id ?? ''))
@php($selectedProposer = old('proposer_id', $claimTracker->proposer_id ?? $claimTracker->policy?->holder_id ?? ''))
@php($selectedPolicy = old('policy_id', $claimTracker->policy_id ?? ''))
@php($selectedInsuredMember = old('insured_member_id', $claimTracker->insured_member_id ?? ''))
@php($selectedIssuer = old('issued_by', $claimTracker->issued_by ?? auth()->id()))
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Family Group <span class="text-danger">*</span></label>
        <select name="family_group_id" id="family_group_id" class="form-select select2 @error('family_group_id') is-invalid @enderror" required>
            <option value="">Search and select family group</option>
            @foreach($familyGroups as $familyGroup)<option value="{{ $familyGroup->id }}" @selected((string)$selectedFamilyGroup === (string)$familyGroup->id)>{{ $familyGroup->group_name }}</option>@endforeach
        </select>
        @error('family_group_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Proposer Name <span class="text-danger">*</span></label>
        <select name="proposer_id" id="proposer_id" class="form-select select2 @error('proposer_id') is-invalid @enderror" required disabled><option value="">Select family group first</option></select>
        @error('proposer_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Policy No. <span class="text-danger">*</span></label>
        <select name="policy_id" id="policy_id" class="form-select select2 @error('policy_id') is-invalid @enderror" required disabled><option value="">Select proposer first</option></select>
        @error('policy_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Insured Member <span class="text-danger">*</span></label>
        <select name="insured_member_id" id="insured_member_id" class="form-select select2 @error('insured_member_id') is-invalid @enderror" required disabled><option value="">Select family group first</option></select>
        @error('insured_member_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Insurance Company</label>
        <input id="company_name" class="form-control" readonly placeholder="Auto-filled from policy">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Claim Amount <span class="text-danger">*</span></label>
        <input type="number" min="0" step="0.01" name="claim_amount" class="form-control @error('claim_amount') is-invalid @enderror" value="{{ old('claim_amount', $claimTracker->claim_amount ?? '') }}" required>
        @error('claim_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label"><i class="fa-solid fa-user-tie text-muted me-1"></i> Claim Issued By <span class="text-danger">*</span></label>
        <select name="issued_by" class="form-select select2 @error('issued_by') is-invalid @enderror" required>
            @foreach($issuers as $issuer)
                <option value="{{ $issuer->id }}" @selected((string)$selectedIssuer === (string)$issuer->id)>{{ $issuer->name }}{{ $issuer->id === auth()->id() ? ' (You)' : '' }}</option>
            @endforeach
        </select>
        @error('issued_by')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Claim File</label>
        <input type="file" name="claim_file" class="form-control claim-file-input @error('claim_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        @if(isset($claimTracker) && $claimTracker->claim_file)<small><a href="{{ Storage::url($claimTracker->claim_file) }}" target="_blank">View current file</a></small>@endif
        @error('claim_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Claim File Received Date <span class="text-danger">*</span></label>
        <input type="date" name="claim_file_received_date" data-allow-past-date="true" class="form-control @error('claim_file_received_date') is-invalid @enderror" value="{{ old('claim_file_received_date', isset($claimTracker) ? $claimTracker->claim_file_received_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('claim_file_received_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Claim File Submit Date</label>
        <input type="date" name="claim_file_submit_date" data-allow-past-date="true" class="form-control @error('claim_file_submit_date') is-invalid @enderror" value="{{ old('claim_file_submit_date', isset($claimTracker) ? $claimTracker->claim_file_submit_date?->format('Y-m-d') : '') }}">
        @error('claim_file_submit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Claim Status <span class="text-danger">*</span></label>
        <select name="claim_status" class="form-select" required>
            @foreach(['Processed', 'Query', 'Closed', 'Rejected'] as $status)<option value="{{ $status }}" @selected(old('claim_status', $claimTracker->claim_status ?? 'Processed') === $status)>{{ $status }}</option>@endforeach
        </select>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container{width:100%!important}.select2-container .select2-selection--single{height:42px;padding:6px}.select2-selection__arrow{height:40px!important}
    .claim-file-input { height: 38px !important; min-height: 38px !important; padding: .25rem .75rem !important; line-height: 1.5; }
    .claim-file-input::file-selector-button { margin: -.25rem .75rem -.25rem -.75rem !important; padding: .25rem .75rem !important; height: 38px; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('.select2').select2({ width: '100%' });

    const initial = {
        familyGroup: @json((string) $selectedFamilyGroup), proposer: @json((string) $selectedProposer),
        policy: @json((string) $selectedPolicy), insuredMember: @json((string) $selectedInsuredMember)
    };
    const proposerUrl = @json(route('claim-tracker.proposers', ['familyGroup' => '__GROUP__']));
    const policiesUrl = @json(route('claim-tracker.policies', ['familyGroup' => '__GROUP__', 'proposer' => '__PROPOSER__']));
    const membersUrl = @json(route('claim-tracker.insured-members', ['familyGroup' => '__GROUP__']));

    function resetSelect(selector, message) {
        $(selector).prop('disabled', true).empty().append(new Option(message, '')).val('').trigger('change.select2');
    }
    function loadOptions(selector, url, placeholder, selected, render) {
        resetSelect(selector, 'Loading...');
        return $.getJSON(url).then(function(items) {
            const select = $(selector).empty().append(new Option(placeholder, '')).prop('disabled', false);
            items.forEach(function(item) {
                const rendered = render(item);
                const option = new Option(rendered.text, item.id, false, String(item.id) === String(selected || ''));
                if (rendered.company) $(option).attr('data-company', rendered.company);
                select.append(option);
            });
            select.trigger('change.select2');
            if (!items.length) resetSelect(selector, 'No records found');
        }).fail(function() {
            resetSelect(selector, 'Unable to load options');
            Swal.fire('Error', 'Unable to load dropdown options. Please try again.', 'error');
        });
    }
    function loadPolicies(selected) {
        const group = $('#family_group_id').val(), proposer = $('#proposer_id').val();
        resetSelect('#policy_id', proposer ? 'Loading...' : 'Select proposer first');
        $('#company_name').val('');
        if (!group || !proposer) return $.Deferred().resolve().promise();
        const url = policiesUrl.replace('__GROUP__', group).replace('__PROPOSER__', proposer);
        return loadOptions('#policy_id', url, 'Search and select policy', selected, function(item) {
            return {text: item.policy_number_no, company: item.company ? item.company.name : ''};
        }).then(fillPolicyDetails);
    }
    function loadFamilyGroup(selectedProposer, selectedMember, selectedPolicy) {
        const group = $('#family_group_id').val();
        resetSelect('#proposer_id', group ? 'Loading...' : 'Select family group first');
        resetSelect('#insured_member_id', group ? 'Loading...' : 'Select family group first');
        resetSelect('#policy_id', 'Select proposer first');
        $('#company_name').val('');
        if (!group) return;
        $.when(
            loadOptions('#proposer_id', proposerUrl.replace('__GROUP__', group), 'Search and select proposer', selectedProposer, item => ({text:item.name})),
            loadOptions('#insured_member_id', membersUrl.replace('__GROUP__', group), 'Search and select insured member', selectedMember, item => ({text:item.member_name + (item.relation ? ' (' + item.relation + ')' : '')}))
        ).then(function() { if (selectedProposer) loadPolicies(selectedPolicy); });
    }
    function fillPolicyDetails() {
        $('#company_name').val($('#policy_id option:selected').data('company') || '');
    }
    $('#family_group_id').on('change', function() { loadFamilyGroup('', '', ''); });
    $('#proposer_id').on('change', function() { loadPolicies(''); });
    $('#policy_id').on('change', fillPolicyDetails);
    if (initial.familyGroup) loadFamilyGroup(initial.proposer, initial.insuredMember, initial.policy);
});
</script>
@endpush
