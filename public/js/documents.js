(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        initDocumentsTable();
    }

    function showToast(type, message, redirectUrl = null) {
        if (typeof window.showAlert === "function") {
            window.showAlert(type, message, type === "success" ? "Success!" : "Error!", redirectUrl);
            return;
        }

        window.crmAlert(message);
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }

    function authHeaders(extraHeaders = {}) {
        if (typeof window.crmApplyAuthHeaders === "function") {
            return window.crmApplyAuthHeaders(extraHeaders);
        }

        return extraHeaders;
    }

    function initDocumentsTable() {
        const tableBody = document.querySelector("#documentsTable tbody");
        const searchInput = document.getElementById("documentsSearch");
        const paginationContainer = document.getElementById("documentsPagination");

        if (!tableBody || !paginationContainer) {
            // Check if we are on the form page instead of index
            initDocumentForm();
            return;
        }

        function renderRows(items) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                            No documents found.
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map(function (item) {
                const date = item.created_at ? new Date(item.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '--';
                const fileType = (item.file_type || '').toLowerCase();
                
                let iconClass = 'bi-file-earmark-text';
                let iconColor = 'text-primary';
                
                if (fileType === 'pdf') { iconClass = 'bi-file-earmark-pdf'; iconColor = 'text-danger'; }
                else if (['png', 'jpg', 'jpeg', 'gif', 'svg'].includes(fileType)) { iconClass = 'bi-file-earmark-image'; iconColor = 'text-success'; }
                else if (['doc', 'docx'].includes(fileType)) { iconClass = 'bi-file-earmark-word'; iconColor = 'text-primary'; }

                return `
                    <tr>
                        <td class="ps-4" data-label="Sr.No">${item.row_number}</td>
                        <td data-label="Title">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3 d-flex align-items-center justify-content-center ${iconColor}" style="width: 36px; height: 36px;">
                                    <i class="bi ${iconClass} fs-5"></i>
                                </div>
                                <div class="fw-semibold text-dark">${item.title}</div>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell" data-label="Type">
                            <span class="badge bg-secondary rounded-pill px-3">${fileType.toUpperCase()}</span>
                        </td>
                        <td class="d-none d-md-table-cell" data-label="Uploaded By">
                            <span class="text-muted">${item.user?.name || 'Unknown'}</span>
                        </td>
                        <td class="d-none d-md-table-cell" data-label="Date">
                            <span class="text-muted">${date}</span>
                        </td>
                        <td class="text-end pe-4 d-none d-md-table-cell" data-label="Actions">
                            <div class="d-inline-flex align-items-center gap-2 justify-content-end">
                                <a href="/documents/${item.id}/download" class="btn crm-action-btn btn-sm" title="Download"><i class="bi bi-download"></i></a>
                                <a href="/documents/${item.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn crm-action-btn btn-sm text-danger ajax-document-delete" data-id="${item.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-id="${item.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${item.id}" style="display: none;">
                        <td colspan="4" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-file"></i> Type :</div>
                                        <div class="expand-value"><span class="badge bg-secondary rounded-pill px-3">${fileType.toUpperCase()}</span></div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-user"></i> Uploaded By :</div>
                                        <div class="expand-value">${item.user?.name || 'Unknown'}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-regular fa-calendar"></i> Date :</div>
                                        <div class="expand-value">${date}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            <a href="/documents/${item.id}/download" class="btn crm-action-btn btn-sm"><i class="bi bi-download"></i></a>
                                            <a href="/documents/${item.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>
                                            <button type="button" class="btn crm-action-btn btn-sm text-danger ajax-document-delete" data-id="${item.id}"><i class="bi bi-trash text-danger"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.id;
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

        function renderPagination(page) {
            if (!page || page.total === 0) {
                paginationContainer.innerHTML = "";
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

            if (page.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let i = 1; i <= lastPage; i += 1) {
                html += i === currentPage
                    ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                    : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }

            if (page.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += `
                    </ul>
                </div>
            `;

            paginationContainer.innerHTML = html;
            paginationContainer.querySelectorAll("[data-page]").forEach(function (link) {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    fetchDocuments(this.dataset.page);
                });
            });
        }

        function fetchDocuments(page) {
            const url = new URL("/api/documents", window.location.origin);
            url.searchParams.set("page", page || 1);

            if (searchInput && searchInput.value.trim()) {
                url.searchParams.set("search", searchInput.value.trim());
            }

            $.ajax({
                url: url.toString(),
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                }),
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                    `;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || []);
                        renderPagination(response.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-exclamation-triangle display-1 opacity-25"></i></div>
                                <p class="text-muted mb-0">Error loading documents. Please try again.</p>
                            </td>
                        </tr>
                    `;
                },
            });
        }

        let timer;
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetchDocuments(1);
                }, 350);
            });
        }

        fetchDocuments(1);
        
        // Expose refresh function for other parts
        window.refreshDocumentsTable = function() {
            fetchDocuments(1);
        };
    }

    function initDocumentForm() {
        const $form = $(".ajax-document-form");
        if (!$form.length) return;

        $form.on("submit", function (e) {
            e.preventDefault();

            const $button = $form.find("#submitBtn");
            const originalHtml = $button.html();
            const formData = new FormData(this);

            $form.find(".is-invalid").removeClass("is-invalid");
            $form.find(".invalid-feedback").html("");
            
            $button.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.ajax({
                url: $form.attr("action"),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                }),
                success: function (response) {
                    $button.prop("disabled", false).html(originalHtml);
                    showToast("success", response.message || "Document saved successfully.", response.redirect || "/documents");
                },
                error: function (xhr) {
                    $button.prop("disabled", false).html(originalHtml);

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            const $input = $form.find("[name='" + field + "'], #" + field);
                            $input.addClass("is-invalid");
                            // Find or create feedback
                            let $feedback = $form.find("#" + field + "-error");
                            if (!$feedback.length) {
                                $feedback = $('<div id="' + field + '-error" class="invalid-feedback"></div>');
                                $input.after($feedback);
                            }
                            $feedback.html(messages[0]);
                        });
                        return;
                    }

                    showToast("error", xhr.responseJSON?.message || "Unable to save the document right now.");
                },
            });
        });
    }

    $(document).on("click", ".ajax-document-delete", function () {
        const button = this;
        const documentId = button.dataset.id;

        window.showDeleteConfirm("This document will be deleted!").then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

            $.ajax({
                url: "/api/v1/documents/" + documentId,
                type: "DELETE",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                }),
                success: function (response) {
                    showToast("success", response.message || "Document deleted successfully.");
                    if (window.refreshDocumentsTable) {
                        window.refreshDocumentsTable();
                    } else {
                        const row = button.closest("tr");
                        if (row) row.remove();
                    }
                },
                error: function (xhr) {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                    showToast("error", xhr.responseJSON?.message || "Unable to delete the document.");
                },
            });
        });
    });
})();
