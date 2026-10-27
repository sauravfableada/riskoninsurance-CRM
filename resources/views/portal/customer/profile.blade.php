@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <style>
        .customer-avatar-box {
            width: 150px;
            height: 150px;
            border-radius: 20px;
            background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 4rem;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin: 0 auto;
        }
        .customer-avatar-img {
            width: 150px;
            height: 150px;
            border-radius: 20px;
            object-fit: cover;
            border: 2px solid #cbd5e1;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
        }
        .client-detail-row {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }
        .client-detail-label {
            font-weight: 600;
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 140px;
        }
        .client-detail-value {
            color: #475569;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <!-- Card Container matching Screenshot -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <!-- Header Section with Back Button (No Edit Button) -->
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark" style="font-size: 1.35rem;">Client Details</h4>
                <p class="text-muted small mb-0">Complete information about this client.</p>
            </div>
            <div>
                <a href="{{ route('customer.portal.dashboard') }}" class="btn btn-dark-blue d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="background: #0a2540; border-radius: 8px;">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <div class="row g-4 align-items-start">
                <!-- Profile Image Box -->
                <div class="col-md-3 col-sm-4 text-center text-md-start">
                    @if($customer->image)
                        <img src="{{ route('customer.portal.profile.image') }}"
                             alt="{{ $customer->name }}" 
                             class="customer-avatar-img img-fluid"
                             onerror="this.onerror=null; this.style.display='none'; document.getElementById('avatarFallbackBox').classList.remove('d-none');">
                        <div id="avatarFallbackBox" class="customer-avatar-box d-none">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    @else
                        <div class="customer-avatar-box">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    @endif
                </div>

                <!-- Client Information Grid (Matching Screenshot) -->
                <div class="col-md-9 col-sm-8">
                    <h2 class="fw-bolder text-dark mb-4" style="font-size: 1.85rem; letter-spacing: -0.01em;">
                        {{ $customer->name ?? '--' }}
                    </h2>

                    <div class="row g-3">
                        <!-- Created By -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-solid fa-user-tie text-muted"></i> Created By:
                            </span>
                            <span class="client-detail-value">{{ $customer->creator?->name ?? 'Fab Insurance CRM' }}</span>
                        </div>

                        <!-- Created At -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-regular fa-calendar-plus text-muted"></i> Created At:
                            </span>
                            <span class="client-detail-value">{{ $customer->created_at ? $customer->created_at->format('d M, Y h:i A') : '--' }}</span>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-regular fa-envelope text-muted"></i> Email:
                            </span>
                            <span class="client-detail-value text-primary">{{ $customer->email ?? '--' }}</span>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-solid fa-phone text-muted"></i> Phone:
                            </span>
                            <span class="client-detail-value text-primary">{{ $customer->phone ?? '--' }}</span>
                        </div>

                        <!-- WhatsApp -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-brands fa-whatsapp text-muted"></i> WhatsApp:
                            </span>
                            <span class="client-detail-value text-primary">{{ $customer->whatsapp ?? '--' }}</span>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-solid fa-cake-candles text-muted"></i> Date of Birth:
                            </span>
                            <span class="client-detail-value">{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d M, Y') : '--' }}</span>
                        </div>

                        <!-- Tax Number -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-regular fa-file-lines text-muted"></i> Tax Number:
                            </span>
                            <span class="client-detail-value">{{ $customer->tax_number ?? '--' }}</span>
                        </div>

                        <!-- Location -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-solid fa-location-dot text-muted"></i> Location:
                            </span>
                            <span class="client-detail-value">
                                {{ $customer->city ? $customer->city->name . ', ' : '' }}{{ $customer->country ? $customer->country->name : ($customer->address ?? '--') }}
                            </span>
                        </div>

                        <!-- Company -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-regular fa-building text-muted"></i> Company:
                            </span>
                            <span class="client-detail-value">{{ $customer->company_name ?? '--' }}</span>
                        </div>

                        <!-- Type -->
                        <div class="col-md-6 client-detail-row">
                            <span class="client-detail-label">
                                <i class="fa-solid fa-tag text-muted"></i> Type:
                            </span>
                            <span class="client-detail-value">{{ ucfirst($customer->type ?? 'Individual') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Management Section -->
        <div class="card-footer bg-light border-top p-4 p-md-5">
            <!-- Navigation Pills -->
            <ul class="nav nav-pills gap-2 mb-4" id="paymentsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold d-inline-flex align-items-center gap-2 px-4 py-2.5 rounded-3" 
                            id="pending-payments-tab" data-bs-toggle="pill" data-bs-target="#pending-payments" type="button" role="tab">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Pending Payments & Renewals</span>
                        <span class="badge bg-danger rounded-pill ms-1">{{ count($pendingPayments) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold d-inline-flex align-items-center gap-2 px-4 py-2.5 rounded-3" 
                            id="history-tab" data-bs-toggle="pill" data-bs-target="#payment-history" type="button" role="tab">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Payment History</span>
                        <span class="badge bg-secondary rounded-pill ms-1">{{ count($paymentHistory) }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="paymentsTabContent">
                <!-- 1. Pending Payments Tab -->
                <div class="tab-pane fade show active" id="pending-payments" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-secondary fw-semibold small text-uppercase">Policy No</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Company</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Premium Due</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Due / Expiry Date</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Status</th>
                                        <th class="text-end pe-4 py-3 text-secondary fw-semibold small text-uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingPayments as $policy)
                                        @php
                                            $endDate = $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->startOfDay() : null;
                                            $today = \Carbon\Carbon::today();
                                            $isExpired = $endDate && $endDate->isPast();
                                            $daysLeft = $endDate ? $today->diffInDays($endDate, false) : null;
                                        @endphp
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <span class="fw-bold text-dark">#{{ $policy->policy_number_no ?: $policy->policy_number }}</span>
                                                <div class="small text-muted">{{ $policy->policyType?->name ?? 'General' }}</div>
                                            </td>
                                            <td class="py-3 fw-medium text-dark">{{ $policy->company?->name ?? '--' }}</td>
                                            <td class="py-3 fw-bold text-danger">₹{{ number_format($policy->premium_amount, 2) }}</td>
                                            <td class="py-3">
                                                <div class="small fw-semibold text-dark">{{ $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->format('d M Y') : 'Immediate' }}</div>
                                                @if(!is_null($daysLeft))
                                                    <small class="text-muted">{{ $isExpired ? 'Expired' : ($daysLeft == 0 ? 'Due Today' : $daysLeft . ' days remaining') }}</small>
                                                @endif
                                            </td>
                                            <td class="py-3">
                                                @if($isExpired)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill fw-semibold">
                                                        <i class="fa-solid fa-circle-xmark me-1 small"></i>Expired
                                                    </span>
                                                @elseif(!is_null($daysLeft) && $daysLeft <= 30)
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2.5 py-1 rounded-pill fw-semibold">
                                                        <i class="fa-solid fa-triangle-exclamation me-1 small"></i>Renewal Due
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 rounded-pill fw-semibold">
                                                        <i class="fa-solid fa-clock me-1 small"></i>Payment Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4 py-3">
                                                <a href="{{ route('customer.portal.policies.show', $policy->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-2">
                                                    <i class="fa-regular fa-eye"></i> View Policy
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fa-solid fa-circle-check text-success fs-1 mb-2 d-block"></i>
                                                <span class="fw-semibold">No pending payments or urgent renewals due.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment History Tab -->
                <div class="tab-pane fade" id="payment-history" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-secondary fw-semibold small text-uppercase">Policy No</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Payment Date</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Payment Mode</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Amount Paid</th>
                                        <th class="py-3 text-secondary fw-semibold small text-uppercase">Status</th>
                                        <th class="text-end pe-4 py-3 text-secondary fw-semibold small text-uppercase">Receipt / Doc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($paymentHistory as $policy)
                                        @php
                                            $doc = $policy->pdfHistories->sortByDesc('id')->first();
                                        @endphp
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <span class="fw-bold text-dark">#{{ $policy->policy_number_no ?: $policy->policy_number }}</span>
                                                <div class="small text-muted">{{ $policy->company?->name ?? 'General' }}</div>
                                            </td>
                                            <td class="py-3 text-dark fw-medium">
                                                {{ $policy->payment_date ? \Carbon\Carbon::parse($policy->payment_date)->format('d M Y') : ($policy->start_date ? \Carbon\Carbon::parse($policy->start_date)->format('d M Y') : '--') }}
                                            </td>
                                            <td class="py-3 text-muted">
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    {{ $policy->payment_mode ?: ($policy->payment_type ?: 'Online / Bank Transfer') }}
                                                </span>
                                            </td>
                                            <td class="py-3 fw-bold text-success">
                                                ₹{{ number_format($policy->payment_amount ?: $policy->premium_amount, 2) }}
                                            </td>
                                            <td class="py-3">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold">
                                                    <i class="fa-solid fa-check me-1 small"></i>Completed
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 py-3">
                                                @if($doc)
                                                    <a href="{{ route('customer.portal.documents.download', $doc->id) }}" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 rounded-2">
                                                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                                                    </a>
                                                @elseif($policy->policy_pdf)
                                                    <a href="{{ asset('storage/policies/' . basename($policy->policy_pdf)) }}" target="_blank" download class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 rounded-2">
                                                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                                                    </a>
                                                @else
                                                    <span class="text-muted small">No receipt</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fa-regular fa-folder-open fs-1 mb-2 d-block opacity-50"></i>
                                                <span class="fw-semibold">No payment history records found yet.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
