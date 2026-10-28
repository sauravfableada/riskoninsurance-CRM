/**
 * Segments CRUD Handler
 * API Integration with modal for create/edit
 */
(function() {
    'use strict';

    const API_BASE = '/api/masters/segments';
    let currentEditId = null;
    const fieldMap = {
        insurance_type_id: 'insuranceTypeId',
        name: 'segmentName',
        description: 'segmentDescription',
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
     * Load insurance types for dropdown
     */
    async function loadInsuranceTypes(selectedId = null) {
        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/insurance-types`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();
            const select = document.getElementById('insuranceTypeId');

            if (response.ok && result.success && select) {
                select.innerHTML = '<option value="">Select Insurance Policy Type</option>' +
                    result.data.map(type => 
                        `<option value="${type.id}" ${selectedId == type.id ? 'selected' : ''}>${escapeHtml(type.name)}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Load insurance types error:', error);
        }
    }

    /**
     * Render table rows
     */
    function renderTable(data) {
        const tbody = document.querySelector('#leadsTable tbody');
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No segments found</td></tr>';
            return;
        }

        tbody.innerHTML = data.map((item, index) => {
            const createdBy = item.creator ? item.creator.name : 'System';
            const modifiedBy = item.modifier ? item.modifier.name : '--';
            const insuranceTypeName = item.insurance_type ? item.insurance_type.name : '--';

            return `
                <tr>
                    <td class="ps-4">${index + 1}</td>
                    <td>${escapeHtml(insuranceTypeName)}</td>
                    <td>${escapeHtml(item.name)}</td>
                    <td class="d-none d-md-table-cell">${escapeHtml(createdBy)}</td>
                    <td class="d-none d-md-table-cell">${escapeHtml(modifiedBy)}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            <button class="btn crm-action-btn btn-sm me-1" onclick="editSegment(${item.id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn crm-action-btn btn-sm text-danger" onclick="deleteSegment(${item.id}, '${escapeHtml(item.name)}')" title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button class="btn crm-action-btn btn-sm" onclick="editSegment(${item.id})" title="Edit">
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
            html += `<li class="page-item"><a class="page-link" href="#" onclick="changePageSegment(${currentPage - 1}); return false;">Previous</a></li>`;
        } else {
            html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }

        for (let i = 1; i <= lastPage; i++) {
            if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += i === currentPage
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" onclick="changePageSegment(${i}); return false;">${i}</a></li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        if (data.next_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="changePageSegment(${currentPage + 1}); return false;">Next</a></li>`;
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

        const actionsContainer = document.getElementById('segmentActions') || document.querySelector('.card-header .d-flex.flex-wrap.gap-2');
        if (actionsContainer && !document.getElementById('btnAddSegment')) {
            const addBtn = document.createElement('button');
            addBtn.id = 'btnAddSegment';
            addBtn.className = 'btn btn-dark-blue btn-sm px-3 master-header-btn';
            addBtn.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Add Segment';
            addBtn.onclick = () => openModalSegment();
            actionsContainer.appendChild(addBtn);
        }

        const form = document.getElementById('segmentForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
            form.addEventListener('input', function(e) {
                const field = e.target;
                if (field instanceof HTMLElement && field.id) {
                    clearFieldError(field.id);
                }
            });
            form.addEventListener('change', function(e) {
                const field = e.target;
                if (field instanceof HTMLElement && field.id) {
                    clearFieldError(field.id);
                }
            });
        }
    }

    /**
     * Initialize modal HTML
     */
    function initModal() {
        if (document.getElementById('segmentModal')) return;

        const modalHtml = `
        <div class="modal fade" id="segmentModal" tabindex="-1" aria-labelledby="segmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="segmentModalLabel">Add Segment</h5>
                        <button type="button" class="btn-close" onclick="closeModalSegment()" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modalAlert" class="alert d-none" role="alert"></div>
                        <form id="segmentForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Insurance Policy Type <span class="text-danger">*</span></label>
                                <select name="insurance_type_id" id="insuranceTypeId" class="form-select" required>
                                    <option value="">Select Insurance Policy Type</option>
                                </select>
                                <div class="invalid-feedback d-block d-none" id="insuranceTypeIdError"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="segmentName" class="form-control" required>
                                <div class="invalid-feedback d-block d-none" id="segmentNameError"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" id="segmentDescription" class="form-control" rows="3"></textarea>
                                <div class="invalid-feedback d-block d-none" id="segmentDescriptionError"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark-blue" onclick="closeModalSegment()">Cancel</button>
                        <button type="button" class="btn btn-dark-blue" id="btnSaveSegment" onclick="saveSegment()">
                            <span class="spinner-border spinner-border-sm d-none" id="saveSpinner"></span>
                            <span id="saveBtnText">Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modal = document.getElementById('segmentModal');
        modal.addEventListener('hidden.bs.modal', cleanupSegmentModalState);
    }

    function cleanupSegmentModalState() {
        if (document.querySelector('.modal.show')) return;

        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
    }

    /**
     * Open modal for create/edit
     */
    async function openModalSegment(id = null) {
        currentEditId = id;
        const modal = document.getElementById('segmentModal');
        const title = document.getElementById('segmentModalLabel');
        const btnText = document.getElementById('saveBtnText');
        const alert = document.getElementById('modalAlert');

        document.getElementById('segmentForm').reset();
        alert.classList.add('d-none');
        clearFormErrors();

        await loadInsuranceTypes();

        if (id) {
            title.textContent = 'Edit Segment';
            btnText.textContent = 'Update';

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
                    document.getElementById('insuranceTypeId').value = result.data.insurance_type_id;
                    document.getElementById('segmentName').value = result.data.name;
                    document.getElementById('segmentDescription').value = result.data.description || '';
                }
            } catch (error) {
                console.error('Load item error:', error);
            }
        } else {
            title.textContent = 'Add Segment';
            btnText.textContent = 'Save';
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = bootstrap.Modal.getOrCreateInstance(modal);
            modalEl.show();
        } else {
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

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
    function closeModalSegment() {
        const modal = document.getElementById('segmentModal');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        } else {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');

            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
            cleanupSegmentModalState();
        }
    }

    /**
     * Handle form submit
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        await saveSegment();
    }

    /**
     * Save segment
     */
    async function saveSegment() {
        const btn = document.getElementById('btnSaveSegment');
        const spinner = document.getElementById('saveSpinner');
        const alert = document.getElementById('modalAlert');
        const insuranceTypeId = document.getElementById('insuranceTypeId').value;
        const name = document.getElementById('segmentName').value.trim();
        const description = document.getElementById('segmentDescription').value.trim();

        clearFormErrors();

        if (!insuranceTypeId) {
            setFieldError('insuranceTypeId', 'Please select an insurance policy type');
            return;
        }

        if (!name) {
            setFieldError('segmentName', 'Name is required');
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
                body: JSON.stringify({
                    insurance_type_id: insuranceTypeId,
                    name: name,
                    description: description
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                closeModalSegment();

                if (window.DashboardAPI?.showToast) {
                    window.DashboardAPI.showToast(result.message || 'Saved successfully', 'success');
                } else {
                    window.crmAlert(result.message || 'Saved successfully');
                }

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
     * Edit item
     */
    async function editSegment(id) {
        // console.log('Edit segment clicked for id:', id);
        try {
            await openModalSegment(id);
        } catch (error) {
            console.error('Error opening edit modal:', error);
            window.crmAlert('Error opening edit form. Please try again.');
        }
    }

    /**
     * Delete item with SweetAlert
     */
    async function deleteSegment(id, name) {
        // console.log('Delete segment clicked for id:', id, 'name:', name);
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
    function changePageSegment(page) {
        const searchInput = document.getElementById('leadsSearch');
        loadData(searchInput ? searchInput.value : '', page);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        let html = div.innerHTML;
        html = html.replace(/'/g, "&#39;");
        return html;
    }

    // Expose functions globally
    window.editSegment = editSegment;
    window.deleteSegment = deleteSegment;
    window.changePageSegment = changePageSegment;
    window.saveSegment = saveSegment;
    window.closeModalSegment = closeModalSegment;

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
