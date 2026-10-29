<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 14mm 14mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            line-height: 1.4;
            background: #ffffff;
        }

        .header-table, .meta-table, .items-table, .summary-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2px;
            letter-spacing: 0.5px;
        }

        .brand-tagline {
            font-size: 10px;
            color: #0284c7;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 4px;
        }

        .brand-details {
            font-size: 9px;
            color: #475569;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #0369a1;
            text-align: right;
            text-transform: uppercase;
            margin: 0 0 4px;
        }

        .invoice-sub {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }

        .badge-box {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }

        .band {
            height: 4px;
            background: #0284c7;
            margin: 12px 0 14px;
        }

        .section-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 10px 12px;
            border-radius: 6px;
        }

        .section-heading {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            letter-spacing: 0.5px;
        }

        .info-label {
            color: #64748b;
            font-size: 9px;
        }

        .info-value {
            color: #0f172a;
            font-weight: bold;
            font-size: 9.5px;
        }

        .items-table {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
        }

        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 8px;
            text-align: left;
            border-right: 1px solid #334155;
        }

        .items-table th:last-child {
            border-right: none;
        }

        .items-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-box {
            width: 250px;
            margin-left: auto;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 12px;
        }

        .summary-box td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5px;
        }

        .summary-box .grand-total td {
            background: #f0f9ff;
            font-size: 11px;
            font-weight: bold;
            color: #0369a1;
            border-bottom: none;
        }

        .notes-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            background: #fbfcfe;
            margin-top: 14px;
        }

        .sign-area {
            text-align: right;
            padding-top: 25px;
        }

        .sign-line {
            display: inline-block;
            border-top: 1px solid #94a3b8;
            width: 180px;
            text-align: center;
            padding-top: 4px;
            font-weight: bold;
            color: #334155;
            font-size: 9px;
        }
    </style>
