(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.followups || {};
        const tableBody = document.getElementById("followUpsTable");

        if (!tableBody) {
            console.error("FollowUps table not found");
            return;
        }

        const paginationContainer = document.getElementById("followupPaginationContainer");
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        // ✅ DELETE FUNCTION
        function deleteFollowUp(id, button) {
            window.showDeleteConfirm("This follow-up will be deleted!").then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/api/follow-ups/${id}`,
                        type: "DELETE",
                        headers: authHeaders({
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                        }),
                        success: function (res) {
                            if (res.success) {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("success", res.message || "Follow-up deleted successfully.");
                                }
                                fetchFollowUps();
                            } else {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("error", res.message || "Delete failed");
                                }
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            }
                        },
                        error: function (xhr) {
                            const message = xhr?.responseJSON?.message || "Something went wrong";
                            if (typeof window.showAlert === "function") {
                                window.showAlert("error", message);
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.status-dropdown, .status-display, .priority-dropdown, .priority-display')) {
                document.querySelectorAll('.status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                document.querySelectorAll('.status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });
                document.querySelectorAll('.priority-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                document.querySelectorAll('.priority-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });
            }
        });

        // ✅ RENDER ROWS (KEEP YOUR DESIGN)
        function renderRows(items) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-telephone-outbound display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No follow ups found.</p>
                            ${permissions.create ? '<a href="/follow-ups/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Schedule Your First Follow Up</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(followUp => {

                const entity = followUp.lead || followUp.customer;
                const entityType = followUp.lead ? 'Lead' : 'Customer';
                const initial = entity?.name
                    ? entity.name.substring(0, 1).toUpperCase()
                    : "?";

                const followDate = followUp.follow_up_at
                    ? new Date(followUp.follow_up_at)
                    : null;

                const date = followDate
                    ? followDate.toLocaleDateString("en-GB", {
                        day: "2-digit",
                        month: "short",
                        year: "numeric",
                    })
                    : "--";

                const time = followDate
                    ? followDate.toLocaleTimeString("en-US", {
                        hour: "2-digit",
                        minute: "2-digit",
                    })
                    : "";

                const priorityClass = {
                    low: "bg-info",
                    medium: "bg-primary",
                    high: "bg-danger",
                }[followUp.priority] || "bg-secondary";

                const statusClass = {
                    pending: "bg-warning text-dark",
                    resheduled: "bg-info",
                    completed: "bg-success",
                    cancelled: "bg-danger",
                }[followUp.status] || "bg-secondary";

                const priorityHtml = permissions.edit 
                    ? `<select class="form-select form-select-sm priority-dropdown d-none" data-id="${followUp.id}">
                            <option value="low" ${followUp.priority === 'low' ? 'selected' : ''}>Low</option>
                            <option value="medium" ${followUp.priority === 'medium' ? 'selected' : ''}>Medium</option>
                            <option value="high" ${followUp.priority === 'high' ? 'selected' : ''}>High</option>
                        </select>
                        <span class="priority-display" style="cursor: pointer;" title="Click to edit priority">
                            <span class="badge ${priorityClass} px-3 py-2 rounded-pill text-capitalize">${followUp.priority ?? '--'}</span>
                        </span>`
                    : `<span class="badge ${priorityClass} px-3 py-2 rounded-pill text-capitalize">${followUp.priority ?? '--'}</span>`;

                const statusHtml = permissions.edit
                    ? `<select class="form-select form-select-sm status-dropdown d-none" data-id="${followUp.id}">
                            <option value="pending" ${followUp.status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="resheduled" ${followUp.status === 'resheduled' ? 'selected' : ''}>Rescheduled</option>
                            <option value="completed" ${followUp.status === 'completed' ? 'selected' : ''}>Completed</option>
                            <option value="cancelled" ${followUp.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                        <span class="status-display" style="cursor: pointer;" title="Click to edit status">
                            <span class="badge ${statusClass} px-3 py-2 rounded-pill text-capitalize">${followUp.status ?? '--'}</span>
                        </span>`
                    : `<span class="badge ${statusClass} px-3 py-2 rounded-pill text-capitalize">${followUp.status ?? '--'}</span>`;

                return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3 py-1">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white bg-primary d-none d-md-flex"
                                style="width: 36px; height: 36px; font-weight: 600; font-size: 0.9rem;">
                                ${initial}
                            </div>
                            <div>
                                <div class="fw-bold small">${followUp.purpose ?? '--'}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    ${entity?.name ?? 'Unknown'} (${entityType})
                                </div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="small fw-semibold">${date}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">
                            ${time}
                        </div>
                    </td>

                    <td class="d-none d-md-table-cell">${priorityHtml}</td>

                    <td class="d-none d-md-table-cell">
                        <div class="small">
                            ${followUp.assigned_user?.name ?? 'Unassigned'}
                        </div>
                    </td>

                    <td class="d-none d-md-table-cell">${statusHtml}</td>

                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            ${permissions.view ? `<a href="/follow-ups/${followUp.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>` : ''}
                            ${permissions.edit ? `<a href="/follow-ups/${followUp.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>` : ''}
                            ${permissions.delete ? `<button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${followUp.id}"><i class="bi bi-trash text-danger"></i></button>` : ''}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-followup-id="${followUp.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${followUp.id}" style="display: none;">
                    <td colspan="7" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                    <div class="expand-value">${statusHtml}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-bolt"></i> Priority :</div>
                                    <div class="expand-value">${priorityHtml}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Staff :</div>
                                    <div class="expand-value">${followUp.assigned_user?.name ?? 'Unassigned'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        ${permissions.view ? `<a href="/follow-ups/${followUp.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>` : ''}
                                        ${permissions.edit ? `<a href="/follow-ups/${followUp.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>` : ''}
                                        ${permissions.delete ? `<button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${followUp.id}"><i class="bi bi-trash text-danger"></i></button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // attach delete
            document.querySelectorAll(".delete-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    deleteFollowUp(this.dataset.id, this);
                });
            });

            document.querySelectorAll('.status-display').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleDropdown(this, '.status-dropdown', '.status-display');
                });
            });

            document.querySelectorAll('.priority-display').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleDropdown(this, '.priority-dropdown', '.priority-display');
                });
            });

            document.querySelectorAll('.status-dropdown').forEach(function (element) {
                element.addEventListener('click', function(e) { e.stopPropagation(); });
                element.addEventListener('change', function () {
                    updateField(this, 'status');
                });
            });

            document.querySelectorAll('.priority-dropdown').forEach(function (element) {
                element.addEventListener('click', function(e) { e.stopPropagation(); });
                element.addEventListener('change', function () {
                    updateField(this, 'priority');
                });
            });

            // attach expand
            document.querySelectorAll(".btn-user-expand").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.dataset.followupId;
                    const detailsRow = document.getElementById(`details-${id}`);
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

        function toggleDropdown(element, dropdownSelector, displaySelector) {
            const row = element.closest('td') || element.closest('div');
            if (!row) return;

            const dropdown = row.querySelector(dropdownSelector);
            const display = row.querySelector(displaySelector);

            document.querySelectorAll(dropdownSelector).forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
            document.querySelectorAll(displaySelector).forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });

            if (dropdown && display) {
                dropdown.classList.remove('d-none');
                dropdown.style.display = 'block';
                display.classList.add('d-none');
                display.style.display = 'none';
                dropdown.focus();
            }
        }

        function updateField(dropdown, field) {
            const id = dropdown.dataset.id;
            const value = dropdown.value;
            const row = dropdown.closest('td') || dropdown.closest('div');
            const display = row ? row.querySelector(`.${field}-display`) : null;
            const originalValue = dropdown.dataset.originalValue || dropdown.value;

            if (field === 'status') {
                Swal.fire({
                    title: 'Add Message',
                    input: 'textarea',
                    inputPlaceholder: 'Enter your message',
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#0f172a',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        popup: 'rounded-4 shadow',
                        input: 'rounded-3'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        performUpdate(id, field, value, display, dropdown, originalValue, result.value);
                    } else {
                        dropdown.value = originalValue;
                        dropdown.classList.add('d-none');
                        if (display) display.classList.remove('d-none');
                    }
                });
            } else {
                performUpdate(id, field, value, display, dropdown, originalValue, null);
            }
        }

        function performUpdate(id, field, value, display, dropdown, originalValue, comment) {
            dropdown.dataset.originalValue = originalValue;
            dropdown.disabled = true;

            const url = field === 'status' ? `/api/follow-ups/${id}/update-status` : `/api/follow-ups/${id}/update-priority`;
            
            const data = {};
            data[field] = value;
            if (comment) {
                data['status_comment'] = comment;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: authHeaders({
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                }),
                data: data,
                success: function (response) {
                    if (response.success) {
                        if (display) {
                            let badgeClass = "bg-secondary";
                            if (field === 'priority') {
                                badgeClass = {
                                    low: "bg-info",
                                    medium: "bg-primary",
                                    high: "bg-danger",
                                }[value] || "bg-secondary";
                            } else if (field === 'status') {
                                badgeClass = {
                                    pending: "bg-warning text-dark",
                                    resheduled: "bg-info",
                                    completed: "bg-success",
                                    cancelled: "bg-danger",
                                }[value] || "bg-secondary";
                            }
                            display.innerHTML = `<span class="badge ${badgeClass} px-3 py-2 rounded-pill text-capitalize">${value}</span>`;
                            display.classList.remove('d-none');
                        }

                        dropdown.classList.add('d-none');
                        dropdown.dataset.originalValue = value;

                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || `${field} updated successfully.`, 'Success');
                        }
                    } else {
                        dropdown.value = originalValue;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', response.message || `Failed to update ${field}.`, 'Error');
                        }
                    }
                },
                error: function (xhr) {
                    dropdown.value = originalValue;
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', xhr?.responseJSON?.message || 'Something went wrong! Please try again.', 'Error');
                    }
                },
                complete: function () {
                    dropdown.disabled = false;
                },
            });
        }

        let timer;
        const searchInput = document.getElementById("followUpSearch");
        searchInput.addEventListener("input", () => {
            clearTimeout(timer);
            timer = setTimeout(() => fetchFollowUps(1), 400);
        });

        // ✅ FETCH API
        function fetchFollowUps(page = 1) {
            let url = `/api/follow-ups?page=${page}`;

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            const fromDate = document.getElementById('filterFromDate')?.value;
            const toDate = document.getElementById('filterToDate')?.value;
            const status = document.getElementById('filterStatus')?.value;
            const perPage = document.getElementById('perPageSelect')?.value;

            const priority = document.getElementById('filterPriority')?.value;
            const staff = document.getElementById('filterStaff')?.value;
            const createdBy = document.getElementById('filterCreatedBy')?.value;
            const updatedBy = document.getElementById('filterUpdatedBy')?.value;

            if (fromDate) url += `&from_date=${encodeURIComponent(fromDate)}`;
            if (toDate) url += `&to_date=${encodeURIComponent(toDate)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (perPage) url += `&per_page=${encodeURIComponent(perPage)}`;
            if (priority) url += `&priority=${encodeURIComponent(priority)}`;
            if (staff) url += `&assigned_user_id=${encodeURIComponent(staff)}`;
            if (createdBy) url += `&created_by=${encodeURIComponent(createdBy)}`;
            if (updatedBy) url += `&updated_by=${encodeURIComponent(updatedBy)}`;

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                }),
                beforeSend: function () {
                    tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </td>
                </tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderRows(res.data.data || []);
                        renderPagination(res.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        Error loading follow-ups
                    </td>
                </tr>`;
                },
            });
        }

        function renderPagination(data) {
            if (!paginationContainer) return;

            if (data.total === 0) {
                paginationContainer.innerHTML = "";
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

            // Previous
            if (data.prev_page_url) {
                html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">
                    Previous
                </a>
            </li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
            }

            // Pages
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            if (data.next_page_url) {
                html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">
                    Next
                </a>
            </li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            }

            html += `</ul></div>`;

            paginationContainer.innerHTML = html;

            // Click events
            document.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchFollowUps(this.dataset.page);
                });
            });
        }

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchFollowUps(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus', 'filterPriority', 'filterStaff', 'filterCreatedBy', 'filterUpdatedBy'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => fetchFollowUps(1), 0));
            }
        });
        
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                if (document.getElementById('filterFromDate')) document.getElementById('filterFromDate').value = '';
                if (document.getElementById('filterToDate')) document.getElementById('filterToDate').value = '';
                if (document.getElementById('filterDateRange')) document.getElementById('filterDateRange').value = '';
                if (document.getElementById('filterStatus')) document.getElementById('filterStatus').value = '';
                if (document.getElementById('filterCreatedBy')) document.getElementById('filterCreatedBy').value = '';
                if (searchInput) searchInput.value = '';
                fetchFollowUps(1);
            });
        }
        
        const filterDateRange = document.getElementById('filterDateRange');
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
                
                document.getElementById('filterFromDate').value = from;
                document.getElementById('filterToDate').value = to;
            });
        }

        // ✅ INITIAL LOAD
        fetchFollowUps();
    }
})();

$(document).ready(function () {
    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    function getEnhancedElement($field) {
        if (!$field || !$field.length || !$field.is('select')) {
            return $();
        }

        if ($field.next('.ts-wrapper').length) {
            return $field.next('.ts-wrapper');
        }

        if ($field.next('.select2-container').length) {
            return $field.next('.select2-container');
        }

        return $();
    }

    function getErrorAnchor($field) {
        const $enhanced = getEnhancedElement($field);
        return $enhanced.length ? $enhanced : $field;
    }

    function handleValidationErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            const fieldEl = $form.find('[name="' + field + '"]');
            const errorEl = $form.find('#' + field + '-error');
            if (fieldEl.length) {
                fieldEl.addClass('is-invalid');
                getEnhancedElement(fieldEl).addClass('is-invalid');
                if (errorEl.length) {
                    errorEl.html(errors[field][0]);
                } else {
                    const errorHtml = `<div class="invalid-feedback ajax-error">${errors[field][0]}</div>`;
                    const errorAnchor = getErrorAnchor(fieldEl);
                    errorAnchor.siblings('.invalid-feedback.ajax-error').remove();
                    errorAnchor.after(errorHtml);
                }
            }
        });
    }

    // Handle form submission
    $('body').on('submit', '.ajax-followup-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const btn = $form.find('button[type=submit]');
        const originalText = btn.html();
        const method = 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.ts-wrapper.is-invalid').removeClass('is-invalid');
        $form.find('.select2-container.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        $form.find('.invalid-feedback').not('.ajax-error').html('');
        $form.find('.ajax-alert').remove();

        const formData = new FormData(this);

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: authHeaders({
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                Accept: 'application/json'
            }),
            success: function (response) {
                const message = response.message || 'Follow-up saved successfully.';

                const isStatusCommentFlow = $form.hasClass('js-status-comment-form') && $form.find('input[name="status_comment"]').val().trim() !== '';

                if (isStatusCommentFlow && response.history_entry && window.crmStatusHistory) {
                    if (typeof window.showAlert === "function") {
                        window.showAlert('success', message);
                    }
                    $form.find('input[name="status_comment"]').val('');
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                const redirect = response.redirect || '/follow-ups';
                if (typeof window.showAlert === "function") {
                    window.showAlert('success', message, 'Success!', redirect);
                } else if (redirect) {
                    window.location.href = redirect;
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    handleValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    const response = xhr.responseJSON || {};
                    const message = response.message || 'Something went wrong while submitting the form. Please try again.';
                    const alert = `<div class="alert alert-danger ajax-alert" role="alert">${message}</div>`;
                    $form.prepend(alert);
                }
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('body').on('input change', '.ajax-followup-form input, .ajax-followup-form select, .ajax-followup-form textarea', function () {
        const $field = $(this);
        $field.removeClass('is-invalid');
        getEnhancedElement($field).removeClass('is-invalid');
        const fieldName = $field.attr('name');
        if (fieldName) {
            $field.closest('form').find('#' + fieldName + '-error').html('');
        }
        getErrorAnchor($field).siblings('.invalid-feedback.ajax-error').remove();
    });

    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            theme: 'bootstrap-5'
        });
    }

    // Handle Add Lead Modal Submit
    $("#formAddLead").on("submit", function (e) {
        e.preventDefault();
        let $form = $(this);
        let url = $form.attr("action");
        let btn = $form.find('button[type="submit"]');
        let spinner = btn.find('.spinner-border');
        
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        $form.find(".invalid-feedback.ajax-error").remove();
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find('.ajax-alert').remove();
        
        $.ajax({
            url: url,
            type: "POST",
            data: $form.serialize(),
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                Accept: "application/json",
            }),
            success: function (response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (response.success && response.data) {
                    let lead = response.data;
                    let $select = $("#lead_id");
                    if ($select.length) {
                        let newOption = new Option(lead.name, lead.id, true, true);
                        $select.append(newOption).trigger('change');
                    }
                    $("#modalAddLead").modal("hide");
                    $form[0].reset();
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", "Lead added successfully.");
                    }
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    handleValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    const msg = xhr.responseJSON?.message || "Error adding lead.";
                    if (typeof window.showAlert === "function") {
                        window.showAlert("error", msg);
                    } else {
                        const alert = `<div class="alert alert-danger ajax-alert" role="alert">${msg}</div>`;
                        $form.prepend(alert);
                    }
                }
            }
        });
    });
});
