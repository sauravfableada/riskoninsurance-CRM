@extends('layouts.app')

@section('page_title', 'Followups Report')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <style>
        .report-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: flex-end; }
        .report-filters .filter-item { display: flex; align-items: center; gap: 8px; }
        .report-filters label { margin: 0; white-space: nowrap; font-weight: 500; }
        .select2-container .select2-selection--single {
            min-height: 31px;
            border-radius: 4px;
            border: 1px solid #ced4da !important;
            display: flex;
            align-items: center;
        }
        .report-reset-btn { min-width: 120px; }
        @media (max-width: 768px) {
            .report-filters { flex-direction: column; align-items: stretch; justify-content: flex-start; }
            .report-filters .filter-item { width: 100%; }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="row g-4">
                <div class="col-12 col-lg-2"><h4 class="fw-bold mb-0">Followups Overview</h4></div>
                <div class="col-12 col-lg-10 d-flex justify-content-end">
                        <div class="report-filters justify-content-lg-end">
                            <div class="filter-item">
                                <label for="top-staff-filter">Sub Agent:</label>
                                <select id="top-staff-filter" class="form-select form-select-sm" style="min-width: 150px;">
                                    <option value="">All Sub Agents</option>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="year-select">Year:</label>
                                <select id="year-select" class="form-select form-select-sm">
                                    <option value="all">All Years</option>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="from-date-filter">From Date:</label>
                                <input id="from-date-filter" class="form-control form-control-sm" placeholder="DD-MM">
                            </div>
                            <div class="filter-item">
                                <label for="to-date-filter">To Date:</label>
                                <input id="to-date-filter" class="form-control form-control-sm" placeholder="DD-MM">
                            </div>
                        </div>
                </div>
            </div>
        </div>
        <div class="card-body px-4 pb-4"><div class="chart-container" style="position: relative; height:350px; width:100%"><canvas id="followupsChart"></canvas></div></div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3"><div><h4 class="fw-bold mb-0">Followups Report</h4><p class="text-muted small mb-0">View all followups.</p></div><div class="d-flex flex-wrap gap-2"><a href="#" id="export-btn" class="btn btn-dark-blue btn-sm report-export-btn"><i class="fa-solid fa-download me-1"></i>Export</a></div></div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"><h6 class="fw-bold mb-0">Active Followups</h6><div class="input-group input-group-sm" style="max-width: 300px; width: 100%;"><span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span><input type="text" id="followupsReportSearch" class="form-control crm-search-input border-0" placeholder="Search follow ups..."></div></div>
        </div>
        <div class="card-body p-0">
            <div class="p-4 pb-0">
                <div class="p-3 rounded mb-4" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label for="staff-filter" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Sub Agent</label>
                            <select id="staff-filter" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-lead" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Lead Name</label>
                            <select id="filter-lead" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                @foreach($leads as $lead)
                                    <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-priority" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Priority</label>
                            <select id="filter-priority" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter-status" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Status</label>
                            <select id="filter-status" class="form-select form-select-sm text-muted">
                                <option value=""></option>
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
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
            <div class="table-responsive"><table class="table table-hover align-middle mb-0 responsive-table"><thead><tr><th class="ps-4">Purpose & Target</th><th class="text-start">Follow Up At</th><th class="d-none d-md-table-cell">Priority</th><th class="d-none d-md-table-cell">Sub Agent</th><th class="d-none d-md-table-cell">Status</th><th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th><th class="text-center d-md-none" style="width: 80px;">Action</th></tr></thead><tbody id="followupsReportBody"></tbody></table></div><div id="followupsReportPagination" class="px-4 pb-3 pt-0"></div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/report-layout.js' : '/js/report-layout.js') }}?v={{ filemtime(public_path('js/report-layout.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function () {
    compactReportLayout({ title: 'Follow-up Report', chart: '#followupsChart', table: '#followupsReportBody', search: '#followupsReportSearch', export: '#export-btn', filterAnchor: '#staff-filter' });
    const tableBody = document.getElementById('followupsReportBody');
    const paginationContainer = document.getElementById('followupsReportPagination');
    const searchInput = document.getElementById('followupsReportSearch');

    $.get("{{ route('reports.staff_list') }}", function (res) {
        const select1 = $('#staff-filter');
        const select2 = $('#top-staff-filter');
        (res || []).forEach(function (staff) {
            const optionHtml = `<option value="${staff.id}">${staff.name}</option>`;
            select1.append(optionHtml);
            select2.append(optionHtml);
        });
        select1.select2({ placeholder: "All Sub Agents", allowClear: true, width: '100%' });
        select2.select2({ placeholder: "All Sub Agents", allowClear: true, width: '150px' });
    });

    let isSyncingStaff = false;
    $('#top-staff-filter, #staff-filter').on('change', function() {
        if (isSyncingStaff) return;
        isSyncingStaff = true;
        const val = $(this).val();
        if (this.id === 'top-staff-filter') {
            $('#staff-filter').val(val).trigger('change.select2');
        } else {
            $('#top-staff-filter').val(val).trigger('change.select2');
        }
        isSyncingStaff = false;
        fetchFollowupsReport(1);
    });

    $('#filter-priority').select2({ placeholder: "All Priorities", allowClear: true, width: '100%' });
    $('#filter-lead').select2({ placeholder: "All Leads", allowClear: true, width: '100%' });
    $('#filter-status').select2({ placeholder: "All Statuses", allowClear: true, width: '100%' });

    const yearSelect = $('#year-select');
    const currentYear = new Date().getFullYear();
    for (let y = currentYear - 3; y <= currentYear; y++) {
        yearSelect.append(`<option value="${y}">${y}</option>`);
    }
    yearSelect.val(currentYear);

    flatpickr('#from-date-filter,#to-date-filter, #start-date, #end-date', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m',
        allowInput: true
    });

    $('#year-select, #from-date-filter, #to-date-filter').on('change', function() {
        fetchFollowupsReport(1);
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

    $('#filter-lead, #filter-priority, #filter-status, #start-date, #end-date, #date-range').on('change', function() {
        fetchFollowupsReport(1);
    });

    $('#export-btn').on('click', function(e) {
        e.preventDefault();
        const params = new URLSearchParams({
            year: $('#year-select').val() || 'all',
            from_date: $('#from-date-filter').val() || '',
            to_date: $('#to-date-filter').val() || '',
            staff_id: $('#staff-filter').val() || '',
            lead_id: $('#filter-lead').val() || '',
            priority: $('#filter-priority').val() || '',
            status: $('#filter-status').val() || '',
            start_date: $('#start-date').val() || '',
            end_date: $('#end-date').val() || '',
            date_range: $('#date-range').val() || ''
        });
        window.location.href = "{{ route('reports.followups.export') }}?" + params.toString();
    });

    function fetchFollowupsReport(page = 1) {
        const url = new URL("{{ route('reports.followups') }}", window.location.origin);
        url.searchParams.set('page', page); 
        url.searchParams.set("year", $('#year-select').val() || "all");
        url.searchParams.set("from_date", $('#from-date-filter').val() || "");
        url.searchParams.set("to_date", $('#to-date-filter').val() || "");
        url.searchParams.set("staff_id", $('#staff-filter').val() || "");
        url.searchParams.set("lead_id", $('#filter-lead').val() || "");
        url.searchParams.set("priority", $('#filter-priority').val() || "");
        url.searchParams.set("status", $('#filter-status').val() || "");
        url.searchParams.set("start_date", $('#start-date').val() || "");
        url.searchParams.set("end_date", $('#end-date').val() || "");
        url.searchParams.set("date_range", $('#date-range').val() || "");
        if (searchInput.value.trim()) url.searchParams.set('search', searchInput.value.trim());
        $.ajax({ 
            url: url.toString(), 
            type: 'GET', 
            dataType: 'json', 
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, 
            beforeSend: function () { tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>'; },
            success: function (res) {
                if (res.success && res.data) {
                    renderRows(res.data.data || []);
                    renderPagination(res.data);
                    if (res.chartData && window.followupsChart) {
                        window.followupsChart.data.labels = res.chartLabels || [];
                        window.followupsChart.data.datasets[0].data = res.chartData;
                        window.followupsChart.update();
                    }
                }
            }, 
            error: function () { tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading follow-ups</td></tr>'; paginationContainer.innerHTML = ''; } 
        });
    }

    function renderRows(items) {
        if (!items || !items.length) { tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="text-muted mb-3"><i class="bi bi-telephone-outbound display-1 opacity-25"></i></div><p class="text-muted">No follow ups found.</p></td></tr>'; return; }
        tableBody.innerHTML = items.map(function (followUp) {
            const entity = followUp.lead || followUp.customer; const entityType = followUp.lead ? 'Lead' : 'Customer'; const initial = entity?.name ? entity.name.substring(0, 1).toUpperCase() : '?'; const followDate = followUp.follow_up_at ? new Date(followUp.follow_up_at) : null; const date = followDate ? followDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '--'; const time = followDate ? followDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : ''; const priorityClass = { low: 'bg-info', medium: 'bg-primary', high: 'bg-danger' }[followUp.priority] || 'bg-secondary'; const statusClass = { pending: 'bg-warning text-dark', resheduled: 'bg-info', completed: 'bg-success', cancelled: 'bg-danger' }[followUp.status] || 'bg-secondary'; const priorityHtml = `<span class="badge ${priorityClass} px-3 py-2 rounded-pill text-capitalize">${followUp.priority ?? '--'}</span>`; const statusHtml = `<span class="badge ${statusClass} px-3 py-2 rounded-pill text-capitalize">${followUp.status ?? '--'}</span>`;
            return `
            <tr><td class="ps-4"><div class="d-flex align-items-center gap-3 py-1"><div class="rounded-circle d-flex align-items-center justify-content-center text-white bg-primary d-none d-md-flex" style="width: 36px; height: 36px; font-weight: 600; font-size: 0.9rem;">${initial}</div><div><div class="fw-bold small">${followUp.purpose ?? '--'}</div><div class="text-muted" style="font-size: 0.7rem;">${entity?.name ?? 'Unknown'} (${entityType})</div></div></div></td><td><div class="small fw-semibold">${date}</div><div class="text-muted small" style="font-size: 0.7rem;">${time}</div></td><td class="d-none d-md-table-cell">${priorityHtml}</td><td class="d-none d-md-table-cell"><div class="small">${followUp.assigned_user?.name ?? 'Unassigned'}</div></td><td class="d-none d-md-table-cell">${statusHtml}</td><td class="text-end pe-4 d-none d-md-table-cell"><div class="d-inline-flex align-items-center gap-2"><a href="/follow-ups/${followUp.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a></div></td><td class="text-center d-md-none"><button type="button" class="btn-user-expand" data-followup-id="${followUp.id}"><i class="fa-solid fa-plus"></i></button></td></tr>
            <tr class="details-row d-md-none border-0" id="details-${followUp.id}" style="display: none;"><td colspan="7" class="p-0 border-0"><div class="details-content"><div class="row g-3"><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div><div class="expand-value">${statusHtml}</div></div><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-bolt"></i> Priority :</div><div class="expand-value">${priorityHtml}</div></div><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-user"></i> Sub Agent :</div><div class="expand-value">${followUp.assigned_user?.name ?? 'Unassigned'}</div></div><div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top"><div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div><div class="d-flex flex-wrap gap-2"><a href="/follow-ups/${followUp.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a></div></div></div></div></td></tr>`;
        }).join('');
        tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) { button.addEventListener('click', function () { const id = this.dataset.followupId; const detailsRow = document.getElementById(`details-${id}`); const icon = this.querySelector('i'); if (detailsRow.style.display === 'none') { detailsRow.style.display = 'table-row'; icon.classList.replace('fa-plus', 'fa-minus'); this.classList.add('active'); } else { detailsRow.style.display = 'none'; icon.classList.replace('fa-minus', 'fa-plus'); this.classList.remove('active'); } }); });
    }

    function renderPagination(data) {
        if (!data || data.total === 0) { paginationContainer.innerHTML = ''; return; }
        const from = data.from || 0, to = data.to || 0, total = data.total || 0, currentPage = data.current_page || 1, lastPage = data.last_page || 1;
        let html = `<div class="crm-pagination-container"><div class="text-muted small">Showing ${from} to ${to} of ${total} results</div><ul class="pagination crm-pagination mb-0">`;
        html += data.prev_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>` : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        for (let i = 1; i <= lastPage; i++) { if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) { html += i === currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`; } else if (i === currentPage - 3 || i === currentPage + 3) { html += '<li class="page-item disabled"><span class="page-link">...</span></li>'; } }
        html += data.next_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>` : '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        html += '</ul></div>'; paginationContainer.innerHTML = html; paginationContainer.querySelectorAll('.page-link[data-page]').forEach(function (link) { link.addEventListener('click', function (e) { e.preventDefault(); fetchFollowupsReport(this.dataset.page); }); });
    }

    let timer; searchInput.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { fetchFollowupsReport(1); }, 400); });

    const ctx = document.getElementById('followupsChart').getContext('2d');
    window.followupsChart = new Chart(ctx, { type: 'bar', data: { labels: @json($chartLabels), datasets: [{ label: 'Followups', data: @json($chartData), backgroundColor: 'rgba(54, 162, 235, 0.25)', borderColor: 'rgba(54, 162, 235, 0.8)', borderWidth: 1, borderRadius: 2, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top', align: 'center', labels: { usePointStyle: true, pointStyle: 'rect', padding: 20, font: { size: 12 } } }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#fff', titleColor: '#333', bodyColor: '#666', borderColor: '#ddd', borderWidth: 1, padding: 10, displayColors: true, callbacks: { label: function (context) { return ' Followups: ' + context.parsed.y; } } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Followups', color: '#666', font: { size: 12 } }, grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false }, ticks: { stepSize: 1, font: { size: 11 } } }, x: { grid: { color: 'rgba(0, 0, 0, 0.05)' }, ticks: { font: { size: 11 } } } } } });
    fetchFollowupsReport(1);
});
</script>
@endpush
