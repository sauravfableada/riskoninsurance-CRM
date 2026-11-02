<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoices Report</title>
    <style>
        @page {
            margin: 20px 25px;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #222;
            background: #fff;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            margin-top: 2px;
        }
        .meta-text {
            font-size: 8px;
            color: #64748b;
            text-align: right;
            line-height: 1.35;
        }

        /* Filter bar */
        .filter-bar {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
            margin-bottom: 10px;
            font-size: 8px;
            color: #334155;
        }
        .filter-label {
            font-weight: bold;
            color: #0f172a;
            margin-right: 4px;
        }
        .filter-pill {
            display: inline-block;
            background-color: #e2e8f0;
            color: #1e293b;
            padding: 1px 5px;
            border-radius: 3px;
            margin-right: 5px;
            font-size: 7.5px;
        }

        /* KPI summary boxes */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 10px;
        }
        .kpi-table td {
            width: 25%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: left;
        }
        .kpi-label {
            font-size: 7px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-value.green { color: #16a34a; }
        .kpi-value.orange { color: #ea580c; }
        .kpi-value.blue { color: #2563eb; }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4.5px 6px;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: monospace; font-size: 8px; }

        .badge-status {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .status-unpaid {
            background-color: #fef9c3;
            color: #a16207;
            border: 1px solid #fef08a;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-type {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }
        .type-payin {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }
        .type-customer {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .total-row {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            border-top: 2px solid #0f172a;
        }

        .footer {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div class="report-title">Invoices &amp; Commissions Report</div>
                <div class="company-name">{{ $companyName }}</div>
            </td>
            <td style="width: 50%;" class="meta-text">
                <div>Generated: <strong>{{ date('d M Y, h:i A') }}</strong></div>
                <div>Generated By: <strong>{{ auth()->user()?->name ?: 'Administrator' }}</strong></div>
                <div>Total Records: <strong>{{ $invoices->count() }}</strong></div>
            </td>
        </tr>
    </table>

    <!-- Filters Summary -->
    <div class="filter-bar">
        <span class="filter-label">Report Scope:</span>
        @if($hasFilters)
            @if(!empty($filters['type']))
                <span class="filter-pill">Type: {{ $filters['type'] === 'company_pay_in' ? 'Company Pay-In' : 'Customer Invoices' }}</span>
            @endif
            @if(!empty($filters['company']))
                <span class="filter-pill">Company: {{ $filters['company'] }}</span>
            @endif
            @if(!empty($filters['status']))
                <span class="filter-pill">Status: {{ ucfirst($filters['status']) }}</span>
            @endif
            @if(!empty($filters['start_date']))
                <span class="filter-pill">From: {{ date('d-m-Y', strtotime($filters['start_date'])) }}</span>
            @endif
            @if(!empty($filters['end_date']))
                <span class="filter-pill">To: {{ date('d-m-Y', strtotime($filters['end_date'])) }}</span>
            @endif
            @if(!empty($filters['search']))
                <span class="filter-pill">Search: "{{ $filters['search'] }}"</span>
            @endif
            <span style="color: #64748b; font-size: 7.5px;">(Filtered results matching the current screen view)</span>
        @else
            <span class="filter-pill" style="background-color: #e0f2fe; color: #0369a1;">All Invoices (No Filters Applied)</span>
        @endif
    </div>

    <!-- Financial KPI Summary Cards -->
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">Total Invoices</div>
                <div class="kpi-value blue">{{ $invoices->count() }}</div>
            </td>
            <td>
                <div class="kpi-label">Total Volume (₹)</div>
                <div class="kpi-value">₹{{ number_format($totalAmount, 2) }}</div>
            </td>
            <td>
                <div class="kpi-label">Paid / Received (₹)</div>
                <div class="kpi-value green">₹{{ number_format($paidAmount, 2) }}</div>
            </td>
            <td>
                <div class="kpi-label">Pending / Unpaid (₹)</div>
                <div class="kpi-value orange">₹{{ number_format($unpaidAmount, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">#</th>
                <th style="width: 130px;">Invoice #</th>
                <th style="width: 85px;">Type</th>
                <th>Billed To</th>
                <th style="width: 100px;">Account ID</th>
                <th style="width: 65px;" class="text-center">Date</th>
                <th style="width: 65px;" class="text-center">Due Date</th>
                <th style="width: 75px;" class="text-right">Subtotal (₹)</th>
                <th style="width: 65px;" class="text-right">GST / Tax</th>
                <th style="width: 85px;" class="text-right">Grand Total (₹)</th>
                <th style="width: 55px;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $idx => $inv)
                @php
                    $isCompany = ($inv->invoice_type === 'company_pay_in');
                    $billedTo = $isCompany ? ($inv->company?->name ?: 'Insurance Company') : ($inv->customer?->name ?: 'Customer');
                    $subtotalVal = (float) ($inv->subtotal ?: ($inv->items->sum('total_price') ?: 0));
                    $taxVal = (float) ($inv->tax_amount ?: 0);
                    $grandVal = (float) ($inv->grand_total ?: ($inv->total_amount ?: ($subtotalVal + $taxVal)));
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold font-mono">{{ $inv->number ?: ('#' . $inv->id) }}</td>
                    <td>
                        <span class="badge-type {{ $isCompany ? 'type-payin' : 'type-customer' }}">
                            {{ $isCompany ? 'Company Pay-In' : 'Customer' }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $billedTo }}</div>
                        @if(!$isCompany && $inv->customer?->email)
                            <div style="font-size: 7px; color: #64748b;">{{ $inv->customer->email }}</div>
                        @endif
                    </td>
                    <td class="font-mono text-muted">{{ $inv->account_id_code ?: '—' }}</td>
                    <td class="text-center">{{ $inv->invoice_date ? date('d-m-Y', strtotime($inv->invoice_date)) : '—' }}</td>
                    <td class="text-center">{{ $inv->due_date ? date('d-m-Y', strtotime($inv->due_date)) : '—' }}</td>
                    <td class="text-right">{{ number_format($subtotalVal, 2) }}</td>
                    <td class="text-right">{{ number_format($taxVal, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($grandVal, 2) }}</td>
                    <td class="text-center">
                        @if($inv->status === 'paid')
                            <span class="badge-status status-paid">Paid</span>
                        @elseif($inv->status === 'cancelled')
                            <span class="badge-status status-cancelled">Cancelled</span>
                        @else
                            <span class="badge-status status-unpaid">Unpaid</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 25px; color: #64748b; font-size: 10px;">
                        No invoices found matching the specified filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($invoices->count() > 0)
            <tfoot>
                <tr class="total-row">
                    <td colspan="7" class="text-right fw-bold">TOTAL:</td>
                    <td class="text-right fw-bold">{{ number_format($totalSubtotal, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($totalTax, 2) }}</td>
                    <td class="text-right fw-bold" style="color: #1e40af;">₹{{ number_format($totalAmount, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Footer -->
    <div class="footer">
        <span>Confidential &bull; Riskon Insurance CRM System &bull; Generated electronically on {{ date('d M Y, h:i:s A') }}</span>
    </div>

</body>
</html>

