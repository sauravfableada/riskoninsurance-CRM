/**
 * Family Groups CRUD Handler
 * API Integration with modal for create/edit and member management
 */
(function() {
    'use strict';

    const API_BASE = '/api/masters/family-groups';
    let currentEditId = null;
    let memberCounter = 0;

    /**
     * Initialize module
     */
    function init() {
        loadData();
        initEventListeners();
        initPolicyHolderFilter();
        initModal();
        initPolicyHolderModal();
    }

    async function initPolicyHolderFilter() {
        const select = document.getElementById('filterHolderId');
        if (!select) return;

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/customers`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });
            const result = await response.json();

            if (response.ok && result.success) {
                select.innerHTML = '<option value="">All Policy Holders</option>' +
                    result.data.map(customer =>
                        `<option value="${customer.id}">${escapeHtml(customer.name)}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Load policy holder filter error:', error);
        }

        if (window.jQuery && jQuery.fn.select2) {
            const $select = jQuery(select);
            $select.select2({
                placeholder: 'All Policy Holders',
                allowClear: true,
                width: '100%'
            });

            // Select2 emits its change through jQuery, which is not reliably
            // received by a native addEventListener handler in every browser.
            $select.off('change.familyGroupFilter').on('change.familyGroupFilter', function() {
                const searchInput = document.getElementById('leadsSearch');
                loadData(searchInput ? searchInput.value : '', 1);
            });
        }
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
            let url = `${API_BASE}?search=${encodeURIComponent(search)}&page=${page}`;
            
            const filterFromDate = document.getElementById('filterFromDate');
            const filterToDate = document.getElementById('filterToDate');
            const filterHolderId = document.getElementById('filterHolderId');
            const filterGroupsName = document.getElementById('filterGroupsName');
            const filterCreatedBy = document.getElementById('filterCreatedBy');
            const filterModifiedBy = document.getElementById('filterModifiedBy');
            const perPageSelect = document.getElementById('perPageSelect');

            if (filterHolderId && filterHolderId.value) {
                url += `&holder_id=${encodeURIComponent(filterHolderId.value)}`;
            }
            if (filterGroupsName && filterGroupsName.value) {
                url += `&group_name=${encodeURIComponent(filterGroupsName.value)}`;
            }
            if (filterCreatedBy && filterCreatedBy.value) {
                url += `&created_by=${encodeURIComponent(filterCreatedBy.value)}`;
            }
            if (filterModifiedBy && filterModifiedBy.value) {
                url += `&modified_by=${encodeURIComponent(filterModifiedBy.value)}`;
            }
            if (filterFromDate && filterFromDate.value) {
                url += `&from_date=${encodeURIComponent(filterFromDate.value)}`;
            }
            if (filterToDate && filterToDate.value) {
                url += `&to_date=${encodeURIComponent(filterToDate.value)}`;
            }
            if (perPageSelect && perPageSelect.value) {
                url += `&per_page=${encodeURIComponent(perPageSelect.value)}`;
            }
            
            const response = await fetch(url, {
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
     * Load customers for dropdown
     */
    async function loadCustomers(selectedId = null) {
        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/customers`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();
            const select = document.getElementById('holderId');

            if (response.ok && result.success && select) {
                select.innerHTML = '<option value="">Select Policy Holder</option>' +
                    result.data.map(customer => 
                        `<option value="${customer.id}" ${selectedId == customer.id ? 'selected' : ''}>${escapeHtml(customer.name)}</option>`
                    ).join('');
                if (selectedId !== null) select.value = selectedId;
                if (window.jQuery && jQuery.fn.select2) jQuery(select).trigger('change.select2');
            }
        } catch (error) {
            console.error('Load customers error:', error);
        }
    }

    /**
     * Render table rows
     */
    function renderTable(data) {
        const tbody = document.querySelector('#leadsTable tbody');
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5">' +
                '<div class="text-muted mb-3"><i class="bi bi-people display-1 opacity-25"></i></div>' +
                '<p class="text-muted">No family groups found.</p>' +
                '<button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-4" onclick="openModalFamilyGroup()">Add Your First Family Group</button>' +
                '</td></tr>';
            return;
        }

        tbody.innerHTML = data.map((item, index) => {
            const createdBy = item.creator ? item.creator.name : 'System';
            const modifiedBy = item.modifier ? item.modifier.name : '--';
            const holderName = item.holder ? item.holder.name : '--';

            const actions = `
                <button class="btn btn-sm crm-action-btn me-1" onclick="editFamilyGroup(${item.id})" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm  crm-action-btn" onclick="deleteFamilyGroup(${item.id}, '${escapeHtml(item.group_name)}')" title="Delete">
                    <i class="bi bi-trash text-danger"></i>
                </button>
            `;

            return `
                <tr>
                    <td class="ps-4">
                        <span class="text-muted small fw-medium">${index + 1}</span>
                    </td>
                    <td>
                        <div class="fw-bold small">${escapeHtml(item.group_name)}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="small">${escapeHtml(holderName)}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="small">${escapeHtml(item.remarks || '--')}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="small fw-semibold text-nowrap">${escapeHtml(createdBy)}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="small fw-semibold text-nowrap">${escapeHtml(modifiedBy)}</div>
                    </td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            ${actions}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-family-id="${item.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="family-details-${item.id}" style="display: none;">
                    <td colspan="4" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-id-card"></i> Policy Holder :</div>
                                    <div class="expand-value">${escapeHtml(holderName)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-comment-dots"></i> Remarks :</div>
                                    <div class="expand-value">${escapeHtml(item.remarks || "--")}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user-plus"></i> Created By :</div>
                                    <div class="expand-value">${escapeHtml(createdBy)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user-pen"></i> Modified By :</div>
                                    <div class="expand-value">${escapeHtml(modifiedBy)}</div>
                                </div>
                             </div>
                             <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                 <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                 <div class="d-flex gap-2">
                                     ${actions}
                                 </div>
                             </div>
                         </div>
                    </td>
                </tr>
            `;
        }).join('');

        // attach expand
        tbody.querySelectorAll(".btn-user-expand").forEach(button => {
            button.addEventListener("click", function () {
                const id = this.dataset.familyId;
                const detailsRow = document.getElementById(`family-details-${id}`);
                const icon = this.querySelector("i");
                
                if (detailsRow.style.display === "none") {
                    detailsRow.style.display = "table-row";
                    icon.classList.replace("fa-plus", "fa-minus");
                    this.classList.add("active");
                } else {
                    detailsRow.style.display = "none";
                    icon.classList.replace("fa-minus", "fa-plus");
                    this.classList.remove("active");
                }
            });
        });
    }

    /**
     * Render pagination
     */
    function renderPagination(data) {
        const container = document.getElementById('leadsPagination');
        if (!container) return;

        if (!data.total || data.total === 0) {
            container.innerHTML = '';
            return;
        }

        const from = data.from || 0;
        const to = data.to || 0;
        const total = data.total || 0;
        const currentPage = data.current_page || 1;
        const lastPage = data.last_page || 1;

        let html = `
        <div class="crm-pagination-container">
            <div class="text-muted small">
                Showing ${from} to ${to} of ${total} results
            </div>
            <ul class="pagination crm-pagination mb-0">
        `;

        if (data.prev_page_url) {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="changePageFamilyGroup(${currentPage - 1}); return false;">Previous</a>
            </li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
        }

        for (let i = 1; i <= lastPage; i++) {
            if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += i === currentPage
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" onclick="changePageFamilyGroup(${i}); return false;">${i}</a></li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        if (data.next_page_url) {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="changePageFamilyGroup(${currentPage + 1}); return false;">Next</a>
            </li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
        }

        html += '</ul></div>';
        container.innerHTML = html;
    }

    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        const searchInput = document.getElementById('leadsSearch');
        const filterFromDate = document.getElementById('filterFromDate');
        const filterToDate = document.getElementById('filterToDate');
        const filterHolderId = document.getElementById('filterHolderId');
        const filterGroupsName = document.getElementById('filterGroupsName');
        const filterCreatedBy = document.getElementById('filterCreatedBy');
        const filterModifiedBy = document.getElementById('filterModifiedBy');
        const filterDateRange = document.getElementById('filterDateRange');
        const perPageSelect = document.getElementById('perPageSelect');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');

        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    loadData(this.value, 1);
                }, 400);
            });
        }
        
        if (filterGroupsName) {
            let timeout;
            filterGroupsName.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    loadData(searchInput ? searchInput.value : '', 1);
                }, 400);
            });
        }
        
        // Old date logic removed as not standard
        
        if (perPageSelect) {
            perPageSelect.addEventListener("change", () => loadData(searchInput ? searchInput.value : '', 1));
        }

        // The Select2 policy-holder change is bound in initPolicyHolderFilter().
        [filterFromDate, filterToDate, filterCreatedBy, filterModifiedBy].forEach((filterInput) => {
            if (filterInput) {
                filterInput.addEventListener("change", () => setTimeout(() => loadData(searchInput ? searchInput.value : '', 1), 0));
            }
        });

        if (filterDateRange) {
            filterDateRange.addEventListener('change', function() {
                const today = new Date();
                let from = "";
                let to = "";
                
                if (this.value === "today") {
                    from = to = today.toISOString().split("T")[0];
                } else if (this.value === "this_week") {
                    const first = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
                    const last = first + 6;
                    from = new Date(new Date(today).setDate(first)).toISOString().split("T")[0];
                    to = new Date(new Date(today).setDate(last)).toISOString().split("T")[0];
                } else if (this.value === "this_month") {
                    from = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split("T")[0];
                    to = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split("T")[0];
                } else if (this.value === "this_year") {
                    from = new Date(today.getFullYear(), 0, 1).toISOString().split("T")[0];
                    to = new Date(today.getFullYear(), 11, 31).toISOString().split("T")[0];
                }
                
                if (filterFromDate) filterFromDate.value = from;
                if (filterToDate) filterToDate.value = to;
                
                loadData(searchInput ? searchInput.value : '', 1);
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener("click", () => {
                if (filterFromDate) filterFromDate.value = "";
                if (filterToDate) filterToDate.value = "";
                if (filterGroupsName) filterGroupsName.value = "";
                if (filterCreatedBy) filterCreatedBy.value = "";
                if (filterModifiedBy) filterModifiedBy.value = "";
                if (filterHolderId) {
                    filterHolderId.value = "";
                    if (window.jQuery && jQuery.fn.select2) jQuery(filterHolderId).trigger('change.select2');
                }
                if (filterDateRange) filterDateRange.value = "";
                if (searchInput) searchInput.value = "";
                loadData('', 1);
            });
        }

        const cardHeader = document.querySelector('.card-header');
        if (cardHeader && !document.getElementById('btnAddFamilyGroup')) {
            const addBtn = document.createElement('button');
            addBtn.id = 'btnAddFamilyGroup';
            addBtn.className = 'btn btn-dark-blue';
            addBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Add Family Group';
            addBtn.onclick = () => openModalFamilyGroup();
            cardHeader.querySelector('div').appendChild(addBtn);
        }

        const form = document.getElementById('familyGroupForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }

        // Add member button
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'btnAddMember') {
                addMemberRow();
            }
            // Check if clicked element or its parent is the remove member button
            const removeBtn = e.target.closest('.btn-remove-member');
            if (removeBtn) {
                removeMemberRow(removeBtn.dataset.index);
            }
        });
    }

    /**
     * Initialize modal HTML
     */
    function initModal() {
        if (document.getElementById('familyGroupModal')) return;

        const modalHtml = `
        <div class="modal fade" id="familyGroupModal" tabindex="-1" aria-labelledby="familyGroupModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="familyGroupModalLabel">Add Family Group</h5>
                        <button type="button" class="btn-close" onclick="closeModalFamilyGroup()" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="familyGroupForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Group Name <span class="text-danger">*</span></label>
                                    <input type="text" name="group_name" id="groupName" class="form-control">
                                    <div class="invalid-feedback staff-validation" id="group_name_error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Policy Holder</label>
                                    <div class="d-flex align-items-center policy-holder-control">
                                        <div class="policy-holder-select-wrap flex-grow-1">
                                            <select name="holder_id" id="holderId" class="form-select">
                                                <option value="">Select Policy Holder</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-dark-blue px-3" id="addPolicyHolderBtn" onclick="openPolicyHolderModal()"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Family Members</h6>
                                <button type="button" class="btn btn-outline-dark-blue" id="btnAddMember">
                                    <i class="bi bi-plus-lg"></i> Add Member
                                </button>
                            </div>
                            <div class="row mb-2 px-2">
                                <div class="col-md-3"><label class="form-label small fw-bold text-muted">Member Name</label></div>
                                <div class="col-md-2"><label class="form-label small fw-bold text-muted">Relation</label></div>
                                <div class="col-md-2"><label class="form-label small fw-bold text-muted">Contact No.</label></div>
                                <div class="col-md-2"><label class="form-label small fw-bold text-muted">Date of Birth</label></div>
                                <div class="col-md-2"><label class="form-label small fw-bold text-muted">Anniversary</label></div>
                                <div class="col-md-1"></div>
                            </div>
                            <div id="membersContainer">
                                <!-- Member rows will be added here -->
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark-blue" onclick="closeModalFamilyGroup()">Cancel</button>
                        <button type="button" class="btn btn-dark-blue" id="btnSaveFamilyGroup" onclick="saveFamilyGroup()">
                            <span class="spinner-border spinner-border-sm d-none" id="saveSpinner"></span>
                            <span id="saveBtnText">Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        initHolderSelect2();
    }

    function initHolderSelect2() {
        if (!window.jQuery || !jQuery.fn.select2) return;
        const $holder = jQuery('#holderId');
        if (!$holder.length) return;
        if ($holder.hasClass('select2-hidden-accessible')) $holder.select2('destroy');
        $holder.select2({
            width: '100%',
            placeholder: 'Select Policy Holder',
            allowClear: true,
            dropdownParent: jQuery('#familyGroupModal')
        });
    }

    /**
     * Add member row
     */
    function addMemberRow(member = null) {
        memberCounter++;
        const container = document.getElementById('membersContainer');
        const index = memberCounter;

        const row = document.createElement('div');
        row.className = 'row member-row mb-2';
        row.dataset.index = index;
        row.innerHTML = `
            <div class="col-md-3">
                <input type="text" name="members[${index}][member_name]" class="form-control form-control-sm" placeholder="Member Name" value="${escapeHtml(member ? member.member_name : '')}" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="members[${index}][relation]" class="form-control form-control-sm" placeholder="Relation" value="${escapeHtml(member ? member.relation : '')}">
            </div>
            <div class="col-md-2">
                <input type="text" name="members[${index}][contact]" class="form-control form-control-sm" placeholder="Contact" value="${escapeHtml(member ? member.contact : '')}">
            </div>
            <div class="col-md-2">
                <input type="date" name="members[${index}][dob]" class="form-control form-control-sm" placeholder="DOB" value="${member ? member.dob : ''}">
            </div>
            <div class="col-md-2">
                <input type="date" name="members[${index}][anniversary]" class="form-control form-control-sm" placeholder="Anniversary" value="${member ? member.anniversary : ''}">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm crm-action-btn btn-remove-member" data-index="${index}">
                    <i class="bi bi-trash text-danger"></i>
                </button>
            </div>
        `;

        container.appendChild(row);
    }

    /**
     * Remove member row
     */
    function removeMemberRow(index) {
        const row = document.querySelector(`.member-row[data-index="${index}"]`);
        if (row) {
            row.remove();
        }
    }

    /**
     * Clear all member rows
     */
    function clearMemberRows() {
        const container = document.getElementById('membersContainer');
        if (container) {
            container.innerHTML = '';
        }
        memberCounter = 0;
    }

    /**
     * Open modal for create/edit
     */
    async function openModalFamilyGroup(id = null) {
        currentEditId = id;
        const modal = document.getElementById('familyGroupModal');
        const title = document.getElementById('familyGroupModalLabel');
        const btnText = document.getElementById('saveBtnText');

        document.getElementById('familyGroupForm').reset();
        
        // Remove validation classes
        document.querySelectorAll('#familyGroupForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        clearMemberRows();

        await loadCustomers();

        if (id) {
            title.textContent = 'Edit Family Group';
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
                    document.getElementById('groupName').value = result.data.group_name;
                    document.getElementById('holderId').value = result.data.holder_id || '';
                    if (window.jQuery && jQuery.fn.select2) jQuery('#holderId').trigger('change.select2');
                    document.getElementById('remarks').value = result.data.remarks || '';

                    // Load existing members
                    if (result.data.members && result.data.members.length > 0) {
                        result.data.members.forEach(member => {
                            addMemberRow(member);
                        });
                    }
                }
            } catch (error) {
                console.error('Load item error:', error);
            }
        } else {
            title.textContent = 'Add Family Group';
            btnText.textContent = 'Save';
            // Add one empty member row by default
            addMemberRow();
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = new bootstrap.Modal(modal);
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
    function closeModalFamilyGroup() {
        const modal = document.getElementById('familyGroupModal');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = bootstrap.Modal.getInstance(modal);
            if (modalEl) modalEl.hide();
        } else {
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
        await saveFamilyGroup();
    }

    /**
     * Collect members data from form
     */
    function collectMembersData() {
        const rows = document.querySelectorAll('.member-row');
        const members = [];

        rows.forEach(row => {
            const nameInput = row.querySelector('input[name*="[member_name]"]');
            if (nameInput && nameInput.value.trim()) {
                const index = row.dataset.index;
                members.push({
                    member_name: nameInput.value.trim(),
                    relation: row.querySelector(`input[name="members[${index}][relation]"]`)?.value || null,
                    contact: row.querySelector(`input[name="members[${index}][contact]"]`)?.value || null,
                    dob: row.querySelector(`input[name="members[${index}][dob]"]`)?.value || null,
                    anniversary: row.querySelector(`input[name="members[${index}][anniversary]"]`)?.value || null,
                });
            }
        });

        return members;
    }

    /**
     * Save family group
     */
    async function saveFamilyGroup() {
        const btn = document.getElementById('btnSaveFamilyGroup');
        const spinner = document.getElementById('saveSpinner');
        
        const groupNameInput = document.getElementById('groupName');
        const groupName = groupNameInput.value.trim();
        const holderId = document.getElementById('holderId').value;
        const remarks = document.getElementById('remarks').value.trim();

        // Reset all validation states
        document.querySelectorAll('#familyGroupForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));

        if (!groupName) {
            groupNameInput.classList.add('is-invalid');
            document.getElementById('group_name_error').textContent = 'Group name is required';
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const token = localStorage.getItem('crm_auth_token');
            const url = currentEditId ? `${API_BASE}/${currentEditId}` : API_BASE;
            const method = currentEditId ? 'PUT' : 'POST';

            const data = {
                group_name: groupName,
                holder_id: holderId || null,
                remarks: remarks || null,
                members: collectMembersData()
            };

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                closeModalFamilyGroup();

                if (window.DashboardAPI?.showToast) {
                    window.DashboardAPI.showToast(result.message || 'Saved successfully', 'success');
                } else {
                    window.crmAlert(result.message || 'Saved successfully');
                }

                const searchInput = document.getElementById('leadsSearch');
                loadData(searchInput ? searchInput.value : '', 1);
            } else {
                if (result.errors) {
                    for (const [key, value] of Object.entries(result.errors)) {
                        let inputName = key;
                        // Convert Laravel validation format (e.g., members.0.member_name) to array format (e.g., members[0][member_name])
                        if (key.includes('.')) {
                            const parts = key.split('.');
                            inputName = parts[0];
                            for (let i = 1; i < parts.length; i++) {
                                inputName += `[${parts[i]}]`;
                            }
                        }
                        
                        // Use querySelector by name, or getElementById for specific fields
                        let inputField = document.querySelector(`[name="${inputName}"]`);
                        if (!inputField && document.getElementById(key)) {
                            inputField = document.getElementById(key);
                        }

                        if (inputField) {
                            inputField.classList.add('is-invalid');
                            let errorFieldId = `${key.replace(/\./g, '_')}_error`;
                            let errorField = document.getElementById(errorFieldId);
                            
                            // Create inline error block if it doesn't exist
                            if (!errorField) {
                                errorField = document.createElement('div');
                                errorField.className = 'invalid-feedback staff-validation';
                                errorField.id = errorFieldId;
                                
                                // Insert after input, or after input-group if it exists
                                if (inputField.parentNode.classList.contains('input-group')) {
                                    inputField.parentNode.parentNode.appendChild(errorField);
                                } else {
                                    inputField.parentNode.appendChild(errorField);
                                }
                            }
                            errorField.textContent = value[0];
                            errorField.style.display = 'block'; // Ensure it's shown for dynamically created elements
                        } else {
                            if (window.DashboardAPI?.showToast) {
                                window.DashboardAPI.showToast(value[0], 'error');
                            } else {
                                window.crmAlert(value[0]);
                            }
                        }
                    }
                } else {
                    if (window.DashboardAPI?.showToast) {
                        window.DashboardAPI.showToast(result.message || 'Failed to save', 'error');
                    } else {
                        window.crmAlert(result.message || 'Failed to save');
                    }
                }
            }
        } catch (error) {
            console.error('Save error:', error);
            if (window.DashboardAPI?.showToast) {
                window.DashboardAPI.showToast('An error occurred. Please try again.', 'error');
            } else {
                window.crmAlert('An error occurred. Please try again.');
            }
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    /**
     * Edit item
     */
    async function editFamilyGroup(id) {
        // console.log('Edit family group clicked for id:', id);
        try {
            await openModalFamilyGroup(id);
        } catch (error) {
            console.error('Error opening edit modal:', error);
            window.crmAlert('Error opening edit form. Please try again.');
        }
    }

    /**
     * Delete item with SweetAlert
     */
    async function deleteFamilyGroup(id, name) {
        // console.log('Delete family group clicked for id:', id, 'name:', name);
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
                text: `Delete "${name}" and all its members?`,
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
            window.crmConfirm(`Are you sure you want to delete "${name}" and all its members?`).then((result) => { if (result.isConfirmed) { doDelete(); } });
        }
    }

    /**
     * Change page
     */
    function changePageFamilyGroup(page) {
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

    /**
     * Initialize Policy Holder Modal HTML
     */
    function initPolicyHolderModal() {
        if (document.getElementById('policyHolderModal')) return;

        const modalHtml = `
        <div class="modal fade" id="policyHolderModal" tabindex="-1" aria-labelledby="policyHolderModalLabel" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold" id="policyHolderModalLabel">Add Policy Holder</h5>
                        <button type="button" class="btn-close" onclick="closePolicyHolderModal()" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <form id="policyHolderForm">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="new_holder_name" class="form-label small fw-bold text-uppercase">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-3" id="new_holder_name" name="name" placeholder="Enter name">
                                    <div class="invalid-feedback staff-validation" id="new_holder_name_error"></div>
                                </div>
                                <div class="col-md-12">
                                    <label for="new_holder_email" class="form-label small fw-bold text-uppercase">Email</label>
                                    <input type="email" class="form-control rounded-3" id="new_holder_email" name="email" placeholder="Enter email">
                                </div>
                                <div class="col-md-12">
                                    <label for="new_holder_contact" class="form-label small fw-bold text-uppercase">Phone <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control flex-grow-0" id="new_holder_country_code" name="country_code" value="+91" list="policyHolderCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                        <input type="tel" class="form-control" id="new_holder_contact" name="contact" placeholder="Enter phone" maxlength="15" inputmode="numeric">
                                    </div>
                                    <datalist id="policyHolderCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                                    <div class="invalid-feedback staff-validation" id="new_holder_contact_error"></div>
                                </div>
                                <div class="col-md-12">
                                    <label for="new_holder_address" class="form-label small fw-bold text-uppercase">Address</label>
                                    <textarea class="form-control rounded-3" id="new_holder_address" name="address" rows="3" placeholder="Enter address"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" onclick="closePolicyHolderModal()">Cancel</button>
                        <button type="button" class="btn btn-dark-blue rounded-pill px-4" id="savePolicyHolderBtn" onclick="savePolicyHolder()">
                            <span class="spinner-border spinner-border-sm d-none" id="savePolicyHolderSpinner"></span>
                            Save Holder
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    function openPolicyHolderModal() {
        const modal = document.getElementById('policyHolderModal');
        document.getElementById('policyHolderForm').reset();
        
        // Remove validation classes
        document.querySelectorAll('#policyHolderForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let modalEl = bootstrap.Modal.getInstance(modal);
            if (!modalEl) {
                modalEl = new bootstrap.Modal(modal);
            }
            modalEl.show();
        } else {
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            
            let backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show policy-holder-backdrop';
            backdrop.style.zIndex = '1055';
            document.body.appendChild(backdrop);
        }
    }

    function closePolicyHolderModal() {
        const modal = document.getElementById('policyHolderModal');
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalEl = bootstrap.Modal.getInstance(modal);
            if (modalEl) modalEl.hide();
        } else {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            
            const backdrop = document.querySelector('.policy-holder-backdrop');
            if (backdrop) backdrop.remove();
        }
    }

    async function savePolicyHolder() {
        const btn = document.getElementById('savePolicyHolderBtn');
        const spinner = document.getElementById('savePolicyHolderSpinner');
        
        const nameInput = document.getElementById('new_holder_name');
        const contactInput = document.getElementById('new_holder_contact');
        
        const name = nameInput.value.trim();
        const email = document.getElementById('new_holder_email').value.trim();
        const contact = contactInput.value.trim();
        const countryCode = document.getElementById('new_holder_country_code').value.trim() || '+91';
        const address = document.getElementById('new_holder_address').value.trim();

        // Reset validation
        nameInput.classList.remove('is-invalid');
        contactInput.classList.remove('is-invalid');

        let hasError = false;

        if (!name) {
            nameInput.classList.add('is-invalid');
            document.getElementById('new_holder_name_error').textContent = 'Please enter name';
            hasError = true;
        }
        
        if (!contact) {
            contactInput.classList.add('is-invalid');
            document.getElementById('new_holder_contact_error').textContent = 'Please enter phone number';
            hasError = true;
        }

        if (hasError) {
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const token = localStorage.getItem('crm_auth_token');
            const data = {
                name: name,
                email: email,
                phone: contact,
                country_code: countryCode,
                address: address
            };

            const response = await fetch('/api/customers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && (result.success || result.data)) {
                closePolicyHolderModal();
                
                const customer = result.data || result;
                const select = document.getElementById('holderId');
                if (select) {
                    const newOption = new Option(customer.name || name, customer.id, false, true);
                    select.appendChild(newOption);
                    select.value = customer.id;
                    if (window.jQuery && jQuery.fn.select2) jQuery(select).trigger('change.select2');
                }

                if (window.DashboardAPI?.showToast) {
                    window.DashboardAPI.showToast('Policy holder created successfully', 'success');
                } else {
                    window.crmAlert('Policy holder created successfully');
                }
            } else {
                if (result.errors) {
                    for (const [key, value] of Object.entries(result.errors)) {
                        const inputField = document.getElementById(`new_holder_${key === 'phone' ? 'contact' : key}`);
                        const errorField = document.getElementById(`new_holder_${key === 'phone' ? 'contact' : key}_error`);
                        if (inputField && errorField) {
                            inputField.classList.add('is-invalid');
                            errorField.textContent = value[0];
                        } else {
                            if (window.DashboardAPI?.showToast) {
                                window.DashboardAPI.showToast(value[0], 'error');
                            } else {
                                window.crmAlert(value[0]);
                            }
                        }
                    }
                } else {
                    if (window.DashboardAPI?.showToast) {
                        window.DashboardAPI.showToast(result.message || 'Failed to save', 'error');
                    } else {
                        window.crmAlert(result.message || 'Failed to save');
                    }
                }
            }
        } catch (error) {
            console.error('Save error:', error);
            if (window.DashboardAPI?.showToast) {
                window.DashboardAPI.showToast('An error occurred. Please try again.', 'error');
            } else {
                window.crmAlert('An error occurred. Please try again.');
            }
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    // Expose functions globally
    window.editFamilyGroup = editFamilyGroup;
    window.deleteFamilyGroup = deleteFamilyGroup;
    window.changePageFamilyGroup = changePageFamilyGroup;
    window.saveFamilyGroup = saveFamilyGroup;
    window.openModalFamilyGroup = openModalFamilyGroup;
    window.closeModalFamilyGroup = closeModalFamilyGroup;
    
    // Policy Holder functions
    window.openPolicyHolderModal = openPolicyHolderModal;
    window.closePolicyHolderModal = closePolicyHolderModal;
    window.savePolicyHolder = savePolicyHolder;

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
