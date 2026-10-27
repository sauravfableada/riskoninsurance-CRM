@extends('layouts.app')

@section('page_title', 'Deals Report')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <style>
        .report-filter-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }

        .report-filter-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .report-filter-label {
            color: #1f3b63;
            font-weight: 500;
            margin-bottom: 0;
        }

        .report-filter-control {
            width: 160px;
            max-width: 100%;
            min-height: 40px;
            border-radius: 8px;
            border: 1px solid #d9e0ea !important;
            box-shadow: none;
        }

        .report-reset-btn {
            min-width: 120px;
        }

        @media (max-width: 767.98px) {
            .report-filter-panel {
                max-width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .report-filter-row {
                display: grid;
                grid-template-columns: 90px minmax(0, 1fr);
                align-items: center;
                gap: 12px;
                width: 100%;
            }

            .report-filter-control {
                width: 100%;
            }

            .report-reset-btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="row g-4">
                    <div class="col-12 col-lg-2">
                        <h4 class="fw-bold mb-0">Deals</h4>
                    </div>
                    <div class="col-12 col-lg-10 d-flex justify-content-end">
                        <form action="" method="GET" class="report-filter-panel">
                            <div class="report-filter-row">
                                <label class="report-filter-label">Year:</label>
                                <select name="year" class="form-select report-filter-control">
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="report-filter-row">
                                <label class="report-filter-label">From Date:</label>
                                <input type="date" name="from_date" value="{{ $from_date }}" class="form-control report-filter-control"
                                    placeholder="DD-MM">
                            </div>
                            <div class="report-filter-row">
                                <label class="report-filter-label">To Date:</label>
                                <input type="date" name="to_date" value="{{ $to_date }}" class="form-control report-filter-control"
                                    placeholder="DD-MM">
                            </div>
                            <div class="d-flex justify-content-start">
                                <a href="{{ route('reports.deals') }}" class="btn btn-dark-blue report-reset-btn">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                <div class="chart-container" style="position: relative; height:350px; width:100%">
                    <canvas id="dealChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Deals Report</h4>
                        <p class="text-muted small mb-0">View all deals.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('reports.deals.export') }}" class="btn btn-outline-dark-blue">
                            <i class="fa-solid fa-download me-1"></i>Export
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Active Deals</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search deals..."
                            id="dealsReportSearch">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="dealsReportTable">
                        <thead>
                            <tr>
                                <th class="ps-4 d-none d-md-table-cell">Sr.No</th>
                                <th class="d-none d-md-table-cell">Created By</th>
                                <th>Deal Name</th>
                                <th>Stage</th>
                                <th class="d-none d-md-table-cell">Deal Value</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="dealsReportBody"></tbody>
                    </table>
                </div>

                <div id="dealsReportPagination" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function () {
            const tableBody = document.getElementById('dealsReportBody');
            const paginationContainer = document.getElementById('dealsReportPagination');
            const searchInput = document.getElementById('dealsReportSearch');

            function escapeHtml(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getStatusBadgeMeta(status) {
                const name = (status?.name || '').toLowerCase().trim();
                const color = status?.color || '';

                if (color) {
                    return {
                        className: '',
                        style: `background-color: ${color}; color: #fff;`,
                    };
                }

                switch (name) {
                    case 'new':
                    case 'open':
                        return { className: 'bg-primary text-white', style: '' };
                    case 'qualified':
                        return { className: 'bg-info text-dark', style: '' };
                    case 'proposal':
                        return { className: 'bg-warning text-dark', style: '' };
                    case 'negotiation':
                    case 'in-process':
                    case 'in process':
                        return { className: 'bg-dark text-white', style: '' };
                    case 'won':
                        return { className: 'bg-success text-white', style: '' };
                    case 'lost':
                        return { className: 'bg-danger text-white', style: '' };
                    case 'paused':
                        return { className: 'bg-secondary text-white', style: '' };
                    default:
                        return { className: 'bg-secondary text-white', style: '' };
                }
            }

            function renderRows(items, meta) {
                if (!items || items.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                                <p class="text-muted">No deals found.</p>
                            </td>
                        </tr>`;
                    return;
                }

                tableBody.innerHTML = items.map((deal, index) => {
                    const currencySymbol = deal.currency?.symbol || deal.currency?.code || '';
                    const amount = deal.amount !== null && deal.amount !== undefined
                        ? Number(deal.amount).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                        : '0.00';
                    const creatorName = deal.creator?.name || deal.created_by?.name || deal.createdBy?.name || '--';
                    const stageName = deal.stage?.name || '--';
                    const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                    const statusName = deal.status?.name || '--';
                    const statusMeta = getStatusBadgeMeta(deal.status);
                    const statusHtml = `<span class="badge rounded-pill px-3 ${statusMeta.className}" style="${statusMeta.style}">${escapeHtml(statusName)}</span>`;

                    return `
                        <tr>
                            <td class="ps-4 d-none d-md-table-cell">${rowNumber}</td>
                            <td class="d-none d-md-table-cell">${escapeHtml(creatorName)}</td>
                            <td>${escapeHtml(deal.title || '--')}</td>
                            <td>${escapeHtml(stageName)}</td>
                            <td class="d-none d-md-table-cell">${escapeHtml(currencySymbol)}${amount}</td>
                            <td class="d-none d-md-table-cell">${statusHtml}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <a href="/deals/${deal.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-deal-id="${deal.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${deal.id}" style="display: none;">
                            <td colspan="8" class="p-0 border-0">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-hashtag"></i> Sr.No :</div>
                                            <div class="expand-value">${rowNumber}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-user"></i> Created By :</div>
                                            <div class="expand-value">${escapeHtml(creatorName)}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-sack-dollar"></i> Deal Value :</div>
                                            <div class="expand-value">${escapeHtml(currencySymbol)}${amount}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                            <div class="expand-value">${statusHtml}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                            <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="/deals/${deal.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                tableBody.querySelectorAll('.btn-user-expand').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.dealId;
                        const detailsRow = document.getElementById(`details-${id}`);
                        const icon = this.querySelector('i');

                        if (detailsRow.style.display === 'none') {
                            detailsRow.style.display = 'table-row';
                            icon.classList.replace('fa-plus', 'fa-minus');
                            this.classList.add('active');
                        } else {
                            detailsRow.style.display = 'none';
                            icon.classList.replace('fa-minus', 'fa-plus');
                            this.classList.remove('active');
                        }
                    });
                });
            }

            function renderPagination(data) {
                if (!paginationContainer || !data || data.total === 0) {
                    paginationContainer.innerHTML = '';
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

                paginationContainer.querySelectorAll('.page-link[data-page]').forEach((link) => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        fetchDealsReport(this.dataset.page);
                    });
                });
            }

            function fetchDealsReport(page = 1) {
                const url = new URL("{{ route('reports.deals') }}", window.location.origin);
                url.searchParams.set('page', page);
                url.searchParams.set('year', $('select[name="year"]').val() || '');
                url.searchParams.set('from_date', $('input[name="from_date"]').val() || '');
                url.searchParams.set('to_date', $('input[name="to_date"]').val() || '');

                if (searchInput.value.trim()) {
                    url.searchParams.set('search', searchInput.value.trim());
                }

                $.ajax({
                    url: url.toString(),
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    beforeSend: function () {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-primary"></div>
                                </td>
                            </tr>`;
                    },
                    success: function (response) {
                        if (response.success && response.data) {
                            renderRows(response.data.data || [], response.data);
                            renderPagination(response.data);
                        }
                    },
                    error: function () {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-5">Error loading deals</td>
                            </tr>`;
                        paginationContainer.innerHTML = '';
                    }
                });
            }

            let searchTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    fetchDealsReport(1);
                }, 400);
            });

            $('select[name="year"], input[name="from_date"], input[name="to_date"]').on('change', function () {
                $(this).closest('form').submit();
            });

            const ctx = document.getElementById('dealChart').getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Deals',
                        data: @json($chartData),
                        backgroundColor: 'rgba(54, 162, 235, 0.25)',
                        borderColor: 'rgba(54, 162, 235, 0.8)',
                        borderWidth: 1,
                        borderRadius: 2,
                        borderSkipped: false,
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
                                pointStyle: 'rect',
                                padding: 20,
                                font: { size: 12 }
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
                                label: function (context) {
                                    return ' Deals: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Deals',
                                color: '#666',
                                font: { size: 12 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                font: { size: 11 }
                            }
                        },
                        x: {
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });

            fetchDealsReport(1);
        });
    </script>
@endpush

