@extends('layouts.app')

@section('page_title', 'View Life Insurance Policy')

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Life Insurance Policy Details</h1>
                    <p class="text-muted small mb-0">View all saved details in section-wise format.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                    <a href="{{ route('life-insurance.edit-page', $policy->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="{{ route('life-insurance.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0 customer-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            @php
                $holderName = $policy->policyHolder?->name ?? trim(implode(' ', array_filter([$policy->first_name, $policy->middle_name, $policy->last_name])));
                $columnDocs = $policy->policy_documents;
                if (is_string($columnDocs) && $columnDocs !== '') { $columnDocs = json_decode($columnDocs, true); }
                if (!is_array($columnDocs)) { $columnDocs = []; }
                $resolveDocUrl = function (?string $path): string {
                    $path = trim((string) $path);
                    if ($path === '') {
                        return '';
                    }

                    $normalized = ltrim($path, '/');
                    $candidates = [
                        public_path($normalized),
                        public_path('storage/' . ltrim(str_replace('storage/', '', $normalized), '/')),
                    ];

                    foreach ($candidates as $candidate) {
                        if ($candidate && file_exists($candidate)) {
                            return asset($normalized);
                        }
                    }

                    if (str_starts_with($normalized, 'storage/')) {
                        return asset($normalized);
                    }

                    return asset('storage/' . ltrim($normalized, '/'));
                };
            @endphp

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="fw-semibold mb-3">1. Personal Info</h6>
                <div class="row g-2 small">
                    <div class="col-md-4"><strong>Transaction Date:</strong> {{ $policy->transaction_date ?: '--' }}</div>
                    <div class="col-md-4"><strong>Document Number:</strong> {{ $policy->document_number ?: '--' }}</div>
                    <div class="col-md-4"><strong>Family Group:</strong> {{ $policy->familyGroup?->group_name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Policy Holder:</strong> {{ $holderName ?: '--' }}</div>
                    <div class="col-md-4"><strong>Gender:</strong> {{ $policy->gender ?: '--' }}</div>
                    <div class="col-md-4"><strong>DOB:</strong> {{ $policy->dob ?: '--' }}</div>
                    <div class="col-md-4"><strong>Relation With Head:</strong> {{ $policy->relation_with_head ?: '--' }}</div>
                    <div class="col-md-4"><strong>Mobile:</strong> {{ $policy->mobile_number ?: '--' }}</div>
                    <div class="col-md-4"><strong>Email:</strong> {{ $policy->email_address ?: '--' }}</div>
                    <div class="col-12"><strong>Address:</strong> {{ trim(($policy->address_line1 ?: '') . ' ' . ($policy->address_line2 ?: '')) ?: '--' }}</div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="fw-semibold mb-3">2. Policy Details</h6>
                <div class="row g-2 small">
                    <div class="col-md-4"><strong>Insurance Company:</strong> {{ $policy->insuranceCompany?->name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Branch:</strong> {{ $policy->branch?->name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Zone:</strong> {{ $policy->zone ?: '--' }}</div>
                    <div class="col-md-4"><strong>Policy Number:</strong> {{ $policy->policy_number ?: '--' }}</div>
                    <div class="col-md-4"><strong>Plan Name:</strong> {{ $policy->insurance_plan_name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Plan Type:</strong> {{ $policy->insurance_plan_type ?: '--' }}</div>
                    <div class="col-md-4"><strong>Premium Mode:</strong> {{ $policy->premium_mode ?: '--' }}</div>
                    <div class="col-md-4"><strong>Risk Term:</strong> {{ $policy->risk_term ?: '--' }}</div>
                    <div class="col-md-4"><strong>Premium Paying Term:</strong> {{ $policy->premium_paying_term ?: '--' }}</div>
                    <div class="col-md-4"><strong>Sum Assured:</strong> {{ $policy->sum_assured !== null ? number_format((float)$policy->sum_assured, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Risk Start Date:</strong> {{ $policy->risk_start_date ?: '--' }}</div>
                    <div class="col-md-4"><strong>Maturity Date:</strong> {{ $policy->maturity_date ?: '--' }}</div>
                    <div class="col-md-4"><strong>Last Premium Payment Date:</strong> {{ $policy->last_premium_payment_date ?: '--' }}</div>
                    <div class="col-md-4"><strong>Next Premium Due Date:</strong> {{ $policy->next_premium_due_date ?: '--' }}</div>
                    <div class="col-md-4"><strong>Basic Premium Amount:</strong> {{ $policy->basic_premium_amount !== null ? number_format((float)$policy->basic_premium_amount, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Tax Amount:</strong> {{ $policy->tax_amount !== null ? number_format((float)$policy->tax_amount, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Total Premium Amount:</strong> {{ $policy->total_premium_amount !== null ? number_format((float)$policy->total_premium_amount, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Commissionable Amount:</strong> {{ $policy->commissionable_amount !== null ? number_format((float)$policy->commissionable_amount, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Policy Status:</strong> {{ $policy->policy_status ?: '--' }}</div>
                    <div class="col-md-4"><strong>Policy Received:</strong> {{ $policy->is_policy_received ? 'Yes' : 'No' }}</div>
                </div>
                <div class="mt-3">
                    <div class="small fw-semibold mb-2">Policy Documents (From Policy Record)</div>
                    <div class="row g-2">
                        @php
                            $topPolicyDocs = $columnDocs;
                        @endphp
                        @forelse($topPolicyDocs as $file)
                            @if(is_string($file) && trim($file) !== '')
                                @php $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); @endphp
                                <div class="col-6 col-md-3 col-lg-2">
                                    @php $docUrl = $resolveDocUrl($file); @endphp
                                    <a href="{{ $docUrl }}" target="_blank" class="text-decoration-none">
                                        @if(in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']))
                                            <img src="{{ $docUrl }}" class="img-fluid rounded border" style="height:90px;object-fit:cover;width:100%;" alt="doc">
                                        @else
                                            <div class="border rounded p-2 text-center small">{{ basename($file) }}</div>
                                        @endif
                                    </a>
                                </div>
                            @endif
                        @empty
                            <div class="col-12"><span class="text-muted small">No policy-level documents found.</span></div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="fw-semibold mb-3">3. Agency & Payment</h6>
                <div class="row g-2 small mb-3">
                    <div class="col-md-4"><strong>Broker:</strong> {{ $policy->broker?->broker_name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Agency Level:</strong> {{ $policy->agencyLevel?->name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Sub Agent:</strong> {{ $policy->subAgent?->name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Paid By Agent:</strong> {{ $policy->paidByAgentMethod?->name ?: ($policy->paid_by_agent ?: '--') }}</div>
                    <div class="col-md-4"><strong>Agent Amount:</strong> {{ $policy->agent_amount !== null ? number_format((float)$policy->agent_amount, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Paid By Client:</strong> {{ $policy->paidByClientMethod?->name ?: ($policy->paid_by_client ?: '--') }}</div>
                    <div class="col-md-4"><strong>Client Amount:</strong> {{ $policy->client_amount !== null ? number_format((float)$policy->client_amount, 2) : '--' }}</div>
                    <div class="col-md-4"><strong>Nominee Name:</strong> {{ $policy->nominee_name ?: '--' }}</div>
                    <div class="col-md-4"><strong>Nominee Contact:</strong> {{ $policy->nominee_contact_no ?: '--' }}</div>
                    <div class="col-12"><strong>Remarks:</strong> {{ $policy->remarks ?: '--' }}</div>
                </div>

                <h6 class="fw-semibold mb-2">Riders</h6>
                @if($policy->policyRiders && $policy->policyRiders->count())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Rider Name</th><th>Sum Assured</th><th>Risk Term</th><th>Premium Paying Term</th></tr></thead>
                            <tbody>
                                @foreach($policy->policyRiders as $rider)
                                    <tr>
                                        <td>{{ $rider->rider_name ?: '--' }}</td>
                                        <td>{{ $rider->sum_assured !== null ? number_format((float)$rider->sum_assured, 2) : '--' }}</td>
                                        <td>{{ $rider->risk_term ?: '--' }}</td>
                                        <td>{{ $rider->premium_paying_term ?: '--' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted small mb-0">No rider records found.</p>
                @endif
            </div>

            <div class="border rounded-3 p-3">
                <h6 class="fw-semibold mb-3">4. Documents</h6>
                <div class="row g-3">
                    @forelse($policy->policyDocuments as $doc)
                        @if($doc->document_file)
                            @php $ext = strtolower(pathinfo($doc->document_file, PATHINFO_EXTENSION)); @endphp
                            <div class="col-6 col-md-3 col-lg-2">
                                @php $docUrl = $resolveDocUrl($doc->document_file); @endphp
                                <a href="{{ $docUrl }}" target="_blank" class="text-decoration-none">
                                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']))
                                        <img src="{{ $docUrl }}" class="img-fluid rounded border" style="height:90px;object-fit:cover;width:100%;" alt="doc">
                                    @else
                                        <div class="border rounded p-2 text-center small">{{ basename($doc->document_file) }}</div>
                                    @endif
                                </a>
                                <div class="small mt-1"><strong>Type:</strong> {{ $doc->documentType?->document_type ?: '--' }}</div>
                                <div class="small"><strong>Name:</strong> {{ $doc->documentName?->document_name ?: '--' }}</div>
                                <div class="small text-muted mt-1">Nos: {{ $doc->nos ?: '--' }}</div>
                            </div>
                        @endif
                    @empty
                        <div class="col-12"><span class="text-muted small">No document rows found in policy documents table.</span></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
