@extends('layouts.app')

@section('page_title', 'Agent Report')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .report-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .report-filters .filter-item { display: flex; align-items: center; gap: 8px; }
    .report-filters label { margin: 0; white-space: nowrap; font-weight: 500; }
    .report-cards h3 { margin: 0; }
    .staff-chart-wrap { height: 235px; }
    .staff-chart-wrap canvas { width: 100% !important; height: 100% !important; }
    .staff-filter-panel { margin: 12px 0 !important; padding: 12px !important; }
    .report-cards .card-body { padding: 0.8rem 1rem; }
    .report-cards h6 { margin-bottom: 0.25rem; font-size: 0.78rem; color: #64748b; }
    .report-cards h3 { font-size: 1.35rem; }
    .staff-table-heading { margin-bottom: 0 !important; }
    .staff-export-btn {
        height: 29px;
        min-height: 29px;
        padding: 0 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff !important;
        background: #0b2341 !important;
        border-color: #0b2341 !important;
        font-size: 0.8rem;
        line-height: 1.1;
    }
    .staff-export-btn:hover,
    .staff-export-btn:focus {
        color: #fff !important;
        background: #071a31 !important;
        border-color: #071a31 !important;
    }
    @media (max-width: 768px) {
        .report-filters { flex-direction: column; align-items: stretch; }
        .report-filters .filter-item { width: 100%; }
        .staff-chart-wrap { height: 190px; }
    }
    
    #staff-report-table {
        font-size: 0.82rem !important;
        border-collapse: collapse !important;
        width: 100% !important;
        margin: 0 !important;
    }
    #staff-report-table thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.025em !important;
        padding: 0.75rem 0.5rem !important;
        border-bottom: 2px solid #e2e8f0 !important;
        position: relative !important;
    }
    #staff-report-table tbody td {
        padding: 0.75rem 0.5rem !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    #staff-report-table tbody tr {
        transition: background-color 0.2s ease !important;
    }
    #staff-report-table tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.04) !important;
    }
    table.dataTable.no-footer {
        border-bottom: 1px solid #e2e8f0 !important;
    }
    
    .dataTables_wrapper {
        padding: 0 !important;
    }
    .dataTables_wrapper::after {
        content: "" !important;
        display: table !important;
        clear: both !important;
    }
    .dataTables_wrapper .dataTables_info {
        float: left !important;
        padding: 1.5rem 2rem !important;
        color: #475569 !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
    }
    .dataTables_wrapper .dataTables_paginate {
        float: right !important;
        padding: 1.2rem 2rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    .dataTables_wrapper .dataTables_paginate span {
        display: flex !important;
        gap: 6px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        box-sizing: border-box !important;
        display: inline-block !important;
        min-width: 1.5em !important;
        text-align: center !important;
        text-decoration: none !important;
        cursor: pointer !important;
        border-radius: 10px !important;
        padding: 0.6rem 1.1rem !important;
        color: #cbd5e1 !important;
        background-color: #fff !important;
        background: #fff !important;
        border: 1px solid #e2e8f0 !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
        margin: 0 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #f8fafc !important;
        background: #f8fafc !important;
        color: #0f172a !important;
        border-color: #0f172a !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        border-color: #0f172a !important;
        color: #fff !important;
        box-shadow: 0 6px 12px rgba(15, 23, 42, 0.2) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:active {
        background-color: #f8fafc !important;
        background: #f8fafc !important;
        color: #cbd5e1 !important;
        border-color: #f1f5f9 !important;
        box-shadow: none !important;
        cursor: not-allowed !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <h4 class="mb-0">Agent Report</h4>
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                    <div class="input-group input-group-sm" style="max-width: 260px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search sub agents..." id="staffReportSearch">
                    </div>
                    <a id="export-btn" href="#" class="btn btn-dark-blue btn-sm staff-export-btn text-nowrap">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                </div>
            </div>
            <div class="staff-chart-wrap mt-2" id="staff-chart-wrap">
                <canvas id="line-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="row report-cards g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body text-center"><h6>Total Policies</h6><h3 id="total-policies">0</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body text-center"><h6>Total Leads</h6><h3 id="total-leads">0</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body text-center"><h6>Total Followups</h6><h3 id="total-followups">0</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body text-center"><h6>Total Meetings</h6><h3 id="total-meetings">0</h3></div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="px-3 pt-2 pb-0" id="staff-filter-section">
                <div class="rounded staff-filter-panel" id="staff-table-filters" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label for="staff-select" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Sub Agent</label>
                            <select id="staff-select" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-customer" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Policy Holder</label>
                            <select id="filter-customer" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->name }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-policy-number" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Policy Number</label>
                            <select id="filter-policy-number" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                @foreach($policyNumbers as $pn)
                                    <option value="{{ $pn }}">{{ $pn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-policy-type" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Policy Type</label>
                            <select id="filter-policy-type" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                <option value="Health">Health</option>
                                <option value="Life Insurance">Life Insurance</option>
                                <option value="Vehicle">Vehicle</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-company" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Company</label>
                            <select id="filter-company" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="start-date" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Start Date</label>
                            <input type="date" id="start-date" class="form-control form-control-sm text-muted">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="end-date" class="form-label mb-1" style="font-size: 13px; color: #64748b;">End Date</label>
                            <input type="date" id="end-date" class="form-control form-control-sm text-muted">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="date-range" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Date Range</label>
                            <select id="date-range" class="form-select form-select-sm text-muted">
                                <option value="custom">Custom</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="last_7_days">Last 7 Days</option>
                                <option value="last_30_days">Last 30 Days</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="staff-report-table" class="table table-hover align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th class="ps-4">Sr.No</th>
                            <th>Sub Agent Name</th>
                            <th>Policy Number</th>
                            <th>Policy Type</th>
                            <th>Sum Assured</th>
                            <th>Premium</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function () {
    // Keep all report controls together above the compact chart.
    $('#staff-table-filters').insertBefore('#staff-chart-wrap');
    $('#staff-filter-section').remove();

    $.get("{{ route('reports.staff_list') }}", function (res) {
        const select1 = $('#staff-select');
        (res || []).forEach(function (staff) {
            const optionHtml = `<option value="${staff.id}">${staff.name}</option>`;
            select1.append(optionHtml);
        });
        select1.select2({ placeholder: "All Sub Agents", allowClear: true, width: '100%' });
    });

    const chart = new Chart(document.getElementById('line-chart').getContext('2d'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Policies', data: [], borderColor: '#4bc0c0', backgroundColor: 'rgba(75,192,192,0.2)', tension: 0.35 },
                { label: 'Leads', data: [], borderColor: '#ff6384', backgroundColor: 'rgba(255,99,132,0.2)', tension: 0.35 },
                { label: 'Followups', data: [], borderColor: '#36a2eb', backgroundColor: 'rgba(54,162,235,0.2)', tension: 0.35 },
                { label: 'Meetings', data: [], borderColor: '#ffcd56', backgroundColor: 'rgba(255,205,86,0.2)', tension: 0.35 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 0, right: 4, bottom: 0, left: 0 } },
            plugins: { legend: { labels: { boxWidth: 18, padding: 12 } } }
        }
    });

    function updateChart(monthlyPolicies, monthlyLeads, monthlyFollowups, monthlyMeetings) {
        const months = Object.keys(Object.assign({}, monthlyPolicies, monthlyLeads, monthlyFollowups, monthlyMeetings));
        months.sort((a, b) => new Date(Date.parse(a + ' 1, 2022')) - new Date(Date.parse(b + ' 1, 2022')));

        chart.data.labels = months;
        chart.data.datasets[0].data = months.map(m => monthlyPolicies[m] || 0);
        chart.data.datasets[1].data = months.map(m => monthlyLeads[m] || 0);
        chart.data.datasets[2].data = months.map(m => monthlyFollowups[m] || 0);
        chart.data.datasets[3].data = months.map(m => monthlyMeetings[m] || 0);
        chart.update();
    }

    const table = $('#staff-report-table').DataTable({
        processing: true,
        serverSide: true,
        dom: 'rtip',
        language: {
            info: "Showing _START_ to _END_ of _TOTAL_ results",
            paginate: {
                previous: "Previous",
                next: "Next"
            }
        },
        ajax: {
            url: "{{ route('reports.staff_data') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.staff_id = $('#staff-select').val();
                d.start_date = $('#start-date').val();
                d.end_date = $('#end-date').val();
                d.date_range = $('#date-range').val();
                d.policy_number = $('#filter-policy-number').val();
                d.customer = $('#filter-customer').val();
                d.policy_type = $('#filter-policy-type').val();
                d.company_id = $('#filter-company').val();
            },
            dataSrc: function (json) {
                updateChart(json.monthlyPolicies || {}, json.monthlyLeads || {}, json.monthlyFollowups || {}, json.monthlyMeetings || {});
                $('#total-policies').text(json.totalPolicyCount || 0);
                $('#total-leads').text(json.totalLeads || 0);
                $('#total-followups').text(json.totalFollowups || 0);
                $('#total-meetings').text(json.totalMeetings || 0);
                return json.data || [];
            }
        },
        columns: [
            { data: null, className: 'ps-4', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'name' },
            { data: 'policy_number' },
            { data: 'policy_type' },
            { data: 'sum_assured' },
            { data: 'premium' },
            { data: 'start_date' },
            { data: 'end_date' },
            { data: 'status', render: function (value) {
                const v = String(value || '').toLowerCase();
                const cls = v === 'active' ? 'bg-success' : (v === 'expired' ? 'bg-danger' : (v === 'pending' ? 'bg-warning text-dark' : 'bg-secondary'));
                return `<span class="badge ${cls}">${value || '--'}</span>`;
            }}
        ]
    });

    $('#staffReportSearch').on('keyup input', function () {
        table.search(this.value).draw();
    });

    $('#filter-policy-number').select2({
        tags: true,
        placeholder: "Enter Policy Number",
        allowClear: true,
        width: '100%'
    });
    
    $('#filter-customer').select2({
        tags: true,
        placeholder: "Enter Policy Holder",
        allowClear: true,
        width: '100%'
    });

    $('#filter-policy-type').select2({
        placeholder: "All Types",
        allowClear: true,
        width: '100%'
    });

    $('#filter-company').select2({
        placeholder: "All Companies",
        allowClear: true,
        width: '100%'
    });
    function calculateDatesForRange(range) {
        const today = new Date();
        let start, end;
        switch (range) {
            case 'today':
                start = today;
                end = today;
                break;
            case 'yesterday':
                start = new Date();
                start.setDate(today.getDate() - 1);
                end = start;
                break;
            case 'last_7_days':
                start = new Date();
                start.setDate(today.getDate() - 6);
                end = today;
                break;
            case 'last_30_days':
                start = new Date();
                start.setDate(today.getDate() - 29);
                end = today;
                break;
            case 'this_month':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'last_month':
                start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                end = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            default:
                return ['', ''];
        }
        
        const formatDate = (d) => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };
        
        return [formatDate(start), formatDate(end)];
    }

    function updateDateRangeDropdown() {
        const startVal = $('#start-date').val();
        const endVal = $('#end-date').val();
        
        if (!startVal || !endVal) {
            $('#date-range').val('custom');
            return;
        }
        
        const ranges = ['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month'];
        let matchedRange = 'custom';
        for (const r of ranges) {
            const [s, e] = calculateDatesForRange(r);
            if (startVal === s && endVal === e) {
                matchedRange = r;
                break;
            }
        }
        $('#date-range').val(matchedRange);
    }

    $('#date-range').on('change', function () {
        const range = $(this).val();
        if (range && range !== 'custom') {
            const [s, e] = calculateDatesForRange(range);
            const startEl = document.getElementById('start-date');
            const endEl = document.getElementById('end-date');
            if (startEl && startEl._flatpickr) startEl._flatpickr.setDate(s); else $('#start-date').val(s);
            if (endEl && endEl._flatpickr) endEl._flatpickr.setDate(e); else $('#end-date').val(e);
        }
    });

    $('#start-date, #end-date').on('change', function () {
        updateDateRangeDropdown();
    });

    // Initialize date inputs if a range is pre-selected on load
    const initialRange = $('#date-range').val();
    if (initialRange && initialRange !== 'custom' && !$('#start-date').val() && !$('#end-date').val()) {
        const [s, e] = calculateDatesForRange(initialRange);
        const startEl = document.getElementById('start-date');
        const endEl = document.getElementById('end-date');
        if (startEl && startEl._flatpickr) startEl._flatpickr.setDate(s); else $('#start-date').val(s);
        if (endEl && endEl._flatpickr) endEl._flatpickr.setDate(e); else $('#end-date').val(e);
    }

    $('#staff-select, #start-date, #end-date, #date-range, #filter-policy-type, #filter-company, #filter-policy-number, #filter-customer').on('change', function () {
        table.ajax.reload();
    });

    $('#export-btn').on('click', function (e) {
        e.preventDefault();
        const params = new URLSearchParams({
            staff_id: $('#staff-select').val() || '',
            start_date: $('#start-date').val() || '',
            end_date: $('#end-date').val() || '',
            date_range: $('#date-range').val() || '',
            policy_number: $('#filter-policy-number').val() || '',
            customer: $('#filter-customer').val() || '',
            policy_type: $('#filter-policy-type').val() || '',
            company_id: $('#filter-company').val() || ''
        });
        window.location.href = "{{ route('reports.staff_export') }}" + '?' + params.toString();
    });
});
</script>
@endpush
