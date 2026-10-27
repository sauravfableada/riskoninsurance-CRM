// ==================== CONFIGURATION ====================
const API_CONFIG = {
    services: '/api/services',
    products: '/api/products'
};
const SERVICE_PERMISSIONS = window.crmUserPermissions?.services || {};
const authHeaders = (extraHeaders = {}) =>
    typeof window.crmApplyAuthHeaders === "function"
        ? window.crmApplyAuthHeaders(extraHeaders)
        : extraHeaders;

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

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, "$&,");
}

function formatInr(num) {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(num || 0));
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
    $.each(errors, function (field, messages) {
        let $input = $(`#${field}`);
        $input.addClass("is-invalid");
        $(`#${field}-error`).html(messages[0]).show();
    });
}

function getServiceIdFromUrl() {
    let pathArray = window.location.pathname.split('/');
    return pathArray[pathArray.length - 2]; // Gets ID from /services/{id}/edit
}

// ==================== PRODUCT LOADER (Single Function) ====================
function loadProducts(selectedProductId = null) {
    let $productSelect = $("#product_id");
    if (!$productSelect.length) return;

    $productSelect.html('<option value="">Loading products...</option>');

    $.ajax({
        url: API_CONFIG.products,
        type: "GET",
        dataType: "json",
        headers: authHeaders({
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        }),
        success: function (response) {
            const payload = response?.data ?? response;
            const products = Array.isArray(payload)
                ? payload
                : (Array.isArray(payload?.data) ? payload.data : []);
            let options = '<option value="">Select Product</option>';

            products.forEach((product) => {
                let selected = (selectedProductId && selectedProductId == product.id) ? "selected" : "";
                options += `<option value="${product.id}" ${selected}>${escapeHtml(product.name)}</option>`;
            });

            $productSelect.html(options);
        },
        error: function () {
            $productSelect.html('<option value="">Error loading products</option>');
            showAlert("error", "Failed to load products");
        }
    });
}

