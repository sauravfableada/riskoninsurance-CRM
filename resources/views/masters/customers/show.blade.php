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

                    {{-- Tabbed Details Section --}}
                    <div class="mt-4 border rounded">
                        <div class="px-4 pt-4">
                            <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-info-circle"></i> Detailed Insights
                            </h6>
                        </div>

                        <div>
                            <ul class="nav nav-tabs px-4 border-bottom-0 flex-nowrap" id="customerDetailsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-semibold text-nowrap" id="address-tab" data-bs-toggle="tab"
                                        data-bs-target="#address" type="button" role="tab">Address Info</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact"
                                        type="button" role="tab">Contact Details</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="meetings-tab" data-bs-toggle="tab" data-bs-target="#meetings"
                                        type="button" role="tab">Meetings</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="deals-tab" data-bs-toggle="tab" data-bs-target="#deals"
                                        type="button" role="tab">Deals</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-nowrap" id="followups-tab" data-bs-toggle="tab" data-bs-target="#followups"
                                        type="button" role="tab">Followups</button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content border-top" id="customerDetailsTabContent">
                            {{-- Address Details --}}
                            <div class="tab-pane fade show active p-0" id="address" role="tabpanel">
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
