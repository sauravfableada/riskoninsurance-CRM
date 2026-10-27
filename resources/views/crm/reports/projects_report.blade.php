@extends('layouts.app')

@section('page_title', 'Projects Report')

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
        .report-filter-label { color: #1f3b63; font-weight: 500; margin-bottom: 0; }
        .report-filter-control {
            width: 160px;
            max-width: 100%;
            min-height: 40px;
            border-radius: 8px;
            border: 1px solid #d9e0ea !important;
            box-shadow: none;
        }
        .report-reset-btn { min-width: 120px; }
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
                <div class="col-12 col-lg-2"><h4 class="fw-bold mb-0">Project</h4></div>
                <div class="col-12 col-lg-10 d-flex justify-content-end">
                    <form action="" method="GET" class="report-filter-panel">
                        <div class="report-filter-row"><label class="report-filter-label">Year:</label><select name="year" class="form-select report-filter-control">@foreach($years as $y)<option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                        <div class="report-filter-row"><label class="report-filter-label">From Date:</label><input type="date" name="from_date" value="{{ $from_date }}" class="form-control report-filter-control" placeholder="DD-MM"></div>
                        <div class="report-filter-row"><label class="report-filter-label">To Date:</label><input type="date" name="to_date" value="{{ $to_date }}" class="form-control report-filter-control" placeholder="DD-MM"></div>
                        <div class="d-flex justify-content-start"><a href="{{ route('reports.projects') }}" class="btn btn-dark-blue report-reset-btn">Reset</a></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body px-4 pb-4"><div class="chart-container" style="position: relative; height:350px; width:100%"><canvas id="projectChart"></canvas></div></div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div><h4 class="fw-bold mb-0">Projects Report</h4><p class="text-muted small mb-0">View all projects.</p></div>
                <div class="d-flex flex-wrap gap-2"><a href="{{ route('reports.projects.export') }}" class="btn btn-outline-dark-blue"><i class="fa-solid fa-download me-1"></i>Export</a></div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">Active Projects</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;"><span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span><input type="text" class="form-control crm-search-input border-0" placeholder="Search projects..." id="projectsReportSearch"></div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="projectsReportTable">
                    <thead><tr><th class="ps-4" style="width: 80px;">Sr.No</th><th>Project Information</th><th class="d-none d-md-table-cell">Customer</th><th class="d-none d-md-table-cell">Status</th><th class="text-start d-none d-md-table-cell">Timeline</th><th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th><th class="text-center d-md-none" style="width: 80px;">Action</th></tr></thead>
                    <tbody id="projectsReportBody"></tbody>
                </table>
            </div>
            <div id="projectsReportPagination" class="card-footer border-top-0 py-4 px-4"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function () {
    const tableBody = document.getElementById('projectsReportBody');
    const paginationContainer = document.getElementById('projectsReportPagination');
    const searchInput = document.getElementById('projectsReportSearch');

    function fetchProjectsReport(page = 1) {
        const url = new URL("{{ route('reports.projects') }}", window.location.origin);
        url.searchParams.set('page', page);
        url.searchParams.set('year', $('select[name="year"]').val() || '');
        url.searchParams.set('from_date', $('input[name="from_date"]').val() || '');
        url.searchParams.set('to_date', $('input[name="to_date"]').val() || '');
        if (searchInput.value.trim()) url.searchParams.set('search', searchInput.value.trim());
        $.ajax({ url: url.toString(), type: 'GET', dataType: 'json', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, beforeSend: function () { tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>'; }, success: function (res) { if (res.success && res.data) { renderRows(res.data.data || [], res.data); renderPagination(res.data); } }, error: function () { tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading projects</td></tr>'; paginationContainer.innerHTML = ''; } });
    }

    function renderRows(items, meta) {
        if (!items || !items.length) { tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div><p class="text-muted">No projects found.</p></td></tr>'; return; }
        tableBody.innerHTML = items.map(function (project, index) {
            const startDate = project.start_date ? new Date(project.start_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : null;
            const endDate = project.end_date ? new Date(project.end_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : null;
            const statusBadge = { pending: 'bg-warning', ongoing: 'bg-info', completed: 'bg-success', canceled: 'bg-danger' }[project.status] || 'bg-secondary';
            const srNo = meta && meta.from ? meta.from + index : index + 1;
            const customerName = project.customer ? project.customer.name : '--';
            const createdByName = project.creator?.name || project.created_by_user?.name || '--';
            return `
                <tr>
                    <td class="ps-4"><span class="text-muted small fw-medium">${srNo}</span></td>
                    <td><div class="py-1"><div class="fw-bold small"><span>${project.name ?? '--'}</span></div><div class="text-muted small">Project ID: #${String(project.id).padStart(5, '0')}</div></div></td>
                    <td class="d-none d-md-table-cell"><div class="d-flex align-items-center"><i class="bi bi-person-circle me-2 text-muted"></i><span class="small">${customerName}</span></div></td>
                    <td class="d-none d-md-table-cell"><span class="badge ${statusBadge} rounded-pill px-3">${project.status ? project.status.charAt(0).toUpperCase() + project.status.slice(1) : '--'}</span></td>
                    <td class="text-start d-none d-md-table-cell"><div class="small fw-semibold text-nowrap">${startDate ? `<i class="bi bi-calendar-check me-1 text-muted"></i>${startDate}${endDate ? `<br><i class="bi bi-calendar-x me-1 text-muted"></i>${endDate}` : ''}` : '<span class="text-muted">Dates not set</span>'}</div></td>
                    <td class="text-end pe-4 d-none d-md-table-cell"><div class="d-inline-flex align-items-center gap-2"><a href="/projects/${project.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a></div></td>
                    <td class="text-center d-md-none"><button type="button" class="btn-user-expand" data-project-id="${project.id}"><i class="fa-solid fa-plus"></i></button></td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${project.id}" style="display: none;"><td colspan="7" class="p-0 border-0"><div class="details-content p-3 bg-light m-2 rounded"><div class="row g-3"><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label text-muted"><i class="fa-solid fa-person"></i> Created By :</div><div class="expand-value fw-semibold">${createdByName}</div></div><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label text-muted"><i class="fa-regular fa-building"></i> Customer :</div><div class="expand-value fw-semibold">${customerName}</div></div><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label text-muted"><i class="fa-solid fa-signal"></i> Status :</div><div class="expand-value fw-semibold"><span class="badge ${statusBadge}">${project.status ? project.status.charAt(0).toUpperCase() + project.status.slice(1) : '--'}</span></div></div><div class="col-12 d-flex justify-content-between align-items-center"><div class="expand-label text-muted"><i class="fa-solid fa-calendar-days"></i> Timeline :</div><div class="expand-value fw-semibold text-end">${startDate ? `${startDate} ${endDate ? `- ${endDate}` : ''}` : '<span class="text-muted">Dates not set</span>'}</div></div></div><div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top"><div class="expand-label text-muted"><i class="fa-solid fa-gear"></i> Actions :</div><div class="d-flex flex-wrap gap-2"><a href="/projects/${project.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a></div></div></div></td></tr>`;
        }).join('');
        tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) { button.addEventListener('click', function () { const id = this.dataset.projectId; const detailsRow = document.getElementById(`details-${id}`); const icon = this.querySelector('i'); if (detailsRow.style.display === 'none') { detailsRow.style.display = 'table-row'; icon.classList.replace('fa-plus', 'fa-minus'); this.classList.add('active'); } else { detailsRow.style.display = 'none'; icon.classList.replace('fa-minus', 'fa-plus'); this.classList.remove('active'); } }); });
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
        paginationContainer.querySelectorAll('.page-link[data-page]').forEach(function (link) { link.addEventListener('click', function (e) { e.preventDefault(); fetchProjectsReport(this.dataset.page); }); });
    }

    let timer; searchInput.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { fetchProjectsReport(1); }, 400); });
    $('select[name="year"], input[name="from_date"], input[name="to_date"]').on('change', function () { $(this).closest('form').submit(); });
    const ctx = document.getElementById('projectChart').getContext('2d');
    new Chart(ctx, { type: 'bar', data: { labels: @json($chartLabels), datasets: [{ label: 'Projects', data: @json($chartData), backgroundColor: 'rgba(54, 162, 235, 0.25)', borderColor: 'rgba(54, 162, 235, 0.8)', borderWidth: 1, borderRadius: 2, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top', align: 'center', labels: { usePointStyle: true, pointStyle: 'rect', padding: 20, font: { size: 12 } } }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#fff', titleColor: '#333', bodyColor: '#666', borderColor: '#ddd', borderWidth: 1, padding: 10, displayColors: true, callbacks: { label: function (context) { return ' Projects: ' + context.parsed.y; } } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Projects', color: '#666', font: { size: 12 } }, grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false }, ticks: { stepSize: 1, font: { size: 11 } } }, x: { grid: { color: 'rgba(0, 0, 0, 0.05)' }, ticks: { font: { size: 11 } } } } } });
    fetchProjectsReport(1);
});
</script>
@endpush