// ==================== SERVICES TABLE ====================
const ServiceTable = {
    init() {
        this.load();
        this.initSearch();
        this.initPagination();
    },

    initSearch() {
        let searchTimer;
        $("#serviceSearch").on("keyup", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.load(), 500);
        });
    },

    initPagination() {
        $(document).on("click", ".pagination a[data-page]", (e) => {
            e.preventDefault();
            let page = $(e.currentTarget).data("page");
            if (page) this.load(parseInt(page));
        });
    },

    load(page = 1) {
        let search = $("#serviceSearch").val();

        // Show loading state
        $("#servicesTable tbody").html(this.getLoadingTemplate());

        $.ajax({
            url: API_CONFIG.services,
            type: "GET",
            data: { page, search },
            dataType: "json",
            headers: authHeaders({ "X-Requested-With": "XMLHttpRequest", Accept: "application/json" }),
            success: (response) => {
                if (response.success) {
                    this.renderRows(response.data.data, response.data);
                    this.renderPagination(response.data);
                } else {
                    showAlert("error", "Failed to load services");
                }
            },
            error: (xhr) => this.handleError(xhr)
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
                        <p>Loading services...</p>
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

        $("#servicesTable tbody").html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-danger">
                        <i class="bi bi-exclamation-triangle display-6 d-block mb-3"></i>
                        <p>Failed to load services. Please try again.</p>
                        <button class="btn btn-dark-blue btn-sm" onclick="location.reload()">
                            Retry
                        </button>
                    </div>
                </td>
            </tr>
        `);
    },

    renderRows(services, pageData = null) {
        let tbody = $("#servicesTable tbody").empty();

        if (!services?.length) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-inbox display-6 d-block mb-3"></i>
                            <p>No services found.</p>
                            ${SERVICE_PERMISSIONS.create ? `<a href="/services/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                                Add Your First Service
                            </a>` : ""}
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = "";
        services.forEach((service, index) => {
            let productName = service.product ? escapeHtml(service.product.name) : "--";
            let currentPage = pageData?.current_page || 1;
            let perPage = pageData?.per_page || services.length || 10;
            let rowNumber = ((currentPage - 1) * perPage) + index + 1;
            let createdAt = service.created_at
                ? new Date(service.created_at).toLocaleString("en-GB", {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: true,
                }).replace(",", "")
                : "--";

            let statusBadge = service.status === "active"
                ? '<span class="badge crm-status-pill rounded-pill bg-success">ACTIVE</span>'
                : '<span class="badge crm-status-pill rounded-pill bg-secondary">INACTIVE</span>';

            html += `
                <tr>
                    <td class="ps-4">
                        <span class="text-muted small fw-medium">${rowNumber}</span>
                    </td>
                    <td>
                        <div class="fw-bold small">${escapeHtml(service.service_name)}</div>
                        <div class="text-muted small mt-1">${escapeHtml(productName)}</div>
                    </td>
                    <td class="d-none d-md-table-cell">${escapeHtml(productName)}</td>
                    <td class="d-none d-md-table-cell"><span class="fw-semibold">${formatInr(service.service_price)}</span></td>
                    <td class="d-none d-md-table-cell">${statusBadge}</td>
                    <td class="text-nowrap d-none d-md-table-cell">${createdAt}</td>
                    <td class="d-none d-md-table-cell">
                        <div class="d-flex justify-content-end gap-2">
                            ${SERVICE_PERMISSIONS.edit ? `<a href="/services/${service.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                            ${SERVICE_PERMISSIONS.view ? `<a href="/services/${service.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                            ${SERVICE_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-service" data-id="${service.id}" title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </button>` : ""}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-service-id="${service.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${service.id}" style="display: none;">
                    <td colspan="8" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-regular fa-folder-open"></i> Product :</div>
                                    <div class="expand-value text-end">${escapeHtml(productName)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Price :</div>
                                    <div class="expand-value text-end">${formatInr(service.service_price)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                    <div class="expand-value text-end">${statusBadge}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                    <div class="expand-value text-end">${createdAt}</div>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    ${SERVICE_PERMISSIONS.edit ? `<a href="/services/${service.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                                    ${SERVICE_PERMISSIONS.view ? `<a href="/services/${service.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                    ${SERVICE_PERMISSIONS.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-service" data-id="${service.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ""}
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);

        tbody.find(".btn-user-expand").each(function () {
            $(this).on("click", function () {
                const id = $(this).data("service-id");
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
    },

    renderPagination(data) {
        let $container = $("#paginationContainer");
        if (!$container.length || !data || data.total === 0) {
            $container.empty();
            return;
        }

        const { from = 0, to = 0, total = 0, current_page = 1, last_page = 1, prev_page_url, next_page_url } = data;
        const maxPagesToShow = 5;
        let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
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
            ? `<li class="page-item"><a class="page-link" href="#" data-page="${current_page - 1}">Previous</a></li>`
            : `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;

        // First page
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html += i === current_page
                ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        // Last page
        if (endPage < last_page) {
            if (endPage < last_page - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${last_page}">${last_page}</a></li>`;
        }

        // Next button
        html += next_page_url
            ? `<li class="page-item"><a class="page-link" href="#" data-page="${current_page + 1}">Next</a></li>`
            : `<li class="page-item disabled"><span class="page-link">Next</span></li>`;

        html += `</ul></div>`;
        $container.html(html);
    }
};

// ==================== SERVICE FORM (Create) ====================
const ServiceForm = {
    init() {
        if ($("#serviceForm").length) {
            loadProducts(); // Load products for dropdown
            $("#serviceForm").on("submit", (e) => this.handleSubmit(e));
        }
    },

    handleSubmit(e) {
        e.preventDefault();
        let form = $(e.currentTarget);
        let btn = $("#submitBtn");

        // Clear validation errors
        $(".invalid-feedback").empty().hide();
        $(".form-control, .form-select").removeClass("is-invalid");

        buttonLoader(btn, "Submitting...");

        $.ajax({
            url: API_CONFIG.services,
            type: "POST",
            data: form.serialize(),
            headers: authHeaders({ "X-Requested-With": "XMLHttpRequest", Accept: "application/json", "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") }),
            success: (response) => {
                buttonLoader(btn, "", false);
                if (response.success) {
                    showAlert("success", response.message, "", response.redirect || "/services");
                    form[0].reset();
                }
            },
            error: (xhr) => {
                buttonLoader(btn, "", false);
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else {
                    showAlert("error", "Something went wrong");
                }
            }
        });
    }
};

// ==================== SERVICE UPDATE ====================
const ServiceUpdate = {
    init() {
        if ($("#serviceupdate").length) {
            let selectedId = $("#current_product_id").val();
            loadProducts(selectedId);
            $("#serviceupdate").on("submit", (e) => {
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

        let formData = {
            product_id: $("#product_id").val(),
            service_name: $("#service_name").val(),
            service_price: $("#service_price").val(),
            status: $("#status").val(),
            description: $("#description").val(),
            _token: $('meta[name="csrf-token"]').attr("content"),
            _method: "PUT",
        };

        $.ajax({
            url: `${API_CONFIG.services}/${getServiceIdFromUrl()}`,
            type: "POST",
            data: formData,
            dataType: "json",
            headers: authHeaders({ "X-Requested-With": "XMLHttpRequest", Accept: "application/json", "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") }),
            success: (response) => {
                buttonLoader("#submitBtn", "", false);
                if (response.history_entry && window.crmStatusHistory) {
                    showAlert("success", response.message || "Service updated successfully", "Success!");
                    $("#serviceupdate").find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                showAlert("success", response.message || "Service updated successfully", "Success!", response.redirect || "/services");
            },
            error: (xhr) => {
                buttonLoader("#submitBtn", "", false);
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else if (xhr.status === 401 || xhr.status === 419) {
                    window.location.href = "/login";
                } else {
                    showAlert("error", xhr.responseJSON?.message || "Failed to update service");
                }
            }
        });
    }
};

// ==================== DELETE SERVICE ====================
function deleteService(id) {
    window.showDeleteConfirm("You won't be able to revert this!").then((result) => {
        if (!result.isConfirmed) return;

        let deleteButton = $(`.delete-service[data-id="${id}"]`);
        buttonLoader(deleteButton, "Deleting", true);

        $.ajax({
            url: `${API_CONFIG.services}/${id}`,
            type: "DELETE",
            headers: authHeaders({ "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), "X-Requested-With": "XMLHttpRequest", Accept: "application/json" }),
            success: (response) => {
                buttonLoader(deleteButton, "", false);
                if (response.success) {
                    showAlert("success", "Service deleted successfully");
                    let currentPage = new URL(window.location.href).searchParams.get("page") || 1;
                    ServiceTable.load(currentPage);
                } else {
                    showAlert("error", "Failed to delete service");
                }
            },
            error: (xhr) => {
                buttonLoader(deleteButton, "", false);
                showAlert("error", "Failed to delete service");
            }
        });
    });
}

// ==================== INITIALIZATION ====================
$(document).ready(function () {
    // Initialize based on current page
    if ($("#serviceSearch").length) {
        ServiceTable.init();
    }

    if ($("#serviceForm").length) {
        ServiceForm.init();
    }

    if ($("#serviceupdate").length) {
        ServiceUpdate.init();
    }

    // Global delete handler
    $(document).on("click", ".delete-service", function () {
        deleteService($(this).data("id"));
    });

    $(document).on("input change", "#serviceForm input, #serviceForm select, #serviceForm textarea, #serviceupdate input, #serviceupdate select, #serviceupdate textarea", function () {
        $(this).removeClass("is-invalid");
        $(`#${$(this).attr("id")}-error`).html("").hide();
    });
});
