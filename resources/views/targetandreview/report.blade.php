@extends('layouts.masters')

@section('page_title', 'Target & Review Report')

@section('masters_content')

<style>
    .report-table {
        font-size: 13px;
        background-color: #ffffff;
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px;
    }
    .report-table th {
        background-color: #e8e8e8;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
        border: 1px solid #d0d0d0;
        padding: 10px 8px;
        color: #333333;
        font-size: 12px;
    }
    .report-table thead tr:first-child th {
        background-color: #e8e8e8;
        font-weight: 700;
        font-size: 13px;
    }
    .report-table thead tr:last-child th {
        background-color: #e8e8e8;
        font-weight: 600;
    }
    .report-table td {
        border: 1px solid #d0d0d0;
        padding: 8px 10px;
        text-align: right;
        color: #333333;
        background-color: #ffffff;
        font-size: 13px;
    }
    .report-table td.text-left {
        text-align: left;
    }
    .report-table .department-row {
        background-color: #f5f5f5;
        font-weight: 500;
    }
    .report-table .total-row {
        background-color: #e8e8e8;
        font-weight: 700;
    }
    .report-table .total-row td {
        background-color: #e8e8e8;
    }
    .tab-button,
    .year-tab-button {
        cursor: pointer;
        padding: 8px 14px;
        border: 1px solid #173b8a;
        background-color: #fff;
        color: #173b8a;
        border-radius: 6px;
        font-weight: 500;
    }
    .year-tab-button {
        margin-left: 5px;
    }
    .tab-button.active,
    .year-tab-button.active {
        background-color: #173b8a;
        color: #fff;
    }
    .nav-button {
        background-color: #fff;
        border: 1px solid #173b8a;
        color: #173b8a;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
    }
    .nav-button:hover {
        background-color: #eef4ff;
    }
    .nav-button i {
        font-size: 12px;
    }
    .month-year-display {
        font-weight: 600;
        color: #333333;
        font-size: 14px;
        min-width: 120px;
        text-align: center;
    }
</style>

<div class="card-header border-bottom-0 py-3 px-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold mb-0">Target & Review Report</h4>
            <p class="text-muted small mb-0">Analyze business and commission review data with month and year filters.</p>
        </div>
    </div>
</div>

<div class="card-body p-4">
                    <!-- Top Row: Report Type Tabs and Year Type Tabs with Download -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                <!-- Left: Report Type Tabs -->
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <button class="tab-button active" data-type="business" id="businessTab">
                                        Business Review Report
                                    </button>
                                    <button class="tab-button" data-type="commission" id="commissionTab">
                                        Commission Review Report
                                    </button>
                                </div>
                                
                                <!-- Right: Year Type Tabs and Download -->
                                <div class="d-flex align-items-center flex-wrap">
                                    <button class="year-tab-button active" data-type="financial" id="financialTab">
                                        Financial Year
                                    </button>
                                    <button class="year-tab-button" data-type="calendar" id="calendarTab">
                                        Calendar Year
                                    </button>
                                    <button type="button" class="btn btn-dark-blue ms-2 mb-0" id="downloadReport">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Row: Month and Year Navigation -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                <!-- Left: Month Navigation -->
                                <div class="d-flex align-items-center">
                                    <button type="button" class="nav-button" id="prevMonth" title="Previous Month">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="mx-3 month-year-display">
                                        <span id="currentMonthDisplay">January 2026</span>
                                    </div>
                                    <button type="button" class="nav-button" id="nextMonth" title="Next Month">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                
                                <!-- Right: Year Navigation -->
                                <div class="d-flex align-items-center">
                                    <button type="button" class="nav-button" id="prevYear" title="Previous Year">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="mx-3 month-year-display">
                                        <span id="currentYearDisplay">2026</span>
                                    </div>
                                    <button type="button" class="nav-button" id="nextYear" title="Next Year">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Report Table -->
                    <div class="table-responsive">
                        <table id="reportTable" class="report-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="min-width: 80px;">Month</th>
                                    <th rowspan="2" style="min-width: 150px;">Department</th>
                                    <th rowspan="2" style="min-width: 150px;">Factor</th>
                                    <th colspan="4" id="currentYearHeader">Current Year 2025-26</th>
                                    <th colspan="4" id="nextYearHeader">Next Year 2026-27</th>
                                    <th colspan="4" id="nextToNextYearHeader">Next To Next Year 2027-28</th>
                                </tr>
                                <tr>
                                    <!-- Current Year Columns -->
                                    <th style="min-width: 100px;">Target</th>
                                    <th style="min-width: 100px;">Achieved</th>
                                    <th style="min-width: 100px;">Difference</th>
                                    <th style="min-width: 120px;">Reason</th>
                                    <!-- Next Year Columns -->
                                    <th style="min-width: 100px;">Target</th>
                                    <th style="min-width: 100px;">Achieved</th>
                                    <th style="min-width: 100px;">Difference</th>
                                    <th style="min-width: 120px;">Reason</th>
                                    <!-- Next To Next Year Columns -->
                                    <th style="min-width: 100px;">Target</th>
                                    <th style="min-width: 100px;">Achieved</th>
                                    <th style="min-width: 100px;">Difference</th>
                                    <th style="min-width: 120px;">Reason</th>
                                </tr>
                            </thead>
                            <tbody id="reportTableBody">
                                <tr>
                                    <td colspan="13" class="text-center">Loading report...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
