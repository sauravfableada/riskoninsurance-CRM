(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initTaskTable);
    } else {
        initTaskTable();
    }

    function initTaskTable() {
        const permissions = window.crmUserPermissions?.tasks || {};
        const tableBody = document.querySelector("#tasksTable tbody");
        const searchInput = document.getElementById("tasksSearch");
        const paginationContainer = document.getElementById("tasksPagination");

        if (!tableBody || !searchInput || !paginationContainer) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        function authHeaders(extraHeaders = {}) {
            if (typeof window.crmApplyAuthHeaders === "function") {
                return window.crmApplyAuthHeaders(extraHeaders);
            }

            return extraHeaders;
        }

        function priorityClass(priority) {
            return {
                high: "text-danger",
                medium: "text-warning",
                low: "text-info",
            }[priority] || "text-muted";
        }

        function statusClass(status) {
            return {
                completed: "bg-success",
                in_progress: "bg-primary",
                pending: "bg-warning text-dark",
            }[status] || "bg-secondary";
        }

        function formatDate(value) {
            if (!value) {
                return "--";
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return "--";
            }

            return date.toLocaleDateString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return "";
            }

            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatLabel(value) {
            if (!value) {
                return "--";
            }

            return String(value)
                .replace(/_/g, " ")
                .replace(/\b\w/g, function (char) {
                    return char.toUpperCase();
                });
        }

        function deleteTask(taskId, button) {
            window.showDeleteConfirm("Delete this task?").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                $.ajax({
                    url: `/api/tasks/${taskId}`,
                    type: "DELETE",
                    headers: authHeaders({
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (response) {
                        if (response.success) {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("success", response.message || "Task deleted successfully.");
                            }
                            fetchTasks();
                        } else {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("error", response.message || "Failed to delete task.");
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function () {
                        if (typeof window.showAlert === "function") {
                            window.showAlert("error", "Something went wrong. Please try again.");
                        }
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
            });
        }

        function renderRows(items, meta) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No tasks added yet.</p>
                            ${permissions.create ? '<a href="/tasks/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Task</a>' : ""}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (task, index) {
                const customerName = escapeHtml(task.customer?.name || task.project?.customer?.name || "--");
                const policyTypeName = escapeHtml(task.policy_type?.name || task.project?.name || "--");
                const subTypeName = escapeHtml(task.policy_sub_type?.name || "");
                const taskTitle = escapeHtml(task.title ?? "--");
                const priorityBadgeHtml = getPriorityBadge(task.priority, task.id, permissions.edit);
                const statusBadgeHtml = getStatusBadge(task.status, task.id, permissions.edit);
                const dueDate = escapeHtml(formatDate(task.due_date));
                const rowNumber = meta && meta.from ? meta.from + index : index + 1;

                return `
                    <tr>
                        <td class="ps-4">
                            <span class="text-muted small fw-medium">${rowNumber}</span>
                        </td>
                        <td>
                            <div class="fw-bold small">${taskTitle}</div>
                            <div class="text-muted small mt-1">${customerName}</div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="fw-semibold small">${policyTypeName}</div>
                            ${subTypeName ? `<div class="text-muted small">${subTypeName}</div>` : ""}
                        </td>
                        <td class="d-none d-md-table-cell">
                            ${priorityBadgeHtml}
                        </td>
                        <td class="d-none d-md-table-cell">
                            ${statusBadgeHtml}
                        </td>
                        <td class="d-none d-md-table-cell">${dueDate}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${permissions.edit ? `<a href="/tasks/${task.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/tasks/${task.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-task-btn" data-task-id="${task.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-task-id="${task.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${task.id}" style="display: none;">
                        <td colspan="8" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Customer :</div>
                                        <div class="expand-value text-end">${customerName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-shield-halved"></i> Policy Type :</div>
                                        <div class="expand-value text-end">${policyTypeName}${subTypeName ? ` (${subTypeName})` : ""}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-flag"></i> Priority :</div>
                                        <div class="expand-value text-end">
                                            ${priorityBadgeHtml}
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                        <div class="expand-value text-end">
                                            ${statusBadgeHtml}
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Due Date :</div>
                                        <div class="expand-value text-end">${dueDate}</div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${permissions.edit ? `<a href="/tasks/${task.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                                        ${permissions.view ? `<a href="/tasks/${task.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-task-btn" data-task-id="${task.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ""}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join("");

            document.querySelectorAll(".delete-task-btn").forEach(function (button) {
                button.addEventListener("click", function () {
                    deleteTask(this.dataset.taskId, this);
                });
            });

            tableBody.querySelectorAll(".btn-user-expand").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.dataset.taskId;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector("i");

                    if (!detailsRow) {
                        return;
                    }

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

            // Inline Edit Event Listeners
            document.querySelectorAll('.priority-display, .status-display').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const row = this.closest('td') || this.closest('div');
                    if (!row) return;

                    const dropdown = row.querySelector('.priority-dropdown, .status-dropdown');
                    const display = this;

                    // Close all other dropdowns
                    document.querySelectorAll('.priority-dropdown, .status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                    document.querySelectorAll('.priority-display, .status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });

                    if (dropdown) {
                        dropdown.classList.remove('d-none');
                        dropdown.style.display = 'block';
                        display.classList.add('d-none');
                        display.style.display = 'none';
                        dropdown.focus();
                    }
                });
            });

            document.querySelectorAll('.priority-dropdown, .status-dropdown').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
                element.addEventListener('change', function () {
                    if (this.classList.contains('priority-dropdown')) {
                        updatePriorityField(this);
                    } else {
                        updateStatusField(this);
                    }
                });
            });
        }

        function renderPagination(data) {
            if (!data || data.total === 0) {
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
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">`;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += "</ul></div>";
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach(function (link) {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    fetchTasks(this.dataset.page);
                });
            });
        }

        function fetchTasks(page = 1) {
            let url = `/api/tasks?page=${page}`;

            if (searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            const fromDate = document.getElementById('filterFromDate')?.value;
            const toDate = document.getElementById('filterToDate')?.value;
            const status = document.getElementById('filterStatus')?.value;
            const perPage = document.getElementById('perPageSelect')?.value;

            if (fromDate) url += `&from_date=${encodeURIComponent(fromDate)}`;
            if (toDate) url += `&to_date=${encodeURIComponent(toDate)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (perPage) url += `&per_page=${encodeURIComponent(perPage)}`;

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                }),
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">Error loading tasks</td>
                        </tr>`;
                },
            });
        }

        let timer;
        searchInput.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                fetchTasks(1);
            }, 300);
        });

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchTasks(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => fetchTasks(1), 0));
            }
        });
        
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                document.getElementById('filterFromDate').value = '';
                document.getElementById('filterToDate').value = '';
                document.getElementById('filterDateRange').value = '';
                document.getElementById('filterStatus').value = '';
                if (searchInput) searchInput.value = '';
                fetchTasks(1);
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

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.priority-dropdown, .priority-display, .status-dropdown, .status-display')) {
                document.querySelectorAll('.priority-dropdown, .status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                document.querySelectorAll('.priority-display, .status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });
            }
        });

        // INLINE EDIT HELPERS
        function getPriorityBadge(priority, id, canEdit) {
            const badgeHtml = `<span class="fw-semibold text-uppercase ${priorityClass(priority)}">${formatLabel(priority)}</span>`;
            if (!canEdit) return badgeHtml;

            return `
                <select class="form-select form-select-sm priority-dropdown d-none" data-id="${id}">
                    <option value="high" ${priority === 'high' ? 'selected' : ''}>High</option>
                    <option value="medium" ${priority === 'medium' ? 'selected' : ''}>Medium</option>
                    <option value="low" ${priority === 'low' ? 'selected' : ''}>Low</option>
                </select>
                <span class="priority-display" style="cursor: pointer;" title="Click to edit priority">
                    ${badgeHtml}
                </span>
            `;
        }

        function getStatusBadge(status, id, canEdit) {
            const badgeHtml = `<span class="badge crm-status-pill rounded-pill ${statusClass(status)}">${formatLabel(status)}</span>`;
            if (!canEdit) return badgeHtml;

            return `
                <select class="form-select form-select-sm status-dropdown d-none" data-id="${id}">
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
                <span class="status-display" style="cursor: pointer;" title="Click to edit status">
                    ${badgeHtml}
                </span>
            `;
        }

        function updatePriorityField(dropdown) {
            const id = dropdown.dataset.id;
            const value = dropdown.value;
            const row = dropdown.closest('td') || dropdown.closest('div');
            const display = row ? row.querySelector('.priority-display') : null;
            const originalValue = dropdown.dataset.originalValue || dropdown.value;

            Swal.fire({
                title: 'Change Priority',
                text: "Are you sure you want to change the priority?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performPriorityUpdate(id, value, display, dropdown, originalValue);
                } else {
                    dropdown.value = originalValue;
                    dropdown.classList.add('d-none');
                    dropdown.style.display = 'none';
                    if (display) {
                        display.classList.remove('d-none');
                        display.style.display = '';
                    }
                }
            });
        }

        function performPriorityUpdate(id, value, display, dropdown, originalValue) {
            dropdown.dataset.originalValue = originalValue;
            dropdown.disabled = true;

            $.ajax({
                url: `/api/tasks/${id}/update-priority`,
                type: 'POST',
                headers: authHeaders({
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                }),
                data: { priority: value },
                success: (response) => {
                    if (response.success) {
                        if (display) {
                            display.innerHTML = getPriorityBadge(value, id, false);
                            display.classList.remove('d-none');
                            display.style.display = '';
                        }
                        dropdown.classList.add('d-none');
                        dropdown.style.display = 'none';
                        dropdown.dataset.originalValue = value;
                        dropdown.disabled = false;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', 'Priority updated successfully.');
                        }
                    } else {
                        dropdown.value = originalValue;
                        dropdown.disabled = false;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Failed to update priority.');
                        }
                    }
                },
                error: () => {
                    dropdown.value = originalValue;
                    dropdown.disabled = false;
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Something went wrong! Please try again.');
                    }
                }
            });
        }

        function updateStatusField(dropdown) {
            const id = dropdown.dataset.id;
            const value = dropdown.value;
            const row = dropdown.closest('td') || dropdown.closest('div');
            const display = row ? row.querySelector('.status-display') : null;
            const originalValue = dropdown.dataset.originalValue || dropdown.value;

            Swal.fire({
                title: 'Add Message',
                input: 'textarea',
                inputPlaceholder: 'Enter your message',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                cancelButtonText: 'Close',
                confirmButtonColor: '#0f172a',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                customClass: {
                    popup: 'rounded-4 shadow',
                    input: 'rounded-3'
                },
                inputValidator: (value) => {
                    if (!value.trim()) {
                        return 'You need to write a message!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    performStatusUpdate(id, value, display, dropdown, originalValue, result.value.trim());
                } else {
                    dropdown.value = originalValue;
                    dropdown.classList.add('d-none');
                    dropdown.style.display = 'none';
                    if (display) {
                        display.classList.remove('d-none');
                        display.style.display = '';
                    }
                }
            });
        }

        function performStatusUpdate(id, value, display, dropdown, originalValue, comment) {
            dropdown.dataset.originalValue = originalValue;
            dropdown.disabled = true;

            $.ajax({
                url: `/api/tasks/${id}/update-status`,
                type: 'POST',
                headers: authHeaders({
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                }),
                data: { status: value, status_comment: comment },
                success: (response) => {
                    if (response.success) {
                        if (display) {
                            display.innerHTML = getStatusBadge(value, id, false);
                            display.classList.remove('d-none');
                            display.style.display = '';
                        }
                        dropdown.classList.add('d-none');
                        dropdown.style.display = 'none';
                        dropdown.dataset.originalValue = value;
                        dropdown.disabled = false;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', 'Status updated successfully.');
                        }
                    } else {
                        dropdown.value = originalValue;
                        dropdown.disabled = false;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Failed to update status.');
                        }
                    }
                },
                error: () => {
                    dropdown.value = originalValue;
                    dropdown.disabled = false;
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Something went wrong! Please try again.');
                    }
                }
            });
        }

        fetchTasks();
    }
})();

