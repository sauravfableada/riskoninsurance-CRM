@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- Notification messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Hero Promotional / Welcome Banner (Matched from Screenshot) -->
    <!-- <div class="card border-0 rounded-4 mb-4 overflow-hidden position-relative" 
         style="background: linear-gradient(108deg, #e0edff 0%, #dbeafe 35%, #bfdbfe 70%, #93c5fd 100%); min-height: 190px; box-shadow: 0 4px 20px -2px rgba(59, 130, 246, 0.15);">
        
       
        <div class="position-absolute" style="right: -40px; top: -50px; width: 280px; height: 280px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 70%); pointer-events: none;"></div>
        <div class="position-absolute" style="right: 250px; bottom: -60px; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(96,165,250,0.3) 0%, rgba(96,165,250,0) 70%); pointer-events: none;"></div>

        <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center position-relative" style="z-index: 1;">
            <div class="max-w-xl">
                <span class="text-primary fw-bold small text-uppercase tracking-wider" style="letter-spacing: 0.08em; font-size: 0.75rem;">
                    MANAGE YOUR INSURANCE
                </span>
                <h2 class="fw-bolder mt-1 mb-2 text-dark" style="font-size: 1.85rem; letter-spacing: -0.02em;">
                    Your Policies, All in One Place
                </h2>
                <p class="text-secondary mb-0" style="max-width: 520px; font-size: 0.95rem; line-height: 1.5;">
                    View, download and manage all your insurance policies easily and securely from your personal dashboard.
                </p>
            </div>

            
            <div class="d-none d-md-flex align-items-center position-relative me-md-2 mt-3 mt-md-0">
                <img src="{{ asset('logo/customer_dashbord.png') }}" 
                     alt="Stay Protected" 
                     class="img-fluid" 
                     style="max-height: 160px; object-fit: contain; filter: drop-shadow(0 10px 15px rgba(37, 99, 235, 0.15));">
            </div>
        </div>
    </div> -->

    <!-- Quick Summary Cards (3 Cards matching Screenshot) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Policies -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3" 
                                 style="width: 48px; height: 48px; background: #eff6ff; color: #2563eb;">
                                <i class="fa-regular fa-file-lines fs-4"></i>
                            </div>
                            <div>
                                <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">TOTAL POLICIES</span>
                                <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.75rem;">{{ $totalPolicies }}</h3>
                            </div>
                        </div>
                        <a href="{{ route('customer.portal.dashboard') }}" class="btn btn-sm btn-light rounded-circle text-muted p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                    <div class="d-flex justify-content-between align-items-end pt-1">
                        <small class="text-muted">All registered policies</small>
                        <!-- Sparkline graphic mockup -->
                        <svg width="70" height="24" viewBox="0 0 70 24" fill="none" class="opacity-75">
                            <path d="M1 20 C 15 22, 25 10, 40 14 C 55 18, 60 5, 69 4" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Active Policies -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3" 
                                 style="width: 48px; height: 48px; background: #ecfdf5; color: #10b981;">
                                <i class="fa-solid fa-shield-halved fs-4"></i>
                            </div>
                            <div>
                                <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">ACTIVE POLICIES</span>
                                <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.75rem;">{{ $activePolicies }}</h3>
                            </div>
                        </div>
                        <a href="{{ route('customer.portal.dashboard') }}" class="btn btn-sm btn-light rounded-circle text-muted p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                    <div class="d-flex justify-content-between align-items-end pt-1">
                        <small class="text-muted">Currently active</small>
                        <!-- Sparkline graphic mockup -->
                        <svg width="70" height="24" viewBox="0 0 70 24" fill="none" class="opacity-75">
                            <path d="M1 22 C 15 20, 25 16, 40 10 C 55 6, 60 4, 69 4" stroke="#10b981" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Premium Amount -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3" 
                                 style="width: 48px; height: 48px; background: #fffbeb; color: #f59e0b;">
                                <i class="fa-solid fa-coins fs-4"></i>
                            </div>
                            <div>
                                <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">TOTAL PREMIUM AMOUNT</span>
                                <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.75rem;">₹{{ number_format($totalPremium, 2) }}</h3>
                            </div>
                        </div>
                        <a href="{{ route('customer.portal.dashboard') }}" class="btn btn-sm btn-light rounded-circle text-muted p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                    <div class="d-flex justify-content-between align-items-end pt-1">
                        <small class="text-muted">Across all policies</small>
                        <!-- Sparkline graphic mockup -->
                        <svg width="70" height="24" viewBox="0 0 70 24" fill="none" class="opacity-75">
                            <path d="M1 21 C 20 23, 35 15, 45 12 C 55 10, 60 5, 69 4" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Policies Section -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-start gap-2">
                <div class="rounded-pill bg-primary" style="width: 4px; height: 24px; margin-top: 2px;"></div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.25rem;">My Insurance Policies</h5>
                    <p class="text-muted small mb-0 mt-1">View all your registered policies and download current or renewal documents</p>
                </div>
            </div>

            <!-- Instant Search (No page reload / no URL query string) -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 280px;">
                    <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="policySearchInput" class="form-control border-start-0 ps-1" placeholder="Search policy or vehicle..." style="box-shadow: none;">
                    <button type="button" id="policySearchClear" class="input-group-text bg-white border-start-0 text-muted pe-3 d-none" style="cursor: pointer;" title="Clear search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background: #f8fafc; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                    <tr>
                        <th class="ps-4 py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Policy No. <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                        <th class="py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Insurer / Company <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                        <th class="py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Type <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                        <th class="py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Validity <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                        <th class="py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Premium <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                        <th class="py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Status <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                        <th class="text-end pe-4 py-3 text-secondary fw-semibold small text-uppercase" style="font-size: 0.78rem;">
                            Documents <i class="fa-solid fa-sort ms-1 opacity-50"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($policies as $policy)
                        @php
                            $endDateCarbon = $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->startOfDay() : null;
                            $todayCarbon = \Carbon\Carbon::today();
                            $isExpired = $endDateCarbon && $endDateCarbon->isPast();
                            $daysLeft = $endDateCarbon ? $todayCarbon->diffInDays($endDateCarbon, false) : null;
                            $isRenewalDue = !$isExpired && !is_null($daysLeft) && $daysLeft >= 0 && $daysLeft <= 30;
                            $latestDoc = $policy->pdfHistories->sortByDesc('id')->first();
                        @endphp
                        <tr class="policy-table-row">
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-dark policy-search-text">{{ $policy->policy_number_no ?: $policy->policy_number ?: 'N/A' }}</div>
                                @if($policy->vehicle_registration_number)
                                    <span class="badge bg-secondary-subtle text-secondary small policy-search-text">
                                        <i class="fas fa-car me-1"></i> {{ $policy->vehicle_registration_number }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-dark fw-medium policy-search-text">{{ $policy->company?->name ?? 'N/A' }}</td>
                            <td class="py-3 text-muted policy-search-text">{{ $policy->policyType?->name ?? 'General' }}</td>
                            <td class="py-3">
                                <div class="small">
                                    <span class="text-muted">Start:</span> {{ $policy->start_date ? \Carbon\Carbon::parse($policy->start_date)->format('d M Y') : 'N/A' }}
                                </div>
                                <div class="small">
                                    <span class="text-muted">End:</span> {{ $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->format('d M Y') : 'N/A' }}
                                </div>
                            </td>
                            <td class="py-3 fw-bold text-dark">₹{{ number_format($policy->premium_amount, 2) }}</td>
                            <td class="py-3">
                                @if($isExpired)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill fw-semibold">
                                        <i class="fa-solid fa-circle-xmark me-1 small"></i>Expired
                                    </span>
                                @elseif($isRenewalDue)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2.5 py-1 rounded-pill fw-semibold" title="Policy expires on {{ \Carbon\Carbon::parse($policy->end_date)->format('d M Y') }}">
                                        <i class="fa-solid fa-triangle-exclamation me-1 small"></i>Renewal Due ({{ $daysLeft == 0 ? 'Today' : $daysLeft . 'd left' }})
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold">
                                        <i class="fa-solid fa-circle-check me-1 small"></i>Active
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4 py-3">
                                <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                    <a href="{{ route('customer.portal.policies.show', $policy->id) }}" 
                                       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-2 shadow-2xs fw-semibold"
                                       title="View Policy Details"
                                       style="font-size: 0.8rem; border-color: #cbd5e1; color: #1e293b; background: #fff;">
                                        <i class="fa-regular fa-eye text-primary"></i>
                                        <span>Details</span>
                                    </a>

                                    @if($latestDoc)
                                        <a href="{{ route('customer.portal.documents.download', $latestDoc->id) }}" 
                                           class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-2 shadow-2xs fw-semibold"
                                           title="Download Policy Document (PDF)"
                                           style="font-size: 0.8rem;">
                                            <i class="fa-solid fa-file-arrow-down"></i>
                                            <span>PDF</span>
                                        </a>
                                    @elseif($policy->policy_pdf)
                                        <a href="{{ asset('storage/policies/' . basename($policy->policy_pdf)) }}" 
                                           target="_blank" download
                                           class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-2 shadow-2xs fw-semibold"
                                           title="Download Policy Document"
                                           style="font-size: 0.8rem;">
                                            <i class="fa-solid fa-file-arrow-down"></i>
                                            <span>PDF</span>
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted border px-2 py-1.5 rounded-2 d-inline-flex align-items-center gap-1 fw-normal" 
                                              style="font-size: 0.76rem; border-color: #e2e8f0 !important;"
                                              title="No document uploaded yet">
                                            <i class="fa-regular fa-file text-secondary opacity-50"></i>
                                            <span>No PDF</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <!-- Empty State Exactly Matching Screenshot -->
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <!-- Folder Graphic Icon Mockup -->
                                    <div class="mb-3 d-inline-flex position-relative">
                                        <div class="rounded-4 d-flex align-items-center justify-content-center text-white" 
                                             style="width: 80px; height: 65px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 14px; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);">
                                            <i class="fa-solid fa-folder-open fs-2"></i>
                                        </div>
                                        <span class="position-absolute top-0 end-0 translate-middle p-1 bg-primary border border-light rounded-circle"></span>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">No policies found for your account.</h5>
                                    <p class="text-muted small mb-0" style="max-width: 480px; margin: 0 auto;">
                                        Once your agent or admin adds a policy for your account, it will appear here with all details and documents for easy access.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <!-- Dynamic No Search Results Row -->
                    <tr id="noSearchMatchRow" class="d-none">
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <div class="mb-3 d-inline-flex">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-muted bg-light" 
                                         style="width: 60px; height: 60px; border: 1px dashed #cbd5e1;">
                                        <i class="fa-solid fa-magnifying-glass fs-4 text-secondary"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No matching policies found</h6>
                                <p class="text-muted small mb-0">Try searching with a different policy number, vehicle number, or insurer name.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($policies->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end" id="policyPaginationFooter">
                {{ $policies->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('policySearchInput');
    const clearBtn = document.getElementById('policySearchClear');
    const rows = document.querySelectorAll('.policy-table-row');
    const noMatchRow = document.getElementById('noSearchMatchRow');
    const paginationFooter = document.getElementById('policyPaginationFooter');

    if (!searchInput) return;

    function performFilter() {
        const query = searchInput.value.trim().toLowerCase();

        // Show/hide clear cross icon
        if (query.length > 0) {
            clearBtn?.classList.remove('d-none');
        } else {
            clearBtn?.classList.add('d-none');
        }

        let visibleCount = 0;

        rows.forEach(function (row) {
            const textContent = row.textContent.toLowerCase();
            if (!query || textContent.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show no-match placeholder if no row matched
        if (rows.length > 0) {
            if (visibleCount === 0 && query.length > 0) {
                noMatchRow?.classList.remove('d-none');
                if (paginationFooter) paginationFooter.style.display = 'none';
            } else {
                noMatchRow?.classList.add('d-none');
                if (paginationFooter) paginationFooter.style.display = '';
            }
        }
    }

    // Live filtering on input and change without page reload or URL params
    searchInput.addEventListener('input', performFilter);
    searchInput.addEventListener('change', performFilter);
    searchInput.addEventListener('keyup', performFilter);

    // Clear search handler
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.focus();
            performFilter();
        });
    }
});
</script>
@endpush
@endsection
