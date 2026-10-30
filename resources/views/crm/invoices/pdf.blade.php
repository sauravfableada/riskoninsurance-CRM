<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax Invoice {{ $invoice->number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000000;
            margin: 0;
            padding: 0;
            line-height: 1.3;
            background: #ffffff;
        }

        .invoice-box {
            width: 100%;
            border: 1.5px solid #000000;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .border-bottom {
            border-bottom: 1px solid #000000;
        }

        .border-top {
            border-top: 1px solid #000000;
        }

        .border-right {
            border-right: 1px solid #000000;
        }

        .border-left {
            border-left: 1px solid #000000;
        }

        /* Header Elements */
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            text-align: right;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }

        .company-address {
            font-size: 8.5px;
            text-align: right;
            line-height: 1.35;
            color: #111111;
        }

        .company-contacts {
            font-size: 8px;
            font-weight: bold;
            text-align: right;
            padding: 3px 0 0 0;
            color: #111111;
        }

        /* Meta Section */
        .meta-table td {
            padding: 5px 8px;
            font-size: 9.5px;
        }

        /* Bill To / Consignee */
        .party-table td {
            padding: 2px 4px;
            font-size: 8.5px;
            vertical-align: top;
        }

        /* Line Items */
        .items-table th {
            padding: 5px 4px;
            font-size: 8.5px;
            font-weight: bold;
            background-color: #f8fafc;
            border-bottom: 1px solid #000000;
            border-right: 1px solid #000000;
        }

        .items-table th:last-child {
            border-right: none;
        }

        .items-table td {
            padding: 4px 5px;
            font-size: 8.5px;
            vertical-align: top;
            border-right: 1px solid #000000;
        }

        .items-table td:last-child {
            border-right: none;
        }

        /* Totals section */
        .totals-table td {
            padding: 3.5px 8px;
            font-size: 8.5px;
        }

        /* Footer */
        .terms-table td {
            padding: 5px 8px;
            font-size: 8.5px;
            vertical-align: top;
        }
    </style>
