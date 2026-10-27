/**
 * Division Master CRUD handler.
 */
(function () {
    'use strict';

    const API_BASE = '/api/masters/divisions';
    let currentEditId = null;
    let currentPage = 1;

    function init() {
        initModal();
        initActions();
        initFilters();
        loadDivisions();
    }

    async function loadDivisions(page = 1) {
        currentPage = page;
        const tbody = document.querySelector('#divisionTable tbody');
        if (!tbody) return;

        const search = document.getElementById('divisionSearch')?.value || '';
        const perPage = document.getElementById('divisionPerPage')?.value || '10';
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

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

            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(result.message || 'Failed to load divisions')}</td></tr>`;
        } catch (error) {
            console.error('Division load error:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Error loading divisions</td></tr>';
        }
    }

    function renderTable(paginated) {
        const tbody = document.querySelector('#divisionTable tbody');
        const rows = paginated?.data || [];

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No divisions found</td></tr>';
            return;
        }

        const start = (paginated.from || 1) - 1;
        tbody.innerHTML = rows.map((item, index) => {
            const serial = start + index + 1;
            const createdBy = item.creator?.name || '--';
            const checked = item.status ? 'checked' : '';

            return `
                <tr>
                    <td class="ps-4">${serial}</td>
                    <td>${escapeHtml(item.name)}</td>
                    <td>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input division-status" type="checkbox" ${checked}
                                onchange="updateDivisionStatus(${item.id}, this.checked)">
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">${escapeHtml(createdBy)}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <button type="button" class="btn division-action-btn btn-edit me-1" onclick="editDivision(${item.id})">Edit</button>
                        <button type="button" class="btn division-action-btn btn-delete" onclick="deleteDivision(${item.id}, '${escapeAttribute(item.name)}')">Delete</button>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn crm-action-btn btn-sm" onclick="editDivision(${item.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(data) {
        const container = document.getElementById('divisionPagination');
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
            ? `<li class="page-item"><a class="page-link" href="#" onclick="changeDivisionPage(${current - 1}); return false;">Previous</a></li>`
            : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

        for (let page = 1; page <= last; page++) {
            if (page === 1 || page === last || (page >= current - 2 && page <= current + 2)) {
                html += page === current
                    ? `<li class="page-item active"><span class="page-link">${page}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" onclick="changeDivisionPage(${page}); return false;">${page}</a></li>`;
            } else if (page === current - 3 || page === current + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += data.next_page_url
            ? `<li class="page-item"><a class="page-link" href="#" onclick="changeDivisionPage(${current + 1}); return false;">Next</a></li>`
            : '<li class="page-item disabled"><span class="page-link">Next</span></li>';

        html += '</ul></div>';
        container.innerHTML = html;
    }

    function initActions() {
        const container = document.getElementById('divisionActions');
        if (!container || document.getElementById('btnAddDivision')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'btnAddDivision';
        button.className = 'btn btn-dark-blue';
        button.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Add Division';
        button.addEventListener('click', () => openDivisionModal());
        container.appendChild(button);
    }

    function initFilters() {
        const searchInput = document.getElementById('divisionSearch');
        const perPageSelect = document.getElementById('divisionPerPage');
        let timeout;

        searchInput?.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => loadDivisions(1), 300);
        });

        perPageSelect?.addEventListener('change', function () {
            loadDivisions(1);
        });
    }

    function initModal() {
        if (document.getElementById('divisionModal')) return;

        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="divisionModal" tabindex="-1" aria-labelledby="divisionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <h5 class="modal-title" id="divisionModalLabel">Add Division</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="divisionModalAlert" class="alert d-none" role="alert"></div>
                            <form id="divisionForm">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Division Name</label>
                                    <input type="text" class="form-control" id="divisionName" name="name" placeholder="Enter division name">
                                    <div class="invalid-feedback d-block d-none" id="divisionNameError"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-dark-blue" id="btnSaveDivision" onclick="saveDivision()">
                                <span class="spinner-border spinner-border-sm d-none" id="divisionSaveSpinner"></span>
                                <span id="divisionSaveText">Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        document.getElementById('divisionForm')?.addEventListener('submit', function (event) {
            event.preventDefault();
            saveDivision();
        });

        document.getElementById('divisionName')?.addEventListener('input', clearDivisionErrors);
    }

    async function openDivisionModal(id = null) {
        currentEditId = id;
        clearDivisionErrors();

        const form = document.getElementById('divisionForm');
        const title = document.getElementById('divisionModalLabel');
        const saveText = document.getElementById('divisionSaveText');
        const nameInput = document.getElementById('divisionName');

        form.reset();
        title.textContent = id ? 'Edit Division' : 'Add Division';
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
                console.error('Division edit load error:', error);
                showToast('Error opening edit form. Please try again.', 'error');
                return;
            }
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('divisionModal')).show();
        nameInput.focus();
    }

    async function saveDivision() {
        const button = document.getElementById('btnSaveDivision');
        const spinner = document.getElementById('divisionSaveSpinner');
        const name = document.getElementById('divisionName').value.trim();

        clearDivisionErrors();

        if (name.length < 3) {
            setDivisionFieldError('Division name must be at least 3 characters');
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
                bootstrap.Modal.getInstance(document.getElementById('divisionModal'))?.hide();
                showToast(result.message || 'Division saved successfully', 'success');
                loadDivisions(currentPage);
                return;
            }

            if (result.errors?.name) {
                setDivisionFieldError(Array.isArray(result.errors.name) ? result.errors.name[0] : result.errors.name);
            } else {
                showModalAlert(result.message || 'Failed to save Division');
            }
        } catch (error) {
            console.error('Division save error:', error);
            showModalAlert('An error occurred. Please try again.');
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    async function updateDivisionStatus(id, status) {
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
                loadDivisions(currentPage);
                return;
            }

            showToast(result.message || 'Failed to update status', 'error');
            loadDivisions(currentPage);
        } catch (error) {
            console.error('Division status error:', error);
            showToast('An error occurred while updating status', 'error');
            loadDivisions(currentPage);
        }
    }

    async function deleteDivision(id, name) {
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
                    showToast(result.message || 'Division deleted successfully', 'success');
                    loadDivisions(currentPage);
                    return;
                }

                showToast(result.message || 'Failed to delete Division', 'error');
            } catch (error) {
                console.error('Division delete error:', error);
                showToast('An error occurred while deleting', 'error');
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff2d55',
                cancelButtonColor: '#003c82',
                confirmButtonText: 'Yes, delete it',
            }).then((result) => {
                if (result.isConfirmed) doDelete();
            });
            return;
        }

        window.crmConfirm(`Are you sure you want to delete "${name}"?`).then((result) => { if (result.isConfirmed) { doDelete(); } });
    }

    function clearDivisionErrors() {
        const input = document.getElementById('divisionName');
        const error = document.getElementById('divisionNameError');
        const alert = document.getElementById('divisionModalAlert');

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

    function setDivisionFieldError(message) {
        const input = document.getElementById('divisionName');
        const error = document.getElementById('divisionNameError');

        input?.classList.add('is-invalid');
        if (error) {
            error.textContent = message;
            error.classList.remove('d-none');
        }
    }

    function showModalAlert(message) {
        const alert = document.getElementById('divisionModalAlert');
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

    function changeDivisionPage(page) {
        loadDivisions(page);
    }

    function editDivision(id) {
        openDivisionModal(id);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return escapeHtml(text).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
    }

    window.changeDivisionPage = changeDivisionPage;
    window.editDivision = editDivision;
    window.deleteDivision = deleteDivision;
    window.updateDivisionStatus = updateDivisionStatus;
    window.saveDivision = saveDivision;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
