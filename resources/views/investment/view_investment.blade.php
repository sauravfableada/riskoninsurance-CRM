@extends('layouts.app')

@section('content')
@php
$inv = $investment ?? [];
$type = $inv['investment_type_main'] ?? '';
$familyGroupName = $familyGroupName ?? '';
$holderName = $holderName ?? '';
$extraDocuments = $inv['documents'] ?? [];

$v = function ($key) use ($inv) {
    return isset($inv[$key]) && $inv[$key] !== '' && $inv[$key] !== null ? e($inv[$key]) : '—';
};
$vd = function ($key) use ($inv) {
    if (empty($inv[$key])) return '—';
    $d = strtotime($inv[$key]);
    return $d ? date('d-m-Y', $d) : e($inv[$key]);
};
$vdf = function ($key) use ($inv) {
    if (empty($inv[$key])) return '—';
    $d = strtotime($inv[$key]);
    return $d ? date('m/d/Y', $d) : e($inv[$key]);
};
@endphp

<style>
    .table-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .form-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 2rem;
    }

    .step-item {
        display: flex;
        align-items: center;
        position: relative;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        z-index: 1;
    }

    .step-item.active .step-circle {
        background: #5e72d8;
        color: #fff;
        box-shadow: 0 4px 10px rgba(94, 114, 216, 0.3);
    }

    .step-item.completed .step-circle {
        background: #2dce89;
        color: #fff;
    }

    .step-line {
        width: 80px;
        height: 2px;
        background: #e9ecef;
    }

    .step-item.completed .step-line {
        background: #2dce89;
    }

    .step-item.active .step-line {
        background: #5e72d8;
    }

    .step-label {
        position: absolute;
        top: 45px;
        font-size: 12px;
        color: #6c757d;
        white-space: nowrap;
    }

    .step-item.active .step-label {
        color: #5e72d8;
        font-weight: 600;
    }

    .step-item.completed .step-label {
        color: #2dce89;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #344767;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
    }

    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(94, 114, 216, 0.1);
        color: #5e72d8;
        margin-right: 10px;
    }

    .detail-table {
        margin-bottom: 0;
    }

    .detail-table th {
        width: 35%;
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
        color: #495057;
        padding: 10px 14px;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-table td {
        padding: 10px 14px;
        font-size: 13px;
        color: #344767;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-table tr:last-child th,
    .detail-table tr:last-child td {
        border-bottom: none;
    }

    .view-value {
        padding: 0.5rem 0.75rem;
        font-size: 14px;
        color: #344767;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        min-height: 38px;
        display: flex;
        align-items: center;
    }

    .type-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .type-mutual_fund {
        background-color: #d4edda;
        color: #155724;
    }

    .type-bank {
        background-color: #cce5ff;
        color: #004085;
    }

    .type-post_office {
        background-color: #fff3cd;
        color: #856404;
    }

    .type-govt_bonds {
        background-color: #e2d5f1;
        color: #4a2c6f;
    }

    .type-other {
        background-color: #e9ecef;
        color: #495057;
    }

    .doc-list-table {
        font-size: 13px;
    }

    .doc-list-table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .step-line {
            width: 40px;
        }

        .step-label {
            font-size: 10px;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }
    }

    .card .card-header {
        padding: 0rem;
    }

    @media (max-width: 576px) {
        .card-header {
            height: 92px;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="card table-card form-card">

        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Investment Details</h1>
                    <p class="text-muted small mb-0">Complete information about this investment</p>
                </div>
                <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                    <a href="{{ route('investment.edit', $inv['id'] ?? '') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="fa-solid fa-pen me-1"></i>Edit
                    </a>
                    <a href="{{ route('investment.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>


        <div class="card-body p-4">
            <!-- Step indicator (all completed for view) -->
            <div class="step-indicator">
                <div class="step-item completed">
                    <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                    <div class="step-line"></div>
                    <span class="step-label">Personal Details</span>
                </div>
                <div class="step-item completed">
                    <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                    <div class="step-line"></div>
                    <span class="step-label">Investment Details</span>
                </div>
                <div class="step-item completed">
                    <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                    <span class="step-label">Documents</span>
                </div>
            </div>

            <!-- 1. Personal Information -->
            <div class="mb-4">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fa-solid fa-user"></i></span>
                    Personal Information
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Transaction Date</label>
                        <div class="view-value">{{ $vdf('transaction_date') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Document Number</label>
                        <div class="view-value">{{ $v('document_number') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Family Group</label>
                        <div class="view-value">{{ $familyGroupName ? e($familyGroupName) : '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Investment Holder Name</label>
                        <div class="view-value">{{ $holderName ? e($holderName) : '—' }}</div>
                    </div>
                </div>
                <div class="form-section-title mt-4">
                    <span class="section-icon"><i class="fa-solid fa-address-card"></i></span>
                    Name & Contact Details
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <div class="view-value">{{ $v('first_name') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <div class="view-value">{{ $v('middle_name') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <div class="view-value">{{ $v('last_name') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobile Number</label>
                        <div class="view-value">{{ $v('mobile_number') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email Address</label>
                        <div class="view-value">{{ $v('email_address') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <div class="view-value">{{ $v('gender') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <div class="view-value">{{ $vd('date_of_birth') }}</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address</label>
                        <div class="view-value">{{ trim($v('address_line_1') . ' ' . $v('address_line_2')) ?: '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <div class="view-value">{{ $v('city') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Area</label>
                        <div class="view-value">{{ $v('area') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Family Head Name</label>
                        <div class="view-value">{{ $v('family_head_name') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Relation with Head</label>
                        <div class="view-value">{{ $v('relation_with_head') }}</div>
                    </div>
                </div>
            </div>

            <!-- 2. Investment Details -->
            <div class="mb-4">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fa-solid fa-chart-line"></i></span>
                    Investment Details
                </div>

                @if ($type === 'mutual_fund')
                    <div class="form-section-title mt-3" style="font-size: 14px;">
                        Transaction Details
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label">Transaction Type</label>
                            <div class="view-value">{{ $v('mf_transaction_type_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Folio Number</label>
                            <div class="view-value">{{ $v('mf_folio_number_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Type</label>
                            <div class="view-value">{{ $v('mf_investment_type_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Fund Name</label>
                            <div class="view-value">{{ $v('mf_fund_name_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Fund Type</label>
                            <div class="view-value">{{ $v('mf_fund_type_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Select Broker</label>
                            <div class="view-value">{{ $v('mf_broker_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Select Mode</label>
                            <div class="view-value">{{ $v('mf_mode_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Start Date</label>
                            <div class="view-value">{{ $vdf('mf_start_date') }}</div>
                        </div>
                    </div>
                    <div class="form-section-title mt-3" style="font-size: 14px;">
                        Financial Details
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Number Of Unit Purchase</label>
                            <div class="view-value">{{ $v('mf_units_purchase') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Unit Price (₹)</label>
                            <div class="view-value">{{ $v('mf_unit_price') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Amount (₹)</label>
                            <div class="view-value">{{ $v('mf_investment_amount') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Total Balance Unit</label>
                            <div class="view-value">{{ $v('mf_total_balance_unit') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Total Fund Value (₹)</label>
                            <div class="view-value">{{ $v('mf_total_fund_value') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Total Count</label>
                            <div class="view-value">{{ $v('mf_total_count') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Total Investment Balance Amount (₹)</label>
                            <div class="view-value">{{ $v('mf_total_investment_balance') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Payment Bank</label>
                            <div class="view-value">{{ $v('mf_payment_bank_name') }}</div>
                        </div>
                        <div class="col-12"><label class="form-label">Remarks</label>
                            <div class="view-value" style="min-height: 60px;">{{ $v('mf_remarks') }}</div>
                        </div>
                        @if (!empty($inv['mf_document']))
                            <div class="col-12"><label class="form-label">Investment Document</label>
                                <div class="view-value"><a href="{{ url('uploads/' . $inv['mf_document']) }}" target="_blank" class="text-primary"><i class="fa-solid fa-file me-1"></i>View file</a></div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($type === 'bank')
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Bank Name</label>
                            <div class="view-value">{{ $v('bank_name_text') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Account Type</label>
                            <div class="view-value">{{ $v('bank_account_type') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Customer No</label>
                            <div class="view-value">{{ $v('bank_customer_no') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Certificate No</label>
                            <div class="view-value">{{ $v('bank_certificate_no') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Amount (₹)</label>
                            <div class="view-value">{{ $v('bank_investment_amount') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Total Invested Amount (₹)</label>
                            <div class="view-value">{{ $v('bank_total_invested_amount') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Deposit Date</label>
                            <div class="view-value">{{ $vd('bank_deposit_date') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Mature Date</label>
                            <div class="view-value">{{ $vd('bank_mature_date') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Interest Rate</label>
                            <div class="view-value">{{ $v('bank_interest_rate') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Mode</label>
                            <div class="view-value">{{ $v('bank_mode_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Payment Bank</label>
                            <div class="view-value">{{ $v('bank_payment_bank_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Paid by Client</label>
                            <div class="view-value">{{ $v('bank_paid_by_client') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Paid by Agent</label>
                            <div class="view-value">{{ $v('bank_paid_by_agent') }}</div>
                        </div>
                        <div class="col-12"><label class="form-label">Remarks</label>
                            <div class="view-value" style="min-height: 60px;">{{ $v('bank_remarks') }}</div>
                        </div>
                        @if (!empty($inv['bank_document']))
                            <div class="col-12"><label class="form-label">Document</label>
                                <div class="view-value"><a href="{{ url('uploads/' . $inv['bank_document']) }}" target="_blank" class="text-primary"><i class="fa-solid fa-file me-1"></i>View file</a></div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($type === 'post_office')
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Account Type</label>
                            <div class="view-value">{{ $v('po_account_type') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Certificate / Account No</label>
                            <div class="view-value">{{ $v('po_certificate_account_no') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Payment Bank</label>
                            <div class="view-value">{{ $v('po_payment_bank_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Amount (₹)</label>
                            <div class="view-value">{{ $v('po_investment_amount') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Opening Date</label>
                            <div class="view-value">{{ $vd('po_opening_date') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Mature Date</label>
                            <div class="view-value">{{ $vd('po_mature_date') }}</div>
                        </div>
                        <div class="col-12"><label class="form-label">Remarks</label>
                            <div class="view-value" style="min-height: 60px;">{{ $v('po_remarks') }}</div>
                        </div>
                        @if (!empty($inv['po_document']))
                            <div class="col-12"><label class="form-label">Document</label>
                                <div class="view-value"><a href="{{ url('uploads/' . $inv['po_document']) }}" target="_blank" class="text-primary"><i class="fa-solid fa-file me-1"></i>View file</a></div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($type === 'govt_bonds')
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Bond Name</label>
                            <div class="view-value">{{ $v('bond_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">ISIN</label>
                            <div class="view-value">{{ $v('bond_id_isin') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Amount (₹)</label>
                            <div class="view-value">{{ $v('bond_investment_amount') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Payment Bank</label>
                            <div class="view-value">{{ $v('bond_payment_bank_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Maturity Date</label>
                            <div class="view-value">{{ $vd('bond_maturity_date') }}</div>
                        </div>
                        <div class="col-12"><label class="form-label">Remarks</label>
                            <div class="view-value" style="min-height: 60px;">{{ $v('bond_remarks') }}</div>
                        </div>
                        @if (!empty($inv['bond_document']))
                            <div class="col-12"><label class="form-label">Document</label>
                                <div class="view-value"><a href="{{ url('uploads/' . $inv['bond_document']) }}" target="_blank" class="text-primary"><i class="fa-solid fa-file me-1"></i>View file</a></div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($type === 'other')
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Investment Type</label>
                            <div class="view-value">{{ $v('other_investment_type_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Place</label>
                            <div class="view-value">{{ $v('other_investment_to_place') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Party Name</label>
                            <div class="view-value">{{ $v('other_invested_party_name') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Investment Amount (₹)</label>
                            <div class="view-value">{{ $v('other_investment_amount') }}</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">Payment Bank</label>
                            <div class="view-value">{{ $v('other_payment_bank_name') }}</div>
                        </div>
                        <div class="col-12"><label class="form-label">Remarks</label>
                            <div class="view-value" style="min-height: 60px;">{{ $v('other_remarks') }}</div>
                        </div>
                        @if (!empty($inv['other_document']))
                            <div class="col-12"><label class="form-label">Document</label>
                                <div class="view-value"><a href="{{ url('uploads/' . $inv['other_document']) }}" target="_blank" class="text-primary"><i class="fa-solid fa-file me-1"></i>View file</a></div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- 3. Upload Documents -->
            <div class="mb-4">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fa-solid fa-file-circle-plus"></i></span>
                    Upload Documents
                </div>
                @if (!empty($extraDocuments))
                    <div class="table-responsive">
                        <table class="table table-bordered doc-list-table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>Document Name</th>
                                    <th>Upload Document</th>
                                    <th>Nos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($extraDocuments as $doc)
                                    <tr>
                                        <td>{{ e($doc['document_type_name'] ?? '—') }}</td>
                                        <td>{{ e($doc['document_name_text'] ?? '—') }}</td>
                                        <td>
                                            @if (!empty($doc['document_image_data']))
                                                <img src="{{ $doc['document_image_data'] }}" alt="Document" class="img-thumbnail" style="max-width: 120px; max-height: 120px; object-fit: contain;">
                                            @elseif (!empty($doc['document_file']))
                                                <a href="{{ !empty($doc['document_file_full_path']) ? $doc['document_file_full_path'] : url('uploads/' . $doc['document_file']) }}" target="_blank" class="text-primary"><i class="fa-solid fa-file me-1"></i>View file</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ e($doc['document_nos'] ?? '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No additional documents uploaded.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
