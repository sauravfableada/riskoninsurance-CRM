@extends('layouts.app')

@section('page_title', 'Policy Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                <!-- Header with Back Button (Read-only, no Edit for customers) -->
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold text-dark" style="font-size: 1.35rem;">Policy Details</h4>
                        <p class="text-muted small mb-0">Complete information about this policy</p>
                    </div>
                    <div>
                        <a href="{{ route('customer.portal.dashboard') }}" class="btn btn-dark-blue d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="background: #0a2540; border-radius: 8px;">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <!-- Policy Number Heading -->
                    <div class="mb-4">
                        @php
                            $detailEndDate = $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->startOfDay() : null;
                            $detailToday = \Carbon\Carbon::today();
                            $detailExpired = $detailEndDate && $detailEndDate->isPast();
                            $detailDaysLeft = $detailEndDate ? $detailToday->diffInDays($detailEndDate, false) : null;
                            $detailRenewalDue = !$detailExpired && !is_null($detailDaysLeft) && $detailDaysLeft >= 0 && $detailDaysLeft <= 30;
                        @endphp
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h5 class="mb-0 text-secondary fw-semibold">
                                Policy Number
                                @if(!empty($policy->policy_number_no ?: $policy->policy_number))
                                    -- <span class="text-primary fw-bold">#{{ $policy->policy_number_no ?: $policy->policy_number }}</span>
                                @endif
                            </h5>
                            <div>
                                @if($detailExpired)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill fw-semibold fs-6">
                                        <i class="fa-solid fa-circle-xmark me-1 small"></i>Expired
                                    </span>
                                @elseif($detailRenewalDue)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-3 py-1.5 rounded-pill fw-semibold fs-6">
                                        <i class="fa-solid fa-triangle-exclamation me-1 small"></i>Renewal Due ({{ $detailDaysLeft == 0 ? 'Today' : $detailDaysLeft . ' days left' }})
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill fw-semibold fs-6">
                                        <i class="fa-solid fa-circle-check me-1 small"></i>Active
                                    </span>
                                @endif
                            </div>
                        </div>

                        @php
                            $subAgentName = $policy->user?->name ?: ($policy->creator?->name ?: 'Fab Insurance CRM');
                            $familyGroupName = $policy->familyGroup?->name ?? '--';
                            $policyHolderName = $policy->holder?->name ?? ($customer->name ?? '--');
                            $policyTypeName = $policy->policyType?->name ?? '--';
                            $subTypeName = $policy->policySubType?->name ?? '--';
                            $segmentName = $policy->segment?->name ?? '--';
                            $companyName = $policy->company?->name ?? '--';
                            $branchName = $policy->branch?->name ?? '--';
                            $startDate = $policy->start_date ? \Carbon\Carbon::parse($policy->start_date)->format('d M Y') : '--';
                            $endDate = $policy->end_date ? \Carbon\Carbon::parse($policy->end_date)->format('d M Y') : '--';
                            $policyMode = $policy->policy_mode ?? '--';
                            $premiumAmount = !is_null($policy->premium_amount) ? number_format($policy->premium_amount, 2) : '--';
                            $taxAmount = !is_null($policy->tax_amount) ? number_format($policy->tax_amount, 2) : '--';
                            $paymentDate = $policy->payment_date ? \Carbon\Carbon::parse($policy->payment_date)->format('d M Y') : '--';
                            $paymentMode = $policy->payment_mode ?? ($policy->payment_type ?? '--');
                            $nomineeName = $policy->nominee_name ?? '--';
                            $nomineeContact = $policy->nominee_contact ?? '--';
                            $paymentAmount = !is_null($policy->payment_amount) ? number_format($policy->payment_amount, 2) : ($premiumAmount ?? '--');
                            $sumAssured = !is_null($policy->sum_assured) ? number_format($policy->sum_assured, 2) : '--';
                            $remarks = $policy->remarks ?? '--';

                            $policyFields = [
                                "Sub Agent" => ['icon' => 'fa-solid fa-user-tie', 'value' => $subAgentName],
                                "Family Group" => ['icon' => 'fa-solid fa-people-group', 'value' => $familyGroupName],
                                "Policy Holder" => ['icon' => 'fa-solid fa-user', 'value' => $policyHolderName],
                                "Policy Type" => ['icon' => 'fa-solid fa-shield-halved', 'value' => $policyTypeName],
                                "Policy Sub Type" => ['icon' => 'fa-solid fa-layer-group', 'value' => $subTypeName],
                                "Segment" => ['icon' => 'fa-solid fa-diagram-project', 'value' => $segmentName],
                                "Insurance Company" => ['icon' => 'fa-solid fa-building-shield', 'value' => $companyName],
                                "Branch" => ['icon' => 'fa-solid fa-code-branch', 'value' => $branchName],
                                "Start Date" => ['icon' => 'fa-solid fa-calendar-day', 'value' => $startDate],
                                "End Date" => ['icon' => 'fa-solid fa-calendar-check', 'value' => $endDate],
                                "Policy Mode" => ['icon' => 'fa-solid fa-repeat', 'value' => $policyMode],
                                "Premium Amount" => ['icon' => 'fa-solid fa-money-bill-wave', 'value' => $premiumAmount],
                                "Tax Amount" => ['icon' => 'fa-solid fa-receipt', 'value' => $taxAmount],
                                "Payment Date" => ['icon' => 'fa-solid fa-calendar-days', 'value' => $paymentDate],
                                "Payment Mode" => ['icon' => 'fa-solid fa-credit-card', 'value' => $paymentMode],
                                "Nominee Name" => ['icon' => 'fa-solid fa-user-check', 'value' => $nomineeName],
                                "Nominee Contact Number" => ['icon' => 'fa-solid fa-phone', 'value' => $nomineeContact],
                                "Payment Amount" => ['icon' => 'fa-solid fa-indian-rupee-sign', 'value' => $paymentAmount],
                                "Sum Assured" => ['icon' => 'fa-solid fa-sack-dollar', 'value' => $sumAssured],
                                "Remarks" => ['icon' => 'fa-solid fa-comment-dots', 'value' => $remarks],
                            ];
                        @endphp

                        <div class="row g-3">
                            @foreach($policyFields as $label => $field)
                                <div class="col-md-6 d-flex justify-content-between align-items-center border-bottom py-2">
                                    <span class="text-secondary small fw-semibold d-inline-flex align-items-center gap-2">
                                        <i class="{{ $field['icon'] }} text-muted" style="width: 18px; text-align: center;"></i>
                                        {{ $label }}:
                                    </span>
                                    <strong class="text-dark small text-end">{{ $field['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Policy PDF History Section (Matching Screenshot) -->
                    <div class="bg-light p-4 rounded-4 mt-4 border">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <h5 class="text-dark fw-bold mb-0" style="font-size: 1.1rem;">Policy PDF History</h5>
                        </div>

                        @if($policy->pdfHistories && $policy->pdfHistories->count() > 0)
                            <div class="list-group list-group-flush bg-transparent">
                                @foreach($policy->pdfHistories as $document)
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom py-3">
                                        <div>
                                            <strong class="text-dark">{{ $document->label ?: ($document->original_file_name ?: 'Policy Copy') }}</strong>
                                            <div class="small text-muted">
                                                {{ $document->original_file_name ?: $document->file_name }}
                                                &middot; Uploaded {{ $document->created_at?->format('j M Y, g:i A') ?? '--' }}
                                                @if($document->uploader)
                                                    by {{ $document->uploader->name }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <a href="{{ route('customer.portal.documents.download', $document->id) }}" class="btn btn-outline-danger btn-sm px-3 d-inline-flex align-items-center gap-1" style="border-radius: 6px;">
                                                <i class="fa-solid fa-file-pdf"></i>
                                                <span>Download PDF</span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($policy->policy_pdf)
                            <div class="d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <strong class="text-dark">Original Policy Document</strong>
                                    <div class="small text-muted">Policy file copy</div>
                                </div>
                                <div>
                                    <a href="{{ asset('storage/policies/' . basename($policy->policy_pdf)) }}" target="_blank" class="btn btn-outline-danger btn-sm px-3" download>
                                        <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                        @else
                            <p class="text-muted mb-0 small">No policy PDFs have been uploaded.</p>
                        @endif
                    </div>
                </div>

                <!-- KYC Documents Section -->
                @php
                    $kycItems = [];
                    if (!empty($kyc)) {
                        $decodeFiles = function($field) use ($kyc) {
                            if (empty($kyc->$field)) return [];
                            $val = $kyc->$field;
                            if (is_string($val) && str_starts_with(trim($val), '[')) {
                                $arr = json_decode($val, true);
                                return is_array($arr) ? $arr : [$val];
                            }
                            return [$val];
                        };

                        $categories = [
                            'aadhaar_card' => ['title' => 'Aadhaar Card (ID Proof)', 'icon' => 'fa-id-card'],
                            'pan_card' => ['title' => 'PAN Card', 'icon' => 'fa-credit-card'],
                            'driving_licence' => ['title' => 'Driving Licence', 'icon' => 'fa-id-badge'],
                            'addr_aadhaar' => ['title' => 'Aadhaar Card (Address Proof)', 'icon' => 'fa-location-dot'],
                            'addr_driving_licence' => ['title' => 'Driving Licence (Address Proof)', 'icon' => 'fa-map-location-dot'],
                            'photo' => ['title' => 'Passport Photo', 'icon' => 'fa-image'],
                            'signature' => ['title' => 'Customer Signature', 'icon' => 'fa-signature'],
                        ];

                        foreach ($categories as $field => $meta) {
                            $files = $decodeFiles($field);
                            if (!empty($files)) {
                                foreach ($files as $idx => $fileName) {
                                    if (!empty($fileName)) {
                                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        $kycItems[] = [
                                            'title' => $meta['title'] . (count($files) > 1 ? ' (' . ($idx + 1) . ')' : ''),
                                            'icon' => $meta['icon'],
                                            'file_name' => $fileName,
                                            'url' => asset('uploads/kyc/' . $fileName),
                                            'is_image' => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']),
                                            'is_pdf' => $ext === 'pdf',
                                        ];
                                    }
                                }
                            }
                        }
                    }
                @endphp

                <div class="card border rounded-3 mb-0" style="border-color: #e2e8f0 !important;">
                    <div class="card-header bg-white py-3 px-3 d-flex align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-primary"></i>
                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 1rem;">Policy KYC Documents</h6>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        @if(!empty($kycItems))
                            <div class="row g-3">
                                @foreach($kycItems as $item)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between bg-light-subtle" style="border-color: #e2e8f0 !important;">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <div class="rounded-circle bg-white border d-flex align-items-center justify-content-center text-primary flex-shrink-0" style="width: 32px; height: 32px;">
                                                        <i class="fa-solid {{ $item['icon'] }} small"></i>
                                                    </div>
                                                    <span class="fw-bold text-dark small text-truncate" title="{{ $item['title'] }}">
                                                        {{ $item['title'] }}
                                                    </span>
                                                </div>
                                                <div class="text-muted small text-truncate mb-2" title="{{ $item['file_name'] }}" style="font-size: 0.78rem;">
                                                    <i class="fa-regular fa-file me-1"></i> {{ $item['file_name'] }}
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 mt-2 pt-2 border-top">
                                                <a href="{{ $item['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1">
                                                    <i class="fa-regular fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                                <a href="{{ $item['url'] }}" download class="btn btn-sm btn-light border flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1">
                                                    <i class="fa-solid fa-download"></i>
                                                    <span>Download</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0 small">No KYC documents have been uploaded for this policy.</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
