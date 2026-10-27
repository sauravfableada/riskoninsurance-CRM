@extends('layouts.masters')

@section('page_title', 'Agency Master')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<style>
    .crm-search-icon { background:#f8fafc; color:#94a3b8; font-size:.85rem; }
    .crm-search-input { background:#f8fafc; font-size:.9rem; }
    .crm-search-input:focus { background:#f8fafc; box-shadow:none; border-color:#e2e8f0 !important; }
    [data-theme="dark"] .crm-search-icon,
    [data-theme="dark"] .crm-search-input { background:#1e293b; color:#94a3b8; border-color:rgba(255,255,255,.06) !important; }

    #agencyTable thead th {
        background-color:#f8fafc; color:#475569; font-weight:700; font-size:.85rem;
        text-transform:uppercase; letter-spacing:.025em; padding:1rem .75rem; border-bottom:2px solid #e2e8f0;
    }
    #agencyTable tbody tr { transition:background-color .2s ease; }
    #agencyTable tbody tr:hover { background-color:rgba(59,130,246,.04); }
    #agencyTable tbody td { vertical-align:middle; padding:.85rem .75rem; color:#334155; font-size:.92rem; }
    [data-theme="dark"] #agencyTable thead th { background-color:#1e293b; color:#94a3b8; border-bottom-color:rgba(255,255,255,.08); }
    [data-theme="dark"] #agencyTable tbody td { color:#cbd5e1; border-bottom-color:rgba(255,255,255,.06); }

    .doc-status-badge {
        display:inline-block; min-width:90px; padding:.3rem .85rem; border-radius:50px;
        font-size:.8rem; font-weight:700; text-align:center; cursor:pointer; border:0;
        transition:transform .15s ease,box-shadow .15s ease; color:#fff;
    }
    .doc-status-badge:hover { transform:translateY(-1px); }
    .doc-status-badge.status-active  { background:linear-gradient(135deg,#24c78b 0%,#19b974 100%); box-shadow:0 4px 10px rgba(25,185,116,.25); }
    .doc-status-badge.status-inactive{ background:linear-gradient(135deg,#94a3b8 0%,#64748b 100%); box-shadow:0 4px 10px rgba(100,116,139,.2); }

    .doc-action-group { display:flex; align-items:center; justify-content:flex-end; gap:.4rem; }
    .doc-icon-btn {
        width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center;
        border-radius:8px; font-size:.82rem; border:1.5px solid; background:transparent; transition:all .2s ease; cursor:pointer;
    }
    .doc-icon-btn.btn-edit  { color:#475569; border-color:#cbd5e1; }
    .doc-icon-btn.btn-edit:hover  { background:#f1f5f9; border-color:#94a3b8; color:#0f172a; }
    .doc-icon-btn.btn-del   { color:#ef4444; border-color:#fecaca; }
    .doc-icon-btn.btn-del:hover   { background:#fef2f2; border-color:#ef4444; }
    [data-theme="dark"] .doc-icon-btn.btn-edit { color:#94a3b8; border-color:rgba(255,255,255,.12); }
    [data-theme="dark"] .doc-icon-btn.btn-edit:hover { background:rgba(255,255,255,.06); color:#e2e8f0; }
    [data-theme="dark"] .doc-icon-btn.btn-del  { color:#f87171; border-color:rgba(248,113,113,.25); }
    [data-theme="dark"] .doc-icon-btn.btn-del:hover  { background:rgba(248,113,113,.1); }

    .btn-dark-blue { background-color:#0f172a !important; border-color:#0f172a !important; color:#fff !important; }
    .btn-dark-blue:hover { background-color:#1e293b !important; border-color:#1e293b !important; color:#fff !important; }
    .master-header-btn {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
    }

    .doc-modal .modal-content { border-radius:16px; overflow:hidden; border:0; box-shadow:0 20px 60px rgba(15,23,42,.18); }
    .doc-modal .modal-header  { background:#0f172a; padding:1rem 1.5rem; border-bottom:0; }
    .doc-modal .modal-title   { color:#fff; font-weight:700; }
    .doc-modal .modal-footer  { background:#f8fafc; border-top:1px solid #e2e8f0; }
    [data-theme="dark"] .doc-modal .modal-content { background:#1e293b; }
    [data-theme="dark"] .doc-modal .modal-footer  { background:#162033; border-top-color:rgba(255,255,255,.08); }
    [data-theme="dark"] .doc-modal .form-label    { color:#94a3b8; }
    [data-theme="dark"] .doc-modal .form-control,
    [data-theme="dark"] .doc-modal .form-select   { background:#0f172a; border-color:rgba(255,255,255,.1); color:#e2e8f0; }

    .doc-empty-state { padding:3rem 1rem; text-align:center; color:#94a3b8; }
    .doc-empty-state i { font-size:2.5rem; margin-bottom:.75rem; opacity:.4; }
</style>
@endpush

@section('masters_content')

{{-- Page Header --}}
<div class="px-4 pt-4 pb-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-4 gap-3">
        <div class="pe-3">
            <h4 class="fw-bold mb-0">Agency Master</h4>
            <p class="text-muted small mb-0">Create and manage agency records.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
            <a href="{{ route('masters.masters.index') }}" class="btn btn-sm btn-dark-blue px-3 master-header-btn">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-dark-blue btn-sm px-3 master-header-btn" id="btn-open-add-modal">
                <i class="fa fa-plus me-1"></i> Add Agency
            </button>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
        <h6 class="fw-bold mb-0">All Agency Master</h6>
        <div class="input-group input-group-sm" style="max-width:280px;width:100%;">
            <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
            <input type="text" id="agencySearch" class="form-control crm-search-input border-0" placeholder="Search agencies...">
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card-body p-0">
    <div class="table-responsive">
        <table id="agencyTable" class="table table-hover align-middle mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th class="ps-4" style="width:70px;">SR.NO</th>
                    <th>Agency Name</th>
                    <th>Agency Level</th>
                    <th style="width:130px;">Status</th>
                    <th class="d-none d-md-table-cell">Created By</th>
                    <th class="d-none d-md-table-cell">Modified By</th>
                    <th class="text-end pe-4" style="width:110px;">Action</th>
                </tr>
            </thead>
            <tbody id="agencyTableBody">
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="doc-empty-state">
                            <i class="fa fa-spinner fa-spin d-block"></i>Loading…
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="agencyPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
</div>

{{-- Add / Edit Modal --}}
<div class="modal fade doc-modal" id="agencyModal" tabindex="-1" aria-labelledby="agencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agencyModalLabel">Add Agency</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="agency-id">

                <div class="mb-3">
                    <label for="agency-level" class="form-label fw-semibold small">
                        Agency Level <span class="text-danger">*</span>
                    </label>
                    <select id="agency-level" class="form-select">
                        <option value="">Select Agency Level</option>
                    </select>
                    <div id="agency-level-error" class="text-danger small mt-1" style="display:none;">Please select an agency level.</div>
                </div>

                <div class="mb-3">
                    <label for="agency-name" class="form-label fw-semibold small">
                        Agency Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="agency-name" class="form-control" placeholder="Enter agency name" autocomplete="off">
                    <div id="agency-name-error" class="text-danger small mt-1" style="display:none;">Agency name is required.</div>
                </div>

                <div class="mb-3">
                    <label for="agency-status" class="form-label fw-semibold small">Status</label>
                    <select id="agency-status" class="form-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue btn-sm px-4" id="btn-save-agency">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade doc-modal" id="agencyDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#dc2626;">
                <h5 class="modal-title text-white fw-bold">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="fa fa-exclamation-triangle text-warning fa-3x mb-3 d-block"></i>
                <p class="mb-1 fw-semibold">Are you sure?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm px-4" id="agency-confirm-delete">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    /* ── State ─────────────────────────────────────────────── */
    let allData     = [];
    let filtered    = [];
    let currentPage = 1;
    const perPage   = 10;
    let deleteId    = null;

    const BASE      = '/api/masters/agencies';
    const AUTH      = () => ({ 'Accept': 'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || '') });

    /* ── Load Agency Levels for dropdown ───────────────────── */
    function loadLevels(selectedId) {
        fetch('/api/masters/agencies/levels', { headers: AUTH() })
            .then(r => r.json())
            .then(res => {
                const $sel = $('#agency-level');
                $sel.find('option:not(:first)').remove();
                if (res.success && res.data) {
                    res.data.forEach(function (lvl) {
                        const opt = $('<option>').val(lvl.id).text(lvl.name);
                        if (selectedId && lvl.id == selectedId) opt.prop('selected', true);
                        $sel.append(opt);
                    });
                }
            });
    }

    /* ── Load Table Data ───────────────────────────────────── */
    function loadData(page) {
        page = page || 1;
        currentPage = page;
        const search  = $('#agencySearch').val() || '';
        const perPageVal = 10;

        $('#agencyTableBody').html(`
            <tr><td colspan="7" class="text-center py-5">
                <div class="doc-empty-state"><i class="fa fa-spinner fa-spin d-block"></i>Loading…</div>
            </td></tr>`);

        fetch(`${BASE}?search=${encodeURIComponent(search)}&per_page=${perPageVal}&page=${page}`, { headers: AUTH() })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    renderTable(res.data);
                    renderPagination(res.data);
                } else {
                    $('#agencyTableBody').html(`
                        <tr><td colspan="7" class="text-center py-5">
                            <div class="doc-empty-state"><i class="fa fa-exclamation-circle d-block"></i>Failed to load data.</div>
                        </td></tr>`);
                }
            })
            .catch(() => {
                $('#agencyTableBody').html(`
                    <tr><td colspan="7" class="text-center py-5">
                        <div class="doc-empty-state"><i class="fa fa-exclamation-circle d-block"></i>Error loading data.</div>
                    </td></tr>`);
            });
    }

    /* ── Render Table ──────────────────────────────────────── */
    function renderTable(paginated) {
        const rows = paginated.data || [];
        if (!rows.length) {
            $('#agencyTableBody').html(`
                <tr><td colspan="7" class="text-center py-5">
                    <div class="doc-empty-state"><i class="fa fa-building d-block"></i>No agencies found.</div>
                </td></tr>`);
            return;
        }

        const start = (paginated.from || 1) - 1;
        let html = '';
        rows.forEach(function (row, idx) {
            const srNo        = start + idx + 1;
            const isActive    = !!row.status;
            const statusClass = isActive ? 'status-active' : 'status-inactive';
            const statusLabel = isActive ? 'Active' : 'Inactive';
            const newStatus   = isActive ? 0 : 1;
            const levelName   = row.agency_level ? escHtml(row.agency_level.name) : '<span class="text-muted">—</span>';
            const createdBy   = escHtml(row.creator?.name || '—');
            const modifiedBy  = escHtml(row.modifier?.name || '—');
            // console.log(createdBy);
            html += `
            <tr>
                <td class="ps-4 text-muted fw-semibold">${srNo}</td>
                <td class="fw-semibold">${escHtml(row.name)}</td>
                <td class="text-muted small">${levelName}</td>
                <td>
                    <button class="doc-status-badge ${statusClass}"
                            onclick="toggleAgencyStatus(${row.id}, ${newStatus})">
                        ${statusLabel}
                    </button>
                </td>
                <td class="text-muted small">${createdBy}</td>
                <td class="text-muted small">${modifiedBy}</td>
                <td class="pe-4">
                    <div class="doc-action-group">
                        <button class="doc-icon-btn btn-edit agency-edit-btn" data-id="${row.id}" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="doc-icon-btn btn-del agency-delete-btn" data-id="${row.id}" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        $('#agencyTableBody').html(html);
    }

    /* ── Pagination ────────────────────────────────────────── */
    function renderPagination(data) {
        const container = $('#agencyPaginationContainer');
        if (!data || !data.total) { container.html(''); return; }

        const current = data.current_page || 1;
        const last    = data.last_page || 1;
        let pages = '';

        for (let p = 1; p <= last; p++) {
            if (p === 1 || p === last || (p >= current - 2 && p <= current + 2)) {
                pages += `<li class="page-item ${p === current ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="agencyGoToPage(${p}); return false;">${p}</a>
                </li>`;
            } else if (p === current - 3 || p === current + 3) {
                pages += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
        }

        container.html(`
            <div class="crm-pagination-container">
                <div class="text-muted small">
                    Showing <strong>${data.from || 0}</strong> to <strong>${data.to || 0}</strong> of <strong>${data.total}</strong> entries
                </div>
                <nav>
                    <ul class="pagination crm-pagination mb-0">
                        <li class="page-item ${!data.prev_page_url ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="agencyGoToPage(${current - 1}); return false;">Previous</a>
                        </li>
                        ${pages}
                        <li class="page-item ${!data.next_page_url ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="agencyGoToPage(${current + 1}); return false;">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>`);
    }

    window.agencyGoToPage = function (page) { loadData(page); };

    /* ── Search ────────────────────────────────────────────── */
    let searchTimeout;
    $('#agencySearch').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadData(1), 300);
    });

    /* ── Modal Helpers ─────────────────────────────────────── */
    function resetModal() {
        $('#agencyModalLabel').text('Add Agency');
        $('#agency-id').val('');
        $('#agency-level').val('').removeClass('is-invalid');
        $('#agency-name').val('').removeClass('is-invalid');
        $('#agency-status').val('1');
        $('#agency-level-error, #agency-name-error').hide();
        $('#btn-save-agency').text('Save');
    }

    function clearErrors() {
        $('#agency-level, #agency-name').removeClass('is-invalid');
        $('#agency-level-error, #agency-name-error').hide();
    }

    /* ── Open Add ──────────────────────────────────────────── */
    $('#btn-open-add-modal').on('click', function () {
        resetModal();
        loadLevels(null);
        new bootstrap.Modal(document.getElementById('agencyModal')).show();
    });

    /* ── Open Edit ─────────────────────────────────────────── */
    $(document).on('click', '.agency-edit-btn', function () {
        const id = $(this).data('id');
        resetModal();
        $('#agencyModalLabel').text('Edit Agency');
        $('#btn-save-agency').text('Update');
        loadLevels(null);

        fetch(`${BASE}/${id}`, { headers: AUTH() })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const d = res.data;
                    $('#agency-id').val(d.id);
                    $('#agency-name').val(d.name);
                    $('#agency-status').val(d.status ? '1' : '0');
                    loadLevels(d.agency_levels_id);
                    new bootstrap.Modal(document.getElementById('agencyModal')).show();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Unable to load record.', confirmButtonColor: '#0f172a' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Network error.', confirmButtonColor: '#0f172a' }));
    });

    /* ── Save ──────────────────────────────────────────────── */
    $('#btn-save-agency').on('click', function () {
        clearErrors();
        const id      = $('#agency-id').val();
        const levelId = $('#agency-level').val();
        const name    = $('#agency-name').val().trim();
        const status  = $('#agency-status').val() === '1';
        let valid     = true;

        if (!levelId) {
            $('#agency-level').addClass('is-invalid');
            $('#agency-level-error').show();
            valid = false;
        }
        if (!name) {
            $('#agency-name').addClass('is-invalid');
            $('#agency-name-error').show();
            valid = false;
        }
        if (!valid) return;

        const url    = id ? `${BASE}/${id}` : BASE;
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: { ...AUTH(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, agency_levels_id: levelId, status })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('agencyModal')).hide();
                Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
                loadData(currentPage);
            } else {
                if (res.errors?.name) {
                    $('#agency-name-error').text(res.errors.name[0] || res.errors.name).show();
                    $('#agency-name').addClass('is-invalid');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to save.', confirmButtonColor: '#0f172a' });
                }
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Network error.', confirmButtonColor: '#0f172a' }));
    });

    /* ── Delete ────────────────────────────────────────────── */
    $(document).on('click', '.agency-delete-btn', function () {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('agencyDeleteModal')).show();
    });

    $('#agency-confirm-delete').on('click', function () {
        if (!deleteId) return;
        fetch(`${BASE}/${deleteId}`, { method: 'DELETE', headers: AUTH() })
            .then(r => r.json())
            .then(res => {
                bootstrap.Modal.getInstance(document.getElementById('agencyDeleteModal')).hide();
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 2000, showConfirmButton: false });
                    loadData(currentPage);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#0f172a' });
                }
            })
            .catch(() => {
                bootstrap.Modal.getInstance(document.getElementById('agencyDeleteModal')).hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network error.', confirmButtonColor: '#0f172a' });
            });
    });

    /* ── Status Toggle ─────────────────────────────────────── */
    window.toggleAgencyStatus = function (id, newStatus) {
        fetch(`${BASE}/${id}`, {
            method: 'PUT',
            headers: { ...AUTH(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: !!newStatus })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1500, showConfirmButton: false });
                loadData(currentPage);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#0f172a' });
            }
        });
    };

    /* ── Clear validation on input ─────────────────────────── */
    $('#agency-level').on('change', function () { $(this).removeClass('is-invalid'); $('#agency-level-error').hide(); });
    $('#agency-name').on('input',   function () { $(this).removeClass('is-invalid'); $('#agency-name-error').hide(); });

    /* ── Reset on modal close ──────────────────────────────── */
    document.getElementById('agencyModal').addEventListener('hidden.bs.modal', resetModal);

    /* ── Escape helper ─────────────────────────────────────── */
    function escHtml(text) {
        const d = document.createElement('div');
        d.textContent = text == null ? '' : String(text);
        return d.innerHTML;
    }

    /* ── Init ──────────────────────────────────────────────── */
    loadData(1);
});
</script>
@endpush
