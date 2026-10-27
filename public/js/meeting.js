const API_CONFIG = {
    meetings: "/api/meetings",
    customers: "/api/meetings/customers",
    users: "/api/meetings/users",
    userSearch: "/api/users/search",
    googleConnect: "/auth/google",
    googleAuthStatus: "/api/meetings/google/auth-status",
    googleAuthUrl: "/api/meetings/google/auth-url",
    googleDisconnect: "/api/meetings/google/disconnect",
    googleEvents: "/api/meetings/google/events",
};
const MEETING_PERMISSIONS = window.crmUserPermissions?.meetings || {};
const authHeaders = (extraHeaders = {}) =>
    typeof window.crmApplyAuthHeaders === "function"
        ? window.crmApplyAuthHeaders(extraHeaders)
        : extraHeaders;

// ==================== CSRF TOKEN HELPER ====================
function getCsrfToken() {
    // First try to get from form's hidden _token field
    let tokenFromForm = $('input[name="_token"]').val();
    if (tokenFromForm) {
        return tokenFromForm;
    }
    // Fallback to meta tag
    return $('meta[name="csrf-token"]').attr("content") || '';
}

// ==================== GLOBAL UTILITIES ====================
function escapeHtml(text) {
    if (!text) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDate(date) {
    if (!date) return "--";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function buttonLoader(btn, text = "", show = true) {
    const button = $(btn);
    if (show) {
        if (!button.data("original-text")) {
            button.data("original-text", button.html());
        }
        button.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm me-2"></span>
            ${text}...
        `);
    } else {
        button.prop("disabled", false).html(button.data("original-text"));
    }
}

function showAlert(type, message, title = "", redirectUrl = null) {
    Swal.fire({
        icon: type,
        title: title || (type === "success" ? "Success!" : "Error!"),
        text: message,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: { popup: "rounded-4 shadow" },
    }).then(() => {
        if (redirectUrl) window.location.href = redirectUrl;
    });
}

function displayErrors(errors) {
    // First, clear all existing errors
    $(".is-invalid").removeClass("is-invalid");
    $(".ts-wrapper.is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").html("").hide();

    // Then display new errors
    $.each(errors, function (field, messages) {
        let $input = $(`#${field}`);
        if (!$input.length) {
            $input = $(`[name="${field}"]`);
        }
        
        $input.addClass("is-invalid");
        if ($input.is("select")) {
            $input.next(".ts-wrapper").addClass("is-invalid");
        }

        let $errorElement = $(`#${field}-error`);
        if (!$errorElement.length) {
            $errorElement = $input.siblings('.invalid-feedback');
            if (!$errorElement.length) {
                $errorElement = $('<div class="invalid-feedback"></div>');
                $input.after($errorElement);
            }
        }
        
        $errorElement.html(messages[0]).show();
    });
}

function getMeetingIdFromUrl() {
    let pathArray = window.location.pathname.split("/");
    return pathArray[pathArray.length - 2]; // Gets ID from /meetings/{id}/edit
}

// ==================== MEETINGS TABLE ====================
const MeetingTable = {
    init() {
        this.load();
        this.initSearch();
        this.initPagination();
    },

    initSearch() {
        let searchTimer;
        $("#meetingsSearch").on("keyup", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.load(), 500);
        });

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => this.load(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus', 'filterMeetingType', 'filterCreatedBy'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => this.load(1), 0));
            }
        });
        
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                if (document.getElementById('filterFromDate')) document.getElementById('filterFromDate').value = '';
                if (document.getElementById('filterToDate')) document.getElementById('filterToDate').value = '';
                if (document.getElementById('filterDateRange')) document.getElementById('filterDateRange').value = '';
                if (document.getElementById('filterStatus')) document.getElementById('filterStatus').value = '';
                if (document.getElementById('filterMeetingType')) document.getElementById('filterMeetingType').value = '';
                if (document.getElementById('filterCreatedBy')) document.getElementById('filterCreatedBy').value = '';
                const searchInput = document.getElementById('meetingsSearch');
                if (searchInput) searchInput.value = '';
                this.load(1);
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
    },

    initPagination() {
        $(document).on("click", ".pagination a[data-page]", (e) => {
            e.preventDefault();
            let page = $(e.currentTarget).data("page");
            if (page) this.load(parseInt(page));
        });
    },

    load(page = 1) {
        let search = $("#meetingsSearch").val();

        // Show loading state
        $("tbody").html(this.getLoadingTemplate());

        const fromDate = document.getElementById('filterFromDate')?.value;
        const toDate = document.getElementById('filterToDate')?.value;
        const status = document.getElementById('filterStatus')?.value;
        const perPage = document.getElementById('perPageSelect')?.value;
        const meetingType = document.getElementById('filterMeetingType')?.value;
        const createdBy = document.getElementById('filterCreatedBy')?.value;

        let data = { page, search };
        if (fromDate) data.from_date = fromDate;
        if (toDate) data.to_date = toDate;
        if (status) data.status = status;
        if (perPage) data.per_page = perPage;
        if (meetingType) data.meeting_type = meetingType;
        if (createdBy) data.created_by = createdBy;

        $.ajax({
            url: API_CONFIG.meetings,
            type: "GET",
            data: data,
            dataType: "json",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            }),
            success: (response) => {
                if (response.success) {
                    this.renderRows(response.data.data, response.data);
                    this.renderPagination(response.data);
                } else {
                    showAlert("error", "Failed to load meetings");
                }
            },
            error: (xhr) => this.handleError(xhr),
        });
    },

    getLoadingTemplate() {
        return `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading meetings...</p>
                    </div>
                </td>
            </tr>
        `;
    },

    handleError(xhr) {
        if (xhr.status === 401 || xhr.status === 419) {
            window.location.href = "/login";
            return;
        }

        if (xhr.responseText?.includes("<!doctype html>")) {
            showAlert("error", "Session expired. Please refresh.");
            return;
        }

        $("tbody").html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-danger">
                        <i class="bi bi-exclamation-triangle display-6 d-block mb-3"></i>
                        <p>Failed to load meetings. Please try again.</p>
                        <button class="btn btn-dark-blue btn-sm" onclick="location.reload()">
                            Retry
                        </button>
                    </div>
                </td>
            </tr>
        `);
    },

    renderRows(meetings, meta = {}) {
        let tbody = $("tbody").empty();

        if (!meetings?.length) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-calendar-x display-6 d-block mb-3"></i>
                            <p>No meetings found.</p>
                            ${MEETING_PERMISSIONS.create ? `<a href="/meetings/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                                 Schedule Your First Meeting
                            </a>` : ""}
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = "";
        meetings.forEach((meeting, index) => {
            let customerName = meeting.customer
                ? escapeHtml(meeting.customer.name)
                : "--";
            let srNo = meta.from ? meta.from + index : index + 1;
            
            const assignedUserData = meeting.assigned_user || meeting.assignedUser;
            let assignedUser = assignedUserData
                ? escapeHtml(assignedUserData.name)
                : "Unassigned";

            let statusBadge = this.getStatusBadge(meeting.status, meeting.id, MEETING_PERMISSIONS.edit);
            
            // Google Calendar sync indicator
            let googleSyncBadge = meeting.is_synced 
                ? '<span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle me-1"></i>Synced</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-cloud-arrow-up me-1"></i>Not Synced</span>';
            
            let googleSyncButton = meeting.is_synced 
                ? `<button type="button" class="btn crm-action-btn btn-sm text-danger" onclick="removeMeetingFromGoogle(${meeting.id}, this)" title="Remove from Google Calendar">
                       <i class="bi bi-calendar-x"></i>
                   </button>`
                : `<button type="button" class="btn crm-action-btn btn-sm text-success" onclick="syncMeetingToGoogle(${meeting.id}, this)" title="Sync to Google Calendar">
                       <i class="bi bi-calendar-plus"></i>
                   </button>`;
            
            // Format location with icon
            let locationDisplay = meeting.address 
                ? `<i class="bi bi-geo-alt text-muted me-1"></i>${escapeHtml(meeting.address)}`
                : '<span class="text-muted">—</span>';

            // Title & Customer column
            let titleDisplay = `
                <div class="fw-semibold">${escapeHtml(meeting.title)}</div>
                <small class="text-muted">${customerName}</small>
            `;

            // Scheduled At column with type
            let scheduledDisplay = `
                <div class="fw-semibold">${formatDate(meeting.scheduled_at)}</div>
                ${meeting.meeting_type ? `<small class="text-muted">${escapeHtml(meeting.meeting_type)}</small>` : ''}
            `;

            html += `
                <tr>
                    <td class="ps-4 d-none d-md-table-cell">
                        <span class="text-muted small fw-medium">${srNo}</span>
                    </td>
                    <td class="ps-4">${titleDisplay}</td>
                    <td class="text-start">${scheduledDisplay}</td>
                    <td class="d-none d-md-table-cell">${locationDisplay}</td>
                    <td class="d-none d-md-table-cell">${assignedUser}</td>
                    <td class="d-none d-md-table-cell">${googleSyncBadge}</td>
                    <td class="d-none d-md-table-cell">${statusBadge}</td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            ${MEETING_PERMISSIONS.edit ? googleSyncButton : ""}
                            ${MEETING_PERMISSIONS.edit ? `<a href="/meetings/${meeting.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                                 <i class="bi bi-pencil"></i>
                            </a>` : ""}
                            ${MEETING_PERMISSIONS.view ? `<a href="/meetings/${meeting.id}" class="btn crm-action-btn btn-sm" title="View">
                                 <i class="bi bi-eye"></i>
                            </a>` : ""}
                            ${MEETING_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-meeting" 
                                    data-id="${meeting.id}" title="Delete">
                                 <i class="bi bi-trash text-danger"></i>
                            </button>` : ""}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-meeting-id="${meeting.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${meeting.id}" style="display: none;">
                    <td colspan="4" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-hashtag"></i> Sr.No :</div>
                                    <div class="expand-value">${srNo}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                    <div class="expand-value">${statusBadge}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-location-dot"></i> Location :</div>
                                    <div class="expand-value">${locationDisplay}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-user"></i> Staff :</div>
                                    <div class="expand-value">${assignedUser}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-brands fa-google"></i> Calendar :</div>
                                    <div class="expand-value">${googleSyncBadge}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${MEETING_PERMISSIONS.edit ? googleSyncButton : ""}
                                        ${MEETING_PERMISSIONS.edit ? `<a href="/meetings/${meeting.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                                        ${MEETING_PERMISSIONS.view ? `<a href="/meetings/${meeting.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                        ${MEETING_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-meeting" data-id="${meeting.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ""}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);

        // attach expand
        document.querySelectorAll(".btn-user-expand").forEach(function (button) {
            button.addEventListener("click", function () {
                const id = this.dataset.meetingId;
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

        // attach inline edit logic
        document.querySelectorAll('.status-display').forEach((element) => {
            element.addEventListener('click', function (e) {
                e.stopPropagation();
                const row = this.closest('td') || this.closest('div');
                if (!row) return;

                const dropdown = row.querySelector('.status-dropdown');
                const display = row.querySelector('.status-display');

                document.querySelectorAll('.status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                document.querySelectorAll('.status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });

                if (dropdown && display) {
                    dropdown.classList.remove('d-none');
                    dropdown.style.display = 'block';
                    display.classList.add('d-none');
                    display.style.display = 'none';
                    dropdown.focus();
                }
            });
        });

        document.querySelectorAll('.status-dropdown').forEach((element) => {
            element.addEventListener('click', function(e) { e.stopPropagation(); });
            element.addEventListener('change', (e) => {
                this.updateStatusField(e.currentTarget);
            });
        });
    },

    getStatusBadge(status, meetingId, canEdit) {
        if (!status) return '<span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">—</span>';
        
        const badges = {
            scheduled: { class: "bg-warning", text: "warning" },
            completed: { class: "bg-success", text: "success" },
            cancelled: { class: "bg-danger", text: "danger" },
        };
        
        const badge = badges[status] || { class: "bg-secondary", text: "secondary" };
        const badgeHtml = `<span class="badge ${badge.class} bg-opacity-10 text-${badge.text} px-3 py-2 rounded-pill">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
        
        if (!canEdit) {
            return badgeHtml;
        }

        return `
            <select class="form-select form-select-sm status-dropdown d-none" data-id="${meetingId}">
                <option value="scheduled" ${status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
            </select>
            <span class="status-display" style="cursor: pointer;" title="Click to edit status">
                ${badgeHtml}
            </span>
        `;
    },

    updateStatusField(dropdown) {
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
                this.performStatusUpdate(id, value, display, dropdown, originalValue, result.value.trim());
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
    },

    performStatusUpdate(id, value, display, dropdown, originalValue, comment) {
        dropdown.dataset.originalValue = originalValue;
        dropdown.disabled = true;

        $.ajax({
            url: `/api/meetings/${id}/update-status`,
            type: 'POST',
            headers: authHeaders({
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            }),
            data: { status: value, status_comment: comment },
            success: (response) => {
                if (response.success) {
                    if (display) {
                        display.innerHTML = this.getStatusBadge(value, id, false);
                        display.classList.remove('d-none');
                        display.style.display = '';
                    }

                    dropdown.classList.add('d-none');
                    dropdown.style.display = 'none';
                    dropdown.dataset.originalValue = value;
                    dropdown.disabled = false;

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Status updated successfully.', 'Success');
                    }
                } else {
                    dropdown.value = originalValue;
                    dropdown.disabled = false;
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', response.message || 'Failed to update status.', 'Error');
                    }
                }
            },
            error: (xhr) => {
                dropdown.value = originalValue;
                dropdown.disabled = false;
                if (typeof window.showAlert === 'function') {
                    window.showAlert('error', xhr?.responseJSON?.message || 'Something went wrong! Please try again.', 'Error');
                }
            }
        });
    },

    renderPagination(data) {
        let $container = $("#meetingPaginationContainer");
        if (
            !$container.length ||
            !data ||
            data.total === 0
        ) {
            $container.empty();
            return;
        }

        const {
            from = 0,
            to = 0,
            total = 0,
            current_page = 1,
            last_page = 1,
            prev_page_url,
            next_page_url,
        } = data;
        const maxPagesToShow = 5;
        let startPage = Math.max(
            1,
            current_page - Math.floor(maxPagesToShow / 2),
        );
        let endPage = Math.min(last_page, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        let html = `
            <div class="crm-pagination-container">
                <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                <ul class="pagination crm-pagination mb-0">
        `;

        // Previous button
        html += prev_page_url

            ? `<li class="page-item"><button type="button" class="page-link" data-page="${current_page - 1}"><span aria-hidden="true">&laquo;</span> Previous</button></li>`
            : `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&laquo;</span> Previous</span></li>`;

        // First page
        if (startPage > 1) {
            html += `<li class="page-item"><button type="button" class="page-link" data-page="1">1</button></li>`;
            if (startPage > 2)
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html +=
                i === current_page
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><button type="button" class="page-link" data-page="${i}">${i}</button></li>`;
        }

        // Last page
        if (endPage < last_page) {
            if (endPage < last_page - 1)
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><button type="button" class="page-link" data-page="${last_page}">${last_page}</button></li>`;
        }

        // Next button
        html += next_page_url

            ? `<li class="page-item"><button type="button" class="page-link" data-page="${current_page + 1}">Next <span aria-hidden="true">&raquo;</span></button></li>`
            : `<li class="page-item disabled"><span class="page-link">Next <span aria-hidden="true">&raquo;</span></span></li>`;

        html += `</ul></div>`;
        $container.html(html);
    },
};

// ==================== MEETING FORM (Create) ====================
const MeetingForm = {
    init() {
        if ($("#meetingForm").length) {
            $("#meetingForm").on("submit", (e) => this.handleSubmit(e));
        }
    },

    handleSubmit(e) {
        e.preventDefault();
        let form = $(e.currentTarget);
        let btn = $("#submitBtn");

        // Clear validation errors
        $(".invalid-feedback").empty().hide();
        $(".form-control, .form-select").removeClass("is-invalid");

        buttonLoader(btn, "Scheduling...");

        // Ensure we have the latest CSRF token
        let formData = form.serialize();
        let csrfToken = getCsrfToken();
        
        // If token is not in form data, add it
        if (!formData.includes('_token=')) {
            formData += '&_token=' + encodeURIComponent(csrfToken);
        }

        $.ajax({
            url: API_CONFIG.meetings,
            type: "POST",
            data: formData,
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            }),
            success: (response) => {
                buttonLoader(btn, "", false);
                if (response.success) {
                    showAlert("success", response.message, "", "/meetings");
                    form[0].reset();
                }
            },
            error: (xhr) => {
                buttonLoader(btn, "", false);
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = "/login";
                } else {
                    showAlert(
                        "error",
                        xhr.responseJSON?.message || "Failed to save meeting",
                    );
                }
            },
        });
    },
};

// ==================== MEETING UPDATE ====================
const MeetingUpdate = {
    init() {
        if ($("#meetingupdate").length) {
            $("#meetingupdate").on("submit", (e) => {
                e.preventDefault();
                this.handleUpdate();
            });
        }
    },

    handleUpdate() {
        // Clear previous errors
        $(".is-invalid").removeClass("is-invalid");
        $(".invalid-feedback").html("").hide();

        buttonLoader("#submitBtn", "Updating", true);

        let csrfToken = getCsrfToken();
        let formData = {
            customer_id: $("#customer_id").val(),
            title: $("#title").val(),
            scheduled_at: $("#scheduled_at").val(),
            meeting_type: $("#meeting_type").val(),
            assigned_user_id: $("#assigned_user_id").val(),
            status: $("#status").val(),
            agenda: $("#agenda").val(),
            address: $("#address").val(),
            _token: csrfToken,
            _method: "PUT",
        };

        $.ajax({
            url: `${API_CONFIG.meetings}/${getMeetingIdFromUrl()}`,
            type: "POST",
            data: formData,
            dataType: "json",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            }),
            success: (response) => {
                buttonLoader("#submitBtn", "", false);
                if (response.history_entry && window.crmStatusHistory) {
                    showAlert(
                        "success",
                        response.message || "Meeting updated successfully",
                        "Success!",
                    );
                    $("#meetingupdate").find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                showAlert(
                    "success",
                    response.message || "Meeting updated successfully",
                    "Success!",
                    response.redirect || "/meetings",
                );
            },
            error: (xhr) => {
                buttonLoader("#submitBtn", "", false);
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = "/login";
                } else {
                    showAlert(
                        "error",
                        xhr.responseJSON?.message || "Failed to update meeting",
                    );
                }
            },
        });
    },
};

// ==================== DELETE MEETING ====================
function deleteMeeting(id) {
    window.showDeleteConfirm("You won't be able to revert this!").then((result) => {
        if (!result.isConfirmed) return;

        let deleteButton = $(`.delete-meeting[data-id="${id}"]`);
        buttonLoader(deleteButton, "Deleting", true);

        $.ajax({
            url: `${API_CONFIG.meetings}/${id}`,
            type: "DELETE",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            }),
            success: (response) => {
                buttonLoader(deleteButton, "", false);
                if (response.success) {
                    showAlert("success", "Meeting deleted successfully");
                    let currentPage =
                        new URL(window.location.href).searchParams.get(
                            "page",
                        ) || 1;
                    MeetingTable.load(currentPage);
                } else {
                    showAlert("error", "Failed to delete meeting");
                }
            },
            error: (xhr) => {
                buttonLoader(deleteButton, "", false);
                showAlert("error", "Failed to delete meeting");
            },
        });
    });
}

// ==================== INITIALIZATION ====================
$(document).ready(function () {
    // Initialize based on current page
    if ($("#meetingsSearch").length) {
        MeetingTable.init();
    }

    if ($("#meetingForm").length) {
        MeetingForm.init();
    }

    if ($("#meetingupdate").length) {
        MeetingUpdate.init();
    }

    $(document).on("input change", "#meetingForm input, #meetingForm select, #meetingForm textarea, #meetingupdate input, #meetingupdate select, #meetingupdate textarea", function () {
        const $field = $(this);
        $field.removeClass("is-invalid");
        if ($field.is("select")) {
            $field.next(".ts-wrapper").removeClass("is-invalid");
        }
        const id = $field.attr("id");
        if (id) {
            $("#" + id + "-error").html("").hide();
        }
    });

    // Global delete handler
    $(document).on("click", ".delete-meeting", function () {
        deleteMeeting($(this).data("id"));
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
        $(".invalid-feedback").html("").hide();
        $(".is-invalid").removeClass("is-invalid");
        
        $.ajax({
            url: url,
            type: "POST",
            data: $form.serialize(),
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            }),
            success: function (response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (response.success && response.data) {
                    let customer = response.data;
                    let tsElement = document.querySelector("#customer_id");
                    if (tsElement && tsElement.tomselect) {
                        tsElement.tomselect.addOption({
                            id: customer.id,
                            name: customer.name,
                            email: customer.email || '',
                            phone: customer.phone || ''
                        });
                        tsElement.tomselect.addItem(customer.id);
                    } else {
                        // Fallback
                        let $select = $("#customer_id");
                        $select.append(new Option(customer.name, customer.id, true, true));
                        $select.trigger("change");
                    }
                    $("#modalAddCustomer").modal("hide");
                    $form[0].reset();
                    showAlert("success", "Customer added successfully.");
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayErrors(xhr.responseJSON.errors);
                } else {
                    showAlert("error", xhr.responseJSON?.message || "Error adding customer.");
                }
            }
        });
    });

    // Google Calendar initialization
    if ($("#googleCalendarSection").length) {
        GoogleCalendar.init();
    }
});

// ==================== GOOGLE CALENDAR ====================
const GoogleCalendar = {
    isAuthenticated: false,

    init() {
        this.checkAuthStatus();
    },

    checkAuthStatus() {
        $.ajax({
            url: API_CONFIG.googleAuthStatus,
            type: "GET",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            }),
            success: (response) => {
                if (response.success) {
                    this.isAuthenticated = response.data.is_authenticated;
                    this.updateUI();
                }
            },
            error: () => {
                this.updateUI();
            },
        });
    },

    updateUI() {
        const connectBtn = $("#connectGoogleBtn");
        const disconnectBtn = $("#disconnectGoogleBtn");
        const statusBadge = $("#googleStatusBadge");

        if (this.isAuthenticated) {
            connectBtn.hide();
            disconnectBtn.show();
            statusBadge.html('<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Connected</span>');
        } else {
            connectBtn.show();
            disconnectBtn.hide();
            statusBadge.html('<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Not Connected</span>');
        }
    },

    connect() {
        window.location.href = API_CONFIG.googleConnect;
    },

    disconnect() {
        Swal.fire({
            title: "Disconnect Google Calendar?",
            text: "This will stop syncing meetings to your Google Calendar.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            confirmButtonText: "Disconnect",
            cancelButtonText: "Cancel",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API_CONFIG.googleDisconnect,
                    type: "POST",
                    headers: authHeaders({
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    }),
                    success: (response) => {
                        if (response.success) {
                            this.isAuthenticated = false;
                            this.updateUI();
                            showAlert("success", response.message);
                        } else {
                            showAlert("error", response.message);
                        }
                    },
                    error: () => {
                        showAlert("error", "Failed to disconnect Google Calendar");
                    },
                });
            }
        });
    },

    syncMeeting(meetingId, buttonElement) {
        const button = $(buttonElement);
        buttonLoader(button, "Syncing", true);

        $.ajax({
            url: `${API_CONFIG.meetings}/${meetingId}/sync-to-google`,
            type: "POST",
            dataType: "json",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            }),
            success: (response) => {
                buttonLoader(button, "", false);
                if (response.success) {
                    showAlert("success", response.message || "Meeting synced to Google Calendar!");
                    setTimeout(() => {
                        if (typeof MeetingTable !== 'undefined' && typeof MeetingTable.load === 'function') {
                            MeetingTable.load();
                        } else {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    showAlert("error", response.message || "Failed to sync meeting");
                }
            },
            error: (xhr) => {
                buttonLoader(button, "", false);
                if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = "/login";
                    return;
                }
                const response = xhr.responseJSON;
                const message = response?.message || "Failed to sync meeting to Google Calendar.";
                showAlert("error", message);
            },
        });
    },

    removeFromGoogle(meetingId, buttonElement) {
        const button = $(buttonElement);
        
        Swal.fire({
            title: "Remove from Google Calendar?",
            text: "This will remove the meeting from your Google Calendar.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            confirmButtonText: "Remove",
            cancelButtonText: "Cancel",
            customClass: { popup: "rounded-4 shadow" },
        }).then((result) => {
            if (result.isConfirmed) {
                buttonLoader(button, "Removing", true);

                $.ajax({
                    url: `${API_CONFIG.meetings}/${meetingId}/remove-from-google`,
                    type: "POST",
                    dataType: "json",
                    headers: authHeaders({
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    }),
                    success: (response) => {
                        buttonLoader(button, "", false);
                        if (response.success) {
                            showAlert("success", response.message || "Meeting removed from Google Calendar");
                            setTimeout(() => {
                                if (typeof MeetingTable !== 'undefined' && typeof MeetingTable.load === 'function') {
                                    MeetingTable.load();
                                } else {
                                    location.reload();
                                }
                            }, 1500);
                        } else {
                            showAlert("error", response.message || "Failed to remove meeting");
                        }
                    },
                    error: (xhr) => {
                        buttonLoader(button, "", false);
                        if (xhr.status === 401 || xhr.status === 419) {
                            window.location.href = "/login";
                            return;
                        }
                        const message = xhr.responseJSON?.message || "Failed to remove meeting from Google Calendar.";
                        showAlert("error", message);
                    },
                });
            }
        });
    },
};

// Global functions for inline event handlers
window.syncMeetingToGoogle = function(meetingId, btn) {
    GoogleCalendar.syncMeeting(meetingId, btn);
};

window.removeMeetingFromGoogle = function(meetingId, btn) {
    GoogleCalendar.removeFromGoogle(meetingId, btn);
};

document.addEventListener("DOMContentLoaded", () => {
    MeetingList.init();
    MeetingForm.init();
    MeetingUpdate.init();

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.status-dropdown, .status-display')) {
            document.querySelectorAll('.status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
            document.querySelectorAll('.status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });
        }
    });
});
