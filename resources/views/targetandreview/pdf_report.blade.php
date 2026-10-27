<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Target Review Report</title>
    <style>
        @page {
            margin: 15px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .header {
            height: 50px;
            padding: 10px 15px;
            border-bottom: 1px solid #ccc;
            background-color: #fff;
            margin-bottom: 15px;
            position: relative;
        }
        .header-logo {
            position: absolute;
            left: 15px;
            top: 10px;
            width: 30px;
            height: 30px;
            background-color: #0a2540;
            border-radius: 4px;
            text-align: center;
            line-height: 30px;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .header h2 {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            line-height: 30px;
        }
        .header .date {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 10px;
            line-height: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 9px;
        }
        th {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        td {
            text-align: right;
        }
        td.text-left {
            text-align: left;
        }
        .department-row {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total-row {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        .total-row td {
            background-color: #e8e8e8;
        }
        .month-col {
            text-align: left;
            font-weight: bold;
        }
        .footer {
            height: 40px;
            padding: 10px 15px;
            border-top: 1px solid #ccc;
            background-color: #fff;
            margin-top: 20px;
        }
        .footer-content {
            display: table;
            width: 100%;
            font-size: 9px;
            margin-top: 5px;
        }
        .footer-left {
            display: table-cell;
            text-align: left;
            width: 33%;
            vertical-align: middle;
        }
        .footer-center {
            display: table-cell;
            text-align: center;
            width: 34%;
            vertical-align: middle;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
            width: 33%;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">P</div>
        <h2>:: Target Review Report (D/L) ::</h2>
        <div class="date">Date :- <?= $current_date ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">Month</th>
                <th rowspan="2" style="width: 12%;">Department</th>
                <th rowspan="2" style="width: 15%;">Factor</th>
                <th colspan="4" style="width: 20%;">Current Year (<?= $financial_years['current']['label'] ?? '' ?>)</th>
                <th colspan="4" style="width: 20%;">Next Year (<?= $financial_years['next']['label'] ?? '' ?>)</th>
                <th colspan="4" style="width: 20%;">Next To Next Year (<?= $financial_years['next_to_next']['label'] ?? '' ?>)</th>
            </tr>
            <tr>
                <!-- Current Year Columns -->
                <th style="width: 5%;">Target</th>
                <th style="width: 5%;">Achieved</th>
                <th style="width: 5%;">Difference</th>
                <th style="width: 5%;">Reason</th>
                <!-- Next Year Columns -->
                <th style="width: 5%;">Target</th>
                <th style="width: 5%;">Achieved</th>
                <th style="width: 5%;">Difference</th>
                <th style="width: 5%;">Reason</th>
                <!-- Next To Next Year Columns -->
                <th style="width: 5%;">Target</th>
                <th style="width: 5%;">Achieved</th>
                <th style="width: 5%;">Difference</th>
                <th style="width: 5%;">Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($report_data)): ?>
                <?php foreach ($report_data as $monthData): ?>
                    <?php 
                    $monthName = $monthData['month'];
                    $departments = $monthData['departments'] ?? [];
                    $isFirstRow = true;
                    $monthRowspan = 0;
                    foreach ($departments as $dept) {
                        $monthRowspan += count($dept['factors'] ?? []);
                    }
                    $monthRowspan += 1; // Add 1 for monthly total row
                    ?>
                    <?php foreach ($departments as $deptData): ?>
                        <?php 
                        $deptName = $deptData['department_name'];
                        $factors = $deptData['factors'] ?? [];
                        $isFirstFactor = true;
                        ?>
                        <?php foreach ($factors as $factorData): ?>
                            <?php
                            $factorName = $factorData['factor_name'];
                            $currentYear = $factorData['current_year'] ?? ['target' => 0, 'achieved' => 0, 'difference' => 0, 'reason' => ''];
                            $nextYear = $factorData['next_year'] ?? ['target' => 0, 'achieved' => 0, 'difference' => 0, 'reason' => ''];
                            $nextToNextYear = $factorData['next_to_next_year'] ?? ['target' => 0, 'achieved' => 0, 'difference' => 0, 'reason' => ''];
                            ?>
                            <tr>
                                <?php if ($isFirstRow && $isFirstFactor): ?>
                                    <td rowspan="<?= $monthRowspan ?>" class="month-col"><?= $monthName ?></td>
                                <?php endif; ?>
                                
                                <?php if ($isFirstFactor): ?>
                                    <td rowspan="<?= count($factors) ?>" class="department-row text-left"><?= $deptName ?></td>
                                <?php endif; ?>
                                
                                <td class="text-left"><?= $factorName ?></td>
                                
                                <!-- Current Year -->
                                <td><?= number_format($currentYear['target'], 0, '.', ',') ?></td>
                                <td><?= number_format($currentYear['achieved'], 0, '.', ',') ?></td>
                                <td><?= number_format($currentYear['difference'], 0, '.', ',') ?></td>
                                <td class="text-left"><?= $currentYear['reason'] ?: '--' ?></td>
                                
                                <!-- Next Year -->
                                <td><?= number_format($nextYear['target'], 0, '.', ',') ?></td>
                                <td><?= number_format($nextYear['achieved'], 0, '.', ',') ?></td>
                                <td><?= number_format($nextYear['difference'], 0, '.', ',') ?></td>
                                <td class="text-left"><?= $nextYear['reason'] ?: '--' ?></td>
                                
                                <!-- Next To Next Year -->
                                <td><?= number_format($nextToNextYear['target'], 0, '.', ',') ?></td>
                                <td><?= number_format($nextToNextYear['achieved'], 0, '.', ',') ?></td>
                                <td><?= number_format($nextToNextYear['difference'], 0, '.', ',') ?></td>
                                <td class="text-left"><?= $nextToNextYear['reason'] ?: '--' ?></td>
                            </tr>
                            <?php $isFirstFactor = false; ?>
                        <?php endforeach; ?>
                        <?php $isFirstRow = false; ?>
                    <?php endforeach; ?>
                    
                    <!-- Monthly Total Row -->
                    <?php if (isset($monthData['monthly_total'])): ?>
                        <?php $total = $monthData['monthly_total']; ?>
                        <tr class="total-row">
                            <td colspan="2" class="text-left"><strong><?= $monthName ?> Monthly Total</strong></td>
                            
                            <!-- Current Year totals -->
                            <td><strong><?= number_format($total['current_year']['target'], 0, '.', ',') ?></strong></td>
                            <td><strong><?= number_format($total['current_year']['achieved'], 0, '.', ',') ?></strong></td>
                            <td><strong><?= number_format($total['current_year']['difference'], 0, '.', ',') ?></strong></td>
                            <td>--</td>
                            
                            <!-- Next Year totals -->
                            <td><strong><?= number_format($total['next_year']['target'], 0, '.', ',') ?></strong></td>
                            <td><strong><?= number_format($total['next_year']['achieved'], 0, '.', ',') ?></strong></td>
                            <td><strong><?= number_format($total['next_year']['difference'], 0, '.', ',') ?></strong></td>
                            <td>--</td>
                            
                            <!-- Next To Next Year totals -->
                            <td><strong><?= number_format($total['next_to_next_year']['target'], 0, '.', ',') ?></strong></td>
                            <td><strong><?= number_format($total['next_to_next_year']['achieved'], 0, '.', ',') ?></strong></td>
                            <td><strong><?= number_format($total['next_to_next_year']['difference'], 0, '.', ',') ?></strong></td>
                            <td>--</td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="13" style="text-align: center;">No data available</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                User :- <?= $user_name ?? '--' ?>
            </div>
            <div class="footer-center">
                It is Online Computer Generated Report. # If any Discrepancy please contact to us.
            </div>
            <div class="footer-right">
                Date :- <?= $current_date ?>
            </div>
        </div>
    </div>
</body>
</html>


