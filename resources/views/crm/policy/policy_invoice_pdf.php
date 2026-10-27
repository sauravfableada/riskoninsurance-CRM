<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Policy Invoice - <?= esc($policy['policy_number_no']) ?></title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #2c3e50;
            line-height: 1.5;
            background-color: #ffffff;
        }

        /* Top Accent Bar */
        .top-bar {
            height: 8px;
            background: linear-gradient(90deg, #1a5276 0%, #2980b9 100%);
        }

        header {
            padding: 30px 40px;
            display: block;
            overflow: hidden;
        }

        .logo-container { float: left; width: 25%; }
        .company-info { float: right; width: 70%; text-align: right; }

        .logo-container img { width: 80px; height: auto; }
        .company-info h1 { 
            margin: 0; 
            font-size: 22px; 
            color: #1a5276; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-info p { margin: 2px 0; color: #7f8c8d; font-size: 10px; }

        main { padding: 0 40px; }

        /* Invoice Summary Header */
        .invoice-meta {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .meta-column { float: left; width: 33.33%; }
        .meta-label { color: #7f8c8d; text-transform: uppercase; font-size: 9px; font-weight: bold; display: block; }
        .meta-value { font-size: 13px; font-weight: bold; color: #2c3e50; }

        .section-header {
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 5px;
            margin-bottom: 15px;
            margin-top: 25px;
        }
        .section-header h2 {
            font-size: 12px;
            color: #1a5276;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        /* Layout Grid */
        .row { width: 100%; clear: both; overflow: hidden; }
        .col-6 { float: left; width: 48%; margin-right: 2%; }
        .col-4 { float: left; width: 31%; margin-right: 2%; }

        /* Modern Table Design */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f4f7f9;
            color: #5d6d7e;
            font-weight: 600;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            text-transform: uppercase;
            font-size: 9px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #f2f2f2;
            vertical-align: middle;
        }

        .data-row { margin-bottom: 8px; }
        .label { color: #7f8c8d; width: 120px; display: inline-block; }
        .value { font-weight: 500; }

        /* Highlights */
        .badge {
            padding: 4px 8px;
            background: #eaf2f8;
            color: #2980b9;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Totals Area */
        .totals-wrapper {
            float: right;
            width: 250px;
            margin-top: 30px;
            background: #1a5276;
            color: white;
            padding: 20px;
            border-radius: 8px;
        }
        .total-row { overflow: hidden; margin-bottom: 10px; }
        .total-label { float: left; font-size: 10px; opacity: 0.8; }
        .total-amount { float: right; font-size: 16px; font-weight: bold; }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            padding: 20px 0;
            text-align: center;
            font-size: 9px;
            color: #bdc3c7;
            border-top: 1px solid #f2f2f2;
        }

        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

<div class="top-bar"></div>

<header class="clearfix">
    <div class="logo-container">
        <img src="<?= base_url('public/assets/img/profile/' . esc(!empty($company_details['company_logo']) ? $company_details['company_logo'] : 'public/assets/img/logos/policysarthi.png')) ?>" alt="Logo">
    </div>
    <div class="company-info">
        <h1><?= esc($company_details['company_name'] ?? 'AKP Insurance') ?></h1>
        <p><?= esc($company_details['address'] ?? '--') ?></p>
        <p>Email: support@policysarthi.com | Web: www.policysarthi.com</p>
    </div>
</header>

<main>
    <div class="invoice-meta clearfix">
        <div class="meta-column">
            <span class="meta-label">Policy Number</span>
            <span class="meta-value"><?= esc($policy['policy_number_no']) ?></span>
        </div>
        <div class="meta-column">
            <span class="meta-label">Period of Insurance</span>
            <span class="meta-value"><?= esc($policy['start_date']) ?> to <?= esc($policy['end_date']) ?></span>
        </div>
        <div class="meta-column" style="text-align: right;">
            <span class="meta-label">Issue Date</span>
            <span class="meta-value"><?= date('d M, Y', strtotime($policy['created_at'])) ?></span>
        </div>
    </div>

    <div class="invoice-meta clearfix">
    <div class="meta-column">
        <span style="color: #7f8c8d; font-size: 9px; font-weight: bold; text-transform: uppercase;">Policy Number</span><br>
        <span style="font-size: 14px; font-weight: bold; color: #2c3e50;"><?= esc($policy['policy_number_no']) ?></span>
    </div>
    <div class="meta-column" style="text-align: center;">
        <span style="color: #7f8c8d; font-size: 9px; font-weight: bold; text-transform: uppercase;">Period of Insurance</span><br>
        <span style="font-size: 12px; font-weight: bold; color: #2c3e50;"><?= esc($policy['start_date']) ?> to <?= esc($policy['end_date']) ?></span>
    </div>
    <div class="meta-column" style="text-align: right;">
        <span style="color: #7f8c8d; font-size: 9px; font-weight: bold; text-transform: uppercase;">Issue Date</span><br>
        <span style="font-size: 12px; font-weight: bold; color: #2c3e50;"><?= date('d M, Y', strtotime($policy['created_at'])) ?></span>
    </div>
</div>

<div class="row clearfix">
    <div class="col-6">
        <div class="section-header"><h2>Proposer Details</h2></div>
        <div class="data-row"><span class="label">Name:</span> <span class="value"><?= esc($users['name'] ?? '--') ?></span></div>
        <div class="data-row"><span class="label">Contact:</span> <span class="value"><?= esc($users['contact'] ?? '--') ?></span></div>
        <div class="data-row"><span class="label">Address:</span> <span class="value"><?= esc($users['address'] ?? '--') ?></span></div>
    </div>
    <div class="col-6">
        <div class="section-header"><h2>Plan Details</h2></div>
        <div class="data-row"><span class="label">Policy Type:</span> <span class="value"><?= esc($policyTypes['name'] ?? '--') ?></span></div>
        <div class="data-row"><span class="label">Sub-Type:</span> <span class="value"><?= esc($subTypes['name'] ?? '--') ?></span></div>
        <div class="data-row"><span class="label">Company:</span> <span class="value"><?= esc($companies['name'] ?? '--') ?></span></div>
    </div>
</div>

    <?php if (!empty($policyTypes) && strtolower($policyTypes['name']) === 'vehicle' && !empty($vehicle)): ?>
    <div class="section-header"><h2>Vehicle Information</h2></div>
    <table>
        <thead>
            <tr>
                <th>Registration No</th>
                <th>Model / Mfg Year</th>
                <th>Engine / Chassis</th>
                <th>NCB %</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value"><?= esc($vehicle['vehicle_reg_no']) ?></td>
                <td><?= esc($vehicle['vehicle_model']) ?> (<?= esc($vehicle['year_of_manufacture']) ?>)</td>
                <td>E: <?= esc($vehicle['engine_no']) ?><br>C: <?= esc($vehicle['chassis_no']) ?></td>
                <td><span class="badge"><?= esc($vehicle['ncb'] ?? '0') ?>%</span></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="section-header"><h2>Insured Family Members</h2></div>
    <table>
        <thead>
            <tr>
                <th>Member Name</th>
                <th>Relationship</th>
                <th>Contact</th>
                <th>Sum Insured</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td class="value"><?= esc($m['member_name']) ?></td>
                        <td><?= esc($m['relation']) ?></td>
                        <td><?= esc($m['contact']) ?></td>
                        <td><?= esc($policy['sum_assured']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align center;">Primary Insured Only</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row clearfix">
        <div class="col-6">
            <div class="section-header"><h2>Nominee</h2></div>
            <p><strong><?= esc($policy['nominee_name']) ?></strong> (<?= esc($policy['nominee_share'] ?? '100') ?>% Share)<br>
            Contact: <?= esc($policy['nominee_contact'] ?? '--') ?></p>
        </div>
        <div class="totals-wrapper">
            <div class="total-row">
                <span class="total-label">Premium Amount</span>
                <span class="total-amount">₹ <?= number_format((float)$policy['premium'], 2) ?></span>
            </div>
            <div class="total-row" style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px;">
                <span class="total-label">Total Payable</span>
                <span class="total-amount">₹ <?= number_format((float)($policy['amount'] ?? $policy['premium']), 2) ?></span>
            </div>
        </div>
    </div>

</main>

<footer>
    &copy; <?= date('Y') ?> SARTHI INSURANCE POLICY Co. Ltd. | This is a computer generated invoice and requires no signature.
</footer>

</body>
</html>