</head>
<body>
    @php
        $isCompanyPayIn = ($invoice->invoice_type === 'company_pay_in' || !empty($invoice->company_id));
        $billedEntityName = $isCompanyPayIn ? ($invoice->company?->name ?? 'Insurance Company') : ($invoice->customer?->name ?? 'Customer');
    @endphp

    {{-- Top Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="brand-name">RISKON INSURANCE</div>
                <div class="brand-tagline">Trust... We Build</div>
                <div class="brand-details">
                    Insurance Brokerage &amp; Commission Management<br>
                    Organization: <strong>{{ $invoice->organization ?? 'Riskon Insurance' }}</strong><br>
                    Direct Account: <strong>{{ $invoice->account_name ?? 'My Billing Account' }}</strong>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="invoice-title">
                    {{ $isCompanyPayIn ? 'COMMISSION TAX INVOICE' : 'TAX INVOICE' }}
                </div>
                <div class="invoice-sub">{{ $invoice->number }}</div>
                <div style="margin-top: 4px;">
                    <span class="badge-box">
                        {{ $isCompanyPayIn ? 'Company Pay-In Commission' : 'Customer Billing' }}
                    </span>
                    <span class="badge-box" style="background: {{ $invoice->status === 'paid' ? '#dcfce7; color: #15803d;' : '#fef3c7; color: #b45309;' }}">
                        {{ strtoupper($invoice->status ?? 'UNPAID') }}
                    </span>
                </div>
                <div style="font-size: 9px; color: #64748b; margin-top: 5px;">
                    Invoice Date: <strong>{{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</strong><br>
                    Due Date: <strong>{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="band"></div>

    {{-- Meta Details: Billed To & Remittance Account (Accounts.csv) --}}
    <table class="meta-table">
        <tr>
            <td style="width: 49%; vertical-align: top;">
                <div class="section-card">
                    <div class="section-heading">Billed To ({{ $isCompanyPayIn ? 'Insurance Company' : 'Customer' }})</div>
                    <div style="font-size: 12px; font-weight: bold; color: #0f172a; margin-bottom: 3px;">
                        {{ $billedEntityName }}
                    </div>
                    @if($isCompanyPayIn)
                        <div class="info-label">Partner Classification: <span class="info-value">Authorized Insurer</span></div>
                        <div class="info-label">Billing Purpose: <span class="info-value">Policy Pay-In Brokerage Commission</span></div>
                    @else
                        @if($invoice->customer?->email)
                            <div class="info-label">Email: <span class="info-value">{{ $invoice->customer->email }}</span></div>
                        @endif
                        @if($invoice->customer?->phone)
                            <div class="info-label">Phone: <span class="info-value">{{ $invoice->customer->phone }}</span></div>
                        @endif
                    @endif
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%; vertical-align: top;">
                <div class="section-card">
                    <div class="section-heading">Remittance Billing Account (Accounts.csv)</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="info-label" style="width: 45%;">Account Name:</td>
                            <td class="info-value">{{ $invoice->account_name ?? 'My Billing Account' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Account ID:</td>
                            <td class="info-value" style="font-family: monospace; color: #0284c7;">{{ $invoice->account_id_code ?? '01E2F9-74AFE8-B222F4' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Account Type:</td>
                            <td class="info-value">{{ $invoice->account_type ?? 'Direct' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Organization:</td>
                            <td class="info-value">{{ $invoice->organization ?? 'Riskon Insurance' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Line Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 32%;">Item / Policy Description</th>
                <th style="width: 17%;">Holder Name</th>
                <th style="width: 13%;" class="text-right">Gross Premium</th>
                <th style="width: 10%;" class="text-center">Pay-In %</th>
                <th style="width: 11%;" class="text-right">Commission</th>
                <th style="width: 12%;" class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $idx => $item)
                <tr>
                    <td class="text-center" style="color: #64748b;">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->policy_number)
                            <div style="font-size: 8.5px; color: #475569; font-family: monospace;">Policy # {{ $item->policy_number }}</div>
                        @endif
                    </td>
                    <td>{{ $item->holder_name ?? '—' }}</td>
                    <td class="text-right">
                        {{ $item->gross_premium ? '₹' . number_format($item->gross_premium, 2) : '—' }}
                    </td>
                    <td class="text-center">
                        {{ $item->pay_in_percentage ? number_format($item->pay_in_percentage, 2) . '%' : '—' }}
                    </td>
                    <td class="text-right">
                        ₹{{ number_format($item->pay_in_amount ?? $item->amount, 2) }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        ₹{{ number_format($item->total_price ?: (($item->pay_in_amount ?? $item->amount) * $item->quantity), 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">No line items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Financial Summary --}}
    <table class="summary-table" style="margin-top: 10px;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="notes-box">
                    <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px; color: #0f172a;">Remittance Instructions:</div>
                    <div style="font-size: 8.5px; color: #475569;">
                        {{ $invoice->comment ?: 'Brokerage Pay-In commission invoice payable by direct remittance referencing Account ID 01E2F9-74AFE8-B222F4.' }}
                    </div>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="summary-box">
                    <tr>
                        <td class="info-label">Subtotal:</td>
                        <td class="text-right" style="font-weight: bold;">
                            ₹{{ number_format($invoice->subtotal ?: $invoice->items->sum('total_price'), 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="info-label">GST / Tax ({{ $invoice->tax_percentage ?? 18 }}%):</td>
                        <td class="text-right" style="font-weight: bold;">
                            ₹{{ number_format($invoice->tax_amount, 2) }}
                        </td>
                    </tr>
                    <tr class="grand-total">
                        <td>Total Amount:</td>
                        <td class="text-right">
                            ₹{{ number_format($invoice->grand_total ?: $invoice->total_amount, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Signatory Footer --}}
    <div class="sign-area">
        <div style="font-size: 9px; color: #64748b; margin-bottom: 25px;">For <strong>RISKON INSURANCE</strong></div>
        <div class="sign-line">Authorized Signatory</div>
    </div>
</body>
</html>