$(document).ready(function () {
    function getRemoteItems(url, query) {
        const requestUrl = `${url}?q=${encodeURIComponent(query || "")}`;

        return fetch(requestUrl, {
            method: "GET",
            headers: (typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders({
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                })
                : {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            }),
            credentials: "same-origin",
        }).then((response) => response.json());
    }

    function initRemoteTomSelect(selector, config) {
        const element = document.querySelector(selector);
        if (!element || element.tomselect) {
            return;
        }

        const searchUrl = element.dataset.searchUrl;
        if (!searchUrl) {
            return;
        }

        new TomSelect(selector, {
            valueField: "id",
            labelField: "name",
            searchField: config.searchField,
            preload: true,
            load: function (query, callback) {
                getRemoteItems(searchUrl, query)
                    .then((json) => callback(Array.isArray(json) ? json : []))
                    .catch(() => callback());
            },
            render: config.render,
            placeholder: config.placeholder,
            allowEmptyOption: true,
            copyAttributesToOptions: true,
        });
    }

    function initTomSelect() {
        const projectElement = document.querySelector("#project_id");
        if (projectElement && !projectElement.tomselect) {
            const canDeleteProjects = projectElement.dataset.canDelete === "1";
            const projectTomSelect = new TomSelect(projectElement, {
                valueField: "value",
                labelField: "text",
                searchField: ["text"],
                placeholder: "Select Project",
                allowEmptyOption: true,
                copyAttributesToOptions: true,
                render: {
                    option: function (item, escape) {
                        const id = item.value ?? item.id ?? "";
                        const name = item.text || item.name || "";
                        const deleteButton = canDeleteProjects && String(id) !== ""
                            ? `<button type="button" class="project-option-delete" data-project-id="${escape(id)}" data-project-name="${escape(name)}" title="Delete project" aria-label="Delete ${escape(name)}">&times;</button>`
                            : "";
                        return `<div class="project-option"><span>${escape(name)}</span>${deleteButton}</div>`;
                    },
                    item: function (item, escape) {
                        return `<div>${escape(item.text || item.name || "")}</div>`;
                    },
                },
            });
            projectTomSelect.dropdown.addEventListener("mousedown", function (event) {
                if (event.target.closest(".project-option-delete")) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }, true);
            projectTomSelect.dropdown.addEventListener("click", function (event) {
                const deleteButton = event.target.closest(".project-option-delete");
                if (!deleteButton) return;
                event.preventDefault();
                event.stopPropagation();
                deleteButton.dispatchEvent(new CustomEvent("project:delete", {bubbles: true}));
            }, true);
        }

        initRemoteTomSelect("#assigned_user_id", {
            searchField: ["name", "email"],
            placeholder: "-- Search User --",
            render: {
                option: function (item, escape) {
                    const name = item.name || item.text;
                    const email = item.email || item.data_email || "";
                    return `<div class="py-2 px-3"><div class="fw-bold">${escape(name)}</div>${email ? `<div class="text-muted small">${escape(email)}</div>` : ""}</div>`;
                },
                item: function (item, escape) {
                    return `<div>${escape(item.name || item.text)}</div>`;
                },
            },
        });

        initRemoteTomSelect("#related_id", {
            searchField: ["name", "email", "phone"],
            placeholder: "-- Search Customer --",
            render: {
                option: function (item, escape) {
                    const name = item.name || item.text;
                    const email = item.email || item.data_email || "";
                    const phone = item.phone || item.data_phone || "";
                    const details = [email, phone].filter(Boolean).join(" | ");
                    return `<div class="py-2 px-3"><div class="fw-bold">${escape(name)}</div>${details ? `<div class="text-muted small">${escape(details)}</div>` : ""}</div>`;
                },
                item: function (item, escape) {
                    return `<div>${escape(item.name || item.text)}</div>`;
                },
            },
        });
    }

    function clearErrors($form) {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".ts-wrapper.is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").html("");
    }

    function setFieldInvalid($input) {
        if (!$input.length) {
            return;
        }

        $input.addClass("is-invalid");

        if ($input.is("select")) {
            $input.next(".ts-wrapper").addClass("is-invalid");
        }

        const flatpickr = $input[0]?._flatpickr;
        if (flatpickr?.altInput) {
            $(flatpickr.altInput).addClass("is-invalid");
        }
    }

    function clearFieldInvalid($input) {
        if (!$input.length) {
            return;
        }

        $input.removeClass("is-invalid");

        if ($input.is("select")) {
            $input.next(".ts-wrapper").removeClass("is-invalid");
        }

        const flatpickr = $input[0]?._flatpickr;
        if (flatpickr?.altInput) {
            $(flatpickr.altInput).removeClass("is-invalid");
        }
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            let input = $form.find(`#${field}`);
            if (!input.length) {
                input = $form.find(`[name="${field}"]`);
            }
            
            let errorDiv = $form.find(`#${field}-error`);
            if (!errorDiv.length) {
                errorDiv = input.siblings('.invalid-feedback');
                if (!errorDiv.length) {
                    errorDiv = $('<div class="invalid-feedback"></div>');
                    input.after(errorDiv);
                }
            }

            setFieldInvalid(input);

            if (errorDiv.length) {
                errorDiv.html(messages[0]);
            }
        });
    }

    initTomSelect();

    $(document).on("project:delete", ".project-option-delete", function (event) {
        event.preventDefault();
        event.stopPropagation();

        const projectId = String($(this).data("project-id") || "");
        const projectName = $(this).data("project-name") || "this project";
        const projectSelect = document.querySelector("#project_id")?.tomselect;
        if (!projectId || !projectSelect) return;

        const confirmation = typeof window.showDeleteConfirm === "function"
            ? window.showDeleteConfirm(`Delete ${projectName}? This action cannot be undone.`)
            : Swal.fire({title: "Are you sure?", text: `Delete ${projectName}? This action cannot be undone.`, icon: "warning", showCancelButton: true, confirmButtonText: "Yes, delete it"});

        confirmation.then(function (result) {
            if (!result.isConfirmed) return;

            projectSelect.disable();
            $.ajax({
                url: `/api/projects/${encodeURIComponent(projectId)}`,
                type: "DELETE",
                headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                }) : {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                }),
                success: function (response) {
                    projectSelect.removeItem(projectId, true);
                    projectSelect.removeOption(projectId);
                    projectSelect.clear(true);
                    projectSelect.refreshOptions(false);
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", response.message || "Project deleted successfully.");
                    }
                },
                error: function (xhr) {
                    if (typeof window.showAlert === "function") {
                        window.showAlert("error", xhr.responseJSON?.message || "Unable to delete project.");
                    }
                },
                complete: function () {
                    projectSelect.enable();
                    projectSelect.focus();
                },
            });
        });
    });

    $("body").on("submit", ".ajax-task-form", function (e) {
        e.preventDefault();

        const $form = $(this);
        const btn = $form.find("#submitBtn");
        const btnText = $form.find("#btnText");
        const spinner = $form.find("#btnSpinner");
        const originalText = btnText.text();

        clearErrors($form);
        spinner.removeClass("d-none");
        btnText.text($form.find('input[name="_method"]').length ? "Updating..." : "Saving...");
        btn.prop("disabled", true);

        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
            headers: (typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                })
                : {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                spinner.addClass("d-none");
                btnText.text(originalText);
                btn.prop("disabled", false);

                if (typeof window.showAlert === "function") {
                    window.showAlert("success", response.message || "Task saved successfully.");
                }

                if (response.history_entry && window.crmStatusHistory) {
                    $form.find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                }

                setTimeout(function () {
                    window.location.href = response.redirect || "/tasks";
                }, 300);
            },
            error: function (xhr) {
                spinner.addClass("d-none");
                btnText.text(originalText);
                btn.prop("disabled", false);

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else if (typeof window.showAlert === "function") {
                    window.showAlert("error", xhr.responseJSON?.message || "An error occurred. Please try again.");
                }
            },
        });
    });

    $("body").on("input change", "input, select, textarea", function () {
        const $field = $(this);
        clearFieldInvalid($field);
        $(`#${$(this).attr("id")}-error`).html("");
    });

    function loadPolicySubTypes(policyTypeId, selectedSubTypeId) {
        const $subTypeSelect = $("#policy_sub_type_id");
        if (!$subTypeSelect.length) return;

        $subTypeSelect.empty().append('<option value="">-- Select Policy Sub Type --</option>');

        if (!policyTypeId) {
            return;
        }

        $.ajax({
            url: `/policy-sub-types/${policyTypeId}`,
            type: "GET",
            dataType: "json",
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            }) : {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            }),
            success: function (data) {
                if (Array.isArray(data)) {
                    data.forEach(function (subType) {
                        const isSelected = selectedSubTypeId && String(subType.id) === String(selectedSubTypeId);
                        $subTypeSelect.append(new Option(subType.name, subType.id, isSelected, isSelected));
                    });
                }
            },
            error: function () {
                console.error("Failed to fetch policy sub-types");
            }
        });
    }

    $(document).on("change", "#policy_type_id", function () {
        const typeId = $(this).val();
        loadPolicySubTypes(typeId, null);
        $("#modal_sub_type_policy_type_id").val(typeId);
    });

    $(document).on("show.bs.modal", "#modalAddPolicySubType", function () {
        const currentTypeId = $("#policy_type_id").val();
        if (currentTypeId) {
            $("#modal_sub_type_policy_type_id").val(currentTypeId);
        }
    });

    // Handle Add Policy Type Modal Submit
    $("#formAddPolicyType").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr("action");
        const btn = $form.find('button[type="submit"]');
        const spinner = btn.find('.spinner-border');

        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        clearErrors($form);

        $.ajax({
            url: url,
            type: "POST",
            data: $form.serialize(),
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }) : {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (response.status && response.id) {
                    const newOpt = new Option(response.name, response.id, true, true);
                    $("#policy_type_id").append(newOpt).trigger("change");

                    $("#modal_sub_type_policy_type_id").append(new Option(response.name, response.id, true, true));

                    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAddPolicyType")).hide();
                    $form[0].reset();
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", response.message || "Policy type added successfully.");
                    }
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else if (typeof window.showAlert === "function") {
                    window.showAlert("error", xhr.responseJSON?.message || "Error adding policy type.");
                }
            }
        });
    });

    // Handle Add Policy Sub Type Modal Submit
    $("#formAddPolicySubType").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr("action");
        const btn = $form.find('button[type="submit"]');
        const spinner = btn.find('.spinner-border');

        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        clearErrors($form);

        $.ajax({
            url: url,
            type: "POST",
            data: $form.serialize(),
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }) : {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (response.status && response.id) {
                    const currentTypeId = $("#policy_type_id").val();
                    const submittedTypeId = $form.find('[name="type_id"]').val();

                    if (String(currentTypeId) === String(submittedTypeId)) {
                        $("#policy_sub_type_id").append(new Option(response.name, response.id, true, true));
                    }

                    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAddPolicySubType")).hide();
                    $form[0].reset();
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", response.message || "Policy sub-type added successfully.");
                    }
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else if (typeof window.showAlert === "function") {
                    window.showAlert("error", xhr.responseJSON?.message || "Error adding policy sub-type.");
                }
            }
        });
    });

    // Handle Add Project Modal Submit
    $("#formAddProject").on("submit", function (e) {
        e.preventDefault();
        let $form = $(this);
        let url = $form.attr("action");
        let btn = $form.find('button[type="submit"]');
        let spinner = btn.find('.spinner-border');
        
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        clearErrors($form);
        
        $.ajax({
            url: url,
            type: "POST",
            data: $form.serialize(),
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }) : {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (response.success && response.data) {
                    let project = response.data;
                    const projectSelect = document.querySelector("#project_id")?.tomselect;
                    if (projectSelect) {
                        projectSelect.addOption({value: String(project.id), text: project.name});
                        projectSelect.setValue(String(project.id));
                    } else {
                        let $select = $("#project_id");
                        $select.append(new Option(project.name, project.id, true, true)).trigger("change");
                    }
                    $("#modalAddProject").modal("hide");
                    $form[0].reset();
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", "Project added successfully.");
                    }
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else if (typeof window.showAlert === "function") {
                    window.showAlert("error", xhr.responseJSON?.message || "Error adding project.");
                }
            }
        });
    });

    // Load the selected project into the Task-contained project editor.
    $(document).on("click", ".edit-selected-project", function () {
        const projectId = $("#project_id").val();
        if (!projectId) {
            if (typeof window.showAlert === "function") window.showAlert("warning", "Please select a project first.");
            return;
        }

        const $button = $(this).prop("disabled", true);
        $.ajax({
            url: `/api/projects/${projectId}`,
            type: "GET",
            dataType: "json",
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest", Accept: "application/json",
            }) : {"X-Requested-With": "XMLHttpRequest", Accept: "application/json"}),
            success: function (response) {
                if (!response.success || !response.data) return;
                const project = response.data;
                const $form = $("#formEditProject");
                $form.attr("action", `/api/projects/${project.id}`).data("project-id", project.id);
                $form.find('[name="name"]').val(project.name || "");
                $form.find('[name="customer_id"]').val(project.customer_id || "");
                $form.find('[name="assigned_user_id"]').val(project.assigned_user_id || "");
                $form.find('[name="status"]').val(project.status || "pending");
                $form.find('[name="start_date"]').val((project.start_date || "").slice(0, 10));
                $form.find('[name="end_date"]').val((project.end_date || "").slice(0, 10));
                $form.find('[name="description"]').val(project.description || "");
                $form.find('[name="status_comment"]').val("");
                clearErrors($form);
                bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEditProject")).show();
            },
            error: function (xhr) {
                if (typeof window.showAlert === "function") window.showAlert("error", xhr.responseJSON?.message || "Unable to load project details.");
            },
            complete: function () { $button.prop("disabled", false); }
        });
    });

    // Save every editable project field without leaving the Task module.
    $("#formEditProject").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this), projectId = $form.data("project-id");
        const $button = $form.find('button[type="submit"]'), $spinner = $button.find(".spinner-border");
        clearErrors($form);
        $button.prop("disabled", true);
        $spinner.removeClass("d-none");

        $.ajax({
            url: `/api/projects/${projectId}`,
            type: "PUT",
            data: $form.serialize(),
            dataType: "json",
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }) : {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                if (!response.success || !response.data) return;
                const project = response.data;
                const projectSelect = document.querySelector("#project_id")?.tomselect;
                if (projectSelect) {
                    projectSelect.updateOption(String(project.id), {value: String(project.id), text: project.name});
                    projectSelect.setValue(String(project.id));
                } else {
                    $("#project_id option").filter(function () { return String(this.value) === String(project.id); }).text(project.name);
                    $("#project_id").val(project.id).trigger("change");
                }
                bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEditProject")).hide();
                if (typeof window.showAlert === "function") window.showAlert("success", response.message || "Project updated successfully.");
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) showErrors($form, xhr.responseJSON.errors);
                else if (typeof window.showAlert === "function") window.showAlert("error", xhr.responseJSON?.message || "Unable to update project.");
            },
            complete: function () { $button.prop("disabled", false); $spinner.addClass("d-none"); }
        });
    });

    // Handle Add Customer Modal Submit
    $("#formAddCustomer").on("submit", function (e) {
        e.preventDefault();
        let $form = $(this);
        let url = $form.attr("action");
        let btn = $form.find('button[type="submit"]');
        let spinner = btn.find('.spinner-border');
        
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        clearErrors($form);
        
        $.ajax({
            url: url,
            type: "POST",
            data: $form.serialize(),
            headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }) : {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (response.success && response.data) {
                    let customer = response.data;
                    let tsElement = document.querySelector("#related_id");
                    if (tsElement && tsElement.tomselect) {
                        tsElement.tomselect.addOption({
                            id: customer.id,
                            name: customer.name,
                            email: customer.email || '',
                            phone: customer.phone || ''
                        });
                        tsElement.tomselect.addItem(customer.id);
                    } else {
                        // Fallback in case TomSelect is not used
                        let $select = $("#related_id");
                        $select.append(new Option(customer.name, customer.id, true, true));
                        $select.trigger("change");
                    }
                    $("#modalAddCustomer").modal("hide");
                    $form[0].reset();
                    if (typeof window.showAlert === "function") {
                        window.showAlert("success", "Customer added successfully.");
                    }
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else if (typeof window.showAlert === "function") {
                    window.showAlert("error", xhr.responseJSON?.message || "Error adding customer.");
                }
            }
        });
    });


});
