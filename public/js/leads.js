(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.leads || {};
        const tableBody = document.querySelector("#leadsTable tbody");
        if (!tableBody) {
            return;
        }

        const paginationContainer = document.getElementById("leadsPagination");
        const searchInput = document.getElementById("leadsSearch");
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        const importButton = document.getElementById("leadsImportButton");
        const importFileInput = document.getElementById("leadsImportFile");

        function authHeaders(extraHeaders) {
            return window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                ...extraHeaders,
            });
        }

        function statusBadge(status) {
            const classes = {
                new: "bg-info",
                qualified: "bg-primary",
                working: "bg-warning",
                ready_to_close: "bg-dark",
                won: "bg-success",
                lost: "bg-danger",
            };

            return classes[status] || "bg-secondary";
        }

        function formatStatus(status) {
            if (!status) return "--";

            const labels = {
                ready_to_close: "Ready to Close",
                won: "Closed Won",
                lost: "Closed Lost",
            };

            return labels[status] || status.replace(/_/g, " ").replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        }

        function formatDate(dateValue) {
            if (!dateValue) return "Not Set";

            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return "Not Set";

            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        }

        function deleteLead(id, button) {
            window.showDeleteConfirm("This lead will be deleted!").then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/api/leads/${id}`,
                        type: "DELETE",
                        headers: authHeaders({
                            "X-CSRF-TOKEN": csrfToken,
                        }),
                        success: function (res) {
                            if (res.success) {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("success", res.message || "Lead deleted successfully.");
                                }
                                fetchLeads();
                            } else {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("error", res.message || "Delete failed");
                                }
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            }
                        },
                        error: function (xhr) {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("error", xhr?.responseJSON?.message || "Something went wrong");
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        function convertLead(id, button) {
            Swal.fire({
                title: "Convert to Customer",
                html: `
                    <p class="text-muted small mb-3">Set an optional customer portal password, or click <strong>Skip</strong> to continue without setting a password now.</p>
                    <div class="text-start mb-2">
                        <label class="form-label fw-semibold small mb-1">Customer Password (Optional)</label>
                        <div class="position-relative">
                            <input type="password" id="swalCustomerPassword" class="form-control rounded-3" placeholder="Enter password (optional)">
                            <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y text-muted border-0 bg-transparent pe-3" id="swalTogglePassword" tabindex="-1">
                                <i class="bi bi-eye" id="swalEyeIcon"></i>
                            </button>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: "#0f172a",
                denyButtonColor: "#64748b",
                cancelButtonColor: "#e2e8f0",
                confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Save & Convert',
                denyButtonText: 'Skip & Convert',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: "rounded-4 shadow",
                    cancelButton: "text-dark border",
                },
                didOpen: () => {
                    const toggleBtn = document.getElementById("swalTogglePassword");
                    const passInput = document.getElementById("swalCustomerPassword");
                    const eyeIcon = document.getElementById("swalEyeIcon");
                    if (toggleBtn && passInput) {
                        toggleBtn.addEventListener("click", () => {
                            if (passInput.type === "password") {
                                passInput.type = "text";
                                eyeIcon.className = "bi bi-eye-slash";
                            } else {
                                passInput.type = "password";
                                eyeIcon.className = "bi bi-eye";
                            }
                        });
                    }
                },
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    const passwordVal = result.isConfirmed ? document.getElementById("swalCustomerPassword")?.value || "" : "";
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/leads/${id}/convert`,
                        type: "POST",
                        data: {
                            password: passwordVal,
                        },
                        headers: authHeaders({
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                        }),
                        success: function (res) {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("success", res?.message || "Lead converted to customer successfully.");
                            }
                            fetchLeads();
                        },
                        error: function (xhr) {
                            if (typeof window.showAlert === "function") {
                                window.showAlert("error", xhr?.responseJSON?.message || "Unable to convert lead. Please try again.");
                            }
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        function renderRows(items, meta) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No leads found in the directory.</p>
                            ${permissions.create ? '<a href="/leads/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Lead</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (lead, index) {
                const isConverted = Boolean(lead.is_converted);
                const sourceText = lead.lead_source?.name || lead.leadSource?.name || lead.source || "--";
                const createdBy = lead.assigned_user?.name || lead.assignedUser?.name || "--";
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const statusHtml = permissions.edit
                    ? `<select class="form-select form-select-sm status-dropdown d-none" data-id="${lead.id}">
                            <option value="new" ${lead.status === 'new' ? 'selected' : ''}>New</option>
                            <option value="qualified" ${lead.status === 'qualified' ? 'selected' : ''}>Qualified</option>
                            <option value="working" ${lead.status === 'working' ? 'selected' : ''}>Working</option>
                            <option value="ready_to_close" ${lead.status === 'ready_to_close' ? 'selected' : ''}>Ready to Close</option>
                            <option value="won" ${lead.status === 'won' ? 'selected' : ''}>Closed Won</option>
                            <option value="lost" ${lead.status === 'lost' ? 'selected' : ''}>Closed Lost</option>
                        </select>
                        <span class="status-display" style="cursor: pointer;" title="Click to edit status">
                            <span class="badge ${statusBadge(lead.status)} rounded-pill px-3">${formatStatus(lead.status)}</span>
                        </span>`
                    : `<span class="badge ${statusBadge(lead.status)} rounded-pill px-3">${formatStatus(lead.status)}</span>`;

                return `
                    <tr>
                        <td class="ps-4">${srNo}</td>
                        <td>
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                <span>${lead.name ?? "--"}</span>
                                ${isConverted ? '<span class="badge lead-converted-badge"><i class="bi bi-person-check me-1"></i>Converted</span>' : ""}
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">${lead.phone || "--"}</td>
                        <td class="d-none d-md-table-cell">${sourceText}</td>
                        <td class="d-none d-md-table-cell">${createdBy}</td>
                        <td class="d-none d-md-table-cell">${formatDate(lead.created_at)}</td>
                        <td class="d-none d-md-table-cell">${statusHtml}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${(!isConverted && permissions.edit) ? `<button type="button" class="btn crm-action-btn btn-sm text-success convert-btn" data-id="${lead.id}" title="Convert to Customer"><i class="bi bi-person-plus"></i></button>` : ""}
                                ${permissions.edit ? `<a href="/leads/${lead.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Details"><i class="bi bi-pencil"></i></a>` : ""}
                                ${permissions.view ? `<a href="/leads/${lead.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${lead.id}" title="Remove Lead">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>` : ""}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-lead-id="${lead.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${lead.id}" style="display: none;">
                        <td colspan="9" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                        <div class="expand-value">${statusHtml}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone :</div>
                                        <div class="expand-value">${lead.phone || "--"}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-people-group"></i> Group :</div>
                                        <div class="expand-value">${lead.group || "--"}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-bullhorn"></i> Source :</div>
                                        <div class="expand-value">${sourceText}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Created By :</div>
                                        <div class="expand-value">${createdBy}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created At :</div>
                                        <div class="expand-value">${formatDate(lead.created_at)}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex gap-2">
                                            ${(!isConverted && permissions.edit) ? `<button type="button" class="btn crm-action-btn btn-sm text-success convert-btn" data-id="${lead.id}" title="Convert to Customer"><i class="bi bi-person-plus"></i></button>` : ""}
                                            ${permissions.edit ? `<a href="/leads/${lead.id}/edit" class="btn crm-action-btn btn-sm" title="Edit Details"><i class="bi bi-pencil"></i></a>` : ""}
                                            ${permissions.view ? `<a href="/leads/${lead.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${lead.id}" title="Remove Lead">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>` : ""}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join("");

            document.querySelectorAll(".delete-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    deleteLead(this.dataset.id, this);
                });
            });

            document.querySelectorAll(".convert-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    convertLead(this.dataset.id, this);
                });
            });

            document.querySelectorAll('.status-display').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleStatusDropdown(this);
                });
            });

            document.querySelectorAll('.status-dropdown').forEach(function (element) {
                element.addEventListener('click', function(e) { e.stopPropagation(); });
                element.addEventListener('change', function () {
                    updateStatus(this);
                });
            });

            // attach expand
            document.querySelectorAll(".btn-user-expand").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.dataset.leadId;
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

        function toggleStatusDropdown(element) {
            const row = element.closest('td') || element.closest('div');
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
        }

        function updateStatus(dropdown) {
            const id = dropdown.dataset.id;
            const status = dropdown.value;
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
                customClass: {
                    popup: 'rounded-4 shadow',
                    input: 'rounded-3'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    performLeadStatusUpdate(id, status, display, dropdown, originalValue, result.value);
                } else {
                    dropdown.value = originalValue;
                    dropdown.classList.add('d-none');
                    if (display) display.classList.remove('d-none');
                }
            });
        }

        function performLeadStatusUpdate(id, status, display, dropdown, originalValue, comment) {
            dropdown.dataset.originalValue = originalValue;
            dropdown.disabled = true;

            const data = { status: status };
            if (comment) {
                data['status_comment'] = comment;
            }

            $.ajax({
                url: `/api/leads/${id}/update-status`,
                type: 'POST',
                headers: authHeaders({
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                }),
                data: data,
                success: function (response) {
                    if (response.success) {
                        if (display) {
                            display.innerHTML = `<span class="badge ${statusBadge(status)} rounded-pill px-3">${formatStatus(status)}</span>`;
                            display.classList.remove('d-none');
                        }

                        dropdown.classList.add('d-none');
                        dropdown.dataset.originalValue = status;

                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || 'Status updated successfully.', 'Success');
                        }
                    } else {
                        dropdown.value = originalValue;
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', response.message || 'Failed to update status.', 'Error');
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

        if (importButton && importFileInput) {
            importButton.addEventListener("click", function () {
                importFileInput.click();
            });

            importFileInput.addEventListener("change", function () {
                const file = this.files?.[0];
                if (!file) return;

                const formData = new FormData();
                formData.append("import_file", file);

                Swal.fire({
                    title: "Importing Leads...",
                    text: "Processing name, phone, and groups columns.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                $.ajax({
                    url: "/api/leads/import",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: authHeaders({
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (response) {
                        Swal.fire({
                            icon: "success",
                            title: "Import complete",
                            text: response.message,
                        });
                        fetchLeads(1);
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: "error",
                            title: "Import failed",
                            text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {})[0]?.[0] || "Unable to import the CSV file.",
                        });
                    },
                    complete: function () {
                        importFileInput.value = "";
                    },
                });
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

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('.page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    fetchLeads(this.dataset.page);
                });
            });
        }

        function fetchLeads(page = 1) {
            let url = `/api/leads?page=${page}`;

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            const fromDate = document.getElementById('filterFromDate')?.value;
            const toDate = document.getElementById('filterToDate')?.value;
            const status = document.getElementById('filterStatus')?.value;
            const perPage = document.getElementById('perPageSelect')?.value;

            const filterName = document.getElementById('filterName')?.value;
            const filterPhone = document.getElementById('filterPhone')?.value;
            const filterGroupId = document.getElementById('filterGroupId')?.value;
            const filterSourceId = document.getElementById('filterSourceId')?.value;
            const filterCreatedBy = document.getElementById('filterCreatedBy')?.value;

            if (fromDate) url += `&from_date=${encodeURIComponent(fromDate)}`;
            if (toDate) url += `&to_date=${encodeURIComponent(toDate)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (perPage) url += `&per_page=${encodeURIComponent(perPage)}`;
            
            if (filterName && filterName.trim()) url += `&name=${encodeURIComponent(filterName.trim())}`;
            if (filterPhone && filterPhone.trim()) url += `&phone=${encodeURIComponent(filterPhone.trim())}`;
            if (filterGroupId) url += `&group_id=${encodeURIComponent(filterGroupId)}`;
            if (filterSourceId) url += `&source_id=${encodeURIComponent(filterSourceId)}`;
            if (filterCreatedBy) url += `&created_by=${encodeURIComponent(filterCreatedBy)}`;

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: authHeaders(),
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderRows(res.data.data || [], res.data);
                        renderPagination(res.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">Error loading leads</td>
                        </tr>`;
                },
            });
        }

        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetchLeads(1);
                }, 400);
            });
        }
        
        ['filterName', 'filterPhone'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                let timer;
                filterInput.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(() => fetchLeads(1), 400);
                });
            }
        });

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchLeads(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus', 'filterGroupId', 'filterSourceId', 'filterCreatedBy'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => fetchLeads(1), 0));
            }
        });
        
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                if (document.getElementById('filterFromDate')) document.getElementById('filterFromDate').value = '';
                if (document.getElementById('filterToDate')) document.getElementById('filterToDate').value = '';
                if (document.getElementById('filterDateRange')) document.getElementById('filterDateRange').value = '';
                if (document.getElementById('filterStatus')) document.getElementById('filterStatus').value = '';
                if (document.getElementById('filterSourceId')) document.getElementById('filterSourceId').value = '';
                if (document.getElementById('filterCreatedBy')) document.getElementById('filterCreatedBy').value = '';
                if (searchInput) searchInput.value = '';
                fetchLeads(1);
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
            if (!event.target.closest('.status-dropdown, .status-display')) {
                document.querySelectorAll('.status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                document.querySelectorAll('.status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });
            }
        });

        fetchLeads();
    }
})();

