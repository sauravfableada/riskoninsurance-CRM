<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 16mm 18mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            line-height: 1.45;
            background: #ffffff;
        }

        .invoice-shell {
            width: 100%;
        }

        .top-band {
            height: 8px;
            background: #f59e0b;
            border-radius: 10px 10px 0 0;
        }

        .hero {
            border: 1px solid #e2e8f0;
            border-top: 0;
            border-radius: 0 0 12px 12px;
            padding: 16px 18px 14px;
            background: #f8fbff;
            margin-bottom: 14px;
        }

        .table-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-logo {
            height: 26px;
            width: auto;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 3px;
        }

        .brand-copy,
        .muted {
            color: #475569;
            font-size: 10px;
        }

        .invoice-kicker {
            text-align: right;
        }

        .invoice-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #f59e0b;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .invoice-number {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 5px;
        }

        .section-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 14px 10px;
            background: #ffffff;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #64748b;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .block-title {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 6px;
            color: #0f172a;
        }

        .meta-grid td {
            vertical-align: top;
        }

        .meta-box {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            background: #ffffff;
        }

        .meta-line {
            margin-bottom: 4px;
        }

        .meta-line strong {
            color: #1e293b;
        }

        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .invoice-items thead th {
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 9px 10px;
            text-align: left;
            border-right: 1px solid #1e293b;
        }

        .invoice-items thead th:last-child {
            border-right: none;
        }

        .invoice-items tbody td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: top;
        }

        .invoice-items tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-wrap {
            margin-top: 10px;
        }

        .summary-box {
            width: 240px;
            margin-left: auto;
            border: 1px solid #dbe4ef;
            border-radius: 12px;
            overflow: hidden;
        }

        .summary-row {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-row td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .summary-row tr:last-child td {
            border-bottom: none;
        }

        .summary-total td {
            background: #eff6ff;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }

        .note-box,
        .terms-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            background: #ffffff;
            margin-top: 12px;
        }

        .terms-list {
            margin: 8px 0 0;
            padding-left: 16px;
            color: #334155;
        }

        .terms-list li {
            margin-bottom: 4px;
        }

        .footer-bar {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #64748b;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('logo/fableadcrmLogo.png');
        $currencyCode = $invoice->currency?->code ?: 'INR';
        $customer = $invoice->customer;
        $invoiceDate = optional($invoice->invoice_date)->format('d M Y');
        $dueDate = optional($invoice->due_date)->format('d M Y');
        $subTotal = collect($invoice->items)->sum(fn ($item) => (float) $item->total_price);
    @endphp

    <div class="invoice-shell">
        <div class="top-band"></div>

        <div class="hero avoid-break">
            <table class="table-layout">
                <tr>
                    <td style="width: 56%; vertical-align: top;">
                        @if(file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Fablead CRM" class="brand-logo">
                        @endif
                        <div class="brand-name">Fablead CRM</div>
                        <div class="brand-copy">Smart travel CRM for sales, invoicing, meetings, and customer operations.</div>
                        <div class="brand-copy">support@fablead.com</div>
                    </td>
                    <td class="invoice-kicker" style="width: 44%; vertical-align: top;">
                        <div class="invoice-label">Tax Invoice</div>
                        <div class="invoice-number">{{ $invoice->number ?: ('INV-' . $invoice->id) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="table-layout meta-grid avoid-break" style="margin-bottom: 12px;">
            <tr>
                <td style="width: 52%; padding-right: 10px;">
                    <div class="meta-box">
                        <div class="section-title">Bill To</div>
                        <div class="block-title">{{ $customer?->name ?: 'Walk-in Customer' }}</div>
                        <div class="meta-line"><strong>Email:</strong> {{ $customer?->email ?: 'Not available' }}</div>
                        <div class="meta-line"><strong>Phone:</strong> {{ $customer?->phone ?: 'Not available' }}</div>
                        <div class="meta-line"><strong>Address:</strong> {{ $customer?->address ?: 'Not available' }}</div>
                    </div>
                </td>
                <td style="width: 48%; padding-left: 10px;">
                    <div class="meta-box">
                        <div class="section-title">Invoice Details</div>
                        <div class="meta-line"><strong>Invoice Date:</strong> {{ $invoiceDate ?: '--' }}</div>
                        <div class="meta-line"><strong>Due Date:</strong> {{ $dueDate ?: '--' }}</div>
                        <div class="meta-line"><strong>Currency:</strong> {{ $currencyCode }}</div>
                        <div class="meta-line"><strong>Prepared By:</strong> {{ $invoice->creator?->name ?: 'Administrator' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-card">
            <div class="section-title">Invoice Items</div>
            <table class="invoice-items">
                <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 42%;">Description</th>
                        <th style="width: 16%;" class="text-right">Unit Price</th>
                        <th style="width: 12%;" class="text-center">Qty</th>
                        <th style="width: 24%;" class="text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $item->product_name ?: 'Invoice Item' }}</strong>
                                <div class="muted">Professional CRM service line item</div>
                            </td>
                            <td class="text-right">{{ number_format((float) $item->amount, 2) }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format((float) $item->total_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center muted">No invoice items available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary-wrap avoid-break">
                <div class="summary-box">
                    <table class="summary-row">
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right">{{ $currencyCode }} {{ number_format($subTotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Tax</td>
                            <td class="text-right">{{ $currencyCode }} 0.00</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="text-right">{{ $currencyCode }} 0.00</td>
                        </tr>
                        <tr class="summary-total">
                            <td>Total Due</td>
                            <td class="text-right">{{ $currencyCode }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="note-box avoid-break">
            <div class="section-title">Notes</div>
            <div>{{ $invoice->comment ?: 'Thank you for choosing Fablead CRM. Please review the invoice details and contact us if any clarification is required.' }}</div>
        </div>

        <div class="terms-box avoid-break">
            <div class="section-title">Terms & Conditions</div>
            <ol class="terms-list">
                <li>Payment is due on or before the due date mentioned on this invoice unless otherwise agreed in writing.</li>
                <li>Services and deliverables will be considered accepted unless discrepancies are reported within 3 business days.</li>
                <li>Late payments may be subject to additional charges as per the applicable commercial agreement.</li>
                <li>This invoice is system generated and valid without physical signature or stamp.</li>
            </ol>
        </div>

        <div class="footer-bar avoid-break">
            <strong>Fablead CRM</strong> | Generated on {{ now()->format('d M Y, h:i A') }} | This is a computer-generated invoice document.
        </div>
    </div>
</body>
</html>
