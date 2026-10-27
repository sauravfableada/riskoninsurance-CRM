(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        initUsersTable();
        initUserForms();
    }

    function getEnabledModuleActionToggles(row) {
        return Array.from(row.querySelectorAll(".module-action-toggle:not(:disabled)"));
    }

    function syncPermissionRowState(row) {
        const allToggle = row.querySelector(".module-all-toggle");
        const actions = getEnabledModuleActionToggles(row);
        const viewToggle = row.querySelector('.module-action-toggle[value^="view_"]');
        const nonViewActions = actions.filter((item) => item !== viewToggle);

        if (!actions.length) {
            if (allToggle) {
                allToggle.checked = false;
                allToggle.indeterminate = false;
            }
            return;
        }

        if (viewToggle) {
            const hasNonViewChecked = nonViewActions.some((item) => item.checked);
            if (hasNonViewChecked) {
                viewToggle.checked = true;
            }

            if (!viewToggle.checked) {
                nonViewActions.forEach((item) => {
                    item.checked = false;
                });
            }
        }

        if (allToggle) {
            allToggle.checked = actions.every((item) => item.checked);
            allToggle.indeterminate = false;
        }
    }

    function syncPermissionSelectAll(root, selectAll) {
        if (!root || !selectAll) {
            return;
        }

        const rows = Array.from(root.querySelectorAll("tbody tr"));
        const availableRows = rows.filter((row) => {
            const toggle = row.querySelector(".module-all-toggle");
            return toggle && !toggle.disabled;
        });

        selectAll.checked = availableRows.length > 0
            && availableRows.every((row) => row.querySelector(".module-all-toggle").checked);
    }

    function bindPermissionMatrix(root, selectAll, onChange) {
        if (!root) {
            return;
        }

        root.querySelectorAll("tbody tr").forEach((row) => {
            syncPermissionRowState(row);

            const allToggle = row.querySelector(".module-all-toggle");
            const actionToggles = getEnabledModuleActionToggles(row);

            if (allToggle && !allToggle.disabled) {
                allToggle.addEventListener("change", function () {
                    actionToggles.forEach((checkbox) => {
                        checkbox.checked = this.checked;
                    });

                    syncPermissionSelectAll(root, selectAll);
                    if (typeof onChange === "function") {
                        onChange();
                    }
                });
            }

            actionToggles.forEach((checkbox) => {
                checkbox.addEventListener("change", function () {
                    syncPermissionRowState(row);
                    syncPermissionSelectAll(root, selectAll);

                    if (typeof onChange === "function") {
                        onChange();
                    }
                });
            });
        });

        if (selectAll) {
            selectAll.addEventListener("change", function () {
                root.querySelectorAll("tbody tr").forEach((row) => {
                    const allToggle = row.querySelector(".module-all-toggle");
                    if (!allToggle || allToggle.disabled) {
                        syncPermissionRowState(row);
                        return;
                    }

                    getEnabledModuleActionToggles(row).forEach((checkbox) => {
                        checkbox.checked = this.checked;
                    });
                    syncPermissionRowState(row);
                });

                syncPermissionSelectAll(root, selectAll);
                if (typeof onChange === "function") {
                    onChange();
                }
            });
        }

        syncPermissionSelectAll(root, selectAll);
    }

    window.crmUsersPermissionMatrix = {
        init(rootSelector, selectAllSelector) {
            const root = typeof rootSelector === "string" ? document.querySelector(rootSelector) : rootSelector;
            const selectAll = typeof selectAllSelector === "string" ? document.querySelector(selectAllSelector) : selectAllSelector;

            bindPermissionMatrix(root, selectAll);
        },
    };

    function notify(message, type = "info") {
        const mappedType = ({ success: "success", error: "error", warning: "warning", info: "info" })[type] || "info";

        if (typeof window.showAlert === "function") {
            window.showAlert(mappedType, message);
            return;
        }

        if (typeof window.showToast === "function") {
            window.showToast(message, mappedType);
            return;
        }

        window.crmAlert(message);
    }

    function initUsersTable() {
        const tableBody = document.querySelector("#usersTable tbody");
        const paginationContainer = document.getElementById("usersPagination");
        const searchInput = document.getElementById("usersSearch");
        const permissionsModalElement = document.getElementById("permissionsModal");
        const permissionsModalBody = document.getElementById("permissionsModalBody");
        const permissionsTitle = document.getElementById("permissionsModalLabel");

        if (!tableBody || !paginationContainer) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;
        const permissionModules = window.usersPermissionsConfig?.modules || {};
        const permissionActions = Object.keys(window.usersPermissionsConfig?.actions || {});
        const userAccess = window.crmUserPermissions?.users || {};
        const permissionsModal = permissionsModalElement && window.bootstrap ? new bootstrap.Modal(permissionsModalElement) : null;
        let activePermissionUserId = null;
        let permissionsSaveTimer = null;

        function formatDate(dateValue) {
            if (!dateValue) return "--";

            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return "--";

            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        }

        function statusButton(user) {
            const isActive = !!(user.is_active ?? true);
            if (!userAccess.edit) {
                return `<span class="badge rounded-pill ${isActive ? "status-active" : "status-inactive"}">${isActive ? "Active" : "Inactive"}</span>`;
            }
            return `
                <button
                    type="button"
                    class="btn btn-sm rounded-pill px-3 user-status-toggle ${isActive ? "status-active" : "status-inactive"}"
                    data-user-id="${user.id}"
                    data-active="${isActive ? 1 : 0}"
                >
                    ${isActive ? "Active" : "Inactive"}
                </button>
            `;
        }

        function renderRows(items, meta) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-people display-1 opacity-25"></i></div>
                            <p class="text-muted">No users found.</p>
                            ${userAccess.create ? '<a href="/users/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Sub Agent</a>' : ''}
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items
                .map(function (user, index) {
                    const srNo = meta && meta.from ? (meta.from + index) : (index + 1);
                    const permissionButton = userAccess.permissions ? `<a href="#" class="btn btn-dark-blue btn-sm rounded-pill px-3 permissions-trigger-btn user-permissions-btn" data-user-id="${user.id}" data-user-name="${escapeHtml(user.name || "Sub Agent")}">Permissions</a>` : '';
                    const editButton = userAccess.edit ? `<a href="/users/${user.id}/edit" class="btn crm-action-btn btn-sm users-icon-btn" title="Edit"><i class="bi bi-pencil"></i></a>` : '';
                    const mobileEditButton = userAccess.edit ? `<a href="/users/${user.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : '';
                    const deleteButton = userAccess.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger user-delete-btn users-icon-btn" title="Delete" data-user-id="${user.id}"><i class="bi bi-trash text-danger"></i></button>` : '';
                    const mobileDeleteButton = userAccess.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger user-delete-btn" title="Delete" data-user-id="${user.id}"><i class="bi bi-trash text-danger"></i></button>` : '';

                    return `
                        <tr>
                            <td class="ps-4">${srNo}</td>
                            <td class="fw-medium">${escapeHtml(user.name || "--")}</td>
                            <td class="d-none d-md-table-cell">
                                <a href="mailto:${user.email || "--"}" class="text-decoration-none link-hover">
                                    ${escapeHtml(user.email || "--")}
                                </a>
                            </td>
                            <td class="text-center d-none d-md-table-cell">${statusButton(user)}</td>
                            <td class="text-nowrap d-none d-md-table-cell">${formatDate(user.created_at)}</td>
                            <td class="text-center pe-4 d-none d-md-table-cell">
                                <div class="users-action-group">
                                    ${permissionButton}
                                    ${editButton}
                                    <a href="/users/${user.id}" class="btn crm-action-btn btn-sm users-icon-btn" title="View"><i class="bi bi-eye"></i></a>
                                    ${deleteButton}
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-user-id="${user.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${user.id}" style="display: none;">
                            <td colspan="6" class="p-0 border">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-envelope"></i> Email Address :</div>
                                            <div class="expand-value">
                                                <a href="mailto:${user.email || "--"}" class="text-decoration-none link-hover">
                                                    ${escapeHtml(user.email || "--")}
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created At :</div>
                                            <div class="expand-value">${formatDate(user.created_at)}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                            <div class="expand-value">${statusButton(user)}</div>
                                        </div>                                        
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-lock"></i> Permissions :</div>
                                            <div class="expand-value">
                                                ${userAccess.permissions ? `<a href="#" class="btn btn-dark-blue btn-sm rounded-pill px-3 user-permissions-btn" data-user-id="${user.id}" data-user-name="${escapeHtml(user.name || "Sub Agent")}">
                                                    <i class="bi bi-shield-lock me-1"></i> Permissions
                                                </a>` : '--'}
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                            <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                            <div class="expand-actions">
                                                ${mobileEditButton}
                                                <a href="/users/${user.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                                ${mobileDeleteButton}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                })
                .join("");

            tableBody.querySelectorAll(".user-status-toggle").forEach((button) => {
                button.addEventListener("click", function () {
                    toggleStatus(this);
                });
            });

            tableBody.querySelectorAll(".user-delete-btn").forEach((button) => {
                button.addEventListener("click", function () {
                    removeUser(this.dataset.userId, this);
                });
            });

            tableBody.querySelectorAll(".user-permissions-btn").forEach((button) => {
                button.addEventListener("click", function (event) {
                    event.preventDefault();
                    openPermissionsModal(this.dataset.userId, this.dataset.userName || "Sub Agent");
                });
            });

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const userId = this.dataset.userId;
                    const detailsRow = document.getElementById(`details-${userId}`);
                    const icon = this.querySelector("i");

                    if (detailsRow.style.display === "none") {
                        detailsRow.style.display = "table-row";
                        icon.classList.replace("fa-plus", "fa-minus");
                        this.classList.add("active");
                        this.closest("tr").classList.add("bg-light");
                    } else {
                        detailsRow.style.display = "none";
                        icon.classList.replace("fa-minus", "fa-plus");
                        this.classList.remove("active");
                        this.closest("tr").classList.remove("bg-light");
                    }
                });
            });
        }

        function renderPagination(data) {
            if (!data || data.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const currentPage = data.current_page || 1;
            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;

            let html = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            // Pages
            const lastPage = data.last_page || 1;
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

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach((link) => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    fetchUsers(this.dataset.page);
                });
            });
        }

        function toggleStatus(button) {
            if (button.dataset.loading === "1") {
                return;
            }

            const userId = button.dataset.userId;
            const currentActive = button.dataset.active === "1";
            const nextActive = !currentActive;

            button.dataset.loading = "1";
            button.disabled = true;

            fetch(`/api/users/${userId}/status`, {
                method: "PATCH",
                headers: authHeaders({
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                }),
                body: JSON.stringify({ is_active: nextActive ? 1 : 0 }),
            })
                .then((response) => response.json().then((payload) => ({ response, payload })))
                .then(({ response, payload }) => {
                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || "Unable to update user status.");
                    }

                    const isActive = !!payload.is_active;
                    button.dataset.active = isActive ? "1" : "0";
                    button.textContent = isActive ? "Active" : "Inactive";
                    button.classList.toggle("status-active", isActive);
                    button.classList.toggle("status-inactive", !isActive);
                    notify(payload.message || "Status updated successfully.", "success");
                })
                .catch((error) => {
                    notify(error.message || "Unable to update user status.", "error");
                })
                .finally(() => {
                    button.disabled = false;
                    button.dataset.loading = "0";
                });
        }

        function removeUser(userId, button) {
            if (typeof window.showDeleteConfirm !== "function") {
                return;
            }

            window.showDeleteConfirm("Are you sure you want to remove this user?").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;

                $.ajax({
                    url: `/api/users/${userId}`,
                    type: "DELETE",
                    headers: authHeaders({
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (res) {
                        if (res.success) {
                            notify(res.message || "User deleted successfully.", "success");
                            fetchUsers();
                        } else {
                            notify(res.message || "Unable to delete user.", "error");
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function (xhr) {
                        notify(xhr?.responseJSON?.message || "Unable to delete user.", "error");
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
            });
        }

        function fetchUsers(page = 1) {
            let url = `/api/users?page=${page}`;
            if (searchInput && searchInput.value.trim()) {
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
                url,
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                }),
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                    `;
                },
                success: function (res) {
                    if (!res.success || !res.data) {
                        renderRows([], null);
                        renderPagination({ total: 0 });
                        return;
                    }

                    renderRows(res.data.data || [], res.data);
                    renderPagination(res.data);
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Error loading users.</td>
                        </tr>
                    `;
                    paginationContainer.innerHTML = "";
                },
            });
        }

        function buildPermissionRows(selectedPermissions) {
            if (!permissionsModalBody) {
                return;
            }

            const selected = new Set(selectedPermissions || []);
            const actions = permissionActions.length ? permissionActions : ["view", "create", "edit", "delete"];

            permissionsModalBody.innerHTML = Object.entries(permissionModules).map(([module, meta]) => {
                const moduleActions = Array.isArray(meta?.actions) && meta.actions.length ? meta.actions : actions;
                const icon = meta?.icon || "bi-shield-check";
                const label = escapeHtml(meta?.label || module);
                const supportsAll = moduleActions.length === actions.length;

                const actionCells = ["view", "create", "edit", "delete"].map((action) => {
                    if (!moduleActions.includes(action)) {
                        return '<td class="check-cell"><input type="checkbox" class="form-check-input permission-checkbox permission-checkbox-disabled" disabled></td>';
                    }

                    const permissionName = `${action}_${module}`;
                    const checked = selected.has(permissionName) ? "checked" : "";
                    return `<td class="check-cell"><input type="checkbox" class="form-check-input permission-checkbox module-action-toggle" value="${permissionName}" ${checked}></td>`;
                }).join("");

                return `
                    <tr>
                        <td class="module-cell">
                            <div class="d-flex align-items-center gap-2">
                                <span class="module-icon"><i class="bi ${icon}"></i></span>
                                <span class="module-name">${label}</span>
                            </div>
                        </td>
                        ${actionCells}
                        <td class="check-cell"><input type="checkbox" class="form-check-input permission-checkbox module-all-toggle ${supportsAll ? "" : "permission-checkbox-disabled"}" ${supportsAll ? "" : "disabled"}></td>
                    </tr>
                `;
            }).join("");

            bindPermissionMatrix(permissionsModalBody.closest(".permissions-matrix-table"), null, queuePermissionsSave);
        }

        function openPermissionsModal(userId, userName) {
            if (!permissionsModal || !permissionsModalBody) {
                return;
            }

            activePermissionUserId = userId;
            if (permissionsTitle) {
                permissionsTitle.textContent = `Sub Agent Permissions - ${userName}`;
            }

            permissionsModalBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </td>
                </tr>
            `;
            permissionsModal.show();

            fetch(`/api/users/${userId}`, {
                method: "GET",
                headers: authHeaders({
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                }),
            })
                .then((response) => response.json().then((payload) => ({ response, payload })))
                .then(({ response, payload }) => {
                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || "Unable to load permissions.");
                    }

                    const directPermissions = (payload.data?.permissions || []).map((permission) => permission.name).filter(Boolean);
                    buildPermissionRows(directPermissions);
                })
                .catch((error) => {
                    permissionsModalBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(error.message || "Unable to load permissions.")}</td></tr>`;
                });
        }

        function savePermissions() {
            if (!activePermissionUserId || !permissionsModalBody) {
                return;
            }

            const permissions = Array.from(permissionsModalBody.querySelectorAll(".module-action-toggle:checked")).map((checkbox) => checkbox.value);

            fetch(`/api/users/${activePermissionUserId}/permissions`, {
                method: "PUT",
                headers: authHeaders({
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                }),
                body: JSON.stringify({ permissions }),
            })
                .then((response) => response.json().then((payload) => ({ response, payload })))
                .then(({ response, payload }) => {
                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || "Unable to update permissions.");
                    }
                })
                .catch((error) => {
                    notify(error.message || "Unable to update permissions.", "error");
                });
        }

        function queuePermissionsSave() {
            clearTimeout(permissionsSaveTimer);
            permissionsSaveTimer = setTimeout(function () {
                savePermissions();
            }, 180);
        }

        if (permissionsModalElement) {
            permissionsModalElement.addEventListener("hidden.bs.modal", function () {
                activePermissionUserId = null;
                clearTimeout(permissionsSaveTimer);
            });
        }

        if (searchInput) {
            let timer;
            searchInput.addEventListener("input", function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetchUsers(1);
                }, 350);
            });
        }

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchUsers(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => fetchUsers(1), 0));
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
                fetchUsers(1);
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

        fetchUsers();
    }

    function initUserForms() {
        const forms = document.querySelectorAll(".ajax-user-form");
        if (!forms.length) {
            return;
        }

        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        forms.forEach((form) => {
            form.addEventListener("submit", function (event) {
                event.preventDefault();

                clearFormErrors(form);

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalHtml = submitBtn ? submitBtn.innerHTML : "";

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                }

                const formData = new FormData(form);

                $.ajax({
                    url: form.getAttribute("action"),
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: authHeaders({
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (response) {
                        notify(response.message || "User saved successfully.", "success");
                        setTimeout(function () {
                            window.location.href = response.redirect || "/users";
                        }, 300);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.code === "subscription_staff_limit_reached") {
                            const message = xhr.responseJSON?.message || "Sub-agent limit reached for your current plan.";

                            if (typeof Swal !== "undefined") {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error!",
                                    text: message,
                                    confirmButtonText: "OK",
                                });
                            } else {
                                notify(message, "error");
                            }

                            return;
                        }

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            showFormErrors(form, xhr.responseJSON.errors);
                            return;
                        }

                        notify(xhr.responseJSON?.message || "Unable to save user.", "error");
                    },
                    complete: function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    },
                });
            });
        });
    }

    function clearFormErrors(form) {
        form.querySelectorAll(".is-invalid").forEach((element) => element.classList.remove("is-invalid"));
        form.querySelectorAll(".invalid-feedback.ajax-error").forEach((element) => element.remove());
        form.querySelectorAll(".staff-validation").forEach((element) => {
            element.textContent = "";
            element.style.display = "none";
        });
    }

    function showFormErrors(form, errors) {
        Object.keys(errors).forEach((field) => {
            const input = form.querySelector(`[name="${field}"]`) || form.querySelector(`[name="${field}[]"]`);
            if (!input) {
                return;
            }

            input.classList.add("is-invalid");
            const message = Array.isArray(errors[field]) ? errors[field][0] : String(errors[field]);

            const wrapper = input.closest(".col-12, .col-md-6, .col-md-12") || input.parentElement;
            const existing = wrapper ? wrapper.querySelector(`#${field}-error`) : null;
            if (existing) {
                existing.textContent = message;
                existing.style.display = "block";
                return;
            }

            const feedback = document.createElement("div");
            feedback.className = "invalid-feedback ajax-error d-block";
            feedback.textContent = message;
            const inputGroup = input.closest(".input-group");
            (inputGroup || input).insertAdjacentElement("afterend", feedback);
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
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();





document.addEventListener('DOMContentLoaded', function () {
    const selectAllBtn = document.getElementById('selectAllPermissions');
    const permissionCheckboxes = document.querySelectorAll('.permission-matrix-card .permission-checkbox:not(:disabled)');

    if (selectAllBtn) {
        selectAllBtn.addEventListener('change', function () {
            const isChecked = this.checked;
            permissionCheckboxes.forEach(checkbox => {
                // Don't toggle the row 'all' checkboxes directly here, or we can, it doesn't matter much.
                // But it's better to toggle all checkboxes.
                checkbox.checked = isChecked;
            });
        });

        // Optional: Uncheck "Select All" if any individual checkbox is manually unchecked
        permissionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAllBtn.checked = false;
                } else {
                    const allChecked = Array.from(permissionCheckboxes).every(c => c.checked);
                    selectAllBtn.checked = allChecked;
                }
            });
        });
    }

    // Row-level "All" toggle functionality
    const rowAllToggles = document.querySelectorAll('.module-all-toggle:not(:disabled)');
    rowAllToggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const isChecked = this.checked;
            // Find all permission checkboxes in the same row
            const rowCheckboxes = this.closest('tr').querySelectorAll('.module-action-toggle:not(:disabled)');
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                // Dispatch change event to trigger the main "Select All" logic if needed
                checkbox.dispatchEvent(new Event('change'));
            });
        });
    });

    // Update row "All" checkbox if individual row checkboxes are changed
    const rowActionToggles = document.querySelectorAll('.module-action-toggle:not(:disabled)');
    rowActionToggles.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const row = this.closest('tr');
            const viewCheckbox = row.querySelector('.module-action-toggle[value^="view_"]');
            const nonViewCheckboxes = Array.from(row.querySelectorAll('.module-action-toggle:not(:disabled)'))
                .filter(item => item !== viewCheckbox);

            if (viewCheckbox) {
                const isNonViewPermission = this !== viewCheckbox;

                if (isNonViewPermission && this.checked) {
                    viewCheckbox.checked = true;
                }

                if (this === viewCheckbox && !this.checked) {
                    nonViewCheckboxes.forEach(item => {
                        item.checked = false;
                    });
                }
            }

            const rowAllToggle = row.querySelector('.module-all-toggle:not(:disabled)');
            if (rowAllToggle) {
                const rowCheckboxes = Array.from(row.querySelectorAll('.module-action-toggle:not(:disabled)'));
                if (!this.checked) {
                    rowAllToggle.checked = false;
                } else {
                    const allRowChecked = rowCheckboxes.every(c => c.checked);
                    rowAllToggle.checked = allRowChecked;
                }
            }
        });
    });

    // Initialize state on page load for pre-checked items (e.g., in users/edit)
    document.querySelectorAll('.permission-matrix-card tbody tr').forEach(row => {
        const rowAllToggle = row.querySelector('.module-all-toggle:not(:disabled)');
        if (rowAllToggle) {
            const rowCheckboxes = Array.from(row.querySelectorAll('.module-action-toggle:not(:disabled)'));
            if (rowCheckboxes.length > 0 && rowCheckboxes.every(c => c.checked)) {
                rowAllToggle.checked = true;
            }
        }
    });

    // Initialize master "Select All" state on page load
    if (selectAllBtn && permissionCheckboxes.length > 0) {
        selectAllBtn.checked = Array.from(permissionCheckboxes).every(c => c.checked);
    }
});


function showImportDialog() {
    Swal.fire({
        html: `
                        <div class="mt-4 mb-3">
                            <i class="fa-solid fa-file-csv fa-4x text-dark-blue"></i>
                        </div>
                        <h2 class="fw-bold mb-3" style="font-size: 1.75rem; color: #333;">Import CSV</h2>
                        <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.5;">
                            Would you like to import a CSV or download the demo template?
                        </p>
                    `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa-solid fa-upload me-1"></i> Import CSV',
        denyButtonText: '<i class="fa-solid fa-download me-1"></i> Download Demo',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-outline-dark-blue me-2',
            denyButton: 'btn btn-dark-blue me-2',
            cancelButton: 'btn btn-outline-dark-blue'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('usersImportFile').click();
        } else if (result.isDenied) {
            downloadCsvDemo();
        }
    });
}

function downloadCsvDemo() {
    const csvContent = "data:text/csv;charset=utf-8,Name,Email,Contact,Address,Role\n" +
        "John Doe,john@example.com,9876543210,123 Street City,staff\n" +
        "Jane Smith,jane@example.com,9876543211,456 Avenue City,admin";
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "staff_demo.csv");
    document.body.appendChild(link);
    link.click();
    link.remove();
}
