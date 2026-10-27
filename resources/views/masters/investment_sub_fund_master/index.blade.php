@extends('layouts.masters')

@section('page_title', 'Investment Sub Fund Master')

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

    #subFundTable thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    #subFundTable tbody tr {
        transition: background-color 0.2s ease;
    }

    #subFundTable tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.04);
    }

    #subFundTable tbody td {
        vertical-align: middle;
        padding: 0.85rem 0.75rem;
        color: #334155;
        font-size: 0.92rem;
    }

    [data-theme="dark"] #subFundTable thead th {
        background-color: #1e293b;
        color: #94a3b8;
        border-bottom-color: rgba(255,255,255,.08);
    }

    [data-theme="dark"] #subFundTable tbody td {
        color: #cbd5e1;
        border-bottom-color: rgba(255,255,255,.06);
    }

    /* ── Status Badge ── */
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

    /* ── Action Buttons ── */
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

    /* ── Buttons ── */
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

    /* ── Modal ── */
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

    /* ── Empty State ── */
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
            <h4 class="fw-bold mb-0">Investment Sub Fund Master</h4>
            <p class="text-muted small mb-0">Manage investment sub fund records.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
            <a href="{{ route('masters.masters.index') }}" class="btn btn-dark-blue btn-sm px-3 master-header-btn">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-dark-blue btn-sm px-3 master-header-btn" id="btn-open-add-modal">
                <i class="fa fa-plus me-1"></i> Add Sub Fund
            </button>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom">
        <h6 class="fw-bold mb-0">All Investment Sub Fund Master</h6>
        <div class="input-group input-group-sm" style="max-width: 280px; width: 100%;">
            <span class="input-group-text crm-search-icon border-0">
                <i class="fa-solid fa-search"></i>
            </span>
            <input type="text" id="subFundSearch" class="form-control crm-search-input border-0"
                   placeholder="Search sub funds...">
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────── --}}
<div class="card-body p-0">
    <div class="table-responsive">
        <table id="subFundTable" class="table table-hover align-middle mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th class="ps-4" style="width:70px;">SR.NO</th>
                    <th>Fund Name</th>
                    <th>Sub Fund Name</th>
                    <th style="width:130px;">Status</th>
                    <th>Created At</th>
                    <th class="text-end pe-4" style="width:110px;">Action</th>
                </tr>
            </thead>
            <tbody id="subFundTableBody">
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

    <div id="subFundPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
</div>

{{-- ── Add / Edit Modal ─────────────────────────────────────── --}}
<div class="modal fade doc-modal" id="subFundModal" tabindex="-1"
     aria-labelledby="subFundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subFundModalLabel">Add New Investment Sub Fund</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="subfund-id">

                <div class="mb-3">
                    <label for="subfund-fund-name" class="form-label fw-semibold small">
                        Select Fund <span class="text-danger">*</span>
                    </label>
                    <select id="subfund-fund-name" class="form-select">
                        <option value="">Select Fund</option>
                        @foreach($fundNames as $fn)
                            <option value="{{ $fn->name }}">{{ $fn->name }}</option>
                        @endforeach
                    </select>
                    <div id="subfund-fund-error" class="text-danger small mt-1" style="display:none;"></div>
                </div>

                <div class="mb-3">
                    <label for="subfund-name" class="form-label fw-semibold small">
                        Sub Fund Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="subfund-name" class="form-control"
                           placeholder="Enter sub fund name" autocomplete="off">
                    <div id="subfund-name-error" class="text-danger small mt-1" style="display:none;"></div>
                </div>

                <div class="mb-3">
                    <label for="subfund-status" class="form-label fw-semibold small">Status</label>
                    <select id="subfund-status" class="form-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue btn-sm px-4"
                        id="btn-save-subfund">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Delete Confirmation Modal ────────────────────────────── --}}
