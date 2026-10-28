/**
 * Insurance Policy Type CRUD Handler
 * API Integration with modal for create/edit
 */
(function() {
    'use strict';

    const API_BASE = '/api/masters/insurance-policy-types';
    let currentEditId = null;
    const fieldMap = {
        name: 'policyTypeName',
    };

    /**
     * Initialize module
     */
    function init() {
        loadData();
        initModal();
        initEventListeners();
    }

    /**
     * Load data from API
     */
    async function loadData(search = '', page = 1) {
        const tbody = document.querySelector('#leadsTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}?search=${encodeURIComponent(search)}&page=${page}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                renderTable(result.data.data || []);
                renderPagination(result.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${result.message || 'Failed to load data'}</td></tr>`;
            }
        } catch (error) {
            console.error('Load error:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Error loading data</td></tr>';
        }
    }

    /**
     * Render table rows
     */
    function renderTable(data) {
        const tbody = document.querySelector('#leadsTable tbody');
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No insurance policy types found</td></tr>';
            return;
        }

        tbody.innerHTML = data.map((item, index) => {
            const createdBy = item.creator ? item.creator.name : 'System';
            const modifiedBy = item.modifier ? item.modifier.name : '--';
            const statusBadge = item.status
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>';

            return `
                <tr>
                    <td class="ps-4">${index + 1}</td>
                    <td>${escapeHtml(item.name)}</td>
                    <td class="d-none d-md-table-cell">${escapeHtml(createdBy)}</td>
                    <td class="d-none d-md-table-cell">${escapeHtml(modifiedBy)}</td>
                    <td class="d-none d-md-table-cell">${statusBadge}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            <button class="btn crm-action-btn btn-sm me-1" onclick="editPolicyType(${item.id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn crm-action-btn btn-sm text-danger" onclick="deletePolicyType(${item.id}, '${escapeHtml(item.name)}')" title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button class="btn crm-action-btn btn-sm" onclick="editPolicyType(${item.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * Render pagination
     */
    function renderPagination(data) {
        const container = document.getElementById('leadsPagination');
        if (!container || !data.last_page) {
            if (container) container.innerHTML = '';
            return;
        }

        if ((data.total || 0) <= 0) {
            if (container) container.innerHTML = '';
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
                <ul class="pagination crm-pagination mb-0">
        `;

        if (data.prev_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }

        for (let i = 1; i <= lastPage; i++) {
            if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += i === currentPage
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a></li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        if (data.next_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }

        html += '</ul></div>';
        container.innerHTML = html;
    }

    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        // Search
        const searchInput = document.getElementById('leadsSearch');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    loadData(this.value, 1);
                }, 300);
            });
        }

        // Add button click - check if button exists, if not create one
        const actionsContainer = document.getElementById('policyTypeActions') || document.querySelector('.card-header .d-flex.flex-wrap.gap-2');
        if (actionsContainer && !document.getElementById('btnAddPolicyType')) {
            const addBtn = document.createElement('button');
            addBtn.id = 'btnAddPolicyType';
            addBtn.className = 'btn btn-dark-blue btn-sm px-3 master-header-btn';
            addBtn.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Add Policy Type';
            addBtn.onclick = () => openModal();
            actionsContainer.appendChild(addBtn);
        }

        // Modal form submit
        const form = document.getElementById('policyTypeForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
            form.addEventListener('input', function(e) {
                const field = e.target;
                if (field instanceof HTMLElement && field.id) {
                    clearFieldError(field.id);
                }
            });
        }
    }

    /**
     * Initialize modal HTML if not exists
     */
    function initModal() {
        if (document.getElementById('policyTypeModal')) return;

        const modalHtml = `
        <div class="modal fade" id="policyTypeModal" tabindex="-1" aria-labelledby="policyTypeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="policyTypeModalLabel">Add Insurance Policy Type</h5>
                        <button type="button" class="btn-close" onclick="closeModal()" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modalAlert" class="alert d-none" role="alert"></div>
                        <form id="policyTypeForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="policyTypeName" class="form-control" required>
                                <div class="invalid-feedback d-block d-none" id="policyTypeNameError"></div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="policyTypeStatus" name="status" checked>
                                    <label class="form-check-label" for="policyTypeStatus">Active</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark-blue" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-dark-blue" id="btnSavePolicyType" onclick="savePolicyType()">
                            <span class="spinner-border spinner-border-sm d-none" id="saveSpinner"></span>
                            <span id="saveBtnText">Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    /**
     * Open modal for create/edit
     */
    async function openModal(id = null) {
        currentEditId = id;
        const modal = document.getElementById('policyTypeModal');
        const title = document.getElementById('policyTypeModalLabel');
        const btnText = document.getElementById('saveBtnText');
        const alert = document.getElementById('modalAlert');

        // Reset form
        document.getElementById('policyTypeForm').reset();
        alert.classList.add('d-none');
        clearFormErrors();

        if (id) {
            title.textContent = 'Edit Insurance Policy Type';
            btnText.textContent = 'Update';

            // Load data
            try {
                const token = localStorage.getItem('crm_auth_token');
                const response = await fetch(`${API_BASE}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    document.getElementById('policyTypeName').value = result.data.name;
                    document.getElementById('policyTypeStatus').checked = result.data.status;
                }
            } catch (error) {
                console.error('Load item error:', error);
            }
        } else {
            title.textContent = 'Add Insurance Policy Type';
            btnText.textContent = 'Save';
        }

        // Show modal - use Bootstrap if available, otherwise manual
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = new bootstrap.Modal(modal);
            modalEl.show();
        } else {
            // Manual show
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            // Add backdrop
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }
    }

    /**
     * Close modal
     */
    function closeModal() {
        const modal = document.getElementById('policyTypeModal');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = bootstrap.Modal.getInstance(modal);
            if (modalEl) modalEl.hide();
        } else {
            // Manual hide
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');

            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        }
    }

    /**
     * Handle form submit
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        await savePolicyType();
    }

    /**
     * Save policy type (create or update)
     */
    async function savePolicyType() {
        const btn = document.getElementById('btnSavePolicyType');
        const spinner = document.getElementById('saveSpinner');
        const alert = document.getElementById('modalAlert');
        const name = document.getElementById('policyTypeName').value.trim();
        const status = document.getElementById('policyTypeStatus').checked;

        clearFormErrors();

        if (!name) {
            setFieldError('policyTypeName', 'Name is required');
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('d-none');
        alert.classList.add('d-none');

        try {
            const token = localStorage.getItem('crm_auth_token');
            const url = currentEditId ? `${API_BASE}/${currentEditId}` : API_BASE;
            const method = currentEditId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                },
                body: JSON.stringify({ name, status })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Hide modal
                closeModal();

                // Show success toast
                if (window.DashboardAPI?.showToast) {
                    window.DashboardAPI.showToast(result.message || 'Saved successfully', 'success');
                } else {
                    window.crmAlert(result.message || 'Saved successfully');
                }

                // Reload data
                const searchInput = document.getElementById('leadsSearch');
                loadData(searchInput ? searchInput.value : '', 1);
            } else {
                if (result.errors) {
                    applyServerValidationErrors(result.errors);
                } else {
                    alert.className = 'alert alert-danger';
                    alert.textContent = result.message || 'Failed to save';
                    alert.classList.remove('d-none');
                }
            }
        } catch (error) {
            console.error('Save error:', error);
            alert.className = 'alert alert-danger';
            alert.textContent = 'An error occurred. Please try again.';
            alert.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    function clearFormErrors() {
        Object.values(fieldMap).forEach(clearFieldError);
        const alert = document.getElementById('modalAlert');
        if (alert) {
            alert.classList.add('d-none');
            alert.textContent = '';
        }
    }

    function clearFieldError(fieldId) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        if (input) {
            input.classList.remove('is-invalid');
        }
        if (error) {
            error.textContent = '';
            error.classList.add('d-none');
        }
    }

    function setFieldError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        if (input) {
            input.classList.add('is-invalid');
        }
        if (error) {
            error.textContent = message;
            error.classList.remove('d-none');
        }
    }

    function applyServerValidationErrors(errors) {
        let hasFieldError = false;
        Object.entries(errors || {}).forEach(([key, messages]) => {
            const fieldId = fieldMap[key];
            if (!fieldId) return;
            hasFieldError = true;
            setFieldError(fieldId, Array.isArray(messages) ? messages[0] : String(messages));
        });

        if (!hasFieldError) {
            const alert = document.getElementById('modalAlert');
            if (alert) {
                const list = Object.values(errors || {}).flat();
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<ul class="mb-0">' + list.map(msg => `<li>${msg}</li>`).join('') + '</ul>';
                alert.classList.remove('d-none');
            }
        }
    }

    /**
     * Edit policy type
     */
    async function editPolicyType(id) {
        // console.log('Edit policy type clicked for id:', id);
        try {
            await openModal(id);
        } catch (error) {
            console.error('Error opening edit modal:', error);
            window.crmAlert('Error opening edit form. Please try again.');
        }
    }

    /**
     * Delete policy type with SweetAlert confirmation
     */
    async function deletePolicyType(id, name) {
        // console.log('Delete policy type clicked for id:', id, 'name:', name);
        const doDelete = async () => {
            try {
                const token = localStorage.getItem('crm_auth_token');
                const response = await fetch(`${API_BASE}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: result.message || 'Deleted successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else if (window.DashboardAPI?.showToast) {
                        window.DashboardAPI.showToast(result.message || 'Deleted successfully', 'success');
                    } else {
                        window.crmAlert(result.message || 'Deleted successfully');
                    }

                    const searchInput = document.getElementById('leadsSearch');
                    loadData(searchInput ? searchInput.value : '', 1);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Failed to delete'
                        });
                    } else {
                        window.crmAlert(result.message || 'Failed to delete');
                    }
                }
            } catch (error) {
                console.error('Delete error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while deleting'
                    });
                } else {
                    window.crmAlert('An error occurred while deleting');
                }
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    doDelete();
                }
            });
        } else {
            window.crmConfirm(`Are you sure you want to delete "${name}"?`).then((result) => { if (result.isConfirmed) { doDelete(); } });
        }
    }

    /**
     * Change page
     */
    function changePage(page) {
        const searchInput = document.getElementById('leadsSearch');
        loadData(searchInput ? searchInput.value : '', page);
    }

    /**
     * Escape HTML and quotes for safe use in onclick handlers
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        let html = div.innerHTML;
        // Also escape single quotes for onclick handlers
        html = html.replace(/'/g, "&#39;");
        return html;
    }

    // Expose functions globally
    window.editPolicyType = editPolicyType;
    window.deletePolicyType = deletePolicyType;
    window.changePage = changePage;
    window.savePolicyType = savePolicyType;
    window.closeModal = closeModal;

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
