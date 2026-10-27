/**
 * Broker Master CRUD handler.
 */
(function () {
    'use strict';

    const API_BASE = '/api/masters/brokers';
    let currentEditId = null;
    let currentPage = 1;

    function init() {
        initModal();
        initActions();
        initFilters();
        loadBrokers();
    }

    async function loadBrokers(page = 1) {
        currentPage = page;
        const tbody = document.querySelector('#brokerTable tbody');
        if (!tbody) return;

        const search = document.getElementById('brokerSearch')?.value || '';
        const perPage = document.getElementById('brokerPerPage')?.value || '10';
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

        try {
            const response = await fetch(`${API_BASE}?search=${encodeURIComponent(search)}&per_page=${perPage}&page=${page}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + (localStorage.getItem('crm_auth_token') || ''),
                },
            });
            const result = await response.json();

            if (response.ok && (result.success || result.status)) {
                renderTable(result.data);
                renderPagination(result.data);
                return;
            }

            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${escapeHtml(result.message || 'Failed to load brokers')}</td></tr>`;
        } catch (error) {
            console.error('Broker load error:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Error loading brokers</td></tr>';
        }
    }

    function renderTable(paginated) {
        const tbody = document.querySelector('#brokerTable tbody');
        const rows = paginated?.data || [];

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No brokers found</td></tr>';
            return;
        }

        const start = (paginated.from || 1) - 1;
        tbody.innerHTML = rows.map((item, index) => {
            const serial      = start + index + 1;
            const active      = isActiveStatus(item.status);
            const statusClass = active ? 'status-active' : 'status-inactive';
            const statusLabel = active ? 'Active' : 'Inactive';
            const newStatus   = active ? 0 : 1;

            return `
                <tr>
                    <td class="ps-4 text-muted fw-semibold">${serial}</td>
                    <td class="fw-semibold">${escapeHtml(item.broker_name)}</td>
                    <td class="text-muted">${escapeHtml(item.email || '--')}</td>
                    <td class="text-muted">${escapeHtml(item.phone || '--')}</td>
                    <td>
                        <button class="doc-status-badge ${statusClass}"
                                onclick="updateBrokerStatus(${item.id}, ${newStatus})">
                            ${statusLabel}
                        </button>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">${formatDateTime(item.created_at)}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="doc-action-group">
                            <button type="button" class="doc-icon-btn btn-edit" onclick="editBroker(${item.id})" title="Edit">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="doc-icon-btn btn-del" onclick="deleteBroker(${item.id}, '${escapeAttribute(item.broker_name)}')" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <div class="doc-action-group justify-content-center">
                            <button type="button" class="doc-icon-btn btn-edit" onclick="editBroker(${item.id})" title="Edit">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="doc-icon-btn btn-del" onclick="deleteBroker(${item.id}, '${escapeAttribute(item.broker_name)}')" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(data) {
        const container = document.getElementById('brokerPagination');
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
            ? `<li class="page-item"><a class="page-link" href="#" onclick="changeBrokerPage(${current - 1}); return false;">Previous</a></li>`
            : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

        for (let page = 1; page <= last; page++) {
            if (page === 1 || page === last || (page >= current - 2 && page <= current + 2)) {
                html += page === current
                    ? `<li class="page-item active"><span class="page-link">${page}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" onclick="changeBrokerPage(${page}); return false;">${page}</a></li>`;
            } else if (page === current - 3 || page === current + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += data.next_page_url
            ? `<li class="page-item"><a class="page-link" href="#" onclick="changeBrokerPage(${current + 1}); return false;">Next</a></li>`
            : '<li class="page-item disabled"><span class="page-link">Next</span></li>';

        html += '</ul></div>';
        container.innerHTML = html;
    }

    function initActions() {
        const container = document.getElementById('brokerActions');
        if (!container || document.getElementById('btnAddBroker')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'btnAddBroker';
        button.className = 'btn btn-dark-blue btn-sm px-3 py-2';
        button.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Add New Broker';
        button.addEventListener('click', () => openBrokerModal());
        container.appendChild(button);
    }

    function initFilters() {
        const searchInput = document.getElementById('brokerSearch');
        const perPageSelect = document.getElementById('brokerPerPage');
        let timeout;

        searchInput?.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => loadBrokers(1), 300);
        });

        perPageSelect?.addEventListener('change', function () {
            loadBrokers(1);
        });
    }

    function initModal() {
        if (document.getElementById('brokerModal')) return;

        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="brokerModal" tabindex="-1" aria-labelledby="brokerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <h5 class="modal-title" id="brokerModalLabel">Add New Broker</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="brokerModalAlert" class="alert d-none" role="alert"></div>
                            <form id="brokerForm">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Broker Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="brokerName" name="broker_name" placeholder="Enter broker name">
                                    <div class="invalid-feedback d-block d-none" id="brokerNameError"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control" id="brokerEmail" name="email" placeholder="Enter email address (optional)">
                                    <div class="form-text">Optional field</div>
                                    <div class="invalid-feedback d-block d-none" id="brokerEmailError"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" class="form-control" id="brokerPhone" name="phone" placeholder="Enter phone number (optional)">
                                    <div class="form-text">Optional field</div>
                                    <div class="invalid-feedback d-block d-none" id="brokerPhoneError"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="brokerStatus" name="status">
                                        <option value="">Select Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback d-block d-none" id="brokerStatusError"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-dark-blue" id="btnSaveBroker" onclick="saveBroker()">
                                <span class="spinner-border spinner-border-sm d-none" id="brokerSaveSpinner"></span>
                                <span id="brokerSaveText">Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        document.getElementById('brokerForm')?.addEventListener('submit', function (event) {
            event.preventDefault();
            saveBroker();
        });

        ['brokerName', 'brokerEmail', 'brokerPhone', 'brokerStatus'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', clearBrokerErrors);
            document.getElementById(id)?.addEventListener('change', clearBrokerErrors);
        });
    }

    async function openBrokerModal(id = null) {
        currentEditId = id;
        clearBrokerErrors();

        const form = document.getElementById('brokerForm');
        const title = document.getElementById('brokerModalLabel');
        const saveText = document.getElementById('brokerSaveText');

        form.reset();
        document.getElementById('brokerStatus').value = '1';
        title.textContent = id ? 'Edit Broker' : 'Add New Broker';
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

                if (response.ok && (result.success || result.status)) {
                    document.getElementById('brokerName').value = result.data.broker_name || '';
                    document.getElementById('brokerEmail').value = result.data.email || '';
                    document.getElementById('brokerPhone').value = result.data.phone || '';
                    document.getElementById('brokerStatus').value = isActiveStatus(result.data.status) ? '1' : '0';
                }
            } catch (error) {
                console.error('Broker edit load error:', error);
                showToast('Error opening edit form. Please try again.', 'error');
                return;
            }
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('brokerModal')).show();
        document.getElementById('brokerName').focus();
    }

    async function saveBroker() {
        const button = document.getElementById('btnSaveBroker');
        const spinner = document.getElementById('brokerSaveSpinner');
        const name = document.getElementById('brokerName').value.trim();
        const email = document.getElementById('brokerEmail').value.trim();
        const phone = document.getElementById('brokerPhone').value.trim();
        const status = document.getElementById('brokerStatus').value;

        clearBrokerErrors();

        if (!name) {
            setBrokerFieldError('brokerName', 'Broker name is required');
            return;
        }

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setBrokerFieldError('brokerEmail', 'Invalid email format');
            return;
        }

        if (status === '') {
            setBrokerFieldError('brokerStatus', 'Status is required');
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
                body: JSON.stringify({
                    broker_name: name,
                    email: email || null,
                    phone: phone || null,
                    status: status === '1',
                }),
            });
            const result = await response.json();

            if (response.ok && (result.success || result.status)) {
                bootstrap.Modal.getInstance(document.getElementById('brokerModal'))?.hide();
                showToast(result.message || 'Broker saved successfully', 'success');
                loadBrokers(currentPage);
                return;
            }

            applyBrokerErrors(result.errors, result.message || 'Failed to save broker');
        } catch (error) {
            console.error('Broker save error:', error);
            showModalAlert('An error occurred. Please try again.');
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    async function updateBrokerStatus(id, status) {
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

            if (response.ok && (result.success || result.status)) {
                showToast(result.message || 'Status updated successfully', 'success');
                loadBrokers(currentPage);
                return;
            }

            showToast(result.message || 'Failed to update status', 'error');
            loadBrokers(currentPage);
        } catch (error) {
            console.error('Broker status error:', error);
            showToast('An error occurred while updating status', 'error');
            loadBrokers(currentPage);
        }
    }

    async function deleteBroker(id, name) {
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

                if (response.ok && (result.success || result.status)) {
                    showToast(result.message || 'Broker deleted successfully', 'success');
                    loadBrokers(currentPage);
                    return;
                }

                showToast(result.message || 'Failed to delete broker', 'error');
            } catch (error) {
                console.error('Broker delete error:', error);
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

    function applyBrokerErrors(errors, fallbackMessage) {
        if (!errors) {
            showModalAlert(fallbackMessage);
            return;
        }

        const map = {
            broker_name: 'brokerName',
            email: 'brokerEmail',
            phone: 'brokerPhone',
            status: 'brokerStatus',
        };
        let applied = false;

        Object.entries(errors).forEach(([key, messages]) => {
            if (!map[key]) return;
            applied = true;
            setBrokerFieldError(map[key], Array.isArray(messages) ? messages[0] : String(messages));
        });

        if (!applied) {
            showModalAlert(fallbackMessage);
        }
    }

    function clearBrokerErrors() {
        ['brokerName', 'brokerEmail', 'brokerPhone', 'brokerStatus'].forEach((fieldId) => {
            const input = document.getElementById(fieldId);
            const error = document.getElementById(fieldId + 'Error');
            input?.classList.remove('is-invalid');
            if (error) {
                error.textContent = '';
                error.classList.add('d-none');
            }
        });

        const alert = document.getElementById('brokerModalAlert');
        if (alert) {
            alert.textContent = '';
            alert.classList.add('d-none');
        }
    }

    function setBrokerFieldError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');

        input?.classList.add('is-invalid');
        if (error) {
            error.textContent = message;
            error.classList.remove('d-none');
        }
    }

    function showModalAlert(message) {
        const alert = document.getElementById('brokerModalAlert');
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

    function changeBrokerPage(page) {
        loadBrokers(page);
    }

    function editBroker(id) {
        openBrokerModal(id);
    }

    function isActiveStatus(status) {
        return status === true || status === 1 || status === '1' || status === 'active';
    }

    function formatDateTime(value) {
        if (!value) return '--';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return escapeHtml(value);

        const pad = (number) => String(number).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return escapeHtml(text).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
    }

    window.changeBrokerPage = changeBrokerPage;
    window.editBroker = editBroker;
    window.deleteBroker = deleteBroker;
    window.updateBrokerStatus = updateBrokerStatus;
    window.saveBroker = saveBroker;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
