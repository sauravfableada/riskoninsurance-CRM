@extends('layouts.masters')

@section('page_title', 'Investment Post Office Master')

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

    #postOfficeTable thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    #postOfficeTable tbody tr {
        transition: background-color 0.2s ease;
    }

    #postOfficeTable tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.04);
    }

    #postOfficeTable tbody td {
        vertical-align: middle;
        padding: 0.85rem 0.75rem;
        color: #334155;
        font-size: 0.92rem;
    }

    [data-theme="dark"] #postOfficeTable thead th {
        background-color: #1e293b;
        color: #94a3b8;
        border-bottom-color: rgba(255,255,255,.08);
    }

    [data-theme="dark"] #postOfficeTable tbody td {
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
    [data-theme="dark"] .doc-modal .form-control {
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
            <h4 class="fw-bold mb-0">Post Office Master</h4>
            <p class="text-muted small mb-0">Manage post office master records.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
            <a href="{{ route('masters.masters.index') }}" class="btn btn-dark-blue btn-sm px-3 master-header-btn">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-dark-blue btn-sm px-3 master-header-btn" id="btn-open-add-modal">
                <i class="fa fa-plus me-1"></i> Add Post Office
            </button>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
        <h6 class="fw-bold mb-0">All Master Post Office</h6>
        <div class="input-group input-group-sm" style="max-width: 280px; width: 100%;">
            <span class="input-group-text crm-search-icon border-0">
                <i class="fa-solid fa-search"></i>
            </span>
            <input type="text" id="poSearch" class="form-control crm-search-input border-0"
                   placeholder="Search post offices...">
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────── --}}
<div class="card-body p-0">
    <div class="table-responsive">
        <table id="postOfficeTable" class="table table-hover align-middle mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th class="ps-4" style="width:70px;">SR.NO</th>
                    <th>Name</th>
                    <th style="width:130px;">Status</th>
                    <th class="d-none d-md-table-cell">Created By</th>
                    <th class="d-none d-md-table-cell">Modified By</th>
                    <th class="text-end pe-4" style="width:110px;">Action</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="doc-empty-state">
                            <i class="fa fa-spinner fa-spin d-block"></i>
                            Loading…
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="poPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
</div>

{{-- ── Add / Edit Modal ─────────────────────────────────────── --}}
<div class="modal fade doc-modal" id="postOfficeModal" tabindex="-1"
     aria-labelledby="postOfficeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postOfficeModalLabel">Add Post Office</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="po-id">

                <div class="mb-3">
                    <label for="po-name" class="form-label fw-semibold small">
                        Post Office Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="po-name" class="form-control"
                           placeholder="Enter post office name" autocomplete="off">
                    <div id="po-name-error" class="text-danger small mt-1" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue btn-sm px-4"
                        id="btn-save-po">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Delete Confirmation Modal ────────────────────────────── --}}
