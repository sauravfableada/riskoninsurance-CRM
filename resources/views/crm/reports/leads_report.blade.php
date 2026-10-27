@extends('layouts.app')

@section('page_title', 'Lead Report')

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
        .lead-converted-badge { background-color: #e6f4ea; color: #2e7d32; font-size: 0.72rem; font-weight: 500; padding: 3px 8px; border-radius: 20px; }
        @media (max-width: 768px) {
            .report-filters { flex-direction: column; align-items: stretch; }
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
                    <div class="col-12 col-lg-2">
                        <h4 class="fw-bold mb-0">Lead</h4>
                    </div>
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
            <div class="card-body px-4 pb-4">
                <div class="chart-container" style="position: relative; height:350px; width:100%">
                    <canvas id="leadChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Lead Report</h4>
                        <p class="text-muted small mb-0">View all leads.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a id="export-btn" href="#" class="btn btn-dark-blue btn-sm report-export-btn">
                            <i class="fa-solid fa-download me-1"></i>Export
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Active Leads</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search leads..." id="leadsReportSearch">
                    </div>
                </div>
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
                                <label for="filter-status" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Status</label>
                                <select id="filter-status" class="form-select form-select-sm text-muted">
                                    <option value=""></option>
                                    <option value="New">New</option>
                                    <option value="Contacted">Contacted</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Lost">Lost</option>
                                    <option value="Qualified">Qualified</option>
                                    <option value="Quotation">Quotation</option>
                                    <option value="Converted">Converted</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label for="filter-lead-source" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Lead Source</label>
                                <select id="filter-lead-source" class="form-select form-select-sm text-muted">
                                    <option value=""></option>
                                    @foreach($leadSources as $source)
                                        <option value="{{ $source->id }}">{{ $source->name }}</option>
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
                    <table class="table table-hover align-middle mb-0" id="leadsReportTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Lead Name</th>
                                <th class="d-none d-md-table-cell">Lead Source</th>
                                <th class="d-none d-md-table-cell">Created By</th>
                                <th class="d-none d-md-table-cell">Created At</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="leadsReportBody"></tbody>
                    </table>
                </div>
                <div class="card-footer border-top-0 py-4 px-4" id="leadsReportPagination"></div>
            </div>
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
            compactReportLayout({ title: 'Lead Report', chart: '#leadChart', table: '#leadsReportTable', search: '#leadsReportSearch', export: '#export-btn', filterAnchor: '#staff-filter' });
            const tableBody = document.getElementById('leadsReportBody');
            const paginationContainer = document.getElementById('leadsReportPagination');
            const searchInput = document.getElementById('leadsReportSearch');

            flatpickr('#from_date, #to_date', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m',
                allowInput: true
            });

            function statusBadge(status) {
                return { new: 'bg-info', qualified: 'bg-primary', working: 'bg-warning', ready_to_close: 'bg-dark', won: 'bg-success', lost: 'bg-danger' }[status] || 'bg-secondary';
            }

            function formatStatus(status) {
                if (!status) return '--';
                const labels = { ready_to_close: 'Ready to Close', won: 'Closed Won', lost: 'Closed Lost' };
                return labels[status] || status.replace(/_/g, ' ').replace(/\b\w/g, function (char) { return char.toUpperCase(); });
            }

            function formatDate(dateValue) {
                if (!dateValue) return 'Not Set';
                const date = new Date(dateValue);
                if (Number.isNaN(date.getTime())) return 'Not Set';
                return date.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }

            function fetchLeadsReport(page = 1) {
                const url = new URL("{{ route('reports.leads') }}", window.location.origin);
                url.searchParams.set('page', page);
                url.searchParams.set("year", $('#year-select').val() || "all");
                url.searchParams.set("from_date", $('#from-date-filter').val() || "");
                url.searchParams.set("to_date", $('#to-date-filter').val() || "");
                url.searchParams.set("staff_id", $('#staff-filter').val() || "");
                url.searchParams.set("lead_id", $('#filter-lead').val() || "");
                url.searchParams.set("status", $('#filter-status').val() || "");
                url.searchParams.set("lead_source_id", $('#filter-lead-source').val() || "");
                url.searchParams.set("start_date", $('#start-date').val() || "");
                url.searchParams.set("end_date", $('#end-date').val() || "");
                url.searchParams.set("date_range", $('#date-range').val() || "");
                if (searchInput.value.trim()) url.searchParams.set('search', searchInput.value.trim());

                $.ajax({
                    url: url.toString(), type: 'GET', dataType: 'json', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    beforeSend: function () {
                        tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
                    },
                    success: function (res) {
                        if (res.success && res.data) {
                            renderRows(res.data.data || [], res.data);
                            renderPagination(res.data);
                            if (res.chartData && window.leadChart) {
                                window.leadChart.data.labels = res.chartLabels || [];
                                window.leadChart.data.datasets[0].data = res.chartData;
                                window.leadChart.update();
                            }
                        }
                    },
                    error: function () { tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-5">Error loading leads</td></tr>'; paginationContainer.innerHTML = ''; }
                });
            }

            function renderRows(items, meta) {
                if (!items || !items.length) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div><p class="text-muted">No leads found.</p></td></tr>';
                    return;
                }

                tableBody.innerHTML = items.map(function (lead, index) {
                    const isConverted = Boolean(lead.is_converted);
                    const sourceText = lead.lead_source?.name || lead.leadSource?.name || '--';
                    const createdBy = lead.assigned_user?.name || lead.assignedUser?.name || '--';
                    const srNo = meta && meta.from ? meta.from + index : index + 1;
                    const statusHtml = `<span class="badge ${statusBadge(lead.status)} rounded-pill px-3">${formatStatus(lead.status)}</span>`;
                    return `
                        <tr>
                            <td class="ps-4">${srNo}</td>
                            <td><div class="fw-semibold d-flex align-items-center gap-2"><span>${lead.name ?? '--'}</span>${isConverted ? '<span class="badge lead-converted-badge"><i class="bi bi-person-check me-1"></i>Converted</span>' : ''}</div></td>
                            <td class="d-none d-md-table-cell">${sourceText}</td>
                            <td class="d-none d-md-table-cell">${createdBy}</td>
                            <td class="d-none d-md-table-cell">${formatDate(lead.created_at)}</td>
                            <td class="d-none d-md-table-cell">${statusHtml}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell"><div class="d-inline-flex align-items-center gap-2"><a href="/leads/${lead.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a></div></td>
                            <td class="text-center d-md-none"><button type="button" class="btn-user-expand" data-lead-id="${lead.id}"><i class="fa-solid fa-plus"></i></button></td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${lead.id}" style="display: none;">
                            <td colspan="8" class="p-0 border-0"><div class="details-content"><div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div><div class="expand-value">${statusHtml}</div></div>
                                <div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-bullhorn"></i> Source :</div><div class="expand-value">${sourceText}</div></div>
                                <div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-user"></i> Created By :</div><div class="expand-value">${createdBy}</div></div>
                                <div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created At :</div><div class="expand-value">${formatDate(lead.created_at)}</div></div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top"><div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div><div class="d-flex flex-wrap gap-2"><a href="/leads/${lead.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a></div></div>
                            </div></div></td>
                        </tr>`;
                }).join('');

                tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const id = this.dataset.leadId;
                        const detailsRow = document.getElementById(`details-${id}`);
                        const icon = this.querySelector('i');
                        if (detailsRow.style.display === 'none') { detailsRow.style.display = 'table-row'; icon.classList.replace('fa-plus', 'fa-minus'); this.classList.add('active'); }
                        else { detailsRow.style.display = 'none'; icon.classList.replace('fa-minus', 'fa-plus'); this.classList.remove('active'); }
                    });
                });
            }

            function renderPagination(data) {
                if (!data || data.total === 0) { paginationContainer.innerHTML = ''; return; }
                const from = data.from || 0, to = data.to || 0, total = data.total || 0, currentPage = data.current_page || 1, lastPage = data.last_page || 1;
                let html = `<div class="crm-pagination-container"><div class="text-muted small">Showing ${from} to ${to} of ${total} results</div><ul class="pagination crm-pagination mb-0">`;
                html += data.prev_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>` : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
                for (let i = 1; i <= lastPage; i++) { if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) { html += i === currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`; } else if (i === currentPage - 3 || i === currentPage + 3) { html += '<li class="page-item disabled"><span class="page-link">...</span></li>'; } }
                html += data.next_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>` : '<li class="page-item disabled"><span class="page-link">Next</span></li>';
                html += '</ul></div>';
                paginationContainer.innerHTML = html;
                paginationContainer.querySelectorAll('.page-link[data-page]').forEach(function (link) { link.addEventListener('click', function (e) { e.preventDefault(); fetchLeadsReport(this.dataset.page); }); });
            }

            let timer;
            searchInput.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { fetchLeadsReport(1); }, 400); });
            $('select[name="year"], select[name="staff_id"], input[name="from_date"], input[name="to_date"]').on('change', function () { fetchLeadsReport(1); });

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
                fetchLeadsReport(1);
            });

            $('#filter-status').select2({ placeholder: "All Statuses", allowClear: true, width: '100%' });
            $('#filter-lead').select2({ placeholder: "All Leads", allowClear: true, width: '100%' });
            $('#filter-lead-source').select2({ placeholder: "All Sources", allowClear: true, width: '100%' });

            const yearSelect = $('#year-select');
            const currentYear = new Date().getFullYear();
            for (let y = currentYear - 3; y <= currentYear; y++) {
                yearSelect.append(`<option value="${y}">${y}</option>`);
            }
            yearSelect.val(currentYear);

            if (typeof flatpickr !== 'undefined') {
                flatpickr('#from-date-filter,#to-date-filter', {
                    dateFormat: 'd-m',
                    allowInput: true
                });
            }

            $('#year-select, #from-date-filter, #to-date-filter').on('change', function() {
                fetchLeadsReport(1);
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

            $('#filter-lead, #filter-status, #filter-lead-source, #start-date, #end-date, #date-range').on('change', function() {
                fetchLeadsReport(1);
            });

            $('#export-btn').on('click', function(e) {
                e.preventDefault();
                const params = new URLSearchParams({
                    year: $('#year-select').val() || 'all',
                    from_date: $('#from-date-filter').val() || '',
                    to_date: $('#to-date-filter').val() || '',
                    staff_id: $('#staff-filter').val() || '',
                    lead_id: $('#filter-lead').val() || '',
                    status: $('#filter-status').val() || '',
                    lead_source_id: $('#filter-lead-source').val() || '',
                    start_date: $('#start-date').val() || '',
                    end_date: $('#end-date').val() || '',
                    date_range: $('#date-range').val() || ''
                });
                window.location.href = "{{ route('reports.leads.export') }}?" + params.toString();
            });


            const ctx = document.getElementById('leadChart').getContext('2d');
            window.leadChart = new Chart(ctx, { type: 'bar', data: { labels: @json($chartLabels), datasets: [{ label: 'Lead', data: @json($chartData), backgroundColor: 'rgba(255, 99, 132, 0.25)', borderColor: 'rgba(255, 99, 132, 0.8)', borderWidth: 1, borderRadius: 2, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top', align: 'center', labels: { usePointStyle: true, pointStyle: 'rect', padding: 20, font: { size: 12 } } }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#fff', titleColor: '#333', bodyColor: '#666', borderColor: '#ddd', borderWidth: 1, padding: 10, displayColors: true, callbacks: { label: function (context) { return ' Leads: ' + context.parsed.y; } } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Leads', color: '#666', font: { size: 12 } }, grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false }, ticks: { stepSize: 1, font: { size: 11 } } }, x: { grid: { color: 'rgba(0, 0, 0, 0.05)' }, ticks: { font: { size: 11 } } } } } });
            fetchLeadsReport(1);
        });
    </script>
@endpush