$(document).ready(function () {
    window.previewImage = function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const icon = document.getElementById('leadImageIcon');

        if (!preview) {
            return;
        }

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (icon) {
                    icon.classList.add('d-none');
                }
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.classList.add('d-none');
            if (icon) {
                icon.classList.remove('d-none');
            }
        }
    };

    function handleValidationErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            const fieldEl = $form.find('[name="' + field + '"]');
            if (fieldEl.length) {
                fieldEl.addClass('is-invalid');
                if (fieldEl.is('select')) {
                    fieldEl.next('.ts-wrapper').addClass('is-invalid');
                }
                const container = fieldEl.closest('.col-md-6, .col-12, .col-md-12');
                let feedback = container.find('#' + field + '-error');

                if (feedback.length) {
                    feedback.html(errors[field][0]);
                } else {
                    fieldEl.after(`<div class="invalid-feedback ajax-error">${errors[field][0]}</div>`);
                }
            }
        });
    }

    $('body').on('submit', '.ajax-lead-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const btn = $form.find('button[type="submit"]');
        const originalText = btn.html();
        const method = 'POST';
        const isEdit = /\/api\/leads\/\d+$/i.test(url);

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.ts-wrapper.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        $form.find('.ajax-alert').remove();
        $form.find('.invalid-feedback').html('');

        const formData = new FormData(this);

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: window.crmApplyAuthHeaders({
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }),
            success: function (response) {
                if (isEdit && response.history_entry && window.crmStatusHistory) {
                    showAlert('success', response.message, 'success');
                    $form.find('input[name="status_comment"]').val('');
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                const redirect = response.redirect || '/leads';
                setTimeout(function () {
                    if (redirect) {
                        showAlert('success', response.message, 'success');
                        window.location.href = redirect;
                    }
                }, 300);
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
            },
        });
    });

    $('body').on('input change', '.ajax-lead-form input, .ajax-lead-form select, .ajax-lead-form textarea', function () {
        const $field = $(this);
        $field.removeClass('is-invalid');
        if ($field.is('select')) {
            $field.next('.ts-wrapper').removeClass('is-invalid');
        }
        const id = $field.attr('id');
        if (id) {
            $('#' + id + '-error').html('');
        }
        $field.siblings('.invalid-feedback.ajax-error').remove();
    });
});