<div class="modal fade doc-modal" id="subFundDeleteModal" tabindex="-1" aria-hidden="true">
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
                        id="subfund-confirm-delete">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    /* ── State ─────────────────────────────────────────────── */
    let allData      = [];
    let filtered     = [];
    let currentPage  = 1;
    const perPage    = 10;
    let deleteId     = null;

    const CSRF  = '{{ csrf_token() }}';
    const BASE  = '{{ url("masters/investment-sub-fund") }}';
    const LIST  = '{{ route("masters.investment_sub_fund_master.list") }}';

    /* ── Load ──────────────────────────────────────────────── */
    function loadData() {
        $('#subFundTableBody').html(`
            <tr><td colspan="6" class="text-center py-5">
                <div class="doc-empty-state"><i class="fa fa-spinner fa-spin d-block"></i>Loading…</div>
            </td></tr>`);

        $.get(LIST, function (json) {
            allData  = (json.data || []);
            filtered = [...allData];
            currentPage = 1;
            renderTable();
        }).fail(function () {
            $('#subFundTableBody').html(`
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
            $('#subFundTableBody').html(`
                <tr><td colspan="6" class="text-center py-5">
                    <div class="doc-empty-state"><i class="fa fa-folder-open d-block"></i>No sub funds found.</div>
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
                <td>${escHtml(row.fund_name || '--')}</td>
                <td class="fw-semibold">${escHtml(row.sub_fund_name || '--')}</td>
                <td>
                    <button class="doc-status-badge ${statusClass}"
                            onclick="toggleSubFundStatus(${row.id}, ${newStatus})">
                        ${statusLabel}
                    </button>
                </td>
                <td class="text-muted small">${row.created_at || '--'}</td>
                <td class="pe-4">
                    <div class="doc-action-group">
                        <button class="doc-icon-btn btn-edit subfund-edit-btn"
                                data-id="${row.id}" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="doc-icon-btn btn-del subfund-delete-btn"
                                data-id="${row.id}" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });

        $('#subFundTableBody').html(rows);
        renderPagination(start + 1, Math.min(start + perPage, total), total, totalPages);
    }

    /* ── Pagination ────────────────────────────────────────── */
    function renderPagination(from, to, total, totalPages) {
        if (total === 0) { $('#subFundPaginationContainer').html(''); return; }

        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);

        let pages = '';
        for (let p = startPage; p <= endPage; p++) {
            pages += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="subFundGoToPage(${p}); return false;">${p}</a>
            </li>`;
        }

        $('#subFundPaginationContainer').html(`
            <div class="crm-pagination-container">
                <div class="text-muted small">
                    Showing <strong>${from}</strong> to <strong>${to}</strong> of <strong>${total}</strong> entries
                </div>
                <nav>
                    <ul class="pagination crm-pagination mb-0">
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="subFundGoToPage(${currentPage - 1}); return false;">Previous</a>
                        </li>
                        ${pages}
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="subFundGoToPage(${currentPage + 1}); return false;">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>`);
    }

    window.subFundGoToPage = function (page) {
        const totalPages = Math.ceil(filtered.length / perPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderTable();
    };

    /* ── Search ────────────────────────────────────────────── */
    $('#subFundSearch').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        filtered = q
            ? allData.filter(d =>
                (d.fund_name     || '').toLowerCase().includes(q) ||
                (d.sub_fund_name || '').toLowerCase().includes(q)
              )
            : [...allData];
        currentPage = 1;
        renderTable();
    });

    /* ── Modal Helpers ─────────────────────────────────────── */
    function resetModal() {
        $('#subFundModalLabel').text('Add New Investment Sub Fund');
        $('#subfund-id').val('');
        $('#subfund-fund-name').val('').removeClass('is-invalid');
        $('#subfund-name').val('').removeClass('is-invalid');
        $('#subfund-status').val('1');
        $('#subfund-fund-error').hide().text('');
        $('#subfund-name-error').hide().text('');
        $('#btn-save-subfund').text('Save');
    }

    function clearErrors() {
        $('#subfund-fund-error').hide().text('');
        $('#subfund-name-error').hide().text('');
        $('#subfund-fund-name').removeClass('is-invalid');
        $('#subfund-name').removeClass('is-invalid');
    }

    /* ── Open Add ──────────────────────────────────────────── */
    $('#btn-open-add-modal').on('click', function () {
        resetModal();
        new bootstrap.Modal(document.getElementById('subFundModal')).show();
    });

    /* ── Open Edit ─────────────────────────────────────────── */
    $(document).on('click', '.subfund-edit-btn', function () {
        const id = $(this).data('id');
        resetModal();
        $('#subFundModalLabel').text('Edit Investment Sub Fund');
        $('#btn-save-subfund').text('Update');

        $.get(BASE + '/edit/' + id, function (res) {
            if (res.status) {
                $('#subfund-id').val(res.data.id);
                $('#subfund-fund-name').val(res.data.fund_name);
                $('#subfund-name').val(res.data.sub_fund_name);
                $('#subfund-status').val(res.data.status);
                new bootstrap.Modal(document.getElementById('subFundModal')).show();
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
    $('#btn-save-subfund').on('click', function () {
        clearErrors();
        const id          = $('#subfund-id').val();
        const fundName    = $('#subfund-fund-name').val();
        const subFundName = $.trim($('#subfund-name').val());
        const status      = $('#subfund-status').val();
        let hasError      = false;

        if (!fundName) {
            $('#subfund-fund-error').text('Please select a fund.').show();
            $('#subfund-fund-name').addClass('is-invalid');
            hasError = true;
        }
        if (!subFundName) {
            $('#subfund-name-error').text('Sub fund name is required.').show();
            $('#subfund-name').addClass('is-invalid');
            hasError = true;
        }
        if (hasError) return;

        const url = id ? BASE + '/update' : BASE + '/store';

        $.ajax({
            url, type: 'POST',
            data: { id, fund_name: fundName, sub_fund_name: subFundName, status, _token: CSRF },
            success: function (res) {
                if (res.status) {
                    bootstrap.Modal.getInstance(document.getElementById('subFundModal')).hide();
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message,
                        timer: 2000, showConfirmButton: false });
                    loadData();
                } else {
                    $('#subfund-name-error').text(res.message || 'Something went wrong.').show();
                    $('#subfund-name').addClass('is-invalid');
                }
            },
            error: function () {
                $('#subfund-name-error').text('Network error. Please try again.').show();
                $('#subfund-name').addClass('is-invalid');
            }
        });
    });

    /* ── Delete ────────────────────────────────────────────── */
    $(document).on('click', '.subfund-delete-btn', function () {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('subFundDeleteModal')).show();
    });

    $('#subfund-confirm-delete').on('click', function () {
        if (!deleteId) return;
        $.ajax({
            url: BASE + '/delete/' + deleteId,
            type: 'DELETE',
            data: { _token: CSRF },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('subFundDeleteModal')).hide();
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
                bootstrap.Modal.getInstance(document.getElementById('subFundDeleteModal')).hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network error. Please try again.',
                    confirmButtonColor: '#0f172a' });
            }
        });
    });

    /* ── Status Toggle ─────────────────────────────────────── */
    window.toggleSubFundStatus = function (id, newStatus) {
        $.post(BASE + '/update-status', { id, status: newStatus, _token: CSRF }, function (res) {
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

    /* ── Clear validation on input ─────────────────────────── */
    $('#subfund-fund-name').on('change', function () {
        $('#subfund-fund-error').hide().text('');
        $(this).removeClass('is-invalid');
    });
    $('#subfund-name').on('input', function () {
        $('#subfund-name-error').hide().text('');
        $(this).removeClass('is-invalid');
    });

    /* ── Reset on modal close ──────────────────────────────── */
    document.getElementById('subFundModal').addEventListener('hidden.bs.modal', resetModal);

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
