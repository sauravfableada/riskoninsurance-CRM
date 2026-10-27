@extends('layouts.app')

@section('content')
@php
// Helper for safe value rendering with default "--"
$val = function ($key, $default = '--') use ($endorsement) {
    if (!isset($endorsement[$key])) {
        return $default;
    }

    $value = $endorsement[$key];

    if ($value === null) {
        return $default;
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '' || strtolower($trimmed) === 'null') {
            return $default;
        }
    }

    return $value;
};

$endorsementType = $endorsement['endorsement_type'] ?? 'general';
@endphp

<style>
    .endorsement-view-wrap {
        padding-top: 0.75rem;
        padding-bottom: 1.25rem;
    }

    .ev-card {
        background: #fff;
        border-radius: 16px;
        border: 0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        padding: 0;
        overflow: hidden;
    }

    .ev-header {
        background: #1B2A4A;
        color: #fff;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ev-header-title {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin: 0;
    }

    .ev-header-subtitle {
        font-size: 0.9rem;
        font-weight: 500;
        opacity: 0.9;
    }

    .ev-body {
        padding: 18px 20px 20px;
    }

    .ev-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1B2A4A;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        padding-bottom: 6px;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .ev-field-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
        font-size: 0.9rem;
    }

    .ev-field-label {
        color: #6c757d;
        margin-right: 12px;
    }

    .ev-field-value {
        font-weight: 600;
        color: #1f2d3d;
        text-align: right;
        max-width: 60%;
        word-break: break-word;
    }

    .ev-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .ev-badge-success { background: #d1e7dd; color: #0f5132; }
    .ev-badge-warning { background: #fff3cd; color: #664d03; }
    .ev-badge-secondary { background: #e2e3e5; color: #41464b; }

    .ev-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
    }

    .ev-table th,
    .ev-table td {
        padding: 8px 10px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        vertical-align: middle;
    }

    .ev-table thead th {
        background: #f8f9fa;
        font-weight: 700;
        color: #2a3a4f;
        white-space: nowrap;
    }
</style>

<div class="container-fluid endorsement-view-wrap mt-4">
    <div class="ev-card">
        <div class="ev-header">
            <div>
                <h6 class="ev-header-title mb-1 text-capitalize" style=" color: white; ">ENDORSEMENT DETAILS</h6>
                <div class="ev-header-subtitle text-capitalize">
                    {{ strtoupper($endorsementType === 'client' ? 'Client Endorsement' : 'General Endorsement') }}
                    &nbsp;|&nbsp; ID #{{ $val('id') }}
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @php
                $status = strtolower((string) ($endorsement['status'] ?? ''));
                $statusClass = 'ev-badge-secondary';
                if ($status === 'approved') $statusClass = 'ev-badge-success';
                elseif ($status === 'pending') $statusClass = 'ev-badge-warning';
                @endphp
                <span class="ev-badge {{ $statusClass }}">{{ strtoupper($status ?: '--') }}</span>
                <a href="{{ route('endorsement.index') }}" class="btn btn-light btn-sm">
                    <i class="fa fa-angle-left"></i> Back
                </a>
            </div>
        </div>

        <div class="ev-body">
            @if ($endorsementType !== 'client')
            <div class="mb-4">
                <div class="ev-section-title">Step 1: Basic Information</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="ev-field-row">
                            <span class="ev-field-label">Transaction Date</span>
                            <span class="ev-field-value">{{ $val('transaction_date') }}</span>
                        </div>
                        <div class="ev-field-row">
                            <span class="ev-field-label">Family Group</span>
                            <span class="ev-field-value">{{ $val('family_group_name') }}</span>
                        </div>
                        <div class="ev-field-row">
                            <span class="ev-field-label">Policy Holder Name</span>
                            <span class="ev-field-value">{{ $val('policy_holder_name') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ev-field-row">
                            <span class="ev-field-label">Created By</span>
                            <span class="ev-field-value">{{ $val('created_by_name') }}</span>
                        </div>
                        <div class="ev-field-row">
                            <span class="ev-field-label">Created At</span>
                            <span class="ev-field-value">{{ $val('created_at') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="ev-section-title">Step 2: Vehicle / Policy Details</div>
                <div class="table-responsive mb-3">
                    <table class="ev-table">
                        <thead>
                        <tr>
                            <th>Field Name</th>
                            <th>Existing Data</th>
                            <th>Transaction Type</th>
                            <th>To Be Endorse</th>
                            <th>Remarks</th>
                            <th>Endorse Effect</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                        $vehicleFields = [
                            'Vehicle Registration Number' => 'vehicle_reg_no',
                            'Vehicle Name' => 'vehicle_name',
                            'Engine No' => 'engine_no',
                            'Chassis No' => 'chassis_no',
                            'Vehicle Brand' => 'vehicle_brand',
                            'Vehicle Fuel Type' => 'vehicle_fuel_type',
                            'Register Date' => 'register_date',
                            'Year Of Manufacture' => 'year_of_manufacture',
                            'RTO' => 'rto',
                            'CC' => 'cc',
                            'GVW' => 'gvw',
                            'NCB' => 'ncb',
                            'Vehicle HP' => 'vehicle_hp',
                        ];
                        @endphp

                        @foreach ($vehicleFields as $label => $baseKey)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>{{ $val($baseKey) }}</td>
                                <td>{{ $val($baseKey . '_transaction_type') }}</td>
                                <td>{{ $val($baseKey . '_to_be_endorse') }}</td>
                                <td>{{ $val($baseKey . '_remarks') }}</td>
                                <td>{{ $val($baseKey . '_endorse_effect') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if ($endorsementType === 'client')
                <div class="mb-4">
                    <div class="ev-section-title">Client Information</div>
                    <div class="table-responsive mb-3">
                        <table class="ev-table">
                            <thead>
                            <tr>
                                <th>Field Name</th>
                                <th>Existing Data</th>
                                <th>Transaction Type</th>
                                <th>To Be Endorse</th>
                                <th>Remarks</th>
                                <th>Endorse Effect</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                            $clientFields = [
                                'First Name' => 'first_name',
                                'Last Name' => 'last_name',
                                'DOB' => 'dob',
                                'Relation' => 'relation_with_head',
                                'Gender' => 'gender',
                                'City' => 'city',
                                'Mobile' => 'mobile_number',
                            ];
                            @endphp
                            @foreach ($clientFields as $label => $baseKey)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>{{ $val($baseKey) }}</td>
                                    <td>{{ $val($baseKey . '_transaction_type') }}</td>
                                    <td>{{ $val($baseKey . '_to_be_endorse') }}</td>
                                    <td>{{ $val($baseKey . '_remarks') }}</td>
                                    <td>{{ $val($baseKey . '_endorse_effect') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mb-2">
                <div class="ev-section-title">Other Details</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="ev-field-row">
                            <span class="ev-field-label">Effective Date</span>
                            <span class="ev-field-value">{{ $val('endorsement_effective_date') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ev-field-row">
                            <span class="ev-field-label">Endorsement Letter</span>
                            <span class="ev-field-value">
                                @if (!empty($endorsement['endorsement_letter']))
                                    <a href="{{ asset('storage/' . $endorsement['endorsement_letter']) }}" target="_blank">View File</a>
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
