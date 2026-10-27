(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.pipeline || {};
        const searchInput = document.getElementById("pipelineSearch");
        const tableBody = document.querySelector("#pipelineTable tbody");
        const paginationContainer = document.getElementById(
            "paginationContainer",
        );

        if (!searchInput || !tableBody || !paginationContainer) {
            return;
        }

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        function showToast(message, type = "info") {
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

        function deletePipeline(pipelineId, button) {
            window.showDeleteConfirm("Are you sure you want to delete this pipeline?").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;

                fetch(`/api/pipelines/${pipelineId}`, {
                    method: "DELETE",
                    headers: authHeaders({
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            showToast(
                                data.message || "Pipeline deleted successfully!",
                                "success",
                            );

                            const row = button.closest("tr");
                            const detailsRow =
                                row && row.nextElementSibling?.classList.contains("details-row")
                                    ? row.nextElementSibling
                                    : null;
                            row.style.transition = "opacity 0.3s ease";
                            row.style.opacity = "0";
                            if (detailsRow) {
                                detailsRow.style.transition = "opacity 0.3s ease";
                                detailsRow.style.opacity = "0";
                            }

                            setTimeout(() => {
                                row.remove();
                                if (detailsRow) {
                                    detailsRow.remove();
                                }
                                if (
                                    document.querySelectorAll("#pipelineTable tbody > tr:not(.details-row)").length === 0
                                ) {
                                    fetchPipelines();
                                }
                            }, 300);
                        } else {
                            showToast(
                                data.message || "Failed to delete pipeline.",
                                "error",
                            );
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    })
                    .catch(() => {
                        showToast("An error occurred. Please try again.", "error");
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    });
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
            if (!page || page.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = page.from || 0;
            const to = page.to || 0;
            const total = page.total || 0;
            const currentPage = page.current_page || 1;
            const lastPage = page.last_page || 1;

            let paginationHtml = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">
                        Showing ${from} to ${to} of ${total} results
                    </div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            if (page.prev_page_url && currentPage > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    paginationHtml += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            if (page.next_page_url && currentPage < lastPage) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            paginationHtml += `</ul></div>`;

            paginationContainer.innerHTML = paginationHtml;

            paginationContainer
                .querySelectorAll(".page-link[data-page]")
                .forEach((link) => {
                    link.addEventListener("click", (e) => {
                        e.preventDefault();
                        fetchPipelines(Number(link.dataset.page));
                    });
                });
        };

        const mapStatus = (status) => {
            const map = {
                in_progress: { label: "In-Process", cls: "bg-info" },
                paused: { label: "Paused", cls: "bg-warning" },
                completed: { label: "Completed", cls: "bg-success" },
            };
            if (!status) return { label: "--", cls: "bg-secondary" };
            const normalized = status.toString().toLowerCase();
            return map[normalized] || {
                label: status
                    .toString()
                    .replace(/_/g, " ")
                    .replace(/\b\w/g, (c) => c.toUpperCase()),
                cls: "bg-secondary",
            };
        };

        const renderRows = (items, page) => {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No pipelines added yet.</p>
                            ${permissions.create ? '<a href="/pipeline/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Pipeline</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items
                .map((pipeline, index) => {
                    const statusInfo = mapStatus(pipeline.status);
                    const customerName = escapeHtml(pipeline.customer?.name || "--");
                    const pipelineName = escapeHtml(pipeline.pipeline_name || "--");
                    const stageName = escapeHtml(pipeline.stage?.name || "--");
                    const createdAt = escapeHtml(formatDate(pipeline.created_at));
                    const rowNumber =
                        (page?.from ? page.from + index : index + 1) ||
                        pipeline.row_number ||
                        pipeline.sr_no ||
                        pipeline.srNo ||
                        index + 1;

                    return `
                    <tr>
                        <td class="ps-4">
                            <span class="text-muted small fw-medium">${rowNumber}</span>
                        </td>
                        <td class="d-none d-md-table-cell" data-label="Customer Name">${pipeline.customer?.name || "--"}</td>
                        <td data-label="Pipeline Name">${pipeline.pipeline_name || "--"}</td>
                        <td class="d-none d-md-table-cell">${stageName}</td>
                        <td class="d-none d-md-table-cell">
                            <span class="badge crm-status-pill rounded-pill ${statusInfo.cls}">${statusInfo.label}</span>
                        </td>
                        <td class="d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${permissions.edit ? `<a href="/pipeline/${pipeline.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/pipeline/${pipeline.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-pipeline-id="${pipeline.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-pipeline-id="${pipeline.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${pipeline.id}" style="display: none;">
                        <td colspan="7" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Customer :</div>
                                        <div class="expand-value text-end">${customerName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-regular fa-folder-open"></i> Stage :</div>
                                        <div class="expand-value text-end">${stageName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                        <div class="expand-value text-end">
                                            <span class="badge crm-status-pill rounded-pill ${statusInfo.cls}">${statusInfo.label}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${permissions.edit ? `<a href="/pipeline/${pipeline.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                                        ${permissions.view ? `<a href="/pipeline/${pipeline.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-pipeline-id="${pipeline.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ""}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
                })
                .join("");

            document.querySelectorAll(".delete-btn").forEach((button) => {
                button.addEventListener("click", function (e) {
                    e.preventDefault();
                    const pipelineId = this.dataset.pipelineId;
                    deletePipeline(pipelineId, this);
                });
            });

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.pipelineId;
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
        };

        const fetchPipelines = (page = 1) => {
            let apiUrl = "/api/pipelines";

            const params = new URLSearchParams();
            params.set("page", page);
            if (searchInput.value.trim()) {
                params.set("search", searchInput.value.trim());
            }

            const urlObj = new URL(apiUrl, window.location.origin);
            params.forEach((value, key) => {
                urlObj.searchParams.set(key, value);
            });

            fetch(urlObj.toString(), {
                headers: authHeaders({
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                }),
            })
                .then((res) => res.json())
                .then((response) => {
                    const page = response.data || {};
                    const items = page.data || [];
                    renderRows(items, page);
                    renderPagination(page);
                })
                .catch(() => {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-exclamation-triangle display-1 opacity-25"></i></div>
                                <p class="text-muted">Error loading pipelines. Please try again.</p>
                            </td>
                        </tr>`;
                    paginationContainer.innerHTML = "";
                });
        };

        let searchTimer;
        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchPipelines(), 300);
        });

        fetchPipelines();
    }
})();

// =========================================== Submit ===========================================

$(document).ready(function () {
    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    const stageModalEl = document.getElementById("stageModal");
    let stageModal = null;

    function openStageModal() {
        if (!stageModalEl) return;
        if (!stageModal) {
            stageModal = new bootstrap.Modal(stageModalEl);
        }
        const form = document.getElementById("stageForm");
        const nameInput = document.getElementById("stageName");
        const errorDiv = document.getElementById("name-error");

        if (form) form.reset();
        if (nameInput) nameInput.classList.remove("is-invalid");
        if (errorDiv) errorDiv.textContent = "";

        stageModal.show();
        if (nameInput) nameInput.focus();
    }

    $(document).on("click", ".addStageBtn", function (e) {
        e.preventDefault();
        openStageModal();
    });

    function showToast(message, type = "info") {
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
        $(".pipeline-stage-inline.is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").removeClass("d-block").html("");
    }

    function showErrors(errors) {
        $.each(errors, function (field, messages) {
            const input = $("#" + field);
            const errorDiv = $("#" + field + "-error");

            if (input.length) {
                input.addClass("is-invalid");
                input.closest(".pipeline-stage-inline").addClass("is-invalid");
                if (input.is("select")) {
                    input.next(".ts-wrapper").addClass("is-invalid");
                }
                if (errorDiv.length) {
                    errorDiv.addClass("d-block").html(messages[0]);
                }
            }
        });
        // no toast for validation errors
    }

    $("#pipelineForm").on("submit", function (e) {
        e.preventDefault();

        if (!this) return;

        clearErrors();

        $("#btnSpinner").removeClass("d-none");
        const isEdit = $(this).find('input[name="_method"]').length > 0;
        $("#btnText").text(isEdit ? "Updating..." : "Saving...");
        $("#submitBtn").prop("disabled", true);

        const formData = $(this).serialize();

        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('input[name="_token"]').val(),
                Accept: "application/json",
            }),
            success: function (response) {
                $("#btnSpinner").addClass("d-none");
                $("#btnText").text(isEdit ? "Update" : "Submit");
                $("#submitBtn").prop("disabled", false);

                showToast(
                    response.message ||
                        (isEdit
                            ? "Pipeline updated successfully!"
                            : "Pipeline created successfully!"),
                    "success",
                );

                if (!isEdit) {
                    $("#pipelineForm")[0].reset();
                }

                if (isEdit && response.history_entry && window.crmStatusHistory) {
                    $("#pipelineForm").find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                setTimeout(function () {
                    window.location.href = response.redirect || "/pipeline";
                }, 300);
            },
            error: function (xhr) {
                $("#btnSpinner").addClass("d-none");
                $("#btnText").text(isEdit ? "Update" : "Submit");
                $("#submitBtn").prop("disabled", false);

                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        showErrors(response.errors);
                    }
                } else if (xhr.status === 419) {
                    showToast(
                        "Session expired. Please refresh the page.",
                        "error",
                    );
                } else if (xhr.status === 500) {
                    showToast("Server error. Please try again later.", "error");
                } else {
                    showToast("An error occurred. Please try again.", "error");
                }
            },
        });
    });

    $("input, select, textarea").on("input change", function () {
        const $field = $(this);
        $field.removeClass("is-invalid");
        $field.closest(".pipeline-stage-inline").removeClass("is-invalid");
        if ($field.is("select")) {
            $field.next(".ts-wrapper").removeClass("is-invalid");
        }
        const target = $("#" + $field.attr("id") + "-error");
        if (target.length) {
            target.removeClass("d-block").html("");
        }
    });

    $("#stageForm").on("submit", function (e) {
        e.preventDefault();

        const form = this;
        const nameInput = document.getElementById("stageName");
        const errorDiv = document.getElementById("name-error");
        if (nameInput) nameInput.classList.remove("is-invalid");
        if (errorDiv) errorDiv.textContent = "";

        const formData = new FormData(form);

        $.ajax({
            url: $(form).attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('input[name="_token"]').val(),
                Accept: "application/json",
            }),
            success: function (response) {
                const stage = response?.data || response?.stage;
                if (stage && stage.id) {
                    const option = new Option(stage.name, stage.id, true, true);
                    $("#stage_id").append(option).trigger("change");
                }

                // No toast on successful stage add (per request)
                if (stageModal) stageModal.hide();
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors?.name) {
                    if (nameInput) nameInput.classList.add("is-invalid");
                    if (errorDiv) errorDiv.textContent = xhr.responseJSON.errors.name[0];
                    return;
                }
                showToast("Unable to add stage. Please try again.", "error");
            },
        });
    });
});