<div class="modal fade doc-modal" id="poDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#dc2626;">
                <h5 class="modal-title text-white fw-bold">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="fa fa-exclamation-triangle text-warning fa-3x mb-3 d-block"></i>
                <p class="mb-1 fw-semibold">Are you sure?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm px-4"
                        id="po-confirm-delete">Delete</button>
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

    const CSRF      = '{{ csrf_token() }}';
    const LIST_URL  = '{{ url("masters/post-office/list") }}';
    const STORE_URL = '{{ url("masters/post-office/store") }}';
    const UPD_URL   = '{{ url("masters/post-office/update") }}';
    const STS_URL   = '{{ url("masters/post-office/update-status") }}';
    const EDIT_BASE = '{{ url("masters/post-office/edit") }}';
    const DEL_BASE  = '{{ url("masters/post-office/delete") }}';

    /* ── Load ──────────────────────────────────────────────── */
    function loadData() {
        $('#poTableBody').html(`
            <tr><td colspan="6" class="text-center py-5">
                <div class="doc-empty-state"><i class="fa fa-spinner fa-spin d-block"></i>Loading…</div>
            </td></tr>`);

        $.get(LIST_URL, function (json) {
            allData  = json.data || [];
            filtered = [...allData];
            currentPage = 1;
            renderTable();
        }).fail(function () {
            $('#poTableBody').html(`
                <tr><td colspan="6" class="text-center py-5">
                    <div class="doc-empty-state"><i class="fa fa-exclamation-circle d-block"></i>Failed to load data.</div>
                </td></tr>`);
        });
    }

    /* ── Render ────────────────────────────────────────────── */
    function renderTable() {
        const total      = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const start    = (currentPage - 1) * perPage;
        const pageData = filtered.slice(start, start + perPage);

        if (!pageData.length) {
            $('#poTableBody').html(`
                <tr><td colspan="6" class="text-center py-5">
                    <div class="doc-empty-state"><i class="fa fa-building d-block"></i>No post offices found.</div>
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
            const newStatus   = isActive ? 0 : 1;

            rows += `
            <tr>
                <td class="ps-4 text-muted fw-semibold">${srNo}</td>
                <td class="fw-semibold">${escHtml(row.name || '--')}</td>
                <td>
                    <button class="doc-status-badge ${statusClass}"
                            onclick="togglePoStatus(${row.id}, ${newStatus})">
                        ${statusLabel}
                    </button>
                </td>
                <td class="d-none d-md-table-cell text-muted small">${escHtml(row.created_by || '—')}</td>
                <td class="d-none d-md-table-cell text-muted small">${escHtml(row.modified_by || '—')}</td>
                <td class="pe-4">
                    <div class="doc-action-group">
                        <button class="doc-icon-btn btn-edit po-edit-btn"
                                data-id="${row.id}" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="doc-icon-btn btn-del po-delete-btn"
                                data-id="${row.id}" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });

        $('#poTableBody').html(rows);
        renderPagination(start + 1, Math.min(start + perPage, total), total, totalPages);
    }

    /* ── Pagination ────────────────────────────────────────── */
    function renderPagination(from, to, total, totalPages) {
        if (total === 0) { $('#poPaginationContainer').html(''); return; }

        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);

        let pages = '';
        for (let p = startPage; p <= endPage; p++) {
            pages += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="poGoToPage(${p}); return false;">${p}</a>
            </li>`;
        }

        $('#poPaginationContainer').html(`
            <div class="crm-pagination-container">
                <div class="text-muted small">
                    Showing <strong>${from}</strong> to <strong>${to}</strong> of <strong>${total}</strong> entries
                </div>
                <nav>
                    <ul class="pagination crm-pagination mb-0">
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="poGoToPage(${currentPage - 1}); return false;">Previous</a>
                        </li>
                        ${pages}
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="poGoToPage(${currentPage + 1}); return false;">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>`);
    }

    window.poGoToPage = function (page) {
        const totalPages = Math.ceil(filtered.length / perPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderTable();
    };

    /* ── Search ────────────────────────────────────────────── */
    $('#poSearch').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        filtered = q
            ? allData.filter(d => (d.name || '').toLowerCase().includes(q))
            : [...allData];
        currentPage = 1;
        renderTable();
    });

    /* ── Modal Helpers ─────────────────────────────────────── */
    function resetModal() {
        $('#postOfficeModalLabel').text('Add Post Office');
        $('#po-id').val('');
        $('#po-name').val('').removeClass('is-invalid');
        $('#po-name-error').hide().text('');
        $('#btn-save-po').text('Save');
    }

    function clearError() {
        $('#po-name-error').hide().text('');
        $('#po-name').removeClass('is-invalid');
    }

    /* ── Open Add ──────────────────────────────────────────── */
    $('#btn-open-add-modal').on('click', function () {
        resetModal();
        new bootstrap.Modal(document.getElementById('postOfficeModal')).show();
    });

    /* ── Open Edit ─────────────────────────────────────────── */
    $(document).on('click', '.po-edit-btn', function () {
        const id = $(this).data('id');
        resetModal();
        $('#postOfficeModalLabel').text('Edit Post Office');
        $('#btn-save-po').text('Update');

        $.get(EDIT_BASE + '/' + id, function (res) {
            if (res.status) {
                $('#po-id').val(res.data.id);
                $('#po-name').val(res.data.name);
                new bootstrap.Modal(document.getElementById('postOfficeModal')).show();
            } else {
                Swal.fire({ icon: 'error', title: 'Error',
                    text: res.message || 'Unable to load record.', confirmButtonColor: '#0f172a' });
            }
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Network error. Please try again.',
                confirmButtonColor: '#0f172a' });
        });
    });

    /* ── Save ──────────────────────────────────────────────── */
    $('#btn-save-po').on('click', function () {
        clearError();
        const id   = $('#po-id').val();
        const name = $.trim($('#po-name').val());

        if (!name) {
            $('#po-name-error').text('Post office name is required.').show();
            $('#po-name').addClass('is-invalid');
            return;
        }

        const url = id ? UPD_URL : STORE_URL;

        $.ajax({
            url, type: 'POST',
            data: { id, name, _token: CSRF },
            success: function (res) {
                if (res.status) {
                    bootstrap.Modal.getInstance(document.getElementById('postOfficeModal')).hide();
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message,
                        timer: 2000, showConfirmButton: false });
                    loadData();
                } else {
                    $('#po-name-error').text(res.message || 'Something went wrong.').show();
                    $('#po-name').addClass('is-invalid');
                }
            },
            error: function () {
                $('#po-name-error').text('Network error. Please try again.').show();
                $('#po-name').addClass('is-invalid');
            }
        });
    });

    /* ── Delete ────────────────────────────────────────────── */
    $(document).on('click', '.po-delete-btn', function () {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('poDeleteModal')).show();
    });

    $('#po-confirm-delete').on('click', function () {
        if (!deleteId) return;
        $.ajax({
            url: DEL_BASE + '/' + deleteId,
            type: 'DELETE',
            data: { _token: CSRF },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('poDeleteModal')).hide();
                if (res.status) {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message,
                        timer: 2000, showConfirmButton: false });
                    loadData();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message,
                        confirmButtonColor: '#0f172a' });
                }
            },
            error: function () {
                bootstrap.Modal.getInstance(document.getElementById('poDeleteModal')).hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network error. Please try again.',
                    confirmButtonColor: '#0f172a' });
            }
        });
    });

    /* ── Status Toggle ─────────────────────────────────────── */
    window.togglePoStatus = function (id, newStatus) {
        $.post(STS_URL, { id, status: newStatus, _token: CSRF }, function (res) {
            if (res.status) {
                Swal.fire({ icon: 'success', title: 'Updated', text: res.message,
                    timer: 1500, showConfirmButton: false });
                loadData();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message,
                    confirmButtonColor: '#0f172a' });
            }
        });
    };

    /* ── Clear error on input / Reset on modal close ───────── */
    $('#po-name').on('input', clearError);
    document.getElementById('postOfficeModal').addEventListener('hidden.bs.modal', resetModal);

    /* ── Escape helper ─────────────────────────────────────── */
    function escHtml(text) {
        const d = document.createElement('div');
        d.textContent = text == null ? '' : String(text);
        return d.innerHTML;
    }

    /* ── Init ──────────────────────────────────────────────── */
    loadData();
});
</script>
@endpush
