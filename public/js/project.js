(function () {
    // Wait for DOM to be fully loaded
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.projects || {};
        const searchInput = document.getElementById("projectsSearch");
        const tableBody = document.querySelector("#projectsTable tbody");
        const paginationContainer = document.getElementById(
            "paginationContainer",
        );

        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById("toastContainer");
        if (!toastContainer) {
            toastContainer = document.createElement("div");
            toastContainer.id = "toastContainer";
            toastContainer.className =
                "toast-container position-fixed top-0 end-0 p-3";
            toastContainer.style.zIndex = "1050";
            document.body.appendChild(toastContainer);
        }

        if (!searchInput || !tableBody || !paginationContainer) {
            console.error("Required elements not found");
            return;
        }

        // Store CSRF token
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";

        function authHeaders(extraHeaders = {}) {
            return window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                ...extraHeaders,
            });
        }

        // Toast notification function
        function showToast(message, type = "info", duration = 5000) {
            const toastId = "toast-" + Date.now();
            const bgColor =
                {
                    success: "bg-success",
                    error: "bg-danger",
                    warning: "bg-warning",
                    info: "bg-info",
                }[type] || "bg-info";

            const icon =
                {
                    success: "bi-check-circle-fill",
                    error: "bi-exclamation-triangle-fill",
                    warning: "bi-exclamation-circle-fill",
                    info: "bi-info-circle-fill",
                }[type] || "bi-info-circle-fill";

            const toast = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgColor} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="${duration}">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center">
                            <i class="bi ${icon} me-2"></i>
                            <span>${message}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            toastContainer.insertAdjacentHTML("beforeend", toast);
            const toastElement = new bootstrap.Toast(
                document.getElementById(toastId),
            );
            toastElement.show();

            document
                .getElementById(toastId)
                .addEventListener("hidden.bs.toast", function () {
                    this.remove();
                });
        }

        // Delete project function - jQuery AJAX
        function deleteProject(projectId, button) {
            window.showDeleteConfirm("You want to delete this project. This action cannot be undone.").then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/api/projects/${projectId}`,
                        type: "DELETE",
                        headers: authHeaders({
                            "X-CSRF-TOKEN": csrfToken,
                        }),
                        success: function (response) {
                            if (response.success) {
                                showAlert(
                                    "success",
                                    response.message ||
                                    "Project deleted successfully!",
                                );
                                fetchProjects();
                            } else {
                                showAlert(
                                    "error",
                                    response.message ||
                                    "Failed to delete project.",
                                );
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            }
                        },
                        error: function () {
                            showAlert(
                                "error",
                                "Something went wrong. Please try again.",
                            );
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        const renderPagination = (data) => {
            if (data.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let paginationHtml = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">
                        Showing ${from} to ${to} of ${total} results
                    </div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            // Previous button
            if (data.prev_page_url) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                            <span aria-hidden="true">&laquo; Previous</span>
                        </a>
                    </li>
                `;
            } else {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">&laquo; Previous</span>
                    </li>
                `;
            }

            // Page numbers
            for (let i = 1; i <= lastPage; i++) {
                if (i === currentPage) {
                    paginationHtml += `
                        <li class="page-item active">
                            <span class="page-link">${i}</span>
                        </li>
                    `;
                } else {
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }
            }

            // Next button
            if (data.next_page_url) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                            <span aria-hidden="true">Next &raquo;</span>
                        </a>
                    </li>
                `;
            } else {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">Next &raquo;</span>
                    </li>
                `;
            }

            paginationHtml += `
                            </ul>
                        </nav>
                    </div>
                </div>
            `;

            paginationContainer.innerHTML = paginationHtml;

            // Add click handlers to pagination links
            document
                .querySelectorAll(".page-link[data-page]")
                .forEach((link) => {
                    link.addEventListener("click", (e) => {
                        e.preventDefault();
                        const page =
                            e.target.closest(".page-link").dataset.page;
                        fetchProjects(page);
                    });
                });
        };

        const renderRows = (items, meta = {}) => {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No projects found in the directory.</p>
                            ${permissions.create ? '<a href="/projects/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Project</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items
                .map((project, index) => {
                    const startDate = project.start_date
                        ? new Date(project.start_date).toLocaleDateString(
                            "en-GB",
                            {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                            },
                        )
                        : null;
                    const endDate = project.end_date
                        ? new Date(project.end_date).toLocaleDateString(
                            "en-GB",
                            {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                            },
                        )
                        : null;

                    const statusBadge = (() => {
                        switch (project.status) {
                            case "pending":
                                return "bg-warning";
                            case "ongoing":
                                return "bg-info";
                            case "completed":
                                return "bg-success";
                            case "canceled":
                                return "bg-danger";
                            default:
                                return "bg-secondary";
                        }
                    })();

                    const srNo = meta.from ? meta.from + index : index + 1;
                    const createdByName =
                        project.creator?.name ||
                        project.created_by_user?.name ||
                        "--";
                    const customerName = project.customer
                        ? project.customer.name
                        : "--";

                    return `
                    <tr>
                        <td class="ps-4">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td>
                            <div class="py-1">
                                <div class="fw-bold small">
                                    <span>${project.name ?? "--"}</span>
                                </div>
                                <div class="text-muted small">Project ID: #${String(project.id).padStart(5, "0")}</div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle me-2 text-muted"></i>
                                <span class="small">${customerName}</span>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span class="badge ${statusBadge} rounded-pill px-3">${project.status ? project.status.charAt(0).toUpperCase() + project.status.slice(1) : "--"}</span>
                        </td>
                        <td class="text-start d-none d-md-table-cell">
                            <div class="small fw-semibold text-nowrap">
                                ${startDate
                            ? `
                                    <i class="bi bi-calendar-check me-1 text-muted"></i>${startDate}
                                    ${endDate ? `<br><i class="bi bi-calendar-x me-1 text-muted"></i>${endDate}` : ""}
                                `
                            : '<span class="text-muted">Dates not set</span>'
                        }
                            </div>
                        </td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${permissions.view ? `<a href="/projects/${project.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.edit ? `<a href="/projects/${project.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Details"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-project-id="${project.id}" title="Remove Project"><i class="bi bi-trash text-danger"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-project-id="${project.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${project.id}" style="display: none;">
                        <td colspan="7" class="p-0 border">
                            <div class="details-content p-3 bg-light m-2 rounded">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label text-muted"><i class="fa-solid fa-person"></i> Created By :</div>
                                        <div class="expand-value fw-semibold">${createdByName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label text-muted"><i class="fa-regular fa-building"></i> Customer :</div>
                                        <div class="expand-value fw-semibold">${customerName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label text-muted"><i class="fa-solid fa-signal"></i> Status :</div>
                                        <div class="expand-value fw-semibold"><span class="badge ${statusBadge}">${project.status ? project.status.charAt(0).toUpperCase() + project.status.slice(1) : "--"}</span></div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label text-muted"><i class="fa-solid fa-calendar-days"></i> Timeline :</div>
                                        <div class="expand-value fw-semibold text-end">
                                            ${startDate ? `${startDate} ${endDate ? `- ${endDate}` : ""}` : '<span class="text-muted">Dates not set</span>'}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label text-muted"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${permissions.view ? `<a href="/projects/${project.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                        ${permissions.edit ? `<a href="/projects/${project.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Details"><i class="bi bi-pencil"></i></a>` : ''}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-project-id="${project.id}" title="Remove Project"><i class="bi bi-trash text-danger"></i></button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
                })
                .join("");

            // Attach click handlers to delete buttons
            document.querySelectorAll(".delete-btn").forEach((button) => {
                button.addEventListener("click", function (e) {
                    e.preventDefault();
                    const projectId = this.dataset.projectId;
                    deleteProject(projectId, this);
                });
            });

            // Attach click handlers to expand buttons
            document.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.projectId;
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
        };

        // jQuery AJAX fetch function - Yeh lo, ab jQuery AJAX use kiya!
        const fetchProjects = (page = 1) => {
            let url = `/api/projects?page=${page}`;

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
                headers: authHeaders(),
                beforeSend: function () {
                    // Show loading state if you want
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>`;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    } else {
                        throw new Error("Invalid response format");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching projects:", error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-exclamation-triangle display-1 opacity-25"></i></div>
                                <p class="text-muted">Error loading projects. Please try again.</p>
                            </td>
                        </tr>`;
                },
            });
        };

        let searchTimer;
        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchProjects(1), 300);
        });

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchProjects(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => fetchProjects(1), 0));
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
                fetchProjects(1);
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

        // Initial fetch
        fetchProjects();
    }
})();

