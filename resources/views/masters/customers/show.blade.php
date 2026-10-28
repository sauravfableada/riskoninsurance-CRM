@extends('layouts.masters')

@section('page_title', 'Client Details')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users-show.css' : '/css/users-show.css') }}?v={{ filemtime(public_path('css/users-show.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('masters_content')
<div class="container-fluid p-0">
    <div class="row g-4">
        <div class="col-lg-12">
            {{-- <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card"> --}}
                <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="flex-grow-1 w-100">
                            <h1 class="h4 mb-1 fw-semibold">Client Details</h1>
                            <p class="text-muted small mb-0">Complete information about this client.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                            @can('update', $customer)
                                <a href="{{ route('masters.customers.edit', $customer) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                            @endcan
                            <a href="{{ route('masters.customers.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="fa-solid fa-angle-left pe-1"></i>
                                <span>Back</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    {{-- Profile Block --}}
                    <div class="detail-view-block mb-2">
                        <div class="row g-4 align-items-start">
                            <div class="col-md-4 col-lg-3 text-center text-md-start">
                                <div class="staff-profile-media">
                                    @if($customer->image)
                                        <img src="{{ route('masters.customers.image', $customer) }}"
                                             alt="{{ $customer->name }}" class="staff-profile-img img-fluid shadow-sm" loading="lazy">
                                    @else
                                        <span class="staff-profile-placeholder d-flex align-items-center justify-content-center">
                                            <i class="bi bi-person-fill"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-8 col-lg-9">
                                <div class="detail-view-title mb-3">{{ $customer->name ?? '--' }}</div>
                                <div class="row g-0 detail-view-grid">
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-user-tie text-muted me-2"></i>Created By:</span>
                                        <span class="detail-view-value">{{ $customer->creator->name ?? 'Admin' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-calendar-plus text-muted me-2"></i>Created At:</span>
                                        <span class="detail-view-value">{{ $customer->created_at?->format('d M, Y h:i A') ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-envelope text-muted me-2"></i>Email:</span>
                                        <span class="detail-view-value">
                                            @if($customer->email)
                                                <a href="mailto:{{ $customer->email }}" class="text-decoration-none link-hover">{{ $customer->email }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-phone-alt text-muted me-2"></i>Phone:</span>
                                        <span class="detail-view-value">
                                            @if($customer->phone)
                                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none link-hover">{{ $customer->phone }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fab fa-whatsapp text-muted me-2"></i>WhatsApp:</span>
                                        <span class="detail-view-value">
                                            @if($customer->whatsapp)
                                                <a href="https://wa.me/{{ $customer->whatsapp }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->whatsapp }}</a>
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-birthday-cake text-muted me-2"></i>Date of Birth:</span>
                                        <span class="detail-view-value">{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d M, Y') : '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-file-invoice text-muted me-2"></i>Tax Number:</span>
                                        <span class="detail-view-value">{{ $customer->tax_number ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-map-marker-alt text-muted me-2"></i>Location:</span>
                                        <span class="detail-view-value">{{ $customer->city ? $customer->city->name . ', ' : '' }}{{ $customer->country ? $customer->country->name : '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-building text-muted me-2"></i>Company:</span>
                                        <span class="detail-view-value">{{ $customer->company_name ?? '--' }}</span>
                                    </div>
                                    <div class="col-md-6 detail-view-row">
                                        <span class="detail-view-label pe-2"><i class="fas fa-tags text-muted me-2"></i>Type:</span>
                                        <span class="detail-view-value">{{ $customer->type ?? '--' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Fields --}}
                    @php
                        $customFields = \App\Models\CustomField::where('module', 'Customer')->where('is_active', true)->get();
                        $hasValues = false;
                    @endphp
                    @if($customFields->count() > 0)
                        <div class="mb-4 pt-3 border-top">
                            <h6 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.05em;">Additional Information</h6>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($customFields as $field)
                                    @php $val = $customer->getCustomFieldValue($field->name); @endphp
                                    @if($val)
                                        @php $hasValues = true; @endphp
                                        <div class="bg-light px-3 py-2 rounded-3 border">
                                            <strong class="text-muted small d-block" style="font-size: 0.65rem;">{{ $field->label }}</strong>
                                            <span class="text-dark fw-semibold small">{{ $val }}</span>
                                        </div>
                                    @endif
                                @endforeach
                                @if(!$hasValues)
                                    <p class="text-muted small fst-italic mb-0 bg-light p-2 rounded w-100">No additional form data found.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Portfolio & Activity Overview KPIs --}}
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-sm-4 col-xl-2">
                            <div class="card border shadow-none bg-light p-3 text-center h-100 rounded-3">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;"><i class="fas fa-shield-alt text-primary me-1"></i>General Policies</div>
                                <div class="h3 fw-bold mb-0 text-dark mt-1">{{ $customer->policies->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-xl-2">
                            <div class="card border shadow-none bg-light p-3 text-center h-100 rounded-3">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;"><i class="fas fa-heartbeat text-danger me-1"></i>Life Policies</div>
                                <div class="h3 fw-bold mb-0 text-dark mt-1">{{ $customer->lifeInsurances->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-xl-2">
                            <div class="card border shadow-none bg-light p-3 text-center h-100 rounded-3">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;"><i class="fas fa-chart-line text-success me-1"></i>Investments</div>
                                <div class="h3 fw-bold mb-0 text-dark mt-1">{{ $customer->investments->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-xl-2">
                            <div class="card border shadow-none bg-light p-3 text-center h-100 rounded-3">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;"><i class="fas fa-file-invoice-dollar text-warning me-1"></i>Claims</div>
                                <div class="h3 fw-bold mb-0 text-dark mt-1">{{ $customer->claims->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-xl-2">
                            <div class="card border shadow-none bg-light p-3 text-center h-100 rounded-3">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;"><i class="fas fa-folder-open text-info me-1"></i>Documents</div>
                                <div class="h3 fw-bold mb-0 text-dark mt-1">{{ $customer->documents->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-xl-2">
                            <div class="card border shadow-none bg-light p-3 text-center h-100 rounded-3">
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;"><i class="fas fa-receipt text-secondary me-1"></i>Invoices</div>
                                <div class="h3 fw-bold mb-0 text-dark mt-1">{{ $customer->invoices->count() }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabbed Details Section --}}
                    <div class="mt-4 border rounded">
                        <div class="px-4 pt-4">
                            <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-info-circle"></i> Detailed Insights
                            </h6>
                        </div>

                        <div class="overflow-auto border-bottom" style="scrollbar-width: thin;">
                            <ul class="nav nav-tabs px-4 border-bottom-0 flex-nowrap" id="customerDetailsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-semibold text-nowrap" id="policies-tab" data-bs-toggle="tab"
                                        data-bs-target="#policies" type="button" role="tab">
                                        <i class="fas fa-shield-alt me-1 text-primary"></i> General Policies
                                        <span class="badge bg-primary rounded-pill ms-1">{{ $customer->policies->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="life-insurance-tab" data-bs-toggle="tab"
                                        data-bs-target="#life-insurance" type="button" role="tab">
                                        <i class="fas fa-heartbeat me-1 text-danger"></i> Life Insurance
                                        <span class="badge bg-danger rounded-pill ms-1">{{ $customer->lifeInsurances->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="investments-tab" data-bs-toggle="tab"
                                        data-bs-target="#investments" type="button" role="tab">
                                        <i class="fas fa-chart-line me-1 text-success"></i> Investments
                                        <span class="badge bg-success rounded-pill ms-1">{{ $customer->investments->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="claims-tab" data-bs-toggle="tab"
                                        data-bs-target="#claims" type="button" role="tab">
                                        <i class="fas fa-file-invoice-dollar me-1 text-warning"></i> Claims
                                        <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $customer->claims->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="documents-tab" data-bs-toggle="tab"
                                        data-bs-target="#documents" type="button" role="tab">
                                        <i class="fas fa-folder-open me-1 text-info"></i> Documents
                                        <span class="badge bg-info text-dark rounded-pill ms-1">{{ $customer->documents->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="invoices-tab" data-bs-toggle="tab"
                                        data-bs-target="#invoices" type="button" role="tab">
                                        <i class="fas fa-receipt me-1 text-secondary"></i> Invoices
                                        <span class="badge bg-secondary rounded-pill ms-1">{{ $customer->invoices->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="family-tab" data-bs-toggle="tab"
                                        data-bs-target="#family" type="button" role="tab">
                                        <i class="fas fa-users me-1 text-secondary"></i> Family Members
                                        @php
                                            $familyMembersCount = $customer->familyGroups->flatMap->members->count();
                                        @endphp
                                        <span class="badge bg-secondary rounded-pill ms-1">{{ $familyMembersCount }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="address-tab" data-bs-toggle="tab"
                                        data-bs-target="#address" type="button" role="tab">
                                        <i class="fas fa-map-marker-alt me-1 text-muted"></i> Address Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="contact-tab" data-bs-toggle="tab"
                                        data-bs-target="#contact" type="button" role="tab">
                                        <i class="fas fa-address-book me-1 text-muted"></i> Contact Details
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="meetings-tab" data-bs-toggle="tab"
                                        data-bs-target="#meetings" type="button" role="tab">
                                        <i class="bi bi-calendar-event me-1"></i> Meetings
                                        <span class="badge bg-light text-dark border ms-1">{{ $customer->meetings->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="deals-tab" data-bs-toggle="tab"
                                        data-bs-target="#deals" type="button" role="tab">
                                        <i class="fa-solid fa-indian-rupee-sign me-1"></i> Deals
                                        <span class="badge bg-light text-dark border ms-1">{{ $customer->deals->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="followups-tab" data-bs-toggle="tab"
                                        data-bs-target="#followups" type="button" role="tab">
                                        <i class="fas fa-clock-rotate-left me-1"></i> Followups
                                        <span class="badge bg-light text-dark border ms-1">{{ $customer->followUps->count() }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content border-top" id="customerDetailsTabContent">
                            {{-- General Insurance Policies --}}
                            <div class="tab-pane fade show active p-0" id="policies" role="tabpanel">
                                @if($customer->policies->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Policy No.</th>
                                                    <th>Insurance Company</th>
                                                    <th>Policy Type & Sub Type</th>
                                                    <th>Coverage Period</th>
                                                    <th>Premium</th>
                                                    <th>Sum Assured</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->policies as $policy)
                                                    @php
                                                        $isExpired = $policy->end_date && \Carbon\Carbon::parse($policy->end_date)->isPast();
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4">
                                                            <a href="{{ url('/view-policy/' . $policy->id) }}" class="fw-bold text-dark text-decoration-none link-hover">
                                                                {{ $policy->policy_number ?: 'N/A' }}
                                                            </a>
                                                            @if($policy->vehicle_registration_number)
                                                                <div class="small text-muted"><i class="fas fa-car me-1"></i>{{ $policy->vehicle_registration_number }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $policy->company->name ?? 'N/A' }}</span>
                                                            @if($policy->branch)
                                                                <div class="small text-muted">{{ $policy->branch->name }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div><span class="badge bg-light text-dark border">{{ $policy->policyType->name ?? 'N/A' }}</span></div>
                                                            @if($policy->policySubType)
                                                                <div class="small text-muted mt-1">{{ $policy->policySubType->name }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="small fw-semibold text-dark">{{ $policy->start_date ? \Carbon\Carbon::parse($policy->start_date)->format('d M, Y') : '--' }}</div>
                                                            <div class="small text-muted">to {{ $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->format('d M, Y') : '--' }}</div>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-dark">₹{{ number_format((float)$policy->premium_amount, 2) }}</span>
                                                            @if($policy->payment_mode)
                                                                <div class="small text-muted">{{ ucfirst($policy->payment_mode) }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold text-muted">₹{{ number_format((float)$policy->sum_assured, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            @if($isExpired)
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Expired</span>
                                                            @else
                                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <div class="d-flex justify-content-end gap-1">
                                                                @if($policy->policy_pdf)
                                                                    <a href="{{ url('/view-policy-document/' . $policy->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Policy PDF">
                                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                                    </a>
                                                                @endif
                                                                <a href="{{ url('/view-policy/' . $policy->id) }}" class="btn btn-sm btn-outline-dark-blue" title="View Full Policy">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-shield-alt display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No general insurance policies found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Life Insurance Policies --}}
                            <div class="tab-pane fade p-0" id="life-insurance" role="tabpanel">
                                @if($customer->lifeInsurances->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Policy No.</th>
                                                    <th>Insurance Company</th>
                                                    <th>Plan Name / Type</th>
                                                    <th>Sum Assured</th>
                                                    <th>Premium Amount</th>
                                                    <th>Risk Term & PPT</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->lifeInsurances as $life)
                                                    <tr>
                                                        <td class="ps-4">
                                                            <a href="{{ route('life-insurance.show', $life->id) }}" class="fw-bold text-dark text-decoration-none link-hover">
                                                                {{ $life->policy_number ?: 'N/A' }}
                                                            </a>
                                                            @if($life->document_number)
                                                                <div class="small text-muted">Doc: {{ $life->document_number }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $life->insuranceCompany->name ?? 'N/A' }}</span>
                                                            @if($life->branch)
                                                                <div class="small text-muted">{{ $life->branch->name }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ $life->insurance_plan_name ?: 'N/A' }}</div>
                                                            @if($life->insurance_plan_type)
                                                                <div class="small text-muted">{{ $life->insurance_plan_type }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-dark">₹{{ number_format((float)$life->sum_assured, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-dark">₹{{ number_format((float)$life->total_premium_amount, 2) }}</span>
                                                            @if($life->premium_mode)
                                                                <div class="small text-muted">{{ ucfirst($life->premium_mode) }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="small"><span class="text-muted">Term:</span> {{ $life->risk_term ? $life->risk_term . ' Yrs' : '--' }}</div>
                                                            <div class="small"><span class="text-muted">PPT:</span> {{ $life->premium_paying_term ? $life->premium_paying_term . ' Yrs' : '--' }}</div>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $lifeStatus = strtolower($life->policy_status ?? 'active');
                                                                $lifeBadge = match($lifeStatus) {
                                                                    'active', 'in force', 'inforce' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                                    'lapsed' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                                    'surrendered', 'closed' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                                                    default => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $lifeBadge }}">{{ ucfirst($life->policy_status ?? 'Active') }}</span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('life-insurance.show', $life->id) }}" class="btn btn-sm btn-outline-dark-blue" title="View Life Policy">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-heartbeat display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No life insurance policies found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Investments --}}
                            <div class="tab-pane fade p-0" id="investments" role="tabpanel">
                                @if($customer->investments->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Type</th>
                                                    <th>Document / Folio</th>
                                                    <th>Investment Details</th>
                                                    <th>Invested Amount</th>
                                                    <th>Start Date</th>
                                                    <th>Maturity Date</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->investments as $inv)
                                                    @php
                                                        $invType = $inv->investment_type_main;
                                                        $typeLabel = match($invType) {
                                                            'mutual_fund' => 'Mutual Fund',
                                                            'bank' => 'Bank Deposit',
                                                            'post_office' => 'Post Office',
                                                            'govt_bonds' => 'Govt Bond',
                                                            'other' => 'Other',
                                                            default => ucfirst(str_replace('_', ' ', $invType ?? 'General')),
                                                        };
                                                        $badgeColor = match($invType) {
                                                            'mutual_fund' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                                            'bank' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                            'post_office' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                                            'govt_bonds' => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25',
                                                            default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                                        };
                                                        
                                                        $amount = match($invType) {
                                                            'mutual_fund' => $inv->mf_investment_amount,
                                                            'bank' => $inv->bank_investment_amount,
                                                            'post_office' => $inv->po_investment_amount,
                                                            'govt_bonds' => $inv->bond_investment_amount,
                                                            'other' => $inv->other_investment_amount,
                                                            default => 0,
                                                        };

                                                        $startDate = match($invType) {
                                                            'mutual_fund' => $inv->mf_start_date,
                                                            'bank' => $inv->bank_deposit_date,
                                                            'post_office' => $inv->po_opening_date,
                                                            'govt_bonds' => $inv->bond_purchase_date,
                                                            'other' => $inv->other_deposit_date,
                                                            default => $inv->transaction_date,
                                                        };

                                                        $matureDate = match($invType) {
                                                            'bank' => $inv->bank_mature_date,
                                                            'post_office' => $inv->po_mature_date,
                                                            'govt_bonds' => $inv->bond_maturity_date,
                                                            'other' => $inv->other_mature_date,
                                                            default => null,
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4">
                                                            <span class="badge {{ $badgeColor }}">{{ $typeLabel }}</span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('investment.show', $inv->id) }}" class="fw-bold text-dark text-decoration-none link-hover">
                                                                {{ $inv->document_number ?: ($inv->po_certificate_account_no ?: ($inv->bank_certificate_no ?: 'INV-#' . $inv->id)) }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @if($invType === 'mutual_fund')
                                                                <div class="fw-semibold text-dark">{{ $inv->mf_remarks ?: 'Mutual Fund Portfolio' }}</div>
                                                            @elseif($invType === 'bank')
                                                                <div class="fw-semibold text-dark">{{ $inv->bank_account_type ?: 'Bank Deposit' }}</div>
                                                            @elseif($invType === 'govt_bonds')
                                                                <div class="fw-semibold text-dark">{{ $inv->bond_name ?: 'Bond Investment' }}</div>
                                                                @if($inv->bond_id_isin)
                                                                    <div class="small text-muted">ISIN: {{ $inv->bond_id_isin }}</div>
                                                                @endif
                                                            @else
                                                                <div class="fw-semibold text-dark">{{ $inv->other_invested_party_name ?: ($inv->family_head_name ?? 'Investment') }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-dark">₹{{ number_format((float)$amount, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="small text-dark">{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M, Y') : '--' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="small text-muted">{{ $matureDate ? \Carbon\Carbon::parse($matureDate)->format('d M, Y') : '--' }}</div>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('investment.show', $inv->id) }}" class="btn btn-sm btn-outline-dark-blue" title="View Investment">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-chart-line display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No investments found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Claims --}}
                            <div class="tab-pane fade p-0" id="claims" role="tabpanel">
                                @if($customer->claims->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Claim ID</th>
                                                    <th>Policy No.</th>
                                                    <th>Insured Member</th>
                                                    <th>Claim Amount</th>
                                                    <th>File Received</th>
                                                    <th>File Submitted</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->claims as $claim)
                                                    <tr>
                                                        <td class="ps-4 fw-bold">#CLM-{{ $claim->id }}</td>
                                                        <td>
                                                            @if($claim->policy)
                                                                <a href="{{ url('/view-policy/' . $claim->policy->id) }}" class="fw-semibold text-dark text-decoration-none link-hover">
                                                                    {{ $claim->policy->policy_number ?: 'Policy #' . $claim->policy->id }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">--</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $claim->insuredMember?->member_name ?? ($customer->name) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-dark">₹{{ number_format((float)$claim->claim_amount, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">{{ $claim->claim_file_received_date?->format('d M, Y') ?? '--' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">{{ $claim->claim_file_submit_date?->format('d M, Y') ?? '--' }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $clmStatusClass = match (strtolower($claim->claim_status ?? '')) {
                                                                    'processed', 'closed' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                                    'query' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                                                    'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                                    default => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $clmStatusClass }}">{{ $claim->claim_status ?: 'Pending' }}</span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('claim-tracker.show', $claim->id) }}" class="btn btn-sm btn-outline-dark-blue" title="View Claim Details">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-file-invoice-dollar display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No claims found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Client Documents --}}
                            <div class="tab-pane fade p-0" id="documents" role="tabpanel">
                                @if($customer->documents->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Document Title</th>
                                                    <th>Type / Category</th>
                                                    <th>Uploaded By</th>
                                                    <th>Uploaded Date</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->documents as $doc)
                                                    <tr>
                                                        <td class="ps-4">
                                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                                <i class="fas fa-file-pdf text-danger fa-lg"></i>
                                                                <span>{{ $doc->title ?: 'Document #' . $doc->id }}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if($doc->is_kyc)
                                                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">KYC Document</span>
                                                            @elseif($doc->file_type)
                                                                <span class="badge bg-light text-dark border">{{ strtoupper($doc->file_type) }}</span>
                                                            @else
                                                                <span class="badge bg-light text-muted border">General</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">{{ $doc->user->name ?? 'System' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">{{ $doc->created_at?->format('d M, Y h:i A') ?? '--' }}</span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-outline-dark-blue" title="Download Document">
                                                                <i class="bi bi-download me-1"></i> Download
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-folder-open display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No documents uploaded for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Invoices --}}
                            <div class="tab-pane fade p-0" id="invoices" role="tabpanel">
                                @if($customer->invoices->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Invoice #</th>
                                                    <th>Invoice Date</th>
                                                    <th>Due Date</th>
                                                    <th>Total Amount</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->invoices as $inv)
                                                    <tr>
                                                        <td class="ps-4 fw-bold">
                                                            <a href="{{ route('invoices.show', $inv) }}" class="text-dark text-decoration-none link-hover">
                                                                {{ $inv->number ?: 'INV-#' . $inv->id }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">{{ $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('d M, Y') : '--' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M, Y') : '--' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-dark">{{ ($inv->currency->symbol ?? '₹') . number_format((float)$inv->total_amount, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $invBadge = match(strtolower($inv->status ?? '')) {
                                                                    'paid' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                                    'unpaid', 'due' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                                                    'overdue', 'cancelled' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                                    default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $invBadge }}">{{ ucfirst($inv->status ?? 'Draft') }}</span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-dark-blue" title="View Invoice">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-receipt display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No invoices found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Family Members --}}
                            <div class="tab-pane fade p-0" id="family" role="tabpanel">
                                @php
                                    $familyGroups = $customer->familyGroups;
                                    $allMembers = $familyGroups->flatMap->members;
                                @endphp
                                @if($allMembers->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Member Name</th>
                                                    <th>Relationship</th>
                                                    <th>Contact No.</th>
                                                    <th>Date of Birth</th>
                                                    <th>Anniversary Date</th>
                                                    <th>Family Group</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($familyGroups as $group)
                                                    @foreach($group->members as $member)
                                                        <tr>
                                                            <td class="ps-4 fw-bold">{{ $member->member_name ?: '--' }}</td>
                                                            <td><span class="badge bg-light text-dark border">{{ $member->relation ?: '--' }}</span></td>
                                                            <td>{{ $member->contact ?: '--' }}</td>
                                                            <td>{{ $member->dob ? \Carbon\Carbon::parse($member->dob)->format('d M, Y') : '--' }}</td>
                                                            <td>{{ $member->anniversary ? \Carbon\Carbon::parse($member->anniversary)->format('d M, Y') : '--' }}</td>
                                                            <td><span class="fw-semibold text-primary">{{ $group->group_name }}</span></td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-users display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No family group members found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Address Details --}}
                            <div class="tab-pane fade p-0" id="address" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4" style="width: 30%;">Website</td>
                                                <td class="pe-4">
                                                    @if($customer->website)
                                                        <a href="{{ $customer->website }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->website }}</a>
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">Address</td>
                                                <td class="pe-4">{{ $customer->address ?? '--' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">Country</td>
                                                <td class="pe-4">{{ $customer->country->name ?? '--' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">State/City</td>
                                                <td class="pe-4">{{ $customer->city->name ?? '--' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">Tax Number</td>
                                                <td class="pe-4">{{ $customer->tax_number ?? '--' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Contact Details --}}
                            <div class="tab-pane fade p-0" id="contact" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4" style="width: 30%;">Phone Number</td>
                                                <td class="pe-4">
                                                    @if($customer->phone)
                                                        <a href="tel:{{ $customer->phone }}" class="text-decoration-none link-hover">{{ $customer->phone }}</a>
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">WhatsApp Number</td>
                                                <td class="pe-4">
                                                    @if($customer->whatsapp)
                                                        <a href="https://wa.me/{{ $customer->whatsapp }}" target="_blank" class="text-decoration-none link-hover">{{ $customer->whatsapp }}</a>
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">Email</td>
                                                <td class="pe-4">
                                                    @if($customer->email)
                                                        <a href="mailto:{{ $customer->email }}" class="text-decoration-none link-hover">{{ $customer->email }}</a>
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">Date of Birth</td>
                                                <td class="pe-4">{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d M Y') : '--' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold ps-4">Status</td>
                                                <td class="pe-4">
                                                    @if($customer->is_active)
                                                        <span class="badge bg-success opacity-75">Active</span>
                                                    @else
                                                        <span class="badge bg-danger opacity-75">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Meetings --}}
                            <div class="tab-pane fade p-0" id="meetings" role="tabpanel">
                                @if($customer->meetings->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Title</th>
                                                    <th>Type</th>
                                                    <th>Scheduled</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->meetings->sortByDesc('scheduled_at') as $meeting)
                                                    <tr>
                                                        <td class="ps-4 fw-bold">{{ $meeting->title ?: '--' }}</td>
                                                        <td><span class="text-muted small">{{ $meeting->meeting_type_label }}</span></td>
                                                        <td><span class="text-muted small">{{ $meeting->scheduled_at?->format('d M Y h:i A') ?? '--' }}</span></td>
                                                        <td>
                                                            @php
                                                                $meetingStatusClass = match ($meeting->status) {
                                                                    'scheduled' => 'bg-primary',
                                                                    'completed' => 'bg-success',
                                                                    'cancelled' => 'bg-danger',
                                                                    default => 'bg-secondary',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $meetingStatusClass }} opacity-75">{{ $meeting->status_label }}</span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('meetings.show', $meeting) }}"
                                                                class="btn btn-sm btn-outline-dark-blue">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="bi bi-calendar-event display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No meetings found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Deals --}}
                            <div class="tab-pane fade p-0" id="deals" role="tabpanel">
                                @if($customer->deals->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Deal</th>
                                                    <th>Stage</th>
                                                    <th>Status</th>
                                                    <th>Value</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->deals->sortByDesc('created_at') as $deal)
                                                    <tr>
                                                        <td class="ps-4 fw-bold">{{ $deal->title ?: '--' }}</td>
                                                        <td><span class="badge border border-info text-info small">{{ $deal->stage->name ?? '--' }}</span></td>
                                                        <td>
                                                            @php
                                                                $dealStatusColor = $deal->status->color ?? null;
                                                            @endphp
                                                            @if($dealStatusColor)
                                                                <span class="badge text-white"
                                                                    style="background-color: {{ $dealStatusColor }};">{{ $deal->status->name ?? '--' }}</span>
                                                            @else
                                                                <span class="badge bg-secondary opacity-75">{{ $deal->status->name ?? '--' }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="fw-bold text-dark">{{ ($deal->currency->symbol ?? $deal->currency->code ?? '') . ($deal->amount ? number_format((float) $deal->amount, 2) : '0.00') }}</td>
                                                        <td class="text-end pe-4">
                                                            <a href="{{ route('deals.show', $deal) }}"
                                                                class="btn btn-sm btn-outline-dark-blue">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fa-solid fa-indian-rupee-sign display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No deals found for this client.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Followups --}}
                            <div class="tab-pane fade p-0" id="followups" role="tabpanel">
                                @if(isset($customer->followUps) && $customer->followUps->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Purpose</th>
                                                    <th>Status</th>
                                                    <th>Follow-up At</th>
                                                    <th>Assigned To</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->followUps as $followup)
                                                    <tr>
                                                        <td class="ps-4 fw-bold">{{ $followup->purpose ?: '--' }}</td>
                                                        <td>
                                                            @php
                                                                $fuStatusClass = match ($followup->status) {
                                                                    'pending' => 'bg-warning text-dark',
                                                                    'completed' => 'bg-success',
                                                                    'cancelled' => 'bg-danger',
                                                                    default => 'bg-secondary',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $fuStatusClass }} opacity-75">{{ ucfirst($followup->status) }}</span>
                                                        </td>
                                                        <td><span class="text-muted small">{{ $followup->follow_up_at?->format('d M Y h:i A') ?? '--' }}</span></td>
                                                        <td>{{ $followup->assignedUser->name ?? '--' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5 px-4">
                                        <i class="fas fa-calendar-alt display-5 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0 fw-semibold">No followups found for this client.</p>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            {{-- </div> --}}
        </div>
    </div>
</div>
@endsection
