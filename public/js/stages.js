(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const API_BASE_URL = "/api/masters/stages";
        const permissions = window.crmUserPermissions?.stages || {};
        const tableBody = document.querySelector("#stagesTable tbody");
        const paginationContainer = document.getElementById("stagesPaginationContainer");
        const searchInput = document.getElementById("stageSearch");
        const countText = document.getElementById("stagesCountText");
        const modalEl = document.getElementById("stageModal");
        const form = document.getElementById("stageForm");
        let searchTimer = null;
        let modal = null;

        if (!tableBody || !searchInput || !modalEl || !form) {
            return;
        }

        function showToast(message, type = "info") {
            const mappedType = {
                success: "success",
                error: "error",
                warning: "warning",
                info: "info",
            }[type] || "info";

            if (typeof window.showAlert === "function") {
                window.showAlert(mappedType, message);
                return;
            }

            window.crmAlert(message);
        }

        async function api(url, options = {}) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const baseHeaders = {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...options.headers,
            };

            if (csrf) {
                baseHeaders["X-CSRF-TOKEN"] = csrf;
            }

            const headers = typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(baseHeaders)
                : baseHeaders;

            const response = await fetch(url, {
                ...options,
                headers,
            });

            const payload = response.status === 204 ? null : await response.json().catch(() => null);

            if (!response.ok) {
                const error = new Error(payload?.message || "Request failed");
                error.payload = payload;
                throw error;
            }

            return payload;
        }

        function formatStatus(status) {
            if (status === "paused") {
                return {
                    label: "Paused",
                    className: "bg-danger-subtle text-danger",
                };
            }

            if (status === "completed") {
                return {
                    label: "Completed",
                    className: "bg-success-subtle text-success",
                };
            }

            return {
                label: "In Progress",
                className: "bg-primary-subtle text-primary",
            };
        }

        function formatDate(value) {
            if (!value) {
                return "--";
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return "--";
            }

            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        }

        const renderPagination = (page) => {
            if (!page || page.total === 0 || !paginationContainer) {
                if (paginationContainer) paginationContainer.innerHTML = "";
                return;
            }

            const from = page.from || 0;
            const to = page.to || 0;
            const total = page.total || 0;

            let paginationHtml = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">
                        Showing ${from} to ${to} of ${total} results
                    </div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            if (page.prev_page_url) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-url="${page.prev_page_url}" aria-label="Previous">
                            <span aria-hidden="true">Previous</span>
                        </a>
                    </li>
                `;
            } else {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">Previous</span>
                    </li>
                `;
            }

            const currentPage = page.current_page || 1;
            const lastPage = page.last_page || 1;

            for (let i = 1; i <= lastPage; i++) {
                if (i === currentPage) {
                    paginationHtml += `
                        <li class="page-item active">
                            <span class="page-link">${i}</span>
                        </li>
                    `;
                } else {
                    const pageUrl = (page.path || API_BASE_URL) + '?page=' + i;
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" data-url="${pageUrl}">${i}</a>
                        </li>
                    `;
                }
            }

            if (page.next_page_url) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-url="${page.next_page_url}" aria-label="Next">
                            <span aria-hidden="true">Next</span>
                        </a>
                    </li>
                `;
            } else {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">Next</span>
                    </li>
                `;
            }

            paginationHtml += `
                            </ul>
                </div>
            `;

            paginationContainer.innerHTML = paginationHtml;

            document
                .querySelectorAll(".page-link[data-url]")
                .forEach((link) => {
                    link.addEventListener("click", (e) => {
                        e.preventDefault();
                        fetchStages(link.dataset.url);
                    });
                });
        };

        function renderRows(stages) {
            if (!stages || !stages.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No stages found in the directory.</p>
                            ${permissions.create ? '<button class="btn btn-dark-blue btn-sm rounded-pill px-4 addStageBtn">Create Your First Stage</button>' : ''}
                        </td>
                    </tr>
                `;

                if (countText) {
                    countText.textContent = "0 stages";
                }
                return;
            }

            tableBody.innerHTML = stages
                .map((stage, index) => {
                    const status = formatStatus(stage.status);
                    const rowNumber = index + 1;
                    const dateRaw = stage.created_at;
                    const formattedDate = formatDate(dateRaw);
                    const statusHtml = `<span class="badge rounded-pill ${status.className}">${status.label}</span>`;

                    return `
                        <tr>
                            <td class="ps-4" data-label="Sr.No">${rowNumber}</td>
                            <td class="fw-semibold" data-label="Stage Name">${stage.name ?? "--"}</td>
                            <td class="text-center d-none d-md-table-cell" data-label="Status">
                                ${statusHtml}
                            </td>
                            <td class="text-muted d-none d-md-table-cell" data-label="Created At">${formattedDate}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell" data-label="Actions">
                                <div class="d-inline-flex align-items-center gap-2">
                                    ${permissions.edit ? `
                                    <button type="button" class="btn crm-action-btn btn-sm editStage" data-id="${stage.id}" data-name="${stage.name ?? ""}" data-status="${stage.status ?? "in_progress"}" title="Edit"><i class="bi bi-pencil"></i></button>` : ''}
                                    ${permissions.delete ? `
                                    <button type="button" class="btn crm-action-btn btn-sm text-danger deleteStage" data-id="${stage.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ''}
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-stage-id="${stage.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${stage.id}" style="display: none;">
                            <td colspan="6" class="p-0 border">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                            <div class="expand-value">${statusHtml}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-regular fa-calendar"></i> Created At :</div>
                                            <div class="expand-value text-muted">${formattedDate}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                            <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                ${permissions.edit ? `<button type="button" class="btn crm-action-btn btn-sm editStage" data-id="${stage.id}" data-name="${stage.name ?? ""}" data-status="${stage.status ?? "in_progress"}"><i class="bi bi-pencil"></i></button>` : ''}
                                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteStage" data-id="${stage.id}"><i class="bi bi-trash text-danger"></i></button>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                })
                .join("");

            if (countText) {
                countText.textContent = `${stages.length} stage${stages.length === 1 ? "" : "s"}`;
            }

            document.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.stageId;
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

        async function fetchStages(url = null) {
            let apiUrl = url || API_BASE_URL;

            const params = new URLSearchParams();
            if (searchInput.value.trim()) {
                params.set("search", searchInput.value.trim());
            }

            const urlObj = new URL(apiUrl, window.location.origin);
            params.forEach((value, key) => {
                urlObj.searchParams.set(key, value);
            });

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </td>
                </tr>
            `;

            try {
                const response = await api(urlObj.toString(), { method: "GET" });
                const page = response?.data;
                if (page && typeof page === 'object' && !Array.isArray(page) && page.data) {
                    renderRows(page.data || []);
                    renderPagination(page);
                } else {
                    renderRows(page || []);
                    if (paginationContainer) paginationContainer.innerHTML = "";
                }
            } catch (_) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            Error loading stages. Please try again.
                        </td>
                    </tr>
                `;
            }
        }

        function clearErrors() {
            const nameInput = document.getElementById("stageName");
            const nameError = document.getElementById("name-error");
            const statusInput = document.getElementById("stageStatus");
            const statusError = document.getElementById("status-error");

            if (nameInput) {
                nameInput.classList.remove("is-invalid");
            }
            if (nameError) {
                nameError.textContent = "";
            }
            if (statusInput) {
                statusInput.classList.remove("is-invalid");
            }
            if (statusError) {
                statusError.textContent = "";
            }
        }

        function showErrors(errors) {
            Object.keys(errors || {}).forEach((field) => {
                const input = field === "name"
                    ? document.getElementById("stageName")
                    : field === "status"
                        ? document.getElementById("stageStatus")
                        : null;
                const errorDiv = field === "name"
                    ? document.getElementById("name-error")
                    : field === "status"
                        ? document.getElementById("status-error")
                        : null;

                if (input) {
                    input.classList.add("is-invalid");
                }
                if (errorDiv) {
                    errorDiv.textContent = errors[field][0];
                }
            });
        }

        function resetStageForm() {
            const titleEl = document.getElementById("stageModalTitle");
            const methodInput = document.getElementById("stageFormMethod");
            const nameInput = document.getElementById("stageName");
            const statusInput = document.getElementById("stageStatus");
            const submitBtn = document.getElementById("stageSubmitBtn");

            form.reset();
            form.setAttribute("action", API_BASE_URL);
            form.dataset.submitMethod = "POST";

            if (methodInput) {
                methodInput.value = "";
                methodInput.disabled = true;
            }
            if (titleEl) {
                titleEl.textContent = "Add Stage";
            }
            if (submitBtn) {
                submitBtn.textContent = "Save";
            }
            if (nameInput) {
                nameInput.value = "";
            }
            if (statusInput) {
                statusInput.value = "in_progress";
            }

            clearErrors();
        }

        function openStageModal(config = {}) {
            const titleEl = document.getElementById("stageModalTitle");
            const methodInput = document.getElementById("stageFormMethod");
            const nameInput = document.getElementById("stageName");
            const statusInput = document.getElementById("stageStatus");
            const submitBtn = document.getElementById("stageSubmitBtn");

            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            resetStageForm();

            if (titleEl) {
                titleEl.textContent = config.title || "Add Stage";
            }
            form.setAttribute("action", config.action || API_BASE_URL);
            form.dataset.submitMethod = config.method || "POST";

            if (methodInput) {
                methodInput.value = config.method === "PUT" ? "PUT" : "";
                methodInput.disabled = config.method !== "PUT";
            }
            if (nameInput) {
                nameInput.value = config.name || "";
            }
            if (statusInput) {
                statusInput.value = config.status || "in_progress";
            }
            if (submitBtn) {
                submitBtn.textContent = config.submitText || "Save";
            }

            modal.show();
            if (nameInput) {
                nameInput.focus();
            }
        }

        modalEl.addEventListener("hidden.bs.modal", resetStageForm);

        document.addEventListener("click", async (event) => {
            const addBtn = event.target.closest(".addStageBtn");
            const editBtn = event.target.closest(".editStage");
            const deleteBtn = event.target.closest(".deleteStage");

            if (addBtn) {
                event.preventDefault();
                openStageModal({
                    title: "Add Stage",
                    action: API_BASE_URL,
                    method: "POST",
                    status: "in_progress",
                    submitText: "Save",
                });
                return;
            }

            if (editBtn) {
                event.preventDefault();
                openStageModal({
                    title: "Edit Stage",
                    action: `${API_BASE_URL}/${editBtn.dataset.id}`,
                    method: "PUT",
                    name: editBtn.dataset.name || "",
                    status: editBtn.dataset.status || "in_progress",
                    submitText: "Update",
                });
                return;
            }

            if (deleteBtn) {
                event.preventDefault();
                const result = await window.showDeleteConfirm("Delete this stage?");
                if (!result.isConfirmed) {
                    return;
                }

                const body = new FormData();
                body.append("_method", "DELETE");

                try {
                    const response = await api(`${API_BASE_URL}/${deleteBtn.dataset.id}`, {
                        method: "POST",
                        body,
                    });
                    showToast(response?.message || "Stage deleted successfully.", "success");
                    fetchStages();
                } catch (error) {
                    showToast(error.payload?.message || "Unable to delete stage.", "error");
                }
            }
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            clearErrors();

            const body = new FormData(form);
            const action = form.getAttribute("action") || API_BASE_URL;
            const submitMethod = form.dataset.submitMethod || "POST";
            const isEdit = submitMethod === "PUT";
            const submittedStatus = body.get("status");

            body.delete("_method");
            if (isEdit) {
                body.append("_method", "PUT");
            }

            try {
                const response = await api(action, {
                    method: "POST",
                    body,
                });

                if (isEdit) {
                    const statusLabel = formatStatus(submittedStatus).label;
                    showToast(response?.message || `Stage updated successfully. Status set to ${statusLabel}.`, "success");
                } else {
                    showToast(response?.message || "Stage created successfully.", "success");
                }

                if (modal) {
                    modal.hide();
                }

                fetchStages();
            } catch (error) {
                if (error.payload?.errors) {
                    showErrors(error.payload.errors);
                    return;
                }

                showToast(error.payload?.message || "Unable to save stage.", "error");
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                fetchStages();
            }, 300);
        });

        document.addEventListener("input", (event) => {
            const input = event.target.closest("#stageName");
            if (!input) {
                return;
            }

            input.classList.remove("is-invalid");
            const errorDiv = document.getElementById("name-error");
            if (errorDiv) {
                errorDiv.textContent = "";
            }
        });

        document.addEventListener("change", (event) => {
            const input = event.target.closest("#stageStatus");
            if (!input) {
                return;
            }

            input.classList.remove("is-invalid");
            const errorDiv = document.getElementById("status-error");
            if (errorDiv) {
                errorDiv.textContent = "";
            }
        });

        resetStageForm();
        fetchStages();
    }
})();