// =========================================== Submit ===========================================

$(document).ready(function () {
    function authHeaders(extraHeaders = {}) {
        return window.crmApplyAuthHeaders({
            "X-Requested-With": "XMLHttpRequest",
            ...extraHeaders,
        });
    }

    function showFormToast(message, type = "info") {
        const mappedType =
            {
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

    function clearErrors() {
        $(".is-invalid").removeClass("is-invalid");
        $(".ts-wrapper.is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").html("");
    }

    function showErrors(errors) {
        $.each(errors, function (field, messages) {
            const input = $(`#${field}`);
            const errorDiv = $(`#${field}-error`);

            if (input.length) {
                input.addClass("is-invalid");
                if (input.is("select")) {
                    input.next(".ts-wrapper").addClass("is-invalid");
                }
            }

            if (errorDiv.length) {
                errorDiv.html(messages[0]);
            }
        });
    }

    $("#projectForm").on("submit", function (e) {
        e.preventDefault();

        const $form = $(this);
        const isEdit = $form.find('input[name="_method"]').length > 0;

        clearErrors();

        $("#btnSpinner").removeClass("d-none");
        $("#btnText").text(isEdit ? "Updating..." : "Saving...");
        $("#submitBtn").prop("disabled", true);

        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
            headers: authHeaders({
                "X-CSRF-TOKEN": $('input[name="_token"]').val(),
                Accept: "application/json",
            }),
            success: function (response) {
                $("#btnSpinner").addClass("d-none");
                $("#btnText").text(isEdit ? "Update Project" : "Save Project");
                $("#submitBtn").prop("disabled", false);
                const redirectUrl = response.redirect || "/projects";

                showFormToast(
                    response.message ||
                    (isEdit
                        ? "Project updated successfully!"
                        : "Project created successfully!"),
                    "success",
                );

                if (!isEdit) {
                    $form[0].reset();
                }

                if (isEdit && response.history_entry && window.crmStatusHistory) {
                    $form.find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                setTimeout(function () {
                    window.location.href = redirectUrl;
                }, 300);
            },
            error: function (xhr) {
                $("#btnSpinner").addClass("d-none");
                $("#btnText").text(isEdit ? "Update Project" : "Save Project");
                $("#submitBtn").prop("disabled", false);

                if (xhr.status === 422) {
                    const response = xhr.responseJSON;

                    if (response && response.errors) {
                        showErrors(response.errors);
                        return;
                    }
                }

                if (xhr.status === 419) {
                    showFormToast(
                        "Session expired. Please refresh the page.",
                        "error",
                    );
                } else if (xhr.status === 403) {
                    showFormToast(
                        "You do not have permission to perform this action.",
                        "error",
                    );
                } else if (xhr.status === 404) {
                    showFormToast("Project not found.", "error");
                } else if (xhr.status === 500) {
                    showFormToast(
                        "Server error. Please try again later.",
                        "error",
                    );
                } else {
                    showFormToast("An error occurred. Please try again.", "error");
                }
            },
        });
    });

    $("input, select, textarea").on("input change", function () {
        $(this).removeClass("is-invalid");
        if ($(this).is("select")) {
            $(this).next(".ts-wrapper").removeClass("is-invalid");
        }
        $(`#${$(this).attr("id")}-error`).html("");
    });
});
