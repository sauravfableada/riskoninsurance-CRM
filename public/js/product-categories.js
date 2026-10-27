(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const config = window.productCategoriesConfig || {};
        const tableBody = document.querySelector("#productCategoriesTable tbody");
        const searchInput = document.getElementById("categorySearch");
        const paginationContainer = document.getElementById("productCategoriesPagination");
        const modalEl = document.getElementById("productCategoryModal");
        const form = document.getElementById("productCategoryForm");
        let searchTimer = null;
        let modal = null;

        if (!config.indexUrl || !tableBody || !searchInput || !modalEl || !form) {
            return;
        }

        function notify(message, type = "info") {
            if (typeof window.showAlert === "function") {
                window.showAlert(type, message);
                return;
            }

            window.crmAlert(message);
        }

        function buildUrl(template, id) {
            return (template || "").replace("__ID__", id);
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

        function formatDate(value) {
            if (!value) {
                return "--";
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return "--";
            }

            const day = String(date.getDate()).padStart(2, "0");
            const month = date.toLocaleString("en-GB", { month: "short" });
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, "0");
            const minutes = String(date.getMinutes()).padStart(2, "0");
            const seconds = String(date.getSeconds()).padStart(2, "0");

            return `${day} ${month} ${year}, ${hours}:${minutes}:${seconds}`;
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

        function statusBadge(category) {
            const isActive = Boolean(Number(category.is_active)) || category.is_active === true;

            return `
                <div class="form-check form-switch status-switch d-inline-flex">
                    <input
                        class="form-check-input toggleCategoryStatus"
                        type="checkbox"
                        role="switch"
                        data-id="${category.id}"
                        ${isActive ? "checked" : ""}
                        aria-label="Toggle category status"
                        title="${isActive ? "Active" : "Inactive"}"
                    >
                </div>
            `;
        }

        function renderRows(categories, meta) {
            if (!categories.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No categories found in the directory.</p>
                            <button class="btn btn-dark-blue btn-sm rounded-pill px-4 addCategoryBtn">Create Your First Category</button>
                        </td>
                    </tr>
                `;

                return;
            }

            tableBody.innerHTML = categories
                .map((category, index) => {
                    const srNo = meta && meta.from ? meta.from + index : index + 1;
                    const categoryName = escapeHtml(category.name ?? "--");
                    const createdAt = escapeHtml(formatDate(category.created_at));

                    return `
                    <tr>
                        <td class="ps-4">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td>
                            <div class="fw-bold small">${categoryName}</div>
                        </td>
                        <td class="text-center d-none d-md-table-cell">${statusBadge(category)}</td>
                        <td class="text-muted d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                <button
                                    type="button"
                                    class="btn crm-action-btn btn-sm editCategory"
                                    data-id="${category.id}"
                                    data-name="${categoryName}"
                                    title="Edit"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn crm-action-btn btn-sm text-danger deleteCategory"
                                    data-id="${category.id}"
                                    title="Delete"
                                >
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-category-id="${category.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${category.id}" style="display: none;">
                        <td colspan="6" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-toggle-on"></i> Status :</div>
                                        <div class="expand-value text-end">${statusBadge(category)}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <button
                                            type="button"
                                            class="btn crm-action-btn btn-sm editCategory"
                                            data-id="${category.id}"
                                            data-name="${categoryName}"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn crm-action-btn btn-sm text-danger deleteCategory"
                                            data-id="${category.id}"
                                            title="Delete"
                                        >
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                })
                .join("");

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.categoryId;
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
        }

        function renderPagination(data) {
            if (!paginationContainer) {
                return;
            }

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
                html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
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
                html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            }

            html += `</ul></div>`;
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach((link) => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    fetchCategories(Number(this.dataset.page), searchInput.value.trim());
                });
            });
        }

        async function fetchCategories(page = 1, query = "") {
            const params = new URLSearchParams();
            params.set("page", page);

            if (query) {
                params.set("search", query);
            }

            const url = `${config.indexUrl}?${params.toString()}`;

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </td>
                </tr>
            `;

            try {
                const response = await api(url, { method: "GET" });
                renderRows(response?.data?.data || [], response?.data);
                renderPagination(response?.data);
            } catch (_) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            Error loading categories. Please try again.
                        </td>
                    </tr>
                `;
                if (paginationContainer) {
                    paginationContainer.innerHTML = "";
                }
            }
        }

        function clearErrors() {
            const nameInput = document.getElementById("productCategoryName");
            const nameError = document.getElementById("product-category-name-error");

            [nameInput].forEach((input) => input?.classList.remove("is-invalid"));

            if (nameError) {
                nameError.textContent = "";
            }
        }

        function showErrors(errors) {
            Object.entries(errors || {}).forEach(([field, messages]) => {
                const input = field === "name"
                    ? document.getElementById("productCategoryName")
                        : null;

                const errorDiv = field === "name"
                    ? document.getElementById("product-category-name-error")
                        : null;

                if (input) {
                    input.classList.add("is-invalid");
                }

                if (errorDiv) {
                    errorDiv.textContent = messages[0];
                }
            });
        }

        function resetCategoryForm() {
            const titleEl = document.getElementById("productCategoryModalTitle");
            const methodInput = document.getElementById("productCategoryFormMethod");
            const nameInput = document.getElementById("productCategoryName");
            const submitBtn = document.getElementById("productCategorySubmitBtn");

            form.reset();
            form.setAttribute("action", config.storeUrl);
            form.dataset.submitMethod = "POST";

            if (methodInput) {
                methodInput.value = "";
                methodInput.disabled = true;
            }

            if (titleEl) {
                titleEl.textContent = "Add Category";
            }

            if (submitBtn) {
                submitBtn.textContent = "Save";
            }

            if (nameInput) {
                nameInput.value = "";
            }

            clearErrors();
        }

        function openCategoryModal(configData = {}) {
            const titleEl = document.getElementById("productCategoryModalTitle");
            const methodInput = document.getElementById("productCategoryFormMethod");
            const nameInput = document.getElementById("productCategoryName");
            const submitBtn = document.getElementById("productCategorySubmitBtn");

            modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            resetCategoryForm();

            if (titleEl) {
                titleEl.textContent = configData.title || "Add Category";
            }

            form.setAttribute("action", configData.action || config.storeUrl);
            form.dataset.submitMethod = configData.method || "POST";

            if (methodInput) {
                methodInput.value = configData.method === "PUT" ? "PUT" : "";
                methodInput.disabled = configData.method !== "PUT";
            }

            if (nameInput) {
                nameInput.value = configData.name || "";
            }

            if (submitBtn) {
                submitBtn.textContent = configData.submitText || "Save";
            }

            modal.show();

            if (nameInput) {
                nameInput.focus();
            }
        }

        async function toggleStatus(input) {
            const id = input.dataset.id;
            const nextState = input.checked;
            const formData = new FormData();
            formData.append("_method", "PATCH");
            formData.append("is_active", nextState ? "1" : "0");

            try {
                const response = await api(buildUrl(config.toggleUrlTemplate, id), {
                    method: "POST",
                    body: formData,
                });

                const isActive = Boolean(response?.data?.is_active);
                input.checked = isActive;
                input.title = isActive ? "Active" : "Inactive";

                notify(response?.message || "Status updated successfully.", "success");
            } catch (error) {
                input.checked = !nextState;
                notify(error.payload?.message || "Unable to update status.", "error");
            }
        }

        modalEl.addEventListener("hidden.bs.modal", resetCategoryForm);

        document.addEventListener("click", async (event) => {
            const addBtn = event.target.closest(".addCategoryBtn");
            const editBtn = event.target.closest(".editCategory");
            const deleteBtn = event.target.closest(".deleteCategory");
            if (addBtn) {
                event.preventDefault();
                openCategoryModal({
                    title: "Add Category",
                    action: config.storeUrl,
                    method: "POST",
                    submitText: "Save",
                });
                return;
            }

            if (editBtn) {
                event.preventDefault();
                openCategoryModal({
                    title: "Edit Category",
                    action: buildUrl(config.updateUrlTemplate, editBtn.dataset.id),
                    method: "PUT",
                    name: editBtn.dataset.name || "",
                    submitText: "Update",
                });
                return;
            }

            if (deleteBtn) {
                event.preventDefault();
                const result = await window.showDeleteConfirm("Delete this category?");
                if (!result.isConfirmed) {
                    return;
                }

                const formData = new FormData();
                formData.append("_method", "DELETE");

                try {
                    const response = await api(buildUrl(config.destroyUrlTemplate, deleteBtn.dataset.id), {
                        method: "POST",
                        body: formData,
                    });
                    notify(response?.message || "Category deleted successfully.", "success");
                    fetchCategories(1, searchInput.value.trim());
                } catch (error) {
                    notify(error.payload?.message || "Unable to delete category.", "error");
                }
                return;
            }

        });

        document.addEventListener("change", (event) => {
            const toggleInput = event.target.closest(".toggleCategoryStatus");
            if (!toggleInput) {
                return;
            }

            toggleStatus(toggleInput);
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            clearErrors();

            const formData = new FormData(form);
            const action = form.getAttribute("action") || config.storeUrl;
            const submitMethod = form.dataset.submitMethod || "POST";
            const isEdit = submitMethod === "PUT";

            formData.delete("_method");
            if (isEdit) {
                formData.append("_method", "PUT");
            }

            try {
                const response = await api(action, {
                    method: "POST",
                    body: formData,
                });

                notify(response?.message || (isEdit ? "Category updated successfully." : "Category created successfully."), "success");

                if (modal) {
                    modal.hide();
                }

                fetchCategories(1, searchInput.value.trim());
            } catch (error) {
                if (error.payload?.errors) {
                    showErrors(error.payload.errors);
                    return;
                }

                notify(error.payload?.message || "Unable to save category.", "error");
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                fetchCategories(1, searchInput.value.trim());
            }, 300);
        });

        document.addEventListener("input", (event) => {
            const input = event.target.closest("#productCategoryName");
            if (!input) {
                return;
            }

            input.classList.remove("is-invalid");

            if (input.id === "productCategoryName") {
                const errorDiv = document.getElementById("product-category-name-error");
                if (errorDiv) {
                    errorDiv.textContent = "";
                }
            }
        });

        resetCategoryForm();
        fetchCategories();
    }
})();
