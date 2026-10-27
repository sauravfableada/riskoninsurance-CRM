/**
 * Bank Master CRUD handler.
 */
(function () {
    'use strict';

    const API_BASE = '/api/masters/banks';
    let currentEditId = null;
    let currentPage = 1;

    function init() {
        initModal();
        initActions();
        initFilters();
        loadBanks();
    }

    async function loadBanks(page = 1) {
        currentPage = page;
        const tbody = document.querySelector('#bankTable tbody');
        if (!tbody) return;

        const search = document.getElementById('bankSearch')?.value || '';
        const perPage = document.getElementById('bankPerPage')?.value || '10';
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

        try {
            const response = await fetch(`${API_BASE}?search=${encodeURIComponent(search)}&per_page=${perPage}&page=${page}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || ''),
                },
            });
            const result = await response.json();

            if (response.ok && result.success) {
                renderTable(result.data);
                renderPagination(result.data);
                return;
            }

            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(result.message || 'Failed to load bank masters')}</td></tr>`;
        } catch (error) {
            console.error('Bank master load error:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Error loading bank masters</td></tr>';
        }
    }

    function renderTable(paginated) {
        const tbody = document.querySelector('#bankTable tbody');
        const rows = paginated?.data || [];

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No bank masters found</td></tr>';
            return;
        }

        const start = (paginated.from || 1) - 1;
        tbody.innerHTML = rows.map((item, index) => {
            const serial      = start + index + 1;
            const createdBy   = item.creator?.name || '--';
            const modifiedBy  = item.modifier?.name || '--';
            const isActive    = !!item.status;
            const statusClass = isActive ? 'status-active' : 'status-inactive';
            const statusLabel = isActive ? 'Active' : 'Inactive';
            const newStatus   = isActive ? false : true;

            return `
                <tr>
                    <td class="ps-4 text-muted fw-semibold">${serial}</td>
                    <td class="fw-semibold">${escapeHtml(item.name)}</td>
                    <td>
                        <button class="doc-status-badge ${statusClass}"
                                onclick="updateBankStatus(${item.id}, ${newStatus})">
                            ${statusLabel}
                        </button>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">${escapeHtml(createdBy)}</td>
                    <td class="d-none d-md-table-cell text-muted small">${escapeHtml(modifiedBy)}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="doc-action-group">
                            <button type="button" class="doc-icon-btn btn-edit" onclick="editBank(${item.id})" title="Edit">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="doc-icon-btn btn-del" onclick="deleteBank(${item.id}, '${escapeAttribute(item.name)}')" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <div class="doc-action-group justify-content-center">
                            <button type="button" class="doc-icon-btn btn-edit" onclick="editBank(${item.id})" title="Edit">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="doc-icon-btn btn-del" onclick="deleteBank(${item.id}, '${escapeAttribute(item.name)}')" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(data) {
        const container = document.getElementById('bankPagination');
        if (!container || !data || !data.total) {
            if (container) container.innerHTML = '';
            return;
        }

        const current = data.current_page || 1;
        const last = data.last_page || 1;

        let html = `
            <div class="crm-pagination-container">
                <div class="text-muted small">Showing ${data.from || 0} to ${data.to || 0} of ${data.total || 0} entries</div>
                <ul class="pagination crm-pagination mb-0">
        `;

        html += data.prev_page_url
            ? `<li class="page-item"><a class="page-link" href="#" onclick="changeBankPage(${current - 1}); return false;">Previous</a></li>`
            : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

        for (let page = 1; page <= last; page++) {
            if (page === 1 || page === last || (page >= current - 2 && page <= current + 2)) {
                html += page === current
                    ? `<li class="page-item active"><span class="page-link">${page}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" onclick="changeBankPage(${page}); return false;">${page}</a></li>`;
            } else if (page === current - 3 || page === current + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += data.next_page_url
            ? `<li class="page-item"><a class="page-link" href="#" onclick="changeBankPage(${current + 1}); return false;">Next</a></li>`
            : '<li class="page-item disabled"><span class="page-link">Next</span></li>';

        html += '</ul></div>';
        container.innerHTML = html;
    }

    function initActions() {
        const container = document.getElementById('bankActions');
        if (!container || document.getElementById('btnAddBank')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'btnAddBank';
        button.className = 'btn btn-dark-blue btn-sm px-3 py-2';
        button.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Add Bank';
        button.addEventListener('click', () => openBankModal());
        container.appendChild(button);
    }

    function initFilters() {
        const searchInput = document.getElementById('bankSearch');
        const perPageSelect = document.getElementById('bankPerPage');
        let timeout;

        searchInput?.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => loadBanks(1), 300);
        });

        perPageSelect?.addEventListener('change', function () {
            loadBanks(1);
        });
    }

    function initModal() {
        if (document.getElementById('bankModal')) return;

        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bankModalLabel">Add Bank</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="bankModalAlert" class="alert d-none" role="alert"></div>
                            <form id="bankForm">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Bank Master Name</label>
                                    <input type="text" class="form-control" id="bankName" name="name" placeholder="Enter bank master name">
                                    <div class="invalid-feedback d-block d-none" id="bankNameError"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-dark-blue" id="btnSaveBank" onclick="saveBank()">
                                <span class="spinner-border spinner-border-sm d-none" id="bankSaveSpinner"></span>
                                <span id="bankSaveText">Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        document.getElementById('bankForm')?.addEventListener('submit', function (event) {
            event.preventDefault();
            saveBank();
        });

        document.getElementById('bankName')?.addEventListener('input', clearBankErrors);
    }

    async function openBankModal(id = null) {
        currentEditId = id;
        clearBankErrors();

        const form = document.getElementById('bankForm');
        const title = document.getElementById('bankModalLabel');
        const saveText = document.getElementById('bankSaveText');
        const nameInput = document.getElementById('bankName');

        form.reset();
        title.textContent = id ? 'Edit Bank' : 'Add Bank';
        saveText.textContent = id ? 'Update' : 'Save';

        if (id) {
            try {
                const response = await fetch(`${API_BASE}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || ''),
                    },
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    nameInput.value = result.data.name || '';
                }
            } catch (error) {
                console.error('Bank master edit load error:', error);
                showToast('Error opening edit form. Please try again.', 'error');
                return;
            }
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bankModal')).show();
        nameInput.focus();
    }

    async function saveBank() {
        const button = document.getElementById('btnSaveBank');
        const spinner = document.getElementById('bankSaveSpinner');
        const name = document.getElementById('bankName').value.trim();

        clearBankErrors();

        if (name.length < 3) {
            setBankFieldError('Bank Master name must be at least 3 characters');
            return;
        }

        button.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const response = await fetch(currentEditId ? `${API_BASE}/${currentEditId}` : API_BASE, {
                method: currentEditId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || ''),
                },
                body: JSON.stringify({ name }),
            });
            const result = await response.json();

            if (response.ok && result.success) {
                bootstrap.Modal.getInstance(document.getElementById('bankModal'))?.hide();
                showToast(result.message || 'Bank master saved successfully', 'success');
                loadBanks(currentPage);
                return;
            }

            if (result.errors?.name) {
                setBankFieldError(Array.isArray(result.errors.name) ? result.errors.name[0] : result.errors.name);
            } else {
                showModalAlert(result.message || 'Failed to save Bank master');
            }
        } catch (error) {
            console.error('Bank master save error:', error);
            showModalAlert('An error occurred. Please try again.');
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    async function updateBankStatus(id, status) {
        try {
            const response = await fetch(`${API_BASE}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || ''),
                },
                body: JSON.stringify({ status }),
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showToast(result.message || 'Status updated successfully', 'success');
                loadBanks(currentPage);
                return;
            }

            showToast(result.message || 'Failed to update status', 'error');
            loadBanks(currentPage);
        } catch (error) {
            console.error('Bank master status error:', error);
            showToast('An error occurred while updating status', 'error');
            loadBanks(currentPage);
        }
    }

    async function deleteBank(id, name) {
        const doDelete = async () => {
            try {
                const response = await fetch(`${API_BASE}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || ''),
                    },
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(result.message || 'Bank master deleted successfully', 'success');
                    loadBanks(currentPage);
                    return;
                }

                showToast(result.message || 'Failed to delete Bank master', 'error');
            } catch (error) {
                console.error('Bank master delete error:', error);
                showToast('An error occurred while deleting', 'error');
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#0f172a',
                confirmButtonText: 'Yes, delete it',
            }).then((result) => {
                if (result.isConfirmed) doDelete();
            });
            return;
        }

        window.crmConfirm(`Are you sure you want to delete "${name}"?`).then((result) => { if (result.isConfirmed) { doDelete(); } });
    }

    function clearBankErrors() {
        const input = document.getElementById('bankName');
        const error = document.getElementById('bankNameError');
        const alert = document.getElementById('bankModalAlert');

        input?.classList.remove('is-invalid');
        if (error) {
            error.textContent = '';
            error.classList.add('d-none');
        }
        if (alert) {
            alert.textContent = '';
            alert.classList.add('d-none');
        }
    }

    function setBankFieldError(message) {
        const input = document.getElementById('bankName');
        const error = document.getElementById('bankNameError');

        input?.classList.add('is-invalid');
        if (error) {
            error.textContent = message;
            error.classList.remove('d-none');
        }
    }

    function showModalAlert(message) {
        const alert = document.getElementById('bankModalAlert');
        if (!alert) return;

        alert.className = 'alert alert-danger';
        alert.textContent = message;
        alert.classList.remove('d-none');
    }

    function showToast(message, type = 'info') {
        if (window.DashboardAPI?.showToast) {
            window.DashboardAPI.showToast(message, type);
            return;
        }

        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        console.info(message);
    }

    function changeBankPage(page) {
        loadBanks(page);
    }

    function editBank(id) {
        openBankModal(id);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return escapeHtml(text).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
    }

    window.changeBankPage = changeBankPage;
    window.editBank = editBank;
    window.deleteBank = deleteBank;
    window.updateBankStatus = updateBankStatus;
    window.saveBank = saveBank;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
