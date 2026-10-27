@extends('layouts.app')

@section('page_title', 'Client Report')

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
            .report-filters { flex-direction: column; align-items: stretch; }
            .report-filters .filter-item { width: 100%; }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="container-fluid p-0">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-lg-2">
                        <h4 class="fw-bold mb-0">Client</h4>
                    </div>
                    <div class="col-12 col-lg-10 d-flex justify-content-end">
                        <div class="report-filters justify-content-lg-end">
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
                    <canvas id="customerChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 p-4 pb-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Client Report</h4>
                        <p class="text-muted small mb-0">View clients and their policy details.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a id="export-btn" href="#" class="btn btn-dark-blue btn-sm report-export-btn">
                            <i class="fa-solid fa-download me-1"></i>Export
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Client Policies</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="customerReportSearch" class="form-control crm-search-input border-0"
                            placeholder="Search clients or policies...">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="p-4 pb-0">
                    <div class="p-3 rounded mb-4" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3 col-sm-6">
                                <label for="filter-sub-agent" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Sub Agent</label>
                                <select id="filter-sub-agent" class="form-select form-select-sm text-muted">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label for="filter-policy-holder" class="form-label mb-1" style="font-size: 13px; color: #64748b;">Policy Holder</label>
                                <select id="filter-policy-holder" class="form-select form-select-sm text-muted">
                                    <option value=""></option>
                                    @foreach($policyHolders as $holder)
                                        <option value="{{ $holder->id }}">{{ $holder->name }}</option>
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
                    <table id="customersReport" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Client Name</th>
                                <th class="d-none d-md-table-cell">Phone</th>
                                <th>Policy Number</th>
                                <th>Policy Type</th>
                                <th>Sum Assured</th>
                                <th>Premium</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="customersReportBody"></tbody>
                    </table>
                </div>
                <div id="customerReportPagination" class="card-footer border-top-0 py-4 px-4"></div>
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
            compactReportLayout({ title: 'Client Report', chart: '#customerChart', table: '#customersReport', search: '#customerReportSearch', export: '#export-btn', filterAnchor: '#filter-sub-agent' });
            const tableBody = document.getElementById('customersReportBody');
            const paginationContainer = document.getElementById('customerReportPagination');
            const searchInput = document.getElementById('customerReportSearch');

            function escapeHtml(value) {
                if (value === null || value === undefined) {
                    return "";
                }

                return String(value)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function renderRows(items, meta) {
                if (!items || items.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-people display-1 opacity-25"></i></div>
                                <p class="text-muted">No client policies found.</p>
                            </td>
                        </tr>`;
                    return;
                }

                tableBody.innerHTML = items.map((customer, index) => {
                    const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                    const rowKey = `${customer.id}-${rowNumber}`;
                    const date = customer.created_at ? new Date(customer.created_at).toLocaleDateString("en-GB", {
                        day: "2-digit",
                        month: "short",
                        year: "numeric",
                    }) : "--";
                    return `
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold small">${escapeHtml(customer.name || "--")}</div>
                            </td>
                            <td class="d-none d-md-table-cell">${escapeHtml(customer.phone || "--")}</td>
                            <td>${escapeHtml(customer.policy_number || "--")}</td>
                            <td>${escapeHtml(customer.policy_type || "--")}</td>
                            <td>${escapeHtml(customer.sum_assured || "--")}</td>
                            <td>${escapeHtml(customer.premium || "--")}</td>
                            <td>${escapeHtml(customer.start_date || "--")}</td>
                            <td>${escapeHtml(customer.end_date || "--")}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell">
                                <a href="/masters/customers/${customer.id}" class="btn crm-action-btn btn-sm" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-row-key="${rowKey}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${rowKey}" style="display: none;">
                            <td colspan="10" class="p-0 border-0">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone :</div>
                                            <div class="expand-value">${escapeHtml(customer.phone || "--")}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                            <div class="expand-value">${date}</div>
                                        </div>
                                        <div class="col-12"><strong>Policy:</strong> ${escapeHtml(customer.policy_number || "--")} (${escapeHtml(customer.policy_type || "--")})</div>
                                        <div class="col-12"><strong>Sum Assured:</strong> ${escapeHtml(customer.sum_assured || "--")} &nbsp; <strong>Premium:</strong> ${escapeHtml(customer.premium || "--")}</div>
                                        <div class="col-12"><strong>Period:</strong> ${escapeHtml(customer.start_date || "--")} - ${escapeHtml(customer.end_date || "--")}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="/masters/customers/${customer.id}" class="btn crm-action-btn btn-sm" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                tableBody.querySelectorAll(".btn-user-expand").forEach(button => {
                    button.addEventListener("click", function () {
                        const id = this.dataset.rowKey;
                        const detailsRow = document.getElementById(`details-${id}`);
                        const icon = this.querySelector("i");

                        if (detailsRow.style.display === "none") {
                            detailsRow.style.display = "table-row";
                            icon.classList.replace("fa-plus", "fa-minus");
                            this.classList.add("active");
                        } else {
                            detailsRow.style.display = "none";
                            icon.classList.replace("fa-minus", "fa-plus");
                            this.classList.remove("active");
                        }
                    });
                });
            }

            function renderPagination(data) {
                if (!paginationContainer || !data || data.total === 0) {
                    paginationContainer.innerHTML = "";
                    return;
                }

                const from = data.from || 0;
                const to = data.to || 0;
                const total = data.total || 0;
                const currentPage = data.current_page || 1;
                const lastPage = data.last_page || 1;

                let html = `
                    <div class="crm-pagination-container">
                        <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                        <ul class="pagination crm-pagination mb-0">`;

                if (data.prev_page_url) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
                }

                for (let i = 1; i <= lastPage; i++) {
                    if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                        html += i === currentPage
                            ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                            : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    } else if (i === currentPage - 3 || i === currentPage + 3) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                if (data.next_page_url) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
                }

                html += `</ul></div>`;
                paginationContainer.innerHTML = html;

                paginationContainer.querySelectorAll(".page-link[data-page]").forEach(link => {
                    link.addEventListener("click", function (e) {
                        e.preventDefault();
                        fetchCustomersReport(this.dataset.page);
                    });
                });
            }

            function fetchCustomersReport(page = 1) {
                const url = new URL("{{ route('reports.customers') }}", window.location.origin);
                url.searchParams.set("page", page);
                url.searchParams.set("year", $('#year-select').val() || "all");
                url.searchParams.set("from_date", $('#from-date-filter').val() || "");
                url.searchParams.set("to_date", $('#to-date-filter').val() || "");
                url.searchParams.set("staff_id", $('#filter-sub-agent').val() || "");
                url.searchParams.set("holder_id", $('#filter-policy-holder').val() || "");
                url.searchParams.set("policy_number", $('#filter-policy-number').val() || "");
                url.searchParams.set("policy_type", $('#filter-policy-type').val() || "");
                url.searchParams.set("company_id", $('#filter-company').val() || "");
                url.searchParams.set("start_date", $('#start-date').val() || "");
                url.searchParams.set("end_date", $('#end-date').val() || "");
                url.searchParams.set("date_range", $('#date-range').val() || "");

                if (searchInput.value.trim()) {
                    url.searchParams.set("search", searchInput.value.trim());
                }

                $.ajax({
                    url: url.toString(),
                    type: "GET",
                    dataType: "json",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    beforeSend: function () {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="spinner-border text-primary"></div>
                                </td>
                            </tr>`;
                    },
                    success: function (response) {
                        if (response.success && response.data) {
                            renderRows(response.data.data || [], response.data);
                            renderPagination(response.data);
                            if (response.chartData && window.customerChart) {
                                window.customerChart.data.datasets[0].data = response.chartData;
                                window.customerChart.update();
                            }
                        }
                    },
                    error: function () {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center py-5">Error loading client policies</td>
                            </tr>`;
                        paginationContainer.innerHTML = "";
                    }
                });
            }

            let searchTimer;
            searchInput.addEventListener("input", function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    fetchCustomersReport(1);
                }, 400);
            });

            $('#filter-sub-agent').select2({ placeholder: "All Sub Agents", allowClear: true, width: '100%' });
            $.get("{{ route('reports.staff_list') }}", function (subAgents) {
                const $subAgentFilter = $('#filter-sub-agent');
                (subAgents || []).forEach(function (subAgent) {
                    $subAgentFilter.append(new Option(subAgent.name, subAgent.id, false, false));
                });
            });
            $('#filter-policy-number').select2({ placeholder: "All Policy Numbers", allowClear: true, width: '100%' });
            $('#filter-policy-holder').select2({ placeholder: "All Policy Holders", allowClear: true, width: '100%' });
            $('#filter-policy-type').select2({ placeholder: "All Policy Types", allowClear: true, width: '100%' });
            $('#filter-company').select2({ placeholder: "All Companies", allowClear: true, width: '100%' });

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
                fetchCustomersReport(1);
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

            $('#filter-sub-agent, #filter-policy-holder, #filter-policy-number, #filter-policy-type, #filter-company, #start-date, #end-date, #date-range').on('change', function() {
                fetchCustomersReport(1);
            });

            $('#export-btn').on('click', function(e) {
                e.preventDefault();
                const params = new URLSearchParams({
                    year: $('#year-select').val() || 'all',
                    from_date: $('#from-date-filter').val() || '',
                    to_date: $('#to-date-filter').val() || '',
                    staff_id: $('#filter-sub-agent').val() || '',
                    holder_id: $('#filter-policy-holder').val() || '',
                    policy_number: $('#filter-policy-number').val() || '',
                    policy_type: $('#filter-policy-type').val() || '',
                    company_id: $('#filter-company').val() || '',
                    start_date: $('#start-date').val() || '',
                    end_date: $('#end-date').val() || '',
                    date_range: $('#date-range').val() || ''
                });
                params.set('search', searchInput.value.trim());
                window.location.href = "{{ route('reports.customers.export') }}?" + params.toString();
            });



            // Chart initialization
            const ctx = document.getElementById('customerChart').getContext('2d');
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(59, 183, 187, 0.2)');
            gradient.addColorStop(1, 'rgba(59, 183, 187, 0)');

            window.customerChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Customers Created',
                        data: @json($chartData),
                        borderColor: '#3bb7bb',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3bb7bb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'center',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#fff',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return ' Customers: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Customers',
                                color: '#666',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month',
                                color: '#666',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            fetchCustomersReport(1);
        });
    </script>
@endpush