</div>

@endsection

@push('scripts')


<script>
$(document).ready(function() {
    const baseUrl = '{{ url('/') }}/';
    let currentReportType = 'business';
    let currentYearType = 'financial';
    let financialYears = {};
    let currentMonth = new Date().getMonth() + 1; // 1-12
    let currentYear = new Date().getFullYear(); // e.g., 2026

    // Report Type Tabs
    $('.tab-button').on('click', function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        currentReportType = $(this).data('type');
        loadReport();
    });

    // Year Type Tabs
    $('.year-tab-button').on('click', function() {
        $('.year-tab-button').removeClass('active');
        $(this).addClass('active');
        currentYearType = $(this).data('type');
        loadReport();
    });

    // Update Month/Year Display
    function updateMonthYearDisplay() {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        $('#currentMonthDisplay').text(monthNames[currentMonth - 1] + ' ' + currentYear);
        $('#currentYearDisplay').text(currentYear);
    }

    // Month Navigation
    $('#prevMonth').on('click', function() {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        updateMonthYearDisplay();
        loadReport();
    });

    $('#nextMonth').on('click', function() {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        updateMonthYearDisplay();
        loadReport();
    });

    // Year Navigation
    $('#prevYear').on('click', function() {
        currentYear--;
        updateMonthYearDisplay();
        loadReport();
    });

    $('#nextYear').on('click', function() {
        currentYear++;
        updateMonthYearDisplay();
        loadReport();
    });

    // Load Report Function
    function loadReport() {
        const $tbody = $('#reportTableBody');
        const selectedMonth = String(currentMonth).padStart(2, '0') + '-' + currentYear;

        $tbody.html('<tr><td colspan="13" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report...</td></tr>');

        $.ajax({
            url: baseUrl + 'targetandreview/getReportData',
            type: 'GET',
            data: {
                report_type: currentReportType,
                year_type: currentYearType,
                month: selectedMonth
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === true && res.data) {
                    financialYears = res.financial_years || {};
                    updateYearHeaders();
                    renderReport(res.data);
                } else {
                    $tbody.html('<tr><td colspan="13" class="text-center text-danger">No data found</td></tr>');
                }
            },
            error: function(xhr) {
                console.error('Report Error:', xhr);
                $tbody.html('<tr><td colspan="13" class="text-center text-danger">Error loading report. Please try again.</td></tr>');
            }
        });
    }

    // Update Year Headers
    function updateYearHeaders() {
        if (financialYears.current) {
            $('#currentYearHeader').text('Current Year ' + financialYears.current.label);
        }
        if (financialYears.next) {
            $('#nextYearHeader').text('Next Year ' + financialYears.next.label);
        }
        if (financialYears.next_to_next) {
            $('#nextToNextYearHeader').text('Next To Next Year ' + financialYears.next_to_next.label);
        }
    }


    // Render Report Table
    function renderReport(data) {
        const $tbody = $('#reportTableBody');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html('<tr><td colspan="13" class="text-center">No data available</td></tr>');
            return;
        }

        data.forEach(function(monthData) {
            const monthName = monthData.month;
            const departments = monthData.departments || [];
            let isFirstRow = true;

            departments.forEach(function(deptData) {
                const deptName = deptData.department_name;
                const factors = deptData.factors || [];
                let isFirstFactor = true;

                factors.forEach(function(factorData) {
                    const factorName = factorData.factor_name;
                    const currentYear = factorData.current_year || {target: 0, achieved: 0, difference: 0, reason: ''};
                    const nextYear = factorData.next_year || {target: 0, achieved: 0, difference: 0, reason: ''};
                    const nextToNextYear = factorData.next_to_next_year || {target: 0, achieved: 0, difference: 0, reason: ''};

                    const row = $('<tr>');
                    
                    // Month column (only show in first row of each month)
                    if (isFirstRow && isFirstFactor) {
                        row.append(`<td rowspan="${getMonthRowspan(monthData)}" class="text-left">${monthName}</td>`);
                    }

                    // Department column (only show in first factor of each department)
                    if (isFirstFactor) {
                        row.append(`<td rowspan="${factors.length}" class="text-left department-row">${deptName}</td>`);
                    }

                    // Factor column
                    row.append(`<td class="text-left">${factorName}</td>`);

                    // Current Year columns
                    row.append(`<td>${formatNumber(currentYear.target)}</td>`);
                    row.append(`<td>${formatNumber(currentYear.achieved)}</td>`);
                    row.append(`<td>${formatNumber(currentYear.difference)}</td>`);
                    row.append(`<td class="text-left">${currentYear.reason || '--'}</td>`);

                    // Next Year columns
                    row.append(`<td>${formatNumber(nextYear.target)}</td>`);
                    row.append(`<td>${formatNumber(nextYear.achieved)}</td>`);
                    row.append(`<td>${formatNumber(nextYear.difference)}</td>`);
                    row.append(`<td class="text-left">${nextYear.reason || '--'}</td>`);

                    // Next To Next Year columns
                    row.append(`<td>${formatNumber(nextToNextYear.target)}</td>`);
                    row.append(`<td>${formatNumber(nextToNextYear.achieved)}</td>`);
                    row.append(`<td>${formatNumber(nextToNextYear.difference)}</td>`);
                    row.append(`<td class="text-left">${nextToNextYear.reason || '--'}</td>`);

                    $tbody.append(row);
                    isFirstFactor = false;
                });

                isFirstRow = false;
            });

            // Monthly Total Row
            if (monthData.monthly_total) {
                const total = monthData.monthly_total;
                const totalRow = $('<tr class="total-row">');
                totalRow.append('<td colspan="2" class="text-left"><strong>Monthly Total (' + monthName + ')</strong></td>');
                
                // Current Year totals
                totalRow.append(`<td><strong>${formatNumber(total.current_year.target)}</strong></td>`);
                totalRow.append(`<td><strong>${formatNumber(total.current_year.achieved)}</strong></td>`);
                totalRow.append(`<td><strong>${formatNumber(total.current_year.difference)}</strong></td>`);
                totalRow.append('<td>--</td>');

                // Next Year totals
                totalRow.append(`<td><strong>${formatNumber(total.next_year.target)}</strong></td>`);
                totalRow.append(`<td><strong>${formatNumber(total.next_year.achieved)}</strong></td>`);
                totalRow.append(`<td><strong>${formatNumber(total.next_year.difference)}</strong></td>`);
                totalRow.append('<td>--</td>');

                // Next To Next Year totals
                totalRow.append(`<td><strong>${formatNumber(total.next_to_next_year.target)}</strong></td>`);
                totalRow.append(`<td><strong>${formatNumber(total.next_to_next_year.achieved)}</strong></td>`);
                totalRow.append(`<td><strong>${formatNumber(total.next_to_next_year.difference)}</strong></td>`);
                totalRow.append('<td>--</td>');

                $tbody.append(totalRow);
            }
        });
    }

    // Calculate rowspan for month column
    function getMonthRowspan(monthData) {
        let rowspan = 0;
        monthData.departments.forEach(function(dept) {
            rowspan += dept.factors ? dept.factors.length : 0;
        });
        // Add 1 for monthly total row
        return rowspan + 1;
    }

    // Format number with Indian locale
    function formatNumber(num) {
        if (num === 0 || num === '0' || num === '') return '0';
        return parseFloat(num).toLocaleString('en-IN');
    }

    // Download Report as PDF
    $('#downloadReport').on('click', function() {
        const selectedMonth = String(currentMonth).padStart(2, '0') + '-' + currentYear;
        const reportType = currentReportType;
        const yearType = currentYearType;
        
        // Build download URL
        const downloadUrl = baseUrl + 'targetandreview/downloadPdf?' + 
            'report_type=' + reportType + 
            '&year_type=' + yearType + 
            '&month=' + selectedMonth;
        
        // Open in new window to download PDF
        window.open(downloadUrl, '_blank');
    });

    // Initialize display
    updateMonthYearDisplay();
    
    // Load report on page load
    loadReport();
});
</script>


@endpush

