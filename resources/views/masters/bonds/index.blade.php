@extends('layouts.masters')

@section('page_title', 'Investment Bond Master')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<style>
    .crm-search-icon {
        background: #f8fafc;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .crm-search-input {
        background: #f8fafc;
        font-size: 0.9rem;
    }

    .crm-search-input:focus {
        background: #f8fafc;
        box-shadow: none;
        border-color: #e2e8f0 !important;
    }

    [data-theme="dark"] .crm-search-icon,
    [data-theme="dark"] .crm-search-input {
        background: #1e293b;
        color: #94a3b8;
        border-color: rgba(255,255,255,.06) !important;
    }

    #bondsTable thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    #bondsTable tbody tr {
        transition: background-color 0.2s ease;
    }

    #bondsTable tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.04);
    }

    #bondsTable tbody td {
        vertical-align: middle;
        padding: 0.85rem 0.75rem;
        color: #334155;
        font-size: 0.92rem;
    }

    [data-theme="dark"] #bondsTable thead th {
        background-color: #1e293b;
        color: #94a3b8;
        border-bottom-color: rgba(255,255,255,.08);
    }

    [data-theme="dark"] #bondsTable tbody td {
        color: #cbd5e1;
        border-bottom-color: rgba(255,255,255,.06);
    }

    .doc-status-badge {
        display: inline-block;
        min-width: 90px;
        padding: 0.3rem 0.85rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        text-align: center;
        cursor: pointer;
        border: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        color: #fff;
    }

    .doc-status-badge:hover { transform: translateY(-1px); }

    .doc-status-badge.status-active {
        background: linear-gradient(135deg, #24c78b 0%, #19b974 100%);
        box-shadow: 0 4px 10px rgba(25, 185, 116, 0.25);
    }

    .doc-status-badge.status-inactive {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        box-shadow: 0 4px 10px rgba(100, 116, 139, 0.2);
    }

    .doc-action-group {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.4rem;
    }

    .doc-icon-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 0.82rem;
        border: 1.5px solid;
        background: transparent;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .doc-icon-btn.btn-edit {
        color: #475569;
        border-color: #cbd5e1;
    }

    .doc-icon-btn.btn-edit:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .doc-icon-btn.btn-del {
        color: #ef4444;
        border-color: #fecaca;
    }

    .doc-icon-btn.btn-del:hover {
        background: #fef2f2;
        border-color: #ef4444;
    }

    [data-theme="dark"] .doc-icon-btn.btn-edit {
        color: #94a3b8;
        border-color: rgba(255,255,255,.12);
    }

    [data-theme="dark"] .doc-icon-btn.btn-edit:hover {
        background: rgba(255,255,255,.06);
        color: #e2e8f0;
    }

    [data-theme="dark"] .doc-icon-btn.btn-del {
        color: #f87171;
        border-color: rgba(248,113,113,.25);
    }

    [data-theme="dark"] .doc-icon-btn.btn-del:hover {
        background: rgba(248,113,113,.1);
    }

    .btn-dark-blue {
        background-color: #0f172a !important;
        border-color: #0f172a !important;
        color: #fff !important;
    }

    .btn-dark-blue:hover {
        background-color: #1e293b !important;
        border-color: #1e293b !important;
        color: #fff !important;
    }

    .master-header-btn {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
    }

    .doc-modal .modal-content {
        border-radius: 16px;
        overflow: hidden;
        border: 0;
        box-shadow: 0 20px 60px rgba(15,23,42,.18);
    }

    .doc-modal .modal-header {
        background: #0f172a;
        padding: 1rem 1.5rem;
        border-bottom: 0;
    }

    .doc-modal .modal-title { color: #fff; font-weight: 700; }

    .doc-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    [data-theme="dark"] .doc-modal .modal-content { background: #1e293b; }
    [data-theme="dark"] .doc-modal .modal-footer { background: #162033; border-top-color: rgba(255,255,255,.08); }
    [data-theme="dark"] .doc-modal .form-label { color: #94a3b8; }
    [data-theme="dark"] .doc-modal .form-control,
    [data-theme="dark"] .doc-modal .form-select {
        background: #0f172a;
        border-color: rgba(255,255,255,.1);
        color: #e2e8f0;
    }

    .doc-empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #94a3b8;
    }

    .doc-empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4; }
</style>
@endpush

@section('masters_content')

{{-- ── Page Header ──────────────────────────────────────────── --}}
<div class="px-4 pt-4 pb-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 gap-3">
        <div class="pe-3">
            <h4 class="fw-bold mb-0">Investment Bond Master</h4>
            <p class="text-muted small mb-0">Manage investment bond names and their status.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
            <a href="{{ route('masters.masters.index') }}" class="btn btn-dark-blue btn-sm px-3 master-header-btn">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-dark-blue btn-sm px-3 master-header-btn" onclick="openAddModal()">
                <i class="fa fa-plus me-1"></i> Add Bond
            </button>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
        <h6 class="fw-bold mb-0">All Investment Bond Master</h6>
        <div class="input-group input-group-sm" style="max-width: 280px; width: 100%;">
            <span class="input-group-text crm-search-icon border-0">
                <i class="fa-solid fa-search"></i>
            </span>
            <input type="text" id="searchTable" class="form-control crm-search-input border-0"
                   placeholder="Search bonds...">
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────── --}}
<div class="card-body p-0">
    <div class="table-responsive">
        <table id="bondsTable" class="table table-hover align-middle mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th class="ps-4" style="width:70px;">SR.NO</th>
                    <th>Bond Name</th>
                    <th style="width:130px;">Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th class="text-end pe-4" style="width:110px;">Action</th>
                </tr>
            </thead>
            <tbody id="bondsTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <div class="doc-empty-state">
                            <i class="fa fa-spinner fa-spin d-block"></i>
                            Loading…
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="bondsPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
</div>

{{-- ── Add / Edit Modal ─────────────────────────────────────── --}}
<div class="modal fade doc-modal" id="bondModal" tabindex="-1"
     aria-labelledby="bondModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bondModalLabel">Add New Bond</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="bondForm">
                    @csrf
                    <input type="hidden" id="bondId" name="id">

                    <div class="mb-3">
                        <label for="bondName" class="form-label fw-semibold small">
                            Bond Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="bondName"
                               name="bond_name" placeholder="Enter bond name" required>
                        <div class="invalid-feedback" id="bondNameError">Please enter a bond name.</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold small">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" id="statusError">Please select a status.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue btn-sm px-4" onclick="saveBond()">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Delete Confirmation Modal ────────────────────────────── --}}
<div class="modal fade doc-modal" id="deleteModal" tabindex="-1"
     aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#dc2626;">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="fa fa-exclamation-triangle text-warning fa-3x mb-3 d-block"></i>
                <p class="mb-1 fw-semibold">Are you sure?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm px-4" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const baseUrl    = '{{ url('/') }}';
    const csrfToken  = '{{ csrf_token() }}';

    let allBonds      = [];
    let filteredBonds = [];
    let currentPage   = 1;
    const perPage     = 10;
    let deleteId      = null;

    /* ── Init ──────────────────────────────────────────────── */
    $(document).ready(function () {
        loadBonds();

        $('#searchTable').on('input', function () {
            const q = $(this).val().toLowerCase().trim();
            filteredBonds = q
                ? allBonds.filter(b => (b.bond_name || '').toLowerCase().includes(q))
                : [...allBonds];
            currentPage = 1;
            renderTable();
        });
    });

    /* ── Load Data ─────────────────────────────────────────── */
    function loadBonds() {
        $('#bondsTableBody').html(`
            <tr><td colspan="6" class="text-center py-5 text-muted">
                <div class="doc-empty-state"><i class="fa fa-spinner fa-spin d-block"></i>Loading…</div>
            </td></tr>`);

        $.ajax({
            url: baseUrl + '/api/bonds/list',
            type: 'GET',
            success: function (json) {
                allBonds = (json.status && json.data) ? json.data : [];
                filteredBonds = [...allBonds];
                currentPage = 1;
                renderTable();
            },
            error: function () {
                $('#bondsTableBody').html(`
                    <tr><td colspan="6" class="text-center py-5 text-muted">
                        <div class="doc-empty-state"><i class="fa fa-exclamation-circle d-block"></i>Failed to load data.</div>
                    </td></tr>`);
            }
        });
    }

    /* ── Render Table ──────────────────────────────────────── */
    function renderTable() {
        const total      = filteredBonds.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const start    = (currentPage - 1) * perPage;
        const pageData = filteredBonds.slice(start, start + perPage);

        if (pageData.length === 0) {
            $('#bondsTableBody').html(`
                <tr><td colspan="6" class="text-center py-5 text-muted">
                    <div class="doc-empty-state"><i class="fa fa-link d-block"></i>No bonds found.</div>
                </td></tr>`);
            renderPagination(0, 0, 0, 1);
            return;
        }

        let rows = '';
        pageData.forEach(function (row, idx) {
            const srNo        = start + idx + 1;
            const isActive    = parseInt(row.status) === 1;
            const statusClass = isActive ? 'status-active' : 'status-inactive';
            const statusLabel = isActive ? 'Active' : 'Inactive';

            rows += `
            <tr>
                <td class="ps-4 text-muted fw-semibold">${srNo}</td>
                <td class="fw-semibold">${row.bond_name || '--'}</td>
                <td>
                    <button class="doc-status-badge ${statusClass}"
                            onclick="updateStatus(${row.id}, ${isActive ? 0 : 1})">
                        ${statusLabel}
                    </button>
                </td>
                <td class="text-muted small">${row.created_at || '--'}</td>
                <td class="text-muted small">${row.updated_at || '--'}</td>
                <td class="pe-4">
                    <div class="doc-action-group">
                        <button class="doc-icon-btn btn-edit" onclick="openEditModal(${row.id})" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="doc-icon-btn btn-del" onclick="confirmDelete(${row.id})" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });

        $('#bondsTableBody').html(rows);
        renderPagination(start + 1, Math.min(start + perPage, total), total, totalPages);
    }

    /* ── Pagination ────────────────────────────────────────── */
    function renderPagination(from, to, total, totalPages) {
        if (total === 0) { $('#bondsPaginationContainer').html(''); return; }

        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);

        let pages = '';
        for (let p = startPage; p <= endPage; p++) {
            pages += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${p}); return false;">${p}</a>
            </li>`;
        }

        $('#bondsPaginationContainer').html(`
            <div class="crm-pagination-container">
                <div class="text-muted small">
                    Showing <strong>${from}</strong> to <strong>${to}</strong> of <strong>${total}</strong> entries
                </div>
                <nav>
                    <ul class="pagination crm-pagination mb-0">
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;">Previous</a>
                        </li>
                        ${pages}
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>`);
    }

    function goToPage(page) {
        const totalPages = Math.ceil(filteredBonds.length / perPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderTable();
    }

    /* ── Modal Helpers ─────────────────────────────────────── */
    function openAddModal() {
        resetForm();
        $('#bondModalLabel').text('Add New Bond');
        $('#bondModal').modal('show');
    }

    function openEditModal(id) {
        resetForm();
        $.ajax({
            url: baseUrl + '/api/bonds/edit/' + id,
            type: 'GET',
            success: function (response) {
                if (response.status && response.data) {
                    const d = response.data;
                    $('#bondId').val(d.id);
                    $('#bondName').val(d.bond_name);
                    const isActive = d.status == 1 || d.status === 'active' || d.status === '1';
                    $('#status').val(isActive ? '1' : '0');
                    $('#bondModalLabel').text('Edit Bond');
                    $('#bondModal').modal('show');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error',
                        text: response.message || 'Failed to load bond data', confirmButtonColor: '#0f172a' });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load bond data',
                    confirmButtonColor: '#0f172a' });
            }
        });
    }

    function resetForm() {
        $('#bondForm')[0].reset();
        $('#bondId').val('');
        $('#status').val('1');
        $('.invalid-feedback').hide();
        $('.form-control, .form-select').removeClass('is-invalid');
    }

    /* ── Save ──────────────────────────────────────────────── */
    function saveBond() {
        $('.invalid-feedback').hide();
        $('.form-control, .form-select').removeClass('is-invalid');

        const id       = $('#bondId').val();
        const bondName = $('#bondName').val().trim();
        const status   = $('#status').val();

        if (!bondName) {
            $('#bondName').addClass('is-invalid');
            $('#bondNameError').show();
            return;
        }

        $.ajax({
            url: baseUrl + '/api/bonds/check-name',
            type: 'POST',
            data: { bond_name: bondName, exclude_id: id, _token: csrfToken },
            success: function (checkResponse) {
                if (checkResponse.exists) {
                    Swal.fire({ icon: 'warning', title: 'Duplicate Entry',
                        text: 'Bond name already exists.', confirmButtonColor: '#0f172a' });
                    return;
                }
                $.ajax({
                    url: id ? baseUrl + '/api/bonds/update' : baseUrl + '/api/bonds/store',
                    type: 'POST',
                    data: { id, bond_name: bondName, status, _token: csrfToken },
                    success: function (response) {
                        if (response.status) {
                            $('#bondModal').modal('hide');
                            Swal.fire({ icon: 'success', title: 'Success', text: response.message,
                                timer: 2000, showConfirmButton: false });
                            loadBonds();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error',
                                text: response.message || 'Failed to save bond', confirmButtonColor: '#0f172a' });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error',
                            text: 'An error occurred while saving.', confirmButtonColor: '#0f172a' });
                    }
                });
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to check bond name',
                    confirmButtonColor: '#0f172a' });
            }
        });
    }

    /* ── Toggle Status ─────────────────────────────────────── */
    function updateStatus(id, newStatus) {
        $.ajax({
            url: baseUrl + '/api/bonds/update-status',
            type: 'POST',
            data: { id, status: newStatus, _token: csrfToken },
            success: function (response) {
                if (response.status) {
                    loadBonds();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error',
                        text: response.message || 'Failed to update status', confirmButtonColor: '#0f172a' });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to update status',
                    confirmButtonColor: '#0f172a' });
            }
        });
    }

    /* ── Delete ────────────────────────────────────────────── */
    function confirmDelete(id) {
        deleteId = id;
        $('#deleteModal').modal('show');
    }

    $('#confirmDeleteBtn').on('click', function () {
        if (!deleteId) return;
        $.ajax({
            url: baseUrl + '/api/bonds/delete/' + deleteId,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                $('#deleteModal').modal('hide');
                if (response.status) {
                    Swal.fire({ icon: 'success', title: 'Deleted', text: response.message,
                        timer: 2000, showConfirmButton: false });
                    loadBonds();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error',
                        text: response.message || 'Failed to delete bond', confirmButtonColor: '#0f172a' });
                }
            },
            error: function () {
                $('#deleteModal').modal('hide');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete bond',
                    confirmButtonColor: '#0f172a' });
            }
        });
    });
</script>
@endpush
