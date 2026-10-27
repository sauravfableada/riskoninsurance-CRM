(function () {
    const TICKET_PERMISSIONS = window.crmUserPermissions?.tickets || {};
    function notify(message, type = "info") {
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

    function getCsrfToken() {
        return (
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
            document.querySelector('input[name="_token"]')?.value ||
            ""
        );
    }

    function authHeaders(extraHeaders = {}) {
        if (typeof window.crmApplyAuthHeaders === "function") {
            return window.crmApplyAuthHeaders(extraHeaders);
        }

        return extraHeaders;
    }

    function clearFormErrors($form) {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".ts-wrapper.is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").html("");
        $form.find(".invalid-feedback.ajax-error").remove();
        $form.find(".ajax-alert").remove();
        $form.find("#formErrors").addClass("d-none").html("");
    }

    function showFormErrors($form, errors) {
        $.each(errors, function (field, messages) {
            const $input = $form.find(`[name="${field}"]`);
            const $error = $form.find(`#${field}-error`);

            if ($input.length) {
                $input.addClass("is-invalid");
                if ($input[0].tomselect) {
                    $input.next(".ts-wrapper").addClass("is-invalid");
                }
            }

            if ($error.length) {
                $error.html(messages[0]);
            } else if ($input.length) {
                $input.after(`<div class="invalid-feedback ajax-error">${messages[0]}</div>`);
            }
        });
    }

    function priorityBadge(priority) {
        switch ((priority || "").toLowerCase()) {
            case "low":
                return "bg-info text-dark";
            case "medium":
                return "bg-primary";
            case "high":
                return "bg-warning text-dark";
            case "urgent":
                return "bg-danger";
            default:
                return "bg-secondary";
        }
    }

    function statusBadge(status) {
        switch ((status || "").toLowerCase()) {
            case "open":
                return "bg-info text-dark";
            case "in progress":
                return "bg-primary";
            case "resolved":
                return "bg-success";
            case "closed":
                return "bg-secondary";
            default:
                return "bg-secondary";
        }
    }

    function formatDateTime(dateTime) {
        if (!dateTime) {
            return "--";
        }

        const date = new Date(dateTime);
        if (Number.isNaN(date.getTime())) {
            return dateTime;
        }

        return date
            .toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
                hour12: true,
            })
            .replace(",", "");
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

    function renderPagination(page, container, fetchTickets) {
        if (!page || page.total === 0) {
            container.innerHTML = "";
            return;
        }

        const from = page.from || 0;
        const to = page.to || 0;
        const total = page.total || 0;
        const currentPage = page.current_page || 1;
        const lastPage = page.last_page || 1;

        let html = `
            <div class="crm-pagination-container">
                <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                <ul class="pagination crm-pagination mb-0">
        `;

        if (page.prev_page_url && currentPage > 1) {
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

        if (page.next_page_url && currentPage < lastPage) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
        }

        html += `</ul></div>`;

        container.innerHTML = html;

        container.querySelectorAll(".page-link[data-page]").forEach((link) => {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                fetchTickets(Number(this.dataset.page));
            });
        });
    }

    function initTicketList() {
        const searchInput = document.getElementById("ticketsSearch");
        const tableBody = document.querySelector("#ticketsTable tbody");
        const paginationContainer = document.getElementById("paginationContainer");

        if (!searchInput || !tableBody || !paginationContainer) {
            return;
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No tickets found.</p>
                            ${TICKET_PERMISSIONS.create ? '<a href="/tickets/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Ticket</a>' : ''}
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items
                .map((ticket, index) => {
                    let cName = ticket.customer?.name || "--";
                    // If the customer_id matches the created_by user id, show the creator's name
                    if (ticket.customer_id && ticket.created_by && ticket.customer_id === ticket.created_by && ticket.creator) {
                        cName = ticket.creator.name;
                    }
                    
                    const customerName = escapeHtml(cName);
                    const ticketName = escapeHtml(ticket.ticket_name || "--");
                    const priorityText = escapeHtml(ticket.priority || "--");
                    const statusText = escapeHtml(ticket.status || "--");
                    const createdAt = escapeHtml(formatDateTime(ticket.created_at));
                    const rowNumber =
                        (meta?.from ? meta.from + index : null) ||
                        ticket.row_number ||
                        ticket.sr_no ||
                        ticket.serial_no ||
                        index + 1;

                    return `
                        <tr>
                            <td class="ps-4 text-nowrap" style="min-width: 90px;">
                                <span class="text-muted small fw-medium">${rowNumber}</span>
                            </td>
                            <td class="text-nowrap d-none d-md-table-cell" data-label="Customer Name">${customerName}</td>
                            <td data-label="Ticket Name">${ticketName}</td>
                            <td class="d-none d-md-table-cell">
                                <span class="badge rounded-pill px-3 ${priorityBadge(ticket.priority)}">${priorityText}</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="badge crm-status-pill rounded-pill ${statusBadge(ticket.status)}">${statusText}</span>
                            </td>
                            <td class="text-nowrap d-none d-md-table-cell">${createdAt}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell">
                                <div class="d-inline-flex align-items-center gap-2">
                                    ${TICKET_PERMISSIONS.edit ? `<a href="/tickets/${ticket.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Ticket">
                                        <i class="bi bi-pencil"></i>
                                    </a>` : ''}
                                    ${TICKET_PERMISSIONS.view ? `<a href="/tickets/${ticket.id}" class="btn crm-action-btn btn-sm" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>` : ''}
                                    ${TICKET_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-ticket" data-id="${ticket.id}" title="Delete Ticket">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>` : ''}
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-ticket-id="${ticket.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${ticket.id}" style="display: none;">
                            <td colspan="7" class="p-0 border">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                            <div class="expand-label"><i class="fa-solid fa-user"></i> Customer :</div>
                                            <div class="expand-value text-end">${customerName}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                            <div class="expand-label"><i class="fa-solid fa-flag"></i> Priority :</div>
                                            <div class="expand-value text-end">
                                                <span class="badge rounded-pill px-3 ${priorityBadge(ticket.priority)}">${priorityText}</span>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                            <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                            <div class="expand-value text-end">
                                                <span class="badge crm-status-pill rounded-pill ${statusBadge(ticket.status)}">${statusText}</span>
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
                                            ${TICKET_PERMISSIONS.edit ? `<a href="/tickets/${ticket.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Ticket"><i class="bi bi-pencil"></i></a>` : ""}
                                            ${TICKET_PERMISSIONS.view ? `<a href="/tickets/${ticket.id}" class="btn crm-action-btn btn-sm" title="View Details"><i class="bi bi-eye"></i></a>` : ""}
                                            ${TICKET_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-ticket" data-id="${ticket.id}" title="Delete Ticket"><i class="bi bi-trash text-danger"></i></button>` : ""}
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
                    const id = this.dataset.ticketId;
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

        function fetchTickets(page = 1) {
            const apiUrl = new URL("/api/tickets", window.location.origin);
            apiUrl.searchParams.set("page", page);

            if (searchInput.value.trim()) {
                apiUrl.searchParams.set("search", searchInput.value.trim());
            } else {
                apiUrl.searchParams.delete("search");
            }

            $.ajax({
                url: apiUrl.toString(),
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                }),
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data, paginationContainer, fetchTickets);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-exclamation-triangle display-1 opacity-25"></i></div>
                                <p class="text-muted">Error loading tickets. Please try again.</p>
                            </td>
                        </tr>
                    `;
                    paginationContainer.innerHTML = "";
                },
            });
        }

        let searchTimer;
        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchTickets();
            }, 300);
        });

        $(document).on("click", ".delete-ticket", function () {
            const $btn = $(this);
            const ticketId = $btn.data("id");
            const originalHtml = $btn.html();

            window.showDeleteConfirm("You want to delete this ticket. This action cannot be undone.").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $btn
                    .prop("disabled", true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                $.ajax({
                    url: `/api/tickets/${ticketId}`,
                    type: "DELETE",
                    dataType: "json",
                    headers: authHeaders({
                        "X-CSRF-TOKEN": getCsrfToken(),
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (response) {
                        if (response.success) {
                            notify(response.message || "Ticket deleted successfully.", "success");
                            fetchTickets();
                            return;
                        }

                        notify(response.message || "Failed to delete ticket.", "error");
                        $btn.prop("disabled", false).html(originalHtml);
                    },
                    error: function (xhr) {
                        notify(xhr.responseJSON?.message || "Failed to delete ticket.", "error");
                        $btn.prop("disabled", false).html(originalHtml);
                    },
                });
            });
        });

        fetchTickets();
    }

    function initTicketForms() {
        $(document).on("submit", ".ajax-ticket-form", function (e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalHtml = $submitBtn.html();
            const isEdit = $form.find('input[name="_method"][value="PUT"]').length > 0;
            const redirectUrl = $form.data("redirect") || "/tickets";
            const savingText = isEdit ? "Updating..." : "Saving...";
            const defaultText = isEdit ? "Update Ticket" : "Create Ticket";

            clearFormErrors($form);

            if ($form.find("#btnSpinner").length && $form.find("#btnText").length) {
                $form.find("#btnSpinner").removeClass("d-none");
                $form.find("#btnText").text(savingText);
                $submitBtn.prop("disabled", true);
            } else {
                $submitBtn
                    .prop("disabled", true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + savingText);
            }

            $.ajax({
                url: $form.attr("action"),
                type: "POST",
                data: $form.serialize(),
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                    Accept: "application/json",
                }),
                success: function (response) {
                    notify(
                        response.message ||
                            (isEdit
                                ? "Ticket updated successfully."
                                : "Ticket created successfully."),
                        "success",
                    );

                    if (isEdit && response.history_entry && window.crmStatusHistory) {
                        $form.find('input[name="status_comment"]').val("");
                        window.crmStatusHistory.prepend(response.history_entry);
                        return;
                    }

                    setTimeout(function () {
                        window.location.href = response.redirect || redirectUrl;
                    }, 300);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showFormErrors($form, xhr.responseJSON.errors);
                        return;
                    }

                    notify(
                        xhr.responseJSON?.message || "Something went wrong. Please try again.",
                        "error",
                    );
                },
                complete: function () {
                    if ($form.find("#btnSpinner").length && $form.find("#btnText").length) {
                        $form.find("#btnSpinner").addClass("d-none");
                        $form.find("#btnText").text(defaultText);
                        $submitBtn.prop("disabled", false);
                    } else {
                        $submitBtn.prop("disabled", false).html(originalHtml);
                    }
                },
            });
        });

        $(document).on("input change", ".ajax-ticket-form input, .ajax-ticket-form select, .ajax-ticket-form textarea", function () {
            $(this).removeClass("is-invalid");
            if (this.tomselect) {
                $(this).next(".ts-wrapper").removeClass("is-invalid");
            }
            const inputId = $(this).attr("id");
            if (inputId) {
                $(`#${inputId}-error`).html("");
            }
        });
    }

    $(document).ready(function () {
        initTicketList();
        initTicketForms();
    });
})();
