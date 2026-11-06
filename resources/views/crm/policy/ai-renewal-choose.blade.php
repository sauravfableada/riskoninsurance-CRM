@extends('layouts.app')
@section('page_title','AI Policy Renewal')
@push('styles')
<style>
.ai-renewal-page .renewal-header { align-items: flex-start; }
.ai-renewal-page .renewal-header-copy { flex: 1 1 420px; min-width: 0; }
.ai-renewal-page .renewal-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 9px 16px; border-radius: 10px; font-size: .85rem; font-weight: 600; line-height: 1.4; white-space: nowrap; text-decoration: none; transition: background .15s ease, box-shadow .15s ease; }
.ai-renewal-page .renewal-btn-primary { background: linear-gradient(135deg, #15385e, #245388); border: 1px solid #245388; color: #fff; box-shadow: 0 2px 6px rgba(21,56,94,.14); }
.ai-renewal-page .renewal-btn-primary:hover { background: #15385e; border-color: #15385e; color: #fff; box-shadow: 0 4px 10px rgba(21,56,94,.22); }
.ai-renewal-page .renewal-btn-back { background: #fff; border: 1px solid #dbe3ee; color: #15385e; flex-shrink: 0; }
.ai-renewal-page .renewal-btn-back:hover { background: #f1f5f9; border-color: #aebed1; }
.ai-renewal-page .renewal-btn:focus-visible { outline: 3px solid #93c5fd; outline-offset: 3px; }
.ai-renewal-page .renewal-actions { width: 180px; min-width: 180px; text-align: right; padding-right: 18px; padding-left: 24px; }
.ai-renewal-page .renewal-coverage { white-space: nowrap; }
.ai-renewal-page .table td { padding-top: 16px; padding-bottom: 16px; }
[data-theme="dark"] .ai-renewal-page .renewal-btn-back { background: #1e293b; border-color: #475569; color: #e2e8f0; }
[data-theme="dark"] .ai-renewal-page .renewal-btn-back:hover { background: #334155; }
[data-theme="dark"] .ai-renewal-page .renewal-btn-primary { background: linear-gradient(135deg, #1e4776, #2c6197); border-color: #4776a6; }
@media (max-width: 575.98px) { .ai-renewal-page .renewal-header-copy { flex-basis: 100%; } }
</style>
@endpush
@section('content')
<div class="card border-0 rounded-4 shadow-sm ai-renewal-page"><div class="card-body p-4">
<div class="d-flex justify-content-between gap-3 flex-wrap mb-3 renewal-header"><div class="renewal-header-copy"><h1 class="h4 fw-bold">AI policy renewal</h1><p class="small text-muted mb-0">Choose the exact existing policy before uploading its renewed PDF. Policies are never linked automatically by number alone.</p></div><a class="btn renewal-btn renewal-btn-back" href="{{ route('policies.legacy.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Policies</a></div>
<form method="get" class="d-flex gap-2 mb-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search policy number or holder name" aria-label="Search existing policies"><button type="submit" class="btn renewal-btn renewal-btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Search</button></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Holder</th><th>Policy</th><th>Type / company</th><th class="renewal-coverage">Coverage</th><th class="renewal-actions">Action</th></tr></thead><tbody>
@forelse($policies as $policy)<tr><td>{{ $policy->holder?->name ?? '--' }}</td><td>{{ $policy->policy_number_no ?: $policy->policy_number }}</td><td>{{ $policy->policyType?->name }}<small class="d-block text-muted">{{ $policy->company?->name }}</small></td><td class="renewal-coverage">{{ $policy->start_date }}<small class="d-block text-muted">to {{ $policy->end_date }}</small></td><td class="renewal-actions"><a class="btn renewal-btn renewal-btn-primary" href="{{ route('fablead.ai.renewal',$policy) }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Renew with AI</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No accessible policies found.</td></tr>@endforelse
</tbody></table></div>{{ $policies->links() }}<p class="small text-muted mb-0">Genuinely new policy? <a href="{{ route('fablead.ai.index') }}">Use new-policy AI upload</a>.</p>
</div></div>
@endsection
