@extends('layouts.app')

@section('page_title', 'Client Documents')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Client Documents</h4>
                    <p class="text-muted small mb-0">Manage uploaded client documents.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('upload-document.create-client') }}" class="btn btn-dark-blue">
                        <i class="fas fa-plus me-1"></i> Add Document
                    </a>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">All Documents</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0"
                           placeholder="Search documents..." id="documentSearch">
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="documentsTable">
                    <thead>
                        <tr>
                            <th style="width:60px;" class="ps-4">Sr.No</th>
                            <th>Family Group</th>
                            <th>Document Type</th>
                            <th>Document Name</th>
                            <th class="d-none d-md-table-cell">Created By</th>
                            <th class="text-center d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Modified By</th>
                            <th class="text-center d-none d-md-table-cell" style="min-width:200px;">Action</th>
                            <th class="text-center d-md-none" style="width:60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="documentsTableBody"></tbody>
                </table>
            </div>
            <div class="card-footer border-top-0 py-4 px-4" id="documentsPagination"></div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {

    const tableBody        = document.getElementById('documentsTableBody');
    const paginationWrap   = document.getElementById('documentsPagination');
    const searchInput      = document.getElementById('documentSearch');
    const csrfToken        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const listUrl          = "{{ route('upload-document.list-ajax') }}";
    const updateStatusUrl  = "{{ route('upload-document.update-status') }}";
    const deleteUrl        = "{{ route('upload-document.delete') }}";
    const editBaseUrl      = "{{ url('upload-document/edit') }}";
    const viewBaseUrl      = "{{ url('upload-document/view') }}";

    // ── Helpers ───────────────────────────────────────────────────────────────

    function escHtml(str) {
        if (!str) return '--';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function notify(message, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: type, title: type === 'success' ? 'Success!' : 'Error!', text: message, timer: 2000, showConfirmButton: false });
        }
    }

    function authHeaders(extra) {
        return typeof window.crmApplyAuthHeaders === 'function'
            ? window.crmApplyAuthHeaders(extra || {})
            : (extra || {});
    }

    // ── Row rendering ─────────────────────────────────────────────────────────

    function statusBadge(doc) {
        const isActive = doc.status == 1;
        return `<button type="button"
                    class="btn btn-sm rounded-pill px-3 doc-status-toggle ${isActive ? 'status-active' : 'status-inactive'}"
                    data-doc-id="${doc.id}"
                    data-active="${isActive ? 1 : 0}">
                    ${isActive ? 'Active' : 'Inactive'}
                </button>`;
    }

    function renderRows(items, meta) {
        if (!items || items.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="text-muted mb-3"><i class="bi bi-file-earmark-text display-1 opacity-25"></i></div>
                        <p class="text-muted mb-3">No documents found.</p>
                        <a href="${editBaseUrl.replace('/edit','')}/create-client" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                            Upload First Document
                        </a>
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = items.map(function (doc, index) {
            const srNo = meta && meta.from ? (meta.from + index) : (index + 1);
            return `
                <tr>
                    <td class="ps-4">${srNo}</td>
                    <td class="fw-medium">${escHtml(doc.family_group)}</td>
                    <td>${escHtml(doc.document_type)}</td>
                    <td>${escHtml(doc.document_name)}</td>
                    <td class="d-none d-md-table-cell">${escHtml(doc.created_by)}</td>
                    <td class="text-center d-none d-md-table-cell">${statusBadge(doc)}</td>
                    <td class="d-none d-md-table-cell">${escHtml(doc.modified_by)}</td>
                    <td class="text-center pe-4 d-none d-md-table-cell">
                        <div class="users-action-group">
                            <a href="${editBaseUrl}/${doc.id}" class="btn crm-action-btn btn-sm users-icon-btn" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="${viewBaseUrl}/${doc.id}" class="btn crm-action-btn btn-sm users-icon-btn" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button"
                                    class="btn crm-action-btn btn-sm text-danger users-icon-btn doc-delete-btn"
                                    data-doc-id="${doc.id}" title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-doc-id="${doc.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="doc-details-${doc.id}" style="display:none;">
                    <td colspan="9" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-layer-group"></i> Document Type:</div>
                                    <div class="expand-value">${escHtml(doc.document_type)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-file"></i> Document Name:</div>
                                    <div class="expand-value">${escHtml(doc.document_name)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status:</div>
                                    <div class="expand-value">${statusBadge(doc)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Created By:</div>
                                    <div class="expand-value">${escHtml(doc.created_by)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions:</div>
                                    <div class="expand-actions">
                                        <a href="${editBaseUrl}/${doc.id}" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="${viewBaseUrl}/${doc.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                        <button type="button" class="btn crm-action-btn btn-sm text-danger doc-delete-btn" data-doc-id="${doc.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        // Bind status toggles
        tableBody.querySelectorAll('.doc-status-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () { toggleStatus(this); });
        });

        // Bind delete buttons
        tableBody.querySelectorAll('.doc-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { deleteDocument(this.dataset.docId, this); });
        });

        // Bind mobile expand
        tableBody.querySelectorAll('.btn-user-expand').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.dataset.docId;
                const row = document.getElementById('doc-details-' + id);
                const icon = this.querySelector('i');
                const isHidden = row.style.display === 'none';
                row.style.display = isHidden ? 'table-row' : 'none';
                icon.classList.toggle('fa-plus', !isHidden);
                icon.classList.toggle('fa-minus', isHidden);
                this.classList.toggle('active', isHidden);
                this.closest('tr').classList.toggle('bg-light', isHidden);
            });
        });
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    function renderPagination(data) {
        if (!data || data.total === 0) { paginationWrap.innerHTML = ''; return; }

        const cur      = data.current_page || 1;
        const last     = data.last_page || 1;
        const from     = data.from || 0;
        const to       = data.to || 0;
        const total    = data.total || 0;

        let html = `<div class="crm-pagination-container">
            <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
            <ul class="pagination crm-pagination mb-0">`;

        html += data.prev_page_url
            ? `<li class="page-item"><a class="page-link" href="#" data-page="${cur - 1}">Previous</a></li>`
            : `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;

        for (let i = 1; i <= last; i++) {
            if (i === 1 || i === last || (i >= cur - 2 && i <= cur + 2)) {
                html += i === cur
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            } else if (i === cur - 3 || i === cur + 3) {
                html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
        }

        html += data.next_page_url
            ? `<li class="page-item"><a class="page-link" href="#" data-page="${cur + 1}">Next</a></li>`
            : `<li class="page-item disabled"><span class="page-link">Next</span></li>`;

        html += '</ul></div>';
        paginationWrap.innerHTML = html;

        paginationWrap.querySelectorAll('.page-link[data-page]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                fetchDocuments(this.dataset.page);
            });
        });
    }

    // ── Fetch ─────────────────────────────────────────────────────────────────

    function fetchDocuments(page) {
        page = page || 1;
        let url = listUrl + '?page=' + page;
        if (searchInput && searchInput.value.trim()) {
            url += '&search=' + encodeURIComponent(searchInput.value.trim());
        }

        tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </td></tr>`;

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            headers: authHeaders({ 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }),
            success: function (res) {
                if (!res.success) { renderRows([], null); renderPagination({ total: 0 }); return; }
                renderRows(res.data, res);
                renderPagination(res);
            },
            error: function () {
                tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-muted">Error loading documents.</td></tr>`;
                paginationWrap.innerHTML = '';
            }
        });
    }

    // ── Status toggle ─────────────────────────────────────────────────────────

    function toggleStatus(btn) {
        if (btn.dataset.loading === '1') return;
        const docId    = btn.dataset.docId;
        const isActive = btn.dataset.active === '1';
        const next     = !isActive;

        btn.dataset.loading = '1';
        btn.disabled = true;

        $.ajax({
            url: updateStatusUrl,
            type: 'POST',
            data: { _token: csrfToken, id: docId, status: next ? 1 : 0 },
            dataType: 'json',
            success: function (res) {
                if (res.status === true) {
                    btn.dataset.active = next ? '1' : '0';
                    btn.textContent    = next ? 'Active' : 'Inactive';
                    btn.classList.toggle('status-active', next);
                    btn.classList.toggle('status-inactive', !next);
                    notify(res.message, 'success');
                } else {
                    notify(res.message, 'error');
                }
            },
            error: function () { notify('Error updating status.', 'error'); },
            complete: function () { btn.disabled = false; btn.dataset.loading = '0'; }
        });
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    function deleteDocument(docId, btn) {
        if (typeof window.showDeleteConfirm === 'function') {
            window.showDeleteConfirm('This will also delete all associated files. This action cannot be undone!')
                .then(function (result) { if (result.isConfirmed) doDelete(docId, btn); });
        } else {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will also delete all associated files. This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then(function (result) { if (result.isConfirmed) doDelete(docId, btn); });
        }
    }

    function doDelete(docId, btn) {
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;

        $.ajax({
            url: deleteUrl,
            type: 'POST',
            data: { _token: csrfToken, id: docId },
            dataType: 'json',
            success: function (res) {
                if (res.status === true) {
                    notify(res.message, 'success');
                    fetchDocuments();
                } else {
                    notify(res.message, 'error');
                    btn.innerHTML = orig;
                    btn.disabled = false;
                }
            },
            error: function () {
                notify('Error deleting document.', 'error');
                btn.innerHTML = orig;
                btn.disabled = false;
            }
        });
    }

    // ── Search ────────────────────────────────────────────────────────────────

    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { fetchDocuments(1); }, 350);
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    fetchDocuments();

})();
</script>
@endpush
