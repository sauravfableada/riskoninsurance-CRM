@extends('layouts.app')

@section('page_title', 'Claim Details')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-show.css' : '/css/users-show.css') }}?v={{ filemtime(public_path('css/users-show.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-semibold">Claim Details</h1>
                            <p class="text-muted small mb-0">Complete information about this insurance claim.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            <a href="{{ route('claim-tracker.edit', $claimTracker) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0"><i class="bi bi-pencil me-1"></i>Edit</a>
                            <a href="{{ route('claim-tracker.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0"><i class="fa-solid fa-angle-left pe-1"></i>Back</a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="detail-view-block">
                        <div class="detail-view-title mb-3">{{ $claimTracker->policy?->policy_number_no ?? 'Claim' }}</div>
                        <div class="row g-0 detail-view-grid">
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-people-roof text-muted me-2"></i>Family Group:</span><span class="detail-view-value">{{ $claimTracker->familyGroup?->group_name ?? $claimTracker->policy?->familyGroup?->group_name ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-user text-muted me-2"></i>Proposer Name:</span><span class="detail-view-value">{{ $claimTracker->proposer?->name ?? $claimTracker->policy?->holder?->name ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-shield-halved text-muted me-2"></i>Policy No.:</span><span class="detail-view-value">{{ $claimTracker->policy?->policy_number_no ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-user-shield text-muted me-2"></i>Insured Member:</span><span class="detail-view-value">{{ $claimTracker->insuredMember?->member_name ?? '--' }}{{ $claimTracker->insuredMember?->relation ? ' (' . $claimTracker->insuredMember->relation . ')' : '' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-building text-muted me-2"></i>Insurance Company:</span><span class="detail-view-value">{{ $claimTracker->policy?->company?->name ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-indian-rupee-sign text-muted me-2"></i>Claim Amount:</span><span class="detail-view-value">{{ number_format((float)$claimTracker->claim_amount, 2) }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-user-tie text-muted me-2"></i>Claim Issued By:</span><span class="detail-view-value">{{ $claimTracker->issuer?->name ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-toggle-on text-muted me-2"></i>Status:</span><span class="detail-view-value"><span class="badge {{ $claimTracker->claim_status === 'Closed' ? 'bg-success' : ($claimTracker->claim_status === 'Query' ? 'bg-warning' : 'bg-info') }} rounded-pill px-3">{{ $claimTracker->claim_status }}</span></span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-calendar-plus text-muted me-2"></i>File Received Date:</span><span class="detail-view-value">{{ $claimTracker->claim_file_received_date?->format('d M, Y') ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-calendar-check text-muted me-2"></i>File Submit Date:</span><span class="detail-view-value">{{ $claimTracker->claim_file_submit_date?->format('d M, Y') ?? '--' }}</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-file text-muted me-2"></i>Claim File:</span><span class="detail-view-value">@if($claimTracker->claim_file)<a href="{{ Storage::url($claimTracker->claim_file) }}" target="_blank" class="text-decoration-none link-hover"><i class="fa-solid fa-download me-1"></i>View / Download File</a>@else--@endif</span></div>
                            <div class="col-md-6 detail-view-row"><span class="detail-view-label pe-2"><i class="fas fa-calendar-plus text-muted me-2"></i>Created At:</span><span class="detail-view-value">{{ $claimTracker->created_at?->format('d M, Y h:i A') ?? '--' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
