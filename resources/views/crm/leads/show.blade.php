@extends('layouts.app')

@section('page_title', 'Lead Profile')

@section('content')
<div class="container-fluid p-0">
    {{-- Header Bar --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3 w-100">
        <div class="flex-grow-1 w-100">
            <h1 class="h4 mb-1">Lead Profile</h1>
            <p class="text-muted small mb-0">{{ $lead->name }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
            @if($lead->is_converted && $lead->converted_customer_id)
                <a href="{{ route('masters.customers.edit', $lead->converted_customer_id) }}" class="btn btn-success flex-grow-1 flex-md-grow-0" title="View converted customer profile">
                    <i class="bi bi-person-check me-1"></i>View Customer
                </a>
                @can('leads.edit')
                <button type="button" class="btn btn-outline-warning flex-grow-1 flex-md-grow-0" id="showRevertLeadBtn" data-id="{{ $lead->id }}" title="Revert back to an active lead">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Revert to Lead
                </button>
                @endcan
            @else
                <button type="button" class="btn btn-success flex-grow-1 flex-md-grow-0" id="showConvertLeadBtn" data-id="{{ $lead->id }}">
                    <i class="bi bi-person-plus me-1"></i>Convert
                </button>
            @endif
            @can('leads.edit')
            <a href="{{ route('leads.edit', $lead) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endcan
            <a href="{{ route('leads.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                <i class="fa-solid fa-angle-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            {{-- Basic Info Card --}}
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-primary small text-uppercase" style="letter-spacing: 0.05em;">Lead Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="detail-view-block">
                        <div class="detail-view-grid">
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Email</span> 
                                <span class="detail-view-value fw-bold text-dark">{{ $lead->email ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Phone</span> 
                                <span class="detail-view-value fw-bold text-dark">{{ $lead->phone ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Family Group</span> 
                                <span class="detail-view-value fw-semibold text-dark">{{ $lead->group ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Status</span> 
                                <span class="detail-view-value">
                                    <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $lead->status)) }}</span>
                                </span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Source</span> 
                                <span class="detail-view-value text-muted">{{ $lead->leadSource?->name ?? $lead->source ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Company</span> 
                                <span class="detail-view-value text-dark">{{ $lead->company_name ?? '--' }}</span>
                            </div>
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Assigned To</span> 
                                <span class="detail-view-value text-dark">{{ $lead->assignedUser?->name ?? '--' }}</span>
                            </div>
                            @if($lead->address)
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Address</span> 
                                <span class="detail-view-value text-dark">{{ $lead->address }}</span>
                            </div>
                            @endif
                            @if($lead->notes)
                            <div class="detail-view-row mb-3">
                                <span class="detail-view-label text-muted small d-block mb-1">Comment</span> 
                                <span class="detail-view-value text-dark">{{ $lead->notes }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $customFields = \App\Models\CustomField::where('module', 'Lead')->where('is_active', true)->get();
                    @endphp
                    @if($customFields->count() > 0)
                        <hr class="my-4">
                        <h6 class="fw-bold small text-primary text-uppercase mb-3" style="letter-spacing: 0.05em;">Additional Data</h6>
                        @foreach($customFields as $field)
                            @php $val = $lead->getCustomFieldValue($field->name); @endphp
                            @if($val)
                                <div class="bg-light p-3 rounded-3 border mb-2">
                                    <strong class="text-muted small d-block mb-1" style="font-size: 0.65rem;">{{ $field->label }}</strong> 
                                    <span class="text-dark small fw-semibold">{{ $val }}</span>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- KYC Documents Card --}}
            @php
                $kyc = $lead->kycDocument;
                $publicPath = env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '';
                $kycBaseUrl = asset($publicPath . 'uploads/kyc') . '/';

                $parseKycFiles = function($val) use ($kycBaseUrl) {
                    if (empty($val)) return [];
                    $list = is_string($val) && str_starts_with(trim($val), '[') ? json_decode($val, true) : (is_array($val) ? $val : [$val]);
                    if (!is_array($list)) $list = [$val];
                    $res = [];
                    foreach ($list as $f) {
                        if (empty($f) || !is_string($f)) continue;
                        $clean = basename($f);
                        $res[] = [
                            'name' => $clean,
                            'url' => str_starts_with($f, 'http') ? $f : $kycBaseUrl . $clean,
                            'ext' => strtolower(pathinfo($clean, PATHINFO_EXTENSION) ?: 'jpg'),
                        ];
                    }
                    return $res;
                };

                $aadhaarDocs = $kyc ? $parseKycFiles($kyc->aadhaar_card) : [];
                $panDocs = $kyc ? $parseKycFiles($kyc->pan_card) : [];
                $dlDocs = $kyc ? $parseKycFiles($kyc->driving_licence) : [];
                $addrAadhaarDocs = $kyc ? $parseKycFiles($kyc->addr_aadhaar) : [];
                $addrDlDocs = $kyc ? $parseKycFiles($kyc->addr_driving_licence) : [];
                $photoDocs = $kyc ? $parseKycFiles($kyc->photo) : [];
                $signDocs = $kyc ? $parseKycFiles($kyc->signature) : [];

                $totalKycDocs = count($aadhaarDocs) + count($panDocs) + count($dlDocs) + count($addrAadhaarDocs) + count($addrDlDocs) + count($photoDocs) + count($signDocs);
            @endphp

            <div class="card shadow-sm border-0 rounded-4 mb-4 kyc-details-card">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0">KYC Documents</h6>
                        @if($totalKycDocs > 0)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 ms-1 small">
                                {{ $totalKycDocs }} Document{{ $totalKycDocs > 1 ? 's' : '' }}
                            </span>
                        @endif
                    </div>
                    @can('leads.edit')
                    <a href="{{ route('leads.edit', $lead) }}#leadKycTabs" class="btn btn-dark-blue btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-upload me-1"></i> Manage KYC
                    </a>
                    @endcan
                </div>
                <div class="card-body p-3 p-md-4">
                    @if($totalKycDocs === 0)
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-file-earmark-lock display-6 d-block mb-2 text-muted opacity-25"></i>
                            <p class="mb-2 fw-semibold">No KYC documents uploaded for this lead.</p>
                            @can('leads.edit')
                            <a href="{{ route('leads.edit', $lead) }}#leadKycTabs" class="btn btn-dark-blue btn-sm rounded-pill px-3">
                                <i class="bi bi-plus-lg me-1"></i> Upload KYC Details
                            </a>
                            @endcan
                        </div>
                    @else
                        <!-- Tabs -->
                        <ul class="nav nav-tabs nav-tabs-bordered mb-3" id="showKycTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="show-idproof-tab" data-bs-toggle="tab" data-bs-target="#show-idproof" type="button" role="tab">
                                    ID Proof ({{ count($aadhaarDocs) + count($panDocs) + count($dlDocs) }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="show-address-tab" data-bs-toggle="tab" data-bs-target="#show-address" type="button" role="tab">
                                    Address Proof ({{ count($addrAadhaarDocs) + count($addrDlDocs) }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="show-photosign-tab" data-bs-toggle="tab" data-bs-target="#show-photosign" type="button" role="tab">
                                    Photo &amp; Signature ({{ count($photoDocs) + count($signDocs) }})
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="showKycTabsContent">
                            <!-- ID Proof -->
                            <div class="tab-pane fade show active" id="show-idproof" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <small class="text-muted fw-bold d-block mb-2">Aadhaar Card</small>
                                        @forelse($aadhaarDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center justify-content-between">
                                                <span class="small text-truncate" title="{{ $d['name'] }}">
                                                    <i class="bi {{ $d['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                                    {{ $d['name'] }}
                                                </span>
                                                <div class="d-flex gap-1 ms-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('Aadhaar Card', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted fw-bold d-block mb-2">PAN Card</small>
                                        @forelse($panDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center justify-content-between">
                                                <span class="small text-truncate" title="{{ $d['name'] }}">
                                                    <i class="bi {{ $d['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                                    {{ $d['name'] }}
                                                </span>
                                                <div class="d-flex gap-1 ms-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('PAN Card', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted fw-bold d-block mb-2">Driving Licence</small>
                                        @forelse($dlDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center justify-content-between">
                                                <span class="small text-truncate" title="{{ $d['name'] }}">
                                                    <i class="bi {{ $d['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                                    {{ $d['name'] }}
                                                </span>
                                                <div class="d-flex gap-1 ms-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('Driving Licence', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <!-- Address Proof -->
                            <div class="tab-pane fade" id="show-address" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <small class="text-muted fw-bold d-block mb-2">Aadhaar Card</small>
                                        @forelse($addrAadhaarDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center justify-content-between">
                                                <span class="small text-truncate" title="{{ $d['name'] }}">
                                                    <i class="bi {{ $d['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                                    {{ $d['name'] }}
                                                </span>
                                                <div class="d-flex gap-1 ms-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('Address Aadhaar', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted fw-bold d-block mb-2">Driving Licence</small>
                                        @forelse($addrDlDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center justify-content-between">
                                                <span class="small text-truncate" title="{{ $d['name'] }}">
                                                    <i class="bi {{ $d['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                                    {{ $d['name'] }}
                                                </span>
                                                <div class="d-flex gap-1 ms-2">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('Address Driving Licence', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <!-- Photo & Signature -->
                            <div class="tab-pane fade" id="show-photosign" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <small class="text-muted fw-bold d-block mb-2">Passport Photo</small>
                                        @forelse($photoDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center gap-3">
                                                <img src="{{ $d['url'] }}" alt="Photo" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                                <div class="flex-grow-1 text-truncate">
                                                    <span class="small fw-semibold d-block text-truncate">{{ $d['name'] }}</span>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('Passport Photo', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted fw-bold d-block mb-2">Signature</small>
                                        @forelse($signDocs as $d)
                                            <div class="p-2 bg-light rounded-3 border mb-2 d-flex align-items-center gap-3">
                                                <img src="{{ $d['url'] }}" alt="Signature" class="rounded border bg-white p-1" style="width: 70px; height: 40px; object-fit: contain;">
                                                <div class="flex-grow-1 text-truncate">
                                                    <span class="small fw-semibold d-block text-truncate">{{ $d['name'] }}</span>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1" onclick="showLeadKycGallery('Signature', ['{{ $d['url'] }}'])"><i class="bi bi-eye"></i></button>
                                                    <a href="{{ $d['url'] }}" download="{{ $d['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not provided</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Document Viewer Modal --}}
            <div class="modal fade" id="leadKycGalleryModal" tabindex="-1" aria-labelledby="leadKycGalleryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom py-3">
                            <h5 class="modal-title fw-semibold fs-6" id="leadKycGalleryModalLabel">Document Viewer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-center">
                            <div id="leadKycGalleryContainer"></div>
                        </div>
                        <div class="modal-footer border-top py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-4 timeline-card">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
                        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                             <i class="bi bi-clock-history text-primary"></i> Activity Timeline
                        </h6>
                        @can('followups.create')
                        <a href="{{ route('followups.create', ['lead_id' => $lead->id]) }}" class="btn btn-dark-blue btn-sm rounded-pill px-3 shadow-sm w-100 w-sm-auto">
                            <i class="bi bi-plus-lg me-1"></i>Add Activity
                        </a>
                        @endcan
                    </div>

                    <div class="timeline ps-2">
                        {{-- Creation Entry --}}
                        <div class="timeline-item d-flex gap-3 mb-4 position-relative">
                            <div class="timeline-line position-absolute h-100 border-start" style="left: 16px; top: 32px; z-index: 0;"></div>
                            <div class="timeline-icon bg-light text-primary rounded-circle d-flex align-items-center justify-content-center border" style="width: 34px; height: 34px; flex-shrink: 0; min-width: 34px; z-index: 1;">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div class="timeline-content pb-3 border-bottom w-100">
                                <div class="d-flex flex-column flex-sm-row justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0">Lead Created</h6>
                                    <span class="small text-muted">{{ $lead->created_at?->format('d M, Y h:i A') }}</span>
                                </div>
                                <p class="text-muted small mb-0 opacity-75">System initialized the lead profile automatically.</p>
                            </div>
                        </div>

                        {{-- Follow-ups --}}
                        @forelse ($lead->followUps->sortByDesc('scheduled_at') as $fu)
                            <div class="timeline-item d-flex gap-3 mb-4 position-relative">
                                @if(!$loop->last)
                                <div class="timeline-line position-absolute h-100 border-start" style="left: 16px; top: 32px; z-index: 0;"></div>
                                @endif
                                <div class="timeline-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center border" style="width: 34px; height: 34px; flex-shrink: 0; min-width: 34px; z-index: 1;">
                                    @switch($fu->channel)
                                        @case('Call') <i class="bi bi-telephone"></i> @break
                                        @case('WhatsApp') <i class="bi bi-whatsapp"></i> @break
                                        @case('Email') <i class="bi bi-envelope"></i> @break
                                        @case('Meeting') <i class="bi bi-people"></i> @break
                                        @default <i class="bi bi-chat-dots"></i>
                                    @endswitch
                                </div>
                                <div class="timeline-content pb-3 border-bottom w-100">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 d-flex flex-wrap align-items-center gap-2">
                                                {{ $fu->channel }} Follow-Up
                                                @if($fu->assignedUser)
                                                    <span class="badge bg-light text-muted border fw-normal small">by {{ $fu->assignedUser->name }}</span>
                                                @endif
                                            </h6>
                                            <div class="text-muted small mb-2 opacity-75">
                                                <i class="bi bi-calendar-event me-1"></i>{{ \Illuminate\Support\Carbon::parse($fu->scheduled_at)->format('d M, Y h:i A') }}
                                            </div>
                                            @if($fu->notes)
                                                <div class="bg-light p-3 rounded-3 small text-dark mb-2 border-start border-primary border-3 shadow-sm">
                                                    {{ $fu->notes }}
                                                </div>
                                            @endif
                                            <div class="small fw-semibold text-primary opacity-75">
                                                <i class="bi bi-clock-history me-1"></i>Next: {{ $fu->next_follow_up_at ? \Illuminate\Support\Carbon::parse($fu->next_follow_up_at)->format('d M, Y h:i A') : 'None scheduled' }}
                                            </div>
                                        </div>
                                        <div class="d-flex flex-row flex-md-column align-items-center align-items-md-end gap-2 mt-2 mt-md-0">
                                            <div class="d-flex align-items-center gap-2">
                                                @php
                                                    $rawStatus = $fu->status ?: 'pending';
                                                    $badgeClass = 'bg-warning text-dark';
                                                    $displayStatus = 'Pending';
                                                    if ($rawStatus === 'completed') { $badgeClass = 'bg-success'; $displayStatus = 'Done'; }
                                                    elseif ($rawStatus === 'resheduled') { $badgeClass = 'bg-info text-dark'; $displayStatus = 'Rescheduled'; }
                                                    elseif ($rawStatus === 'cancelled') { $badgeClass = 'bg-danger'; $displayStatus = 'Cancelled'; }
                                                @endphp
                                                <span class="badge {{ $badgeClass }} status-badge px-3 py-2 cursor-pointer" 
                                                      data-id="{{ $fu->id }}" 
                                                      data-status="{{ $rawStatus }}"
                                                      style="cursor: pointer;"
                                                      title="Click to update status">
                                                    {{ $displayStatus }}
                                                </span>
                                            </div>
                                            <div class="btn-group btn-group-sm ms-auto ms-md-0 shadow-sm">
                                                @can('followups.edit')
                                                <a href="{{ route('followups.edit', $fu) }}" class="btn btn-white border" title="Edit"><i class="bi bi-pencil small"></i></a>
                                                @endcan
                                                <form action="{{ route('api.follow_ups.destroy', $fu) }}" method="POST" class="d-inline" data-confirm-message="Delete activity?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-white border text-danger"><i class="bi bi-trash text-danger small"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small px-3">
                                <i class="bi bi-chat-left-dots display-6 d-block mb-3 opacity-25"></i>
                                <p class="mb-0 fw-semibold">No follow-up activities recorded yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeline = document.querySelector('.timeline');
        if (!timeline) return;

        timeline.addEventListener('click', function(e) {
            if (e.target.classList.contains('status-badge')) {
                const badge = e.target;
                const id = badge.dataset.id;
                const currentStatus = badge.dataset.status;
                Swal.fire({
                    title: 'Update Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label class="form-label fw-bold small">Status <span class="text-danger">*</span></label>
                            <select id="swal-status-input" class="form-select rounded-3">
                                <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="completed" ${currentStatus === 'completed' || currentStatus === '1' ? 'selected' : ''}>Completed</option>
                                <option value="resheduled" ${currentStatus === 'resheduled' ? 'selected' : ''}>Rescheduled</option>
                                <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </div>
                        <div class="text-start">
                            <label class="form-label fw-bold small">Message <span class="text-danger">*</span></label>
                            <textarea id="swal-comment-input" class="form-control rounded-3" placeholder="Enter your message" rows="3"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0f172a',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        popup: 'rounded-4 shadow'
                    },
                    preConfirm: () => {
                        const status = document.getElementById('swal-status-input').value;
                        const comment = document.getElementById('swal-comment-input').value.trim();
                        if (!comment) {
                            Swal.showValidationMessage('You need to write a message!');
                            return false;
                        }
                        return { status: status, comment: comment };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const nextStatus = result.value.status;
                        const comment = result.value.comment;
                        
                        Swal.fire({
                            title: 'Saving...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('/api/status-history', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                module: 'followup',
                                record_id: id,
                                status: nextStatus,
                                comment: comment
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const isCompleted = nextStatus === 'completed';
                                
                                // Map internal status value to display label
                                const statusLabels = {
                                    'pending': 'Pending',
                                    'completed': 'Done',
                                    'resheduled': 'Rescheduled',
                                    'cancelled': 'Cancelled'
                                };
                                const displayLabel = statusLabels[nextStatus] || nextStatus;
                                
                                badge.textContent = displayLabel;
                                
                                let badgeClass = 'bg-light text-dark';
                                if (isCompleted) badgeClass = 'bg-success';
                                else if (nextStatus === 'pending') badgeClass = 'bg-warning text-dark';
                                else if (nextStatus === 'cancelled') badgeClass = 'bg-danger';
                                else if (nextStatus === 'resheduled') badgeClass = 'bg-info text-dark';

                                badge.className = `badge ${badgeClass} status-badge px-3 py-2 cursor-pointer`;
                                badge.dataset.status = nextStatus;
                                
                                Swal.close();
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('success', 'Status updated successfully');
                                }
                            } else {
                                throw new Error(data.message || 'Failed to update status');
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('error', err.message || 'Failed to update status');
                            }
                        });
                    }
                });
            }
        });
    });

    const convertBtn = document.getElementById('showConvertLeadBtn');
    if (convertBtn) {
        convertBtn.addEventListener('click', function() {
            const leadId = this.dataset.id;
            const button = this;

            Swal.fire({
                title: "Convert to Customer",
                html: `
                    <p class="text-muted small mb-3">Set an optional customer portal password, or click <strong>Skip</strong> to continue without setting a password now.</p>
                    <div class="text-start mb-2">
                        <label class="form-label fw-semibold small mb-1">Customer Password (Optional)</label>
                        <div class="position-relative">
                            <input type="password" id="swalCustomerPassword" class="form-control rounded-3" placeholder="Enter password (optional)">
                            <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y text-muted border-0 bg-transparent pe-3" id="swalTogglePassword" tabindex="-1">
                                <i class="bi bi-eye" id="swalEyeIcon"></i>
                            </button>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: "#0f172a",
                denyButtonColor: "#64748b",
                cancelButtonColor: "#e2e8f0",
                confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Save & Convert',
                denyButtonText: 'Skip & Convert',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: "rounded-4 shadow",
                    cancelButton: "text-dark border",
                },
                didOpen: () => {
                    const toggleBtn = document.getElementById("swalTogglePassword");
                    const passInput = document.getElementById("swalCustomerPassword");
                    const eyeIcon = document.getElementById("swalEyeIcon");
                    if (toggleBtn && passInput) {
                        toggleBtn.addEventListener("click", () => {
                            if (passInput.type === "password") {
                                passInput.type = "text";
                                eyeIcon.className = "bi bi-eye-slash";
                            } else {
                                passInput.type = "password";
                                eyeIcon.className = "bi bi-eye";
                            }
                        });
                    }
                },
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    const passwordVal = result.isConfirmed ? document.getElementById("swalCustomerPassword")?.value || "" : "";
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    fetch(`/leads/${leadId}/convert`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            password: passwordVal
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('success', data.message || 'Lead converted successfully.');
                            }
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            throw new Error(data.message || 'Failed to convert lead.');
                        }
                    })
                    .catch(err => {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', err.message || 'Failed to convert lead.');
                        }
                    });
                }
            });
        });
    }

    const revertBtn = document.getElementById('showRevertLeadBtn');
    if (revertBtn) {
        revertBtn.addEventListener('click', function() {
            const leadId = this.dataset.id;
            const button = this;

            Swal.fire({
                title: "Revert from Customer?",
                text: "This will revert the conversion and restore this record back to an active lead.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d97706",
                cancelButtonColor: "#64748b",
                confirmButtonText: '<i class="bi bi-arrow-counterclockwise me-1"></i> Yes, Revert to Lead',
                cancelButtonText: "Cancel",
                customClass: {
                    popup: "rounded-4 shadow",
                    cancelButton: "text-dark border",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    fetch(`/leads/${leadId}/revert`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('success', data.message || 'Lead converted status reverted successfully.');
                            }
                            window.location.reload();
                        } else {
                            throw new Error(data.message || 'Failed to revert lead.');
                        }
                    })
                    .catch(err => {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', err.message || 'Failed to revert lead.');
                        }
                    });
                }
            });
        });
    }

    function showLeadKycGallery(title, files) {
        const modalEl = document.getElementById('leadKycGalleryModal');
        if (!modalEl) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const label = document.getElementById('leadKycGalleryModalLabel');
        const container = document.getElementById('leadKycGalleryContainer');

        label.textContent = title + ' Viewer';
        container.innerHTML = '';

        if (!files || !files.length) {
            container.innerHTML = '<p class="text-muted">No files available to display.</p>';
            modal.show();
            return;
        }

        files.forEach(fileUrl => {
            const fileName = fileUrl.split('/').pop();
            const ext = fileName.split('.').pop().toLowerCase();
            const isImg = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp', 'svg'].includes(ext);

            const fileCard = document.createElement('div');
            fileCard.className = 'mb-4 pb-3 border-bottom';

            if (isImg) {
                fileCard.innerHTML = `
                    <h6 class="text-truncate fw-semibold mb-2">${fileName}</h6>
                    <img src="${fileUrl}" class="img-fluid rounded border mb-2 shadow-sm" style="max-height: 420px; object-fit: contain;">
                    <div class="mt-2">
                        <a href="${fileUrl}" target="_blank" class="btn btn-dark-blue btn-sm px-3">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Open Original
                        </a>
                        <a href="${fileUrl}" download="${fileName}" class="btn btn-success btn-sm px-3 ms-2">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                    </div>
                `;
            } else {
                fileCard.innerHTML = `
                    <div class="p-4 bg-light rounded-3 border mb-2">
                        <i class="bi bi-file-earmark-pdf text-danger display-4 mb-2 d-block"></i>
                        <h6 class="fw-semibold text-truncate mb-3">${fileName}</h6>
                        <a href="${fileUrl}" target="_blank" class="btn btn-dark-blue btn-sm px-3">
                            <i class="bi bi-eye me-1"></i> View PDF
                        </a>
                        <a href="${fileUrl}" download="${fileName}" class="btn btn-success btn-sm px-3 ms-2">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </a>
                    </div>
                `;
            }
            container.appendChild(fileCard);
        });

        modal.show();
    }
</script>
@endpush
