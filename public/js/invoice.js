(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.invoices || {};
        const tableBody = document.getElementById("invoicesTable");

        if (!tableBody) {
            console.error("Invoices table not found");
            return;
        }

        const paginationContainer = document.getElementById("invoicePaginationContainer");
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        // ✅ DELETE FUNCTION
        function deleteInvoice(id, button) {
            window.showDeleteConfirm("This invoice will be deleted!").then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/api/invoices/${id}`,
                        type: "DELETE",
                        headers: authHeaders({
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                        }),
                        success: function (res) {
                            if (res.success) {
                                if (typeof window.showAlert === "function") {
                                    window.showAlert("success", res.message || "Invoice deleted successfully.");
                                }
                                fetchInvoices();
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

        // ✅ RENDER ROWS
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

        function formatInvoiceDate(value) {
            if (!value) {
                return "--";
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return "--";
            }

            return date.toLocaleDateString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        }

        function renderRows(items, meta) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-file-earmark-text display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No invoices found.</p>
                            ${permissions.create ? '<a href="/invoices/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">' : ''}
                                Create Your First Invoice
                            ${permissions.create ? '</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map((invoice, index) => {
                const customer = invoice.customer;
                const customerName = escapeHtml(customer?.name ?? "Unknown");
                const customerEmail = escapeHtml(customer?.email ?? "");
                const invoiceNumber = escapeHtml(invoice.number ?? "--");
                const invDate = escapeHtml(formatInvoiceDate(invoice.invoice_date));
                const dueDate = escapeHtml(formatInvoiceDate(invoice.due_date));
                const statusLabel = escapeHtml(invoice.status ? invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) : "Unpaid");
                const rowNumber = meta && meta.from ? meta.from + index : index + 1;

                return `
                <tr>
                    <td class="ps-4">
                        <span class="text-muted small fw-medium">${rowNumber}</span>
                    </td>

                    <td>
                        <div class="fw-bold small">${invoiceNumber}</div>
                        <div class="text-muted small mt-1">${customerName}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">
                            ${customerEmail}
                        </div>
                    </td>

                    <td class="d-none d-md-table-cell">
                        <div class="small fw-semibold text-dark">${invDate}</div>
                    </td>

                    <td class="d-none d-md-table-cell">
                        <div class="small fw-semibold text-dark">${dueDate}</div>
                    </td>

                    <td class="d-none d-md-table-cell">
                        <button class="btn ${invoice.status === 'paid' ? 'btn-success' : 'btn-secondary'} btn-sm rounded-pill w-100 toggle-status" data-id="${invoice.id}">
                             ${statusLabel}
                        </button>
                    </td>

                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            ${permissions.view ? `<a href="/invoices/${invoice.id}/pdf" class="btn crm-action-btn btn-sm" target="_blank" title="Download PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </a>` : ''}
                            ${permissions.view ? `<a href="/invoices/${invoice.id}" class="btn crm-action-btn btn-sm" title="View">
                                <i class="bi bi-eye"></i>
                            </a>` : ''}
                            ${permissions.edit ? `<a href="/invoices/${invoice.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>` : ''}
                            ${permissions.delete ? `<button class="btn crm-action-btn btn-sm text-danger delete-btn"
                                data-id="${invoice.id}">
                                <i class="bi bi-trash text-danger"></i>
                            </button>` : ''}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-invoice-id="${invoice.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${invoice.id}" style="display: none;">
                    <td colspan="7" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Date :</div>
                                    <div class="expand-value text-end">${invDate}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-regular fa-clock"></i> Due Date :</div>
                                    <div class="expand-value text-end">${dueDate}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                    <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                    <div class="expand-value text-end">
                                        <button class="btn ${invoice.status === 'paid' ? 'btn-success' : 'btn-secondary'} btn-sm rounded-pill toggle-status px-4" data-id="${invoice.id}">
                                            ${statusLabel}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    ${permissions.view ? `<a href="/invoices/${invoice.id}/pdf" class="btn crm-action-btn btn-sm" target="_blank" title="Download PDF"><i class="bi bi-filetype-pdf"></i></a>` : ""}
                                    ${permissions.view ? `<a href="/invoices/${invoice.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ""}
                                    ${permissions.edit ? `<a href="/invoices/${invoice.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ""}
                                    ${permissions.delete ? `<button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${invoice.id}"><i class="bi bi-trash text-danger"></i></button>` : ""}
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // attach delete
            document.querySelectorAll(".delete-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    deleteInvoice(this.dataset.id, this);
                });
            });

            tableBody.querySelectorAll(".btn-user-expand").forEach(button => {
                button.addEventListener("click", function () {
                    const id = this.dataset.invoiceId;
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

        $(document).on('click', '.toggle-status', function () {
            const $button = $(this);
            const invoiceId = $button.data('id');
            const willBePaid = !$button.hasClass('btn-success'); // toggle logic

            const newStatus = willBePaid ? 'paid' : 'unpaid';

            $.ajax({
                url: `/api/invoices/${invoiceId}/status`,
                method: 'PATCH',
                data: {
                    status: newStatus,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                }),
                success: function () {
                    $button
                        .text(newStatus === 'paid' ? 'Paid' : 'Unpaid')
                        .removeClass('btn-success btn-secondary')
                        .addClass(willBePaid ? 'btn-success' : 'btn-secondary');
                },
                error: function (xhr) {
                    console.error(xhr);
                    // Optional: window.crmAlert('Could not update invoice status');
                }
            });
        });


        let timer;
        const searchInput = document.getElementById("invoiceSearch");
        const startDateInput = document.getElementById("invoiceStartDate");
        const endDateInput = document.getElementById("invoiceEndDate");
        const statusSelect = document.getElementById("invoiceStatusFilter");
        const exportBtn = document.getElementById("invoiceExportBtn");

        function refreshInvoices() {
            clearTimeout(timer);
            timer = setTimeout(() => fetchInvoices(1), 300);
        }

        [searchInput, startDateInput, endDateInput, statusSelect].forEach(el => {
            if (!el) return;
            el.addEventListener("change", refreshInvoices);
            if (el === searchInput) {
                el.addEventListener("input", refreshInvoices);
            }
        });

        if (exportBtn) {
            exportBtn.addEventListener("click", function (e) {
                e.preventDefault();
                const params = [];
                if (startDateInput?.value) params.push(`start_date=${encodeURIComponent(startDateInput.value)}`);
                if (endDateInput?.value) params.push(`end_date=${encodeURIComponent(endDateInput.value)}`);
                if (statusSelect?.value) params.push(`status=${encodeURIComponent(statusSelect.value)}`);
                let url = '/invoices/export';
                if (params.length) url += `?${params.join('&')}`;
                window.location.href = url;
            });
        }

        // ✅ FETCH API
        function fetchInvoices(page = 1) {
            let url = `/api/invoices?page=${page}`;

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }
            if (startDateInput && startDateInput.value) {
                url += `&start_date=${encodeURIComponent(startDateInput.value)}`;
            }
            if (endDateInput && endDateInput.value) {
                url += `&end_date=${encodeURIComponent(endDateInput.value)}`;
            }
            if (statusSelect && statusSelect.value) {
                url += `&status=${encodeURIComponent(statusSelect.value)}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                }),
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
                    <td colspan="7" class="text-center py-5">
                        Error loading invoices
                    </td>
                </tr>`;
                },
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
            <ul class="pagination crm-pagination mb-0">
    `;

            // Previous
            if (data.prev_page_url) {
                html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">
                    Previous
                </a>
            </li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
            }

            // Pages
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            if (data.next_page_url) {
                html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">
                    Next
                </a>
            </li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            }

            html += `</ul></div>`;

            paginationContainer.innerHTML = html;

            // Click events
            paginationContainer.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchInvoices(this.dataset.page);
                });
            });
        }

        // ✅ INITIAL LOAD
        fetchInvoices();
    }
})();

$(document).ready(function () {
    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    function getEnhancedElement($field) {
        if (!$field || !$field.length || !$field.is('select')) {
            return $();
        }

        if ($field.next('.ts-wrapper').length) {
            return $field.next('.ts-wrapper');
        }

        if ($field.next('.select2-container').length) {
            return $field.next('.select2-container');
        }

        return $();
    }

    function getErrorAnchor($field) {
        const $enhanced = getEnhancedElement($field);
        return $enhanced.length ? $enhanced : $field;
    }

    function handleValidationErrors($form, errors) {
        // First, remove all previous ajax errors and is-invalid classes
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.ts-wrapper.is-invalid').removeClass('is-invalid');
        $form.find('.select2-container.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();

        Object.keys(errors).forEach(function (key) {
            const messages = errors[key]; // usually array with 1 message
            if (!messages || !messages.length) return;

            // Normalize key: items.0.product_id → items[0][product_id]
            let inputName = key.replace(/\.(\d+)\./g, '[$1][');

            // 1. Try exact name match first (most reliable)
            let $input = $form.find(`[name="${inputName}"]`);

            // 2. Fallback: if not found, try starts-with for array fields
            if (!$input.length) {
                const parts = key.split('.');
                const base = parts[0];             // "items"
                const index = parts[1];            // "0", "1", etc.
                const field = parts[2];            // "product_id", "amount", etc.

                if (index !== undefined && field !== undefined) {
                    $input = $form.find(`[name^="${base}[${index}][${field}]"]`);
                }
            }

            if ($input.length) {
                $input.addClass('is-invalid');
                getEnhancedElement($input).addClass('is-invalid');

                // Add error message below the input
                const $error = $(`<div class="invalid-feedback ajax-error">${messages[0]}</div>`);
                const $errorAnchor = getErrorAnchor($input);
                $errorAnchor.siblings('.invalid-feedback.ajax-error').remove();
                $errorAnchor.after($error);
            } else {
                console.warn(`Could not find input for validation key: ${key}`);
            }
        });
    }

    // Handle form submission
    $('body').on('submit', '.ajax-invoice-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const btn = $form.find('button[type=submit]');
        const originalText = btn.html();
        const method = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.ts-wrapper.is-invalid').removeClass('is-invalid');
        $form.find('.select2-container.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        $form.find('.ajax-alert').remove();

        const formData = new FormData(this);

        btn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>${method === 'PUT' ? 'Updating' : 'Creating'}`);

        $.ajax({
            url: url,
            method: 'POST', // Always POST with FormData, use _method for spoofing
            data: formData,
            processData: false,
            contentType: false,
            headers: authHeaders({
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                Accept: 'application/json'
            }),
            success: function (response) {
                const redirect = response.redirect || '/invoices';
                if (typeof window.showAlert === 'function') {
                    window.showAlert('success', response.message || 'Invoice saved successfully.', 'Success', redirect);
                } else {
                    window.location.href = redirect;
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    handleValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    const response = xhr.responseJSON || {};
                    const message = response.message || 'Something went wrong while submitting the form. Please try again.';
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', message);
                    } else {
                        const alertHtml = `<div class="alert alert-danger ajax-alert" role="alert">${message}</div>`;
                        $form.prepend(alertHtml);
                    }
                }
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('body').on('input change', '.ajax-invoice-form input, .ajax-invoice-form select, .ajax-invoice-form textarea', function () {
        const $field = $(this);
        $field.removeClass('is-invalid');
        getEnhancedElement($field).removeClass('is-invalid');
        getErrorAnchor($field).siblings('.invalid-feedback.ajax-error').remove();
    });

    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            theme: 'bootstrap-5'
        });
    }

    function initInvoiceItemTable() {
        const products = window.invoiceProducts || [];
        const tbody = document.getElementById('invoiceItemsBody');
        const addItemBtn = document.getElementById('addItemBtn');

        if (!tbody || !addItemBtn) {
            return;
        }

        let itemIndex = tbody.querySelectorAll('tr').length;

        function getProductOptionsHtml(selectedId) {
            return `
                <option value="">Select product</option>
                ${products
                    .map(product => `
                        <option value="${product.id}" data-name="${product.name}" data-price="${product.price}" ${selectedId == product.id ? 'selected' : ''}>
                            ${product.name}
                        </option>
                    `)
                    .join('')}
            `;
        }

        function updateRowTotal(row) {
            const amountInput = row.querySelector('.item-amount');
            const quantityInput = row.querySelector('.item-quantity');
            const totalInput = row.querySelector('.item-total');

            const amount = parseFloat(amountInput?.value || 0);
            const quantity = parseInt(quantityInput?.value || 0, 10) || 0;
            if (totalInput) {
                totalInput.value = (amount * quantity).toFixed(2);
            }
        }

        function syncProductName(row) {
            const productSelect = row.querySelector('.product-select');
            const hiddenName = row.querySelector('[name$="[product_name]"]');
            if (!productSelect || !hiddenName) return;

            const selectedOption = productSelect.selectedOptions[0];
            hiddenName.value = selectedOption?.dataset.name || '';
        }

        function updateTotals() {
            tbody.querySelectorAll('tr').forEach(row => {
                syncProductName(row);
                updateRowTotal(row);
            });
        }

        function addNewItemRow(item = {}) {
            const row = document.createElement('tr');
            const selectedId = item.product_id ?? '';
            const amountValue = item.amount !== undefined ? item.amount : '';
            const quantityValue = item.quantity !== undefined ? item.quantity : 1;
            const totalValue = ((parseFloat(amountValue) || 0) * (parseInt(quantityValue, 10) || 0)).toFixed(2);

            row.innerHTML = `
                <td>
                    <select name="items[${itemIndex}][product_id]" class="form-select product-select" required>
                        ${getProductOptionsHtml(selectedId)}
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" name="items[${itemIndex}][amount]" class="form-control item-amount" value="${amountValue}" required></td>
                <td><input type="number" min="1" name="items[${itemIndex}][quantity]" class="form-control item-quantity" value="${quantityValue}" required></td>
                <td><input type="text" class="form-control item-total" readonly value="${totalValue}"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-item">Delete</button></td>
            `;

            tbody.appendChild(row);
            itemIndex++;
            updateRowTotal(row);
        }

        addItemBtn.addEventListener('click', function () {
            addNewItemRow({});
        });

        tbody.addEventListener('change', function (event) {
            if (event.target.classList.contains('product-select')) {
                const row = event.target.closest('tr');
                const selectedOption = event.target.selectedOptions[0];
                const amountInput = row.querySelector('.item-amount');
                const quantityInput = row.querySelector('.item-quantity');
                const hiddenName = row.querySelector('[name$="[product_name]"]');

                if (selectedOption) {
                    const price = parseFloat(selectedOption.dataset.price || 0);
                    const name = selectedOption.dataset.name || '';
                    if (amountInput) {
                        amountInput.value = price.toFixed(2);
                    }
                    if (quantityInput) {
                        quantityInput.value = 1;
                    }
                    if (hiddenName) {
                        hiddenName.value = name;
                    }
                }
                updateRowTotal(row);
            }
        });

        tbody.addEventListener('input', function (event) {
            if (event.target.classList.contains('item-quantity')) {
                if (!event.target.value || parseInt(event.target.value) < 1) {
                    event.target.value = 1;
                }
            }
            if (event.target.classList.contains('item-amount') || event.target.classList.contains('item-quantity')) {
                const row = event.target.closest('tr');
                updateRowTotal(row);
            }
        });

        tbody.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-item') && tbody.querySelectorAll('tr').length > 1) {
                event.target.closest('tr').remove();
                updateTotals();
            }
        });

        updateTotals();
    }

    initInvoiceItemTable();
});
