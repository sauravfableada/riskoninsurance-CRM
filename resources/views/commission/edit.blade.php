@extends('layouts.masters')

@section('page_title', 'Masters - Edit Commission')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single { height: 44px; border: 1px solid #dee2e6; border-radius: .375rem; }
        .select2-container .select2-selection--single .select2-selection__rendered { line-height: 42px; padding-left: 14px; }
        .select2-container .select2-selection--single .select2-selection__arrow { height: 42px; right: 8px; }
        .card-summary-pill {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            transition: all .2s ease;
        }
    </style>
@endpush

@section('masters_content')
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h4 mb-1 fw-semibold">Edit Commission (Pay-In & Pay-Out)</h1>
                <p class="text-muted small mb-0">Modify company-wise / type-wise Pay-In (Admin earnings) and Pay-Out (Sub-agent commission).</p>
            </div>
            <a href="{{ route('commission-rules.index') }}" class="btn btn-dark-blue">
                <i class="fa-solid fa-angle-left pe-1"></i>
                <span>Back</span>
            </a>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <form action="{{ route('commission-rules.update', $commissionRule->id) }}" method="POST" id="commissionEditForm">
            @csrf
            @method('PUT')

            <!-- Scope Selection (Company, Policy Type, Policy) -->
            <div class="row g-3 mb-4">
                {{-- Insurance Company --}}
                <div class="col-md-4">
                    <label for="company_id" class="form-label fw-semibold">Insurance Company <span class="text-danger">*</span></label>
                    <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        <option value="">-- Select Insurance Company --</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}" {{ (old('company_id', $commissionRule->company_id) == $comp->id || $commissionRule->company?->name == $comp->name) ? 'selected' : '' }}>
                                {{ $comp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Policy Type (Optional or All Types) --}}
                <div class="col-md-4">
                    <label for="policy_type_id" class="form-label fw-semibold">Policy Type (Category)</label>
                    <select name="policy_type_id" id="policy_type_id" class="form-select @error('policy_type_id') is-invalid @enderror">
                        <option value="">-- All Policy Types (Entire Company) --</option>
                        @foreach ($policyTypes as $type)
                            <option value="{{ $type->id }}" {{ old('policy_type_id', $commissionRule->policy_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Leave empty to apply across all policy types under this company.</small>
                </div>

                {{-- Specific Policy (Optional) --}}
                <div class="col-md-4">
                    <label for="policy_id" class="form-label fw-semibold">Specific Policy (Optional)</label>
                    <select name="policy_id" id="policy_id" class="form-select @error('policy_id') is-invalid @enderror">
                        <option value="">-- Apply to All Policies (General Rule) --</option>
                        @if($commissionRule->policy)
                            <option value="{{ $commissionRule->policy_id }}" selected>
                                {{ $commissionRule->policy->policy_sub_type?->name ?? 'Policy' }} — {{ $commissionRule->policy->holder?->name ?? 'Customer' }} ({{ $commissionRule->policy->policy_number ?? '#' . $commissionRule->policy_id }})
                            </option>
                        @endif
                    </select>
                </div>
            </div>

            <!-- SELECTED POLICY PREMIUM (HIDDEN BY DEFAULT, ONLY SHOWN WHEN A POLICY IS SELECTED) -->
            <div id="selectedPolicyCard" class="card border border-info border-opacity-50 rounded-4 mb-4 shadow-sm" style="{{ $commissionRule->policy ? '' : 'display: none;' }} background: #f0f9ff;">
                <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-info fs-4"></i>
                        <div>
                            <span class="fw-bold text-dark">Selected Policy Gross Premium:</span>
                            <small class="text-muted d-block">Premium amount fetched directly from this policy record</small>
                        </div>
                    </div>
                    <div class="h3 fw-bolder text-primary mb-0" id="selectedPolicyPremiumDisplay">₹{{ number_format($commissionRule->policy?->premium_amount ?? 0, 2) }}</div>
                </div>
            </div>

            <!-- Sub-Agent & Effective Date -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="agent_id" class="form-label fw-semibold">Sub-Agent / Staff (Optional)</label>
                    <select name="agent_id" id="agent_id" class="form-select">
                        <option value="">-- All Sub-Agents / Default --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('agent_id', $commissionRule->agent_id) == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }} ({{ ucfirst($agent->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="commission_on" class="form-label fw-semibold">Effective Date</label>
                    <input type="date" name="commission_on" id="commission_on" class="form-control" value="{{ old('commission_on', $commissionRule->commission_on?->format('Y-m-d') ?? date('Y-m-d')) }}">
                </div>
            </div>

            <hr class="my-4">

            <!-- PAY-IN AND PAY-OUT SIDE-BY-SIDE CONFIGURATION -->
            <div class="row g-4 mb-4">
                {{-- 1. PAY-IN (Admin's Commission from Insurer) --}}
                <div class="col-md-6">
                    <div class="card h-100 border-primary border-opacity-25 shadow-sm rounded-4" style="background: #f8fafc;">
                        <div class="card-header bg-primary text-white py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="fa-solid fa-arrow-down-left me-1"></i> PAY-IN (Admin Commission)</span>
                            <span class="badge bg-white text-primary">From Company</span>
                        </div>
                        <div class="card-body p-3">
                            <p class="text-muted small mb-3">Commission received by Admin from the insurance company (e.g. 20% on ₹50,000).</p>
                            
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Pay-In Type <span class="text-danger">*</span></label>
                                    <select name="pay_in_type" id="pay_in_type" class="form-select" required>
                                        <option value="percentage" {{ old('pay_in_type', $commissionRule->pay_in_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>% (Percentage)</option>
                                        <option value="flat" {{ old('pay_in_type', $commissionRule->pay_in_type) === 'flat' ? 'selected' : '' }}>Fixed (₹ Amount)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Pay-In Value <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="pay_in_value" id="pay_in_value" class="form-control" value="{{ old('pay_in_value', $commissionRule->pay_in_value > 0 ? $commissionRule->pay_in_value : ($commissionRule->commission_value ?: 20)) }}" required>
                                        <span class="input-group-text pay-in-unit">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-summary-pill mt-3 text-center border-primary border-opacity-25 bg-white">
                                <div class="text-muted small fw-semibold">Admin Total Commission</div>
                                <div class="h3 fw-bolder text-primary mb-0 mt-1" id="displayPayInAmount">₹0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. PAY-OUT (Sub-Agent Payout) --}}
                <div class="col-md-6">
                    <div class="card h-100 border-warning border-opacity-50 shadow-sm rounded-4" style="background: #fffbeb;">
                        <div class="card-header bg-warning text-dark py-2 px-3 rounded-top-4 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> PAY-OUT (Agent / Sub-Agent)</span>
                            <span class="badge bg-dark text-white">To Sub-Agent</span>
                        </div>
                        <div class="card-body p-3">
                            <p class="text-muted small mb-3">Share given to sub-agent (e.g. 10% from Admin's Pay-In or % of premium).</p>

                            <div class="row g-3">
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Pay-Out Type <span class="text-danger">*</span></label>
                                    <select name="pay_out_type" id="pay_out_type" class="form-select" required>
                                        <option value="percentage" {{ old('pay_out_type', $commissionRule->pay_out_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>% (Percentage)</option>
                                        <option value="flat" {{ old('pay_out_type', $commissionRule->pay_out_type) === 'flat' ? 'selected' : '' }}>Fixed (₹ Amount)</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Calculation Base</label>
                                    <select name="pay_out_based_on" id="pay_out_based_on" class="form-select">
                                        <option value="admin_commission" {{ old('pay_out_based_on', $commissionRule->pay_out_based_on ?? 'admin_commission') === 'admin_commission' ? 'selected' : '' }}>% of Admin Pay-In</option>
                                        <option value="premium" {{ old('pay_out_based_on', $commissionRule->pay_out_based_on) === 'premium' ? 'selected' : '' }}>% of Gross Premium</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Pay-Out Value <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="pay_out_value" id="pay_out_value" class="form-control" value="{{ old('pay_out_value', $commissionRule->pay_out_value > 0 ? $commissionRule->pay_out_value : ($commissionRule->commission_value ?: 10)) }}" required>
                                        <span class="input-group-text pay-out-unit">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-summary-pill mt-3 text-center border-warning border-opacity-50 bg-white">
                                <div class="text-muted small fw-semibold">Sub-Agent Share</div>
                                <div class="h3 fw-bolder text-warning mb-0 mt-1 text-dark" id="displayPayOutAmount">₹0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROFIT & SUMMARY BANNER -->
            <div class="card border border-success border-2 rounded-4 overflow-hidden mb-4 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; background: #059669; color: #ffffff;">
                            <i class="fa-solid fa-hand-holding-dollar fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.15rem; color: #064e3b !important;">Admin Net Earning (Profit)</h5>
                            <span class="text-secondary fw-semibold small">Pay-In received (₹) minus Pay-Out given (₹)</span>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <div class="h2 fw-bolder mb-0 text-success" id="displayNetProfit" style="color: #047857 !important; font-size: 2.2rem;">₹0.00</div>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1 fw-bold small">Retained by Admin</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center gap-2">
                <a href="{{ route('commission-rules.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-dark-blue px-4 ms-auto">Update Commission Rule</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const companySelect = document.getElementById('company_id');
                const policyTypeSelect = document.getElementById('policy_type_id');
                const policySelect = document.getElementById('policy_id');

                const payInType = document.getElementById('pay_in_type');
                const payInValue = document.getElementById('pay_in_value');
                const payOutType = document.getElementById('pay_out_type');
                const payOutValue = document.getElementById('pay_out_value');
                const payOutBasedOn = document.getElementById('pay_out_based_on');

                const displayPayInAmount = document.getElementById('displayPayInAmount');
                const displayPayOutAmount = document.getElementById('displayPayOutAmount');
                const displayNetProfit = document.getElementById('displayNetProfit');

                $('#company_id, #policy_type_id, #policy_id, #agent_id').select2({
                    width: '100%',
                    allowClear: false
                });

                let selectedPolicyGross = {{ (float) ($commissionRule->policy?->premium_amount ?? 0) }};

                $(policySelect).on('change', function() {
                    const polId = this.value;
                    const card = document.getElementById('selectedPolicyCard');
                    const displayAmt = document.getElementById('selectedPolicyPremiumDisplay');

                    if (!polId) {
                        selectedPolicyGross = 0;
                        if (card) card.style.display = 'none';
                        calculateCommissionSummary();
                        return;
                    }

                    fetch(`{{ url('/commission/get-policy-details') }}/${polId}`)
                        .then(r => r.json())
                        .then(d => {
                            selectedPolicyGross = parseFloat(d.premium_amount) || 0;
                            if (card && displayAmt) {
                                card.style.display = 'block';
                                displayAmt.textContent = '₹' + selectedPolicyGross.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                            calculateCommissionSummary();
                        })
                        .catch(() => {
                            selectedPolicyGross = 0;
                            if (card) card.style.display = 'none';
                            calculateCommissionSummary();
                        });
                });

                function calculateCommissionSummary() {
                    const inType = payInType.value;
                    const inVal = parseFloat(payInValue.value) || 0;
                    const outType = payOutType.value;
                    const outVal = parseFloat(payOutValue.value) || 0;
                    const outBase = payOutBasedOn.value;

                    // Update unit labels
                    $('.pay-in-unit').text(inType === 'percentage' ? '%' : '₹');
                    $('.pay-out-unit').text(outType === 'percentage' ? '%' : '₹');

                    if (selectedPolicyGross > 0) {
                        let payInAmt = 0;
                        if (inType === 'percentage') {
                            payInAmt = (selectedPolicyGross * inVal) / 100;
                        } else {
                            payInAmt = inVal;
                        }

                        let payOutAmt = 0;
                        if (outType === 'percentage') {
                            const base = (outBase === 'premium') ? selectedPolicyGross : payInAmt;
                            payOutAmt = (base * outVal) / 100;
                        } else {
                            payOutAmt = outVal;
                        }

                        const netProfit = Math.max(0, payInAmt - payOutAmt);

                        displayPayInAmount.textContent = '₹' + payInAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + inVal + '%)';
                        displayPayOutAmount.textContent = '₹' + payOutAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + outVal + '%)';
                        displayNetProfit.textContent = '₹' + netProfit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        return;
                    }

                    if (inType === 'percentage') {
                        displayPayInAmount.textContent = inVal + '%';

                        if (outType === 'percentage') {
                            if (outBase === 'admin_commission') {
                                const effectiveAgentPct = (inVal * outVal) / 100;
                                const effectiveAdminPct = Math.max(0, inVal - effectiveAgentPct);
                                displayPayOutAmount.textContent = outVal + '% of Pay-In (' + effectiveAgentPct.toFixed(2) + '%)';
                                displayNetProfit.textContent = effectiveAdminPct.toFixed(2) + '%';
                            } else {
                                const effectiveAdminPct = Math.max(0, inVal - outVal);
                                displayPayOutAmount.textContent = outVal + '% (Gross)';
                                displayNetProfit.textContent = effectiveAdminPct.toFixed(2) + '%';
                            }
                        } else {
                            displayPayOutAmount.textContent = '₹' + outVal.toFixed(2);
                            displayNetProfit.textContent = inVal + '% - ₹' + outVal.toFixed(2);
                        }
                    } else {
                        displayPayInAmount.textContent = '₹' + inVal.toFixed(2);
                        if (outType === 'percentage') {
                            const agentAmt = (inVal * outVal) / 100;
                            const profit = Math.max(0, inVal - agentAmt);
                            displayPayOutAmount.textContent = '₹' + agentAmt.toFixed(2) + ' (' + outVal + '%)';
                            displayNetProfit.textContent = '₹' + profit.toFixed(2);
                        } else {
                            const profit = Math.max(0, inVal - outVal);
                            displayPayOutAmount.textContent = '₹' + outVal.toFixed(2);
                            displayNetProfit.textContent = '₹' + profit.toFixed(2);
                        }
                    }
                }

                payInType.addEventListener('change', calculateCommissionSummary);
                payInValue.addEventListener('input', calculateCommissionSummary);
                payOutType.addEventListener('change', calculateCommissionSummary);
                payOutValue.addEventListener('input', calculateCommissionSummary);
                payOutBasedOn.addEventListener('change', calculateCommissionSummary);

                calculateCommissionSummary();

                $('#commissionEditForm').on('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = $(this).find('button[type="submit"]');
                    const origText = submitBtn.html();
                    const formData = new FormData(this);

                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Updating...');

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: res.message || 'Commission rule successfully updated.',
                                confirmButtonColor: '#2563eb'
                            }).then(() => {
                                window.location.href = "{{ route('commission-rules.index') }}";
                            });
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(origText);
                            let msg = 'Failed to update commission rule.';
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire({ icon: 'error', title: 'Error', html: msg });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