</head>
<body>
    @php
        // Number to words helper
        if (!function_exists('indianAmountToWords')) {
            function indianAmountToWords($amount) {
                $amount = (int) round($amount);
                if ($amount == 0) return 'Zero Only';
                
                $ones = [
                    0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
                    5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                    10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
                    14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
                    18 => 'Eighteen', 19 => 'Nineteen'
                ];
                $tens = [
                    2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
                    6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
                ];
                
                $convertBelowThousand = function($n) use ($ones, $tens) {
                    $word = '';
                    if ($n >= 100) {
                        $h = floor($n / 100);
                        $word .= $ones[$h] . ' Hundred ';
                        $n %= 100;
                    }
                    if ($n >= 20) {
                        $t = floor($n / 10);
                        $word .= $tens[$t] . ' ';
                        $n %= 10;
                    }
                    if ($n > 0) {
                        $word .= $ones[$n] . ' ';
                    }
                    return trim($word);
                };

                $crore = floor($amount / 10000000);
                $amount %= 10000000;
                $lakh = floor($amount / 100000);
                $amount %= 100000;
                $thousand = floor($amount / 1000);
                $amount %= 1000;
                $remainder = $amount;

                $parts = [];
                if ($crore > 0) {
                    $parts[] = $convertBelowThousand($crore) . ' Crore';
                }
                if ($lakh > 0) {
                    $parts[] = $convertBelowThousand($lakh) . ' Lakh';
                }
                if ($thousand > 0) {
                    $parts[] = $convertBelowThousand($thousand) . ' Thousand';
                }
                if ($remainder > 0) {
                    $parts[] = $convertBelowThousand($remainder);
                }

                return trim(implode(' ', $parts)) . ' Only';
            }
        }

        // Determine buyer entity
        $isCompany = !empty($invoice->company_id) || $invoice->invoice_type === 'company_pay_in';
        $buyerName = $isCompany ? ($invoice->company?->name ?: 'IndusInd General Insurance Co. Ltd') : ($invoice->customer?->name ?: 'Customer');
        $buyerAddress = $isCompany 
            ? ($invoice->company?->address ?: ($invoice->company?->description ?: '—')) 
            : ($invoice->customer?->address ?: '—');
        $buyerState = $isCompany ? ($invoice->company?->state ?: 'Delhi') : ($invoice->customer?->state ?: 'Delhi');
        $buyerGstin = $isCompany ? ($invoice->company?->gstin ?: ($invoice->company?->tax_number ?: '—')) : ($invoice->customer?->tax_number ?: '—');

        // Dynamic Company Profile Details (from /profile settings and Admin User)
        $companySettings = \App\Models\Setting::query()
            ->whereIn('key', [
                'company_name',
                'company_address',
                'company_tax_id',
                'company_logo_path',
                'company_phone',
                'company_email'
            ])->pluck('value', 'key');

        $adminUser = \App\Models\User::where('id', 1)->first() 
            ?? \App\Models\User::whereIn('type', ['admin', 'super_admin'])->first() 
            ?? $invoice->creator 
            ?? auth()->user();

        $companyName = !empty($companySettings['company_name']) 
            ? trim($companySettings['company_name']) 
            : 'RISKON IMF PRIVATE LIMITED';

        $companyAddress = !empty($companySettings['company_address']) 
            ? trim($companySettings['company_address']) 
            : 'BG-513, Sanjay Gandhi, Transport Nagar, Pehlad Pur, North West Delhi, New Delhi-110042';

        $companyTaxNo = !empty($companySettings['company_tax_id']) 
            ? trim($companySettings['company_tax_id']) 
            : '07AANCR6912L1ZU';

        $companyPhone = !empty($companySettings['company_phone']) 
            ? trim($companySettings['company_phone']) 
            : ($adminUser?->phone ?: '+91 9650800804');

        $companyEmail = !empty($companySettings['company_email']) 
            ? trim($companySettings['company_email']) 
            : ($adminUser?->email ?: 'Info.riskoninsurance@gmail.com');

        // Calculation
        $taxableValue = (float) ($invoice->subtotal ?: $invoice->items->sum('total_price'));
        $taxRate = (float) ($invoice->tax_percentage ?? 18);
        $totalTax = (float) ($invoice->tax_amount ?: round(($taxableValue * $taxRate) / 100, 2));
        $grandTotal = (float) ($invoice->grand_total ?: ($taxableValue + $totalTax));
        $grandTotalRounded = round($grandTotal);

        // Check if Delhi (intra-state)
        $isDelhi = str_contains(strtolower($buyerState), 'delhi') || str_starts_with(strtoupper(trim($buyerGstin)), '07') || empty($buyerGstin) || $buyerGstin === '—';
        $cgst = $isDelhi ? round($totalTax / 2, 2) : 0.00;
        $sgst = $isDelhi ? round($totalTax - $cgst, 2) : 0.00;
        $igst = $isDelhi ? 0.00 : $totalTax;

        // If not Delhi but no IGST, default to standard CGST & SGST
        if ($cgst == 0 && $sgst == 0 && $igst == 0 && $totalTax > 0) {
            $cgst = round($totalTax / 2, 2);
            $sgst = round($totalTax - $cgst, 2);
        }

        // Resolve logo dynamically
        if (!isset($logoBase64) || empty($logoBase64)) {
            $logoBase64 = null;
            $logoSettingPath = $companySettings['company_logo_path'] ?? null;
            if ($logoSettingPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoSettingPath)) {
                $p = \Illuminate\Support\Facades\Storage::disk('public')->path($logoSettingPath);
                $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                $mime = ($ext === 'svg') ? 'image/svg+xml' : (($ext === 'png') ? 'image/png' : 'image/jpeg');
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
            } elseif (file_exists(public_path('logo/riskon_logo.png'))) {
                $p = public_path('logo/riskon_logo.png');
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($p));
            }
        }
    @endphp

    <div class="invoice-box">
        <!-- 1. Top Header (Logo Left, Dynamic Company Details Right) -->
        <table class="border-bottom" style="width: 100%;">
            <tr>
                <td style="width: 32%; vertical-align: middle; padding: 8px 12px;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="{{ $companyName }}" style="height: 52px; width: auto; max-width: 180px;">
                    @endif
                </td>
                <td style="width: 68%; text-align: right; vertical-align: middle; padding: 8px 12px;">
                    <div class="company-name" style="text-align: right; font-size: 15px; font-weight: bold; color: #000000; margin: 0 0 3px 0; letter-spacing: 0.5px;">{{ strtoupper($companyName) }}</div>
                    <div class="company-address" style="text-align: right; font-size: 8.5px; line-height: 1.35; color: #111111;">
                        {!! nl2br(e($companyAddress)) !!}
                    </div>
                    <div class="company-contacts" style="text-align: right; font-size: 8px; font-weight: bold; padding: 3px 0 0 0; color: #111111;">
                        @if($companyTaxNo)
                            <span class="fw-bold">GSTIN/Tax No:</span> {{ $companyTaxNo }} &nbsp;&nbsp;|&nbsp;&nbsp;
                        @endif
                        @if($companyPhone)
                            <span class="fw-bold">Mob:</span> {{ $companyPhone }} &nbsp;&nbsp;|&nbsp;&nbsp;
                        @endif
                        @if($companyEmail)
                            <span class="fw-bold">Email:</span> {{ $companyEmail }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- 2. Invoice Number & Date -->
        <table class="meta-table border-bottom">
            <tr>
                <td style="width: 50%;" class="border-right">
                    <span class="fw-bold">Invoice No.:</span> {{ $invoice->number }}
                </td>
                <td style="width: 50%;">
                    <span class="fw-bold">Invoice Date :</span> {{ optional($invoice->invoice_date)->format('d-m-Y') ?: date('d-m-Y') }}
                </td>
            </tr>
        </table>

        <!-- 3. Buyer (Bill to) & Consignee (Ship to) -->
        <table class="border-bottom">
            <tr>
                <!-- Buyer (Bill to) -->
                <td style="width: 50%; vertical-align: top; padding: 6px 8px;" class="border-right">
                    <div class="fw-bold" style="text-decoration: underline; margin-bottom: 5px; font-size: 9.5px;">Buyer (Bill to)</div>
                    <table class="party-table">
                        <tr>
                            <td style="width: 26%;" class="fw-bold">Name</td>
                            <td style="width: 74%;" class="fw-bold">{{ $buyerName }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Address</td>
                            <td>{{ $buyerAddress }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">State</td>
                            <td>{{ $buyerState }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">GSTIN/UIN</td>
                            <td class="fw-bold" style="font-family: monospace;">{{ $buyerGstin }}</td>
                        </tr>
                    </table>
                </td>

                <!-- Consignee (Ship to) -->
                <td style="width: 50%; vertical-align: top; padding: 6px 8px;">
                    <div class="fw-bold" style="text-decoration: underline; margin-bottom: 5px; font-size: 9.5px;">Consignee(Ship to)</div>
                    <table class="party-table">
                        <tr>
                            <td style="width: 26%;" class="fw-bold">Name</td>
                            <td style="width: 74%;" class="fw-bold">{{ $buyerName }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Address</td>
                            <td>{{ $buyerAddress }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">State</td>
                            <td>{{ $buyerState }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">GSTIN/UIN</td>
                            <td class="fw-bold" style="font-family: monospace;">{{ $buyerGstin }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 4. Line Items Table -->
        <table class="items-table border-bottom">
            <thead>
                <tr>
                    <th style="width: 6%; text-align: center;">S.No</th>
                    <th style="width: 48%; text-align: left; padding-left: 8px;">Description of Goods</th>
                    <th style="width: 12%; text-align: center;">HSN/SAC</th>
                    <th style="width: 10%; text-align: center;">Rate</th>
                    <th style="width: 11%; text-align: right;">Qty/Unit</th>
                    <th style="width: 13%; text-align: right; padding-right: 8px;">Total Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $idx => $item)
                    @php
                        $itemTotal = (float) ($item->total_price ?: (($item->pay_in_amount ?? $item->amount) * $item->quantity));
                        $itemRate = $item->pay_in_percentage ? number_format($item->pay_in_percentage, 0) . '%' : '1';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">IRDA Commission</div>
                            @if($item->policy_number)
                                <div style="font-size: 8px; color: #222;">({{ $item->policy_number }})</div>
                            @elseif($item->product_name && !str_starts_with($item->product_name, 'Pay-In Commission'))
                                <div style="font-size: 8px; color: #222;">({{ $item->product_name }})</div>
                            @endif
                            @if($item->holder_name && $item->holder_name !== '—')
                                <div style="font-size: 7.5px; color: #555;">Holder: {{ $item->holder_name }}</div>
                            @endif
                        </td>
                        <td class="text-center">997161</td>
                        <td class="text-center">{{ $itemRate }}</td>
                        <td class="text-right">{{ number_format($itemTotal, 2) }}</td>
                        <td class="text-right fw-bold" style="padding-right: 8px;">{{ number_format($itemTotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center">1</td>
                        <td>
                            <div class="fw-bold">IRDA Commission</div>
                        </td>
                        <td class="text-center">997161</td>
                        <td class="text-center">1</td>
                        <td class="text-right">{{ number_format($taxableValue, 2) }}</td>
                        <td class="text-right fw-bold" style="padding-right: 8px;">{{ number_format($taxableValue, 2) }}</td>
                    </tr>
                @endforelse

                <!-- Blank spacer rows to keep consistent layout height without showing 0s -->
                @php $renderedRows = max(1, count($invoice->items)); @endphp
                @for($i = $renderedRows; $i < 6; $i++)
                    <tr style="height: 18px;">
                        <td class="text-center">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-right" style="padding-right: 8px;">&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <!-- 5. Totals & Tax Calculation Breakdown -->
        <table class="totals-table border-bottom">
            <tr>
                <td style="width: 75%;" class="border-right fw-bold">Total Value of Goods / Services</td>
                <td style="width: 25%; text-align: right; padding-right: 8px;" class="fw-bold">{{ number_format($taxableValue, 2) }}</td>
            </tr>
            @if(!empty($invoice->freight_amount) && (float)$invoice->freight_amount > 0)
            <tr>
                <td class="border-right">Add: Freight/Cartage</td>
                <td style="text-align: right; padding-right: 8px;">{{ number_format((float)$invoice->freight_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="border-top border-bottom" style="background-color: #fafafa;">
                <td class="border-right fw-bold">Total Taxable Value of Goods &amp; Services</td>
                <td style="text-align: right; padding-right: 8px;" class="fw-bold">{{ number_format($taxableValue, 2) }}</td>
            </tr>
            @php
                $halfTaxRate = round($taxRate / 2, 2);
            @endphp
            @if($igst > 0)
            <tr>
                <td class="border-right">IGST @ {{ $taxRate }}%</td>
                <td style="text-align: right; padding-right: 8px;">{{ number_format($igst, 2) }}</td>
            </tr>
            @endif
            @if($cgst > 0 || $igst == 0)
            <tr>
                <td class="border-right">CGST @ {{ $halfTaxRate }}%</td>
                <td style="text-align: right; padding-right: 8px;">{{ number_format($cgst, 2) }}</td>
            </tr>
            <tr>
                <td class="border-right">SGST @ {{ $halfTaxRate }}%</td>
                <td style="text-align: right; padding-right: 8px;">{{ number_format($sgst, 2) }}</td>
            </tr>
            @endif
            <tr class="border-top border-bottom" style="background-color: #f1f5f9;">
                <td class="border-right fw-bold" style="font-size: 10.5px;">Amount Chargeable (r/o)</td>
                <td style="text-align: right; padding-right: 8px; font-size: 11px;" class="fw-bold">₹{{ number_format($grandTotalRounded, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 6px 8px;" class="fw-bold">
                    Rupees in Words : <span style="font-weight: bold;">{{ indianAmountToWords($grandTotalRounded) }}</span>
                </td>
            </tr>
        </table>

        <!-- 6. Terms and Conditions, Bank Details, and Signatures -->
        @php
            $defaultTerms = "1. We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.\n2. Subject to 'Delhi' Jurisdiction Only\n3. Bank Details :\n    Bank Name: ICICI Bank (Tagore Garden)\n    A/C No: 335905001025\n    IFSC CODE: ICIC0003359";
            $rawTerms = !empty(trim($invoice->terms_and_conditions ?? '')) ? $invoice->terms_and_conditions : $defaultTerms;
            $formattedTerms = nl2br(str_replace('  ', '&nbsp;&nbsp;', e($rawTerms)));
        @endphp
        <table class="terms-table">
            <tr>
                <td style="width: 58%; padding: 6px 8px;" class="border-right">
                    <div class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">Terms and Conditions</div>
                    <div class="fw-bold" style="margin-bottom: 3px;">E. &amp; O.E.</div>
                    <div style="line-height: 1.35; color: #111; font-size: 8px;">
                        {!! $formattedTerms !!}
                    </div>
                </td>
                <td style="width: 42%; padding: 6px 8px; text-align: right; vertical-align: top;">
                    <div class="fw-bold" style="font-size: 9.5px; color: #000; text-align: right;">
                        For {{ strtoupper($companyName) }}
                    </div>
                    <div style="height: 52px;"></div>
                    <div style="font-weight: bold; font-size: 9.5px; text-align: right; display: inline-block; border-top: 1px solid #333; padding-top: 3px; min-width: 140px;">
                        Authorized Signatory
                    </div>
                </td>
            </tr>
            <tr class="border-top">
                <td style="padding: 16px 8px 6px; font-weight: bold; vertical-align: bottom;">
                    Customer's/Receiver's Signature.
                </td>
                <td style="padding: 16px 8px 6px; text-align: right; vertical-align: bottom;">
                    &nbsp;
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
