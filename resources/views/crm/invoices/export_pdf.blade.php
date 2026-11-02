<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoices Statement Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 8.5px;
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

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: monospace; font-size: 8px; }

        .border-bottom { border-bottom: 1px solid #000000; }
        .border-top { border-top: 1px solid #000000; }
        .border-right { border-right: 1px solid #000000; }
        .border-left { border-left: 1px solid #000000; }

        /* Header Elements matching Invoice */
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            text-align: right;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }

        .company-address {
            font-size: 8px;
            text-align: right;
            line-height: 1.3;
            color: #111111;
        }

        .company-contacts {
            font-size: 7.5px;
            font-weight: bold;
            text-align: right;
            padding: 2px 0 0 0;
            color: #111111;
        }

        /* Meta Section */
        .meta-table td {
            padding: 4px 8px;
            font-size: 9px;
        }

        /* Items Table */
        .items-table th {
            padding: 5px 4px;
            font-size: 8px;
            font-weight: bold;
            background-color: #f8fafc;
            border-bottom: 1px solid #000000;
            border-right: 1px solid #000000;
        }

        .items-table th:last-child {
            border-right: none;
        }

        .items-table td {
            padding: 3.5px 5px;
            font-size: 8px;
            vertical-align: middle;
            border-right: 1px solid #000000;
            border-bottom: 0.5px solid #e2e8f0;
        }

        .items-table td:last-child {
            border-right: none;
        }

        .totals-table td {
            padding: 3px 8px;
            font-size: 8px;
        }

        .badge-status {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 0.5px solid #bbf7d0;
        }
        .status-unpaid {
            background-color: #fef9c3;
            color: #a16207;
            border: 0.5px solid #fef08a;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 0.5px solid #fecaca;
        }
    </style>
</head>
<body>
    @php
        if (!function_exists('indianAmountToWordsReport')) {
            function indianAmountToWordsReport($amount) {
                $amount = (int) round($amount);
                if ($amount == 0) return 'Zero Only';
                
                $ones = [
                    0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
                    6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
                    11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
                    16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
                ];
                $tens = [
                    2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
                    6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
                ];

                $convertBelowThousand = function($num) use ($ones, $tens) {
                    $res = '';
                    if ($num >= 100) {
                        $res .= $ones[(int)($num / 100)] . ' Hundred ';
                        $num %= 100;
                    }
                    if ($num >= 20) {
                        $res .= $tens[(int)($num / 10)] . ' ';
                        $num %= 10;
                    }
                    if ($num > 0) {
                        $res .= $ones[$num] . ' ';
                    }
                    return trim($res);
                };

                $parts = [];
                $crore = (int) ($amount / 10000000);
                $amount %= 10000000;
                $lakh = (int) ($amount / 100000);
                $amount %= 100000;
                $thousand = (int) ($amount / 1000);
                $remainder = $amount % 1000;

                if ($crore > 0) $parts[] = $convertBelowThousand($crore) . ' Crore';
                if ($lakh > 0) $parts[] = $convertBelowThousand($lakh) . ' Lakh';
                if ($thousand > 0) $parts[] = $convertBelowThousand($thousand) . ' Thousand';
                if ($remainder > 0) $parts[] = $convertBelowThousand($remainder);

                return trim(implode(' ', $parts)) . ' Only';
            }
        }
    @endphp

    <div class="invoice-box">
        <!-- 1. Header Design (matching Tax Invoice: Logo Left, Dynamic Company Details Right) -->
        <table class="border-bottom" style="width: 100%;">
            <tr>
                <td style="width: 30%; vertical-align: middle; padding: 6px 10px;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="{{ $companyName }}" style="height: 48px; width: auto; max-width: 175px;">
                    @else
                        <div style="font-size: 14px; font-weight: bold; color: #1e40af;">{{ strtoupper($companyName) }}</div>
                    @endif
                </td>
                <td style="width: 70%; text-align: right; vertical-align: middle; padding: 6px 10px;">
                    <div class="company-name">{{ strtoupper($companyName) }}</div>
                    <div class="company-address">{!! nl2br(e($companyAddress)) !!}</div>
                    <div class="company-contacts">
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

        <!-- 2. Statement Meta Bar -->
        <table class="meta-table border-bottom">
            <tr>
                <td style="width: 50%;" class="border-right">
                    <span class="fw-bold">Statement:</span> INVOICES &amp; COMMISSIONS REPORT
                </td>
                <td style="width: 50%; text-align: right;">
                    <span class="fw-bold">Statement Date :</span> {{ date('d-m-Y') }} &nbsp;&nbsp;|&nbsp;&nbsp; <span class="fw-bold">Time:</span> {{ date('h:i A') }}
                </td>
            </tr>
        </table>

        <!-- 3. Scope / Filters & Overview Summary -->
        <table class="border-bottom" style="width: 100%;">
            <tr>
                <!-- Left: Scope / Filter applied -->
                <td style="width: 50%; vertical-align: top; padding: 5px 8px;" class="border-right">
                    <div class="fw-bold" style="text-decoration: underline; margin-bottom: 3px; font-size: 8.5px;">Statement Scope / Applied Filters</div>
                    <div style="font-size: 8px; line-height: 1.35;">
                        @if($hasFilters)
                            @if(!empty($filters['type']))
                                <div>&bull; <strong>Type:</strong> {{ $filters['type'] === 'company_pay_in' ? 'Company Pay-In Invoices' : 'Customer Invoices' }}</div>
                            @endif
                            @if(!empty($filters['company']))
                                <div>&bull; <strong>Company:</strong> {{ $filters['company'] }}</div>
                            @endif
                            @if(!empty($filters['status']))
                                <div>&bull; <strong>Status:</strong> {{ ucfirst($filters['status']) }}</div>
                            @endif
                            @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                                <div>&bull; <strong>Date Period:</strong> {{ $filters['start_date'] ? date('d-m-Y', strtotime($filters['start_date'])) : 'All Previous' }} to {{ $filters['end_date'] ? date('d-m-Y', strtotime($filters['end_date'])) : 'Present' }}</div>
                            @endif
                            @if(!empty($filters['search']))
                                <div>&bull; <strong>Search Filter:</strong> "{{ $filters['search'] }}"</div>
                            @endif
                            <div style="color: #64748b; font-size: 7.5px; margin-top: 2px;">(Exported based on active on-screen filters)</div>
                        @else
                            <div style="color: #1e40af; font-weight: bold;">&bull; All Invoices (Complete Full Statement)</div>
                            <div style="color: #64748b; font-size: 7.5px; margin-top: 2px;">(No filters applied &mdash; includes all Pay-In &amp; Customer billings)</div>
                        @endif
                    </div>
                </td>

                <!-- Right: Summary Totals -->
                <td style="width: 50%; vertical-align: top; padding: 5px 8px;">
                    <div class="fw-bold" style="text-decoration: underline; margin-bottom: 3px; font-size: 8.5px;">Summary Overview</div>
                    <table style="width: 100%; font-size: 8px;">
                        <tr>
                            <td style="padding: 1px 0;">Total Records:</td>
                            <td style="text-align: right; padding: 1px 0; font-weight: bold;">{{ $invoices->count() }} Invoices</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0;">Total Amount Invoiced:</td>
                            <td style="text-align: right; padding: 1px 0; font-weight: bold;">₹{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; color: #15803d;">Total Paid / Remitted:</td>
                            <td style="text-align: right; padding: 1px 0; color: #15803d; font-weight: bold;">₹{{ number_format($paidAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; color: #b45309;">Total Pending / Due:</td>
                            <td style="text-align: right; padding: 1px 0; color: #b45309; font-weight: bold;">₹{{ number_format($unpaidAmount, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 4. Line Items Table -->
        <table class="items-table border-bottom">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">S.No</th>
                    <th style="width: 25%; text-align: left; padding-left: 6px;">Invoice Number</th>
                    <th style="width: 14%; text-align: center;">Invoice Type</th>
                    <th style="width: 24%; text-align: left; padding-left: 6px;">Billed To</th>
                    <th style="width: 10%; text-align: center;">Date</th>
                    <th style="width: 8%; text-align: center;">Status</th>
                    <th style="width: 14%; text-align: right; padding-right: 6px;">Total Value (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $idx => $inv)
                    @php
                        $isCompany = ($inv->invoice_type === 'company_pay_in');
                        $billedTo = $isCompany ? ($inv->company?->name ?: 'Insurance Company') : ($inv->customer?->name ?: 'Customer');
                        $invTotal = (float) ($inv->grand_total ?: ($inv->total_amount ?: ($inv->subtotal + $inv->tax_amount)));
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold font-mono">{{ $inv->number ?: ('#' . $inv->id) }}</div>
                            @if($inv->account_id_code)
                                <div style="font-size: 7px; color: #64748b;">Acct: {{ $inv->account_id_code }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $isCompany ? 'Company Pay-In' : 'Customer' }}
                        </td>
                        <td>
                            <div class="fw-bold">{{ $billedTo }}</div>
                            @if(!$isCompany && $inv->customer?->email)
                                <div style="font-size: 7px; color: #64748b;">{{ $inv->customer->email }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $inv->invoice_date ? date('d-m-Y', strtotime($inv->invoice_date)) : '—' }}
                        </td>
                        <td class="text-center">
                            @if($inv->status === 'paid')
                                <span class="badge-status status-paid">PAID</span>
                            @elseif($inv->status === 'cancelled')
                                <span class="badge-status status-cancelled">CANCELLED</span>
                            @else
                                <span class="badge-status status-unpaid">UNPAID</span>
                            @endif
                        </td>
                        <td class="text-right fw-bold" style="padding-right: 6px;">
                            {{ number_format($invTotal, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                            No invoices found matching the specified filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 5. Totals Section -->
        <table class="totals-table border-bottom">
            <tr>
                <td style="width: 72%; text-align: right; font-weight: bold;" class="border-right">
                    Total Taxable Value (Subtotal)
                </td>
                <td style="width: 28%; text-align: right; font-weight: bold;">
                    ₹{{ number_format($totalSubtotal, 2) }}
                </td>
            </tr>
            <tr>
                <td style="width: 72%; text-align: right; font-weight: bold;" class="border-right">
                    Total Tax Amount (GST)
                </td>
                <td style="width: 28%; text-align: right; font-weight: bold;">
                    ₹{{ number_format($totalTax, 2) }}
                </td>
            </tr>
            <tr style="background-color: #f8fafc;">
                <td style="width: 72%; text-align: right; font-weight: bold; font-size: 9.5px; padding: 4px 8px;" class="border-right">
                    Amount Chargeable (Total Invoiced)
                </td>
                <td style="width: 28%; text-align: right; font-weight: bold; font-size: 9.5px; padding: 4px 8px;">
                    ₹{{ number_format($totalAmount, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 4px 8px; font-size: 8px;">
                    <span class="fw-bold">Rupees in Words :</span> {{ indianAmountToWordsReport($totalAmount) }}
                </td>
            </tr>
        </table>

        <!-- 6. Footer & Signatory Box -->
        <table class="border-bottom">
            <tr>
                <td style="width: 58%; padding: 5px 8px; vertical-align: top;" class="border-right">
                    <div class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">Declarations &amp; Terms</div>
                    <div class="fw-bold" style="margin-bottom: 2px; font-size: 7.5px;">E. &amp; O.E.</div>
                    <div style="line-height: 1.3; font-size: 7.5px; color: #222;">
                        1. This statement reflects official invoice and brokerage commission records in the Riskon CRM.<br>
                        2. Subject to 'Delhi' Jurisdiction Only.
                    </div>
                </td>
                <td style="width: 42%; padding: 5px 8px; text-align: right; vertical-align: top;">
                    <div class="fw-bold" style="font-size: 9px; color: #000; text-align: right;">
                        For {{ strtoupper($companyName) }}
                    </div>

                    @php
                        $showImageSig = (!empty($signatureBase64)) && (!isset($dscEnabled) || !$dscEnabled || in_array($dscDisplayMode ?? 'both', ['both', 'image_only']));
                        $showDscBox = (!empty($dscEnabled) && in_array($dscDisplayMode ?? 'both', ['both', 'dsc_only']));
                    @endphp

                    @if($showImageSig)
                        <div style="height: 38px; text-align: right; padding-top: 2px;">
                            <img src="{{ $signatureBase64 }}" style="max-height: 34px; max-width: 130px; object-fit: contain;">
                        </div>
                    @endif

                    @if($showDscBox)
                        <div style="text-align: right; margin-top: 2px; margin-bottom: 2px;">
                            <div style="display: inline-block; text-align: left; border: 1px solid #198754; border-left: 3px solid #198754; background-color: #f8fff9; padding: 2px 5px; border-radius: 3px; max-width: 190px; font-size: 7px; line-height: 1.25; color: #212529;">
                                <div style="font-size: 7.5px; font-weight: bold; color: #198754; border-bottom: 1px solid #d1e7dd; padding-bottom: 1px; margin-bottom: 1px;">
                                    <span style="font-size: 8.5px; font-weight: bold;">&#10003;</span> Digitally Signed by
                                </div>
                                <div style="font-weight: bold; color: #000;">{{ $dscSignatoryName ?? 'Authorized Signatory' }}</div>
                                <div><strong>Desg:</strong> {{ $dscDesignation ?? 'Authorized Signatory' }}</div>
                                <div><strong>Date:</strong> {{ now()->format('d-m-Y H:i') }} IST</div>
                                <div><strong>Reason:</strong> {{ $dscReason ?? 'Tax Invoice Verification' }}</div>
                                <div><strong>Loc:</strong> {{ $dscLocation ?? 'Delhi, India' }}</div>
                            </div>
                        </div>
                    @elseif(!$showImageSig)
                        <div style="height: 38px;"></div>
                    @endif

                    <div style="font-weight: bold; font-size: 9px; text-align: right; display: inline-block; border-top: 1px solid #333; padding-top: 2px; min-width: 130px;">
                        Authorized Signatory
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
