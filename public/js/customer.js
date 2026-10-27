(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const tableBody = document.getElementById("customersTable");

        if (!tableBody) {
            // Probably on create/edit page
            return;
        }

        const paginationContainer = document.getElementById("customerPaginationContainer");
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        const importFileInput = document.getElementById("customersImportFile");
        if (importFileInput) {
            importFileInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('import_file', file);

                Swal.fire({
                    title: 'Importing Customers...',
                    text: 'Please wait while we process the file.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '/api/customers/import',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    headers: authHeaders({
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    }),
                    success: function (res) {
                        Swal.close();
                        if (res.success) {
                            if (window.showAlert) {
                                window.showAlert('success', res.message || 'Customers imported successfully.', 'Success');
                            }
                            fetchCustomers(1);
                        } else {
                            if (window.showAlert) {
                                window.showAlert('error', res.message || 'Import failed.', 'Error');
                            }
                        }
                    },
                    error: function (xhr) {
                        Swal.close();
                        const message = xhr.responseJSON?.message || 'Unable to import customers right now.';
                        if (window.showAlert) {
                            window.showAlert('error', message, 'Error');
                        } else {
                            window.crmAlert(message);
                        }
                    },
                    complete: function () {
                        importFileInput.value = '';
                    }
                });
            });
        }

        // ✅ DELETE FUNCTION
        function deleteCustomer(id, button) {
            if (window.showDeleteConfirm) {
                window.showDeleteConfirm("This customer and all associated data will be deleted!").then((result) => {
                    if (result.isConfirmed) {
                        performDelete(id, button);
                    }
                });
            } else {
                window.crmConfirm("Are you sure you want to delete this customer?").then((result) => {
                    if (result.isConfirmed) {
                        performDelete(id, button);
                    }
                });
            }
        }

        function performDelete(id, button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

            const headers = authHeaders({
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            });

            $.ajax({
                url: `/api/customers/${id}`,
                type: "DELETE",
                headers: headers,
                success: function (res) {
                    if (res.success) {
                        if (window.showAlert) {
                            window.showAlert('success', res.message || "Customer deleted successfully", 'Success');
                        }
                        fetchCustomers();
                    } else {
                        window.crmAlert(res.message || "Delete failed");
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || "Something went wrong";
                    window.crmAlert(message);
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                },
            });
        }

        // ✅ RENDER ROWS
        function renderRows(items) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-people display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No customers found.</p>
                            <a href="/masters/customers/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                                Add Your First Customer
                            </a>
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map((customer, index) => {
                const date = customer.created_at ? new Date(customer.created_at).toLocaleDateString("en-GB", {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                }) : "--";
                const actions = renderActions(customer);


                return `
                <tr>
                    <td class="ps-4">
                        <span class="text-muted small fw-medium">${index + 1}</span>
                    </td>

                    <td>
                        <div>
                            <div class="fw-bold small">${escapeHtml(customer.name)}</div>
                        </div>
                    </td>

                    <td class="d-none d-md-table-cell">
                        <div class="small">${escapeHtml(customer.email || '--')}</div>
                    </td>

                    <td class="d-none d-md-table-cell">
                        <div class="small">${escapeHtml(customer.phone || '--')}</div>
                    </td>

                    <td class="d-none d-md-table-cell">
                        <div class="small fw-semibold text-nowrap">${date}</div>
                    </td>

                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            ${actions}
                        </div>
                    </td>

                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-customer-id="${customer.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>

                <tr class="details-row d-md-none border-0" id="details-${customer.id}" style="display: none;">
                    <td colspan="6" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-envelope"></i> Email :</div>
                                    <div class="expand-value">${escapeHtml(customer.email || "--")}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone :</div>
                                    <div class="expand-value">${escapeHtml(customer.phone || "--")}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                    <div class="expand-value">${date}</div>
                                </div>
                             </div>
                             <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                 <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                 <div class="d-flex gap-2">
                                 ${actions}
                                 </div>
                             </div>
                         </div>
                    </td>
                </tr>`;
            }).join("");

            // attach delete
            tableBody.querySelectorAll(".delete-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    deleteCustomer(this.dataset.id, this);
                });
            });

            // attach expand
            tableBody.querySelectorAll(".btn-user-expand").forEach(button => {
                button.addEventListener("click", function () {
                    const id = this.dataset.customerId;
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

        function renderActions(customer) {
            const viewAction = customer.can_view
                ? `
                    <a href="/masters/customers/${customer.id}" class="btn crm-action-btn btn-sm" title="View">
                        <i class="bi bi-eye"></i>
                    </a>`
                : `
                    <button class="btn crm-action-btn btn-sm" title="View" disabled aria-disabled="true">
                        <i class="bi bi-eye"></i>
                    </button>`;

            const editAction = customer.can_update
                ? `
                    <a href="/masters/customers/${customer.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>`
                : `
                    <button class="btn crm-action-btn btn-sm" title="Edit" disabled aria-disabled="true">
                        <i class="bi bi-pencil"></i>
                    </button>`;

            const deleteAction = customer.can_delete
                ? `
                    <button class="btn crm-action-btn btn-sm text-danger delete-btn"
                        data-id="${customer.id}" title="Delete">
                        <i class="bi bi-trash text-danger"></i>
                    </button>`
                : `
                    <button class="btn crm-action-btn btn-sm text-danger" title="Delete" disabled aria-disabled="true">
                        <i class="bi bi-trash text-danger"></i>
                    </button>`;

            return `${viewAction}${editAction}${deleteAction}`;
        }

        let timer;
        const searchInput = document.getElementById("customerSearch");
        const filterClientName = document.getElementById("filterClientName");
        const filterEmail = document.getElementById("filterEmail");
        const filterPhone = document.getElementById("filterPhone");
        const filterCreatedAt = document.getElementById("filterCreatedAt");
        const filterCreatedBy = document.getElementById("filterCreatedBy");
        const perPageSelect = document.getElementById("perPageSelect");
        const clearFiltersBtn = document.getElementById("clearFiltersBtn");

        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchCustomers(1), 400);
            });
        }
        
        // old date filters removed
        
        if (perPageSelect) {
            perPageSelect.addEventListener("change", () => fetchCustomers(1));
        }

        const filterFromDate = document.getElementById('filterFromDate');
        const filterToDate = document.getElementById('filterToDate');

        ['filterFromDate', 'filterToDate', 'filterType', 'filterCountry', 'filterCity', 'filterStatus'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                if (filterInput.classList.contains('select2')) {
                    $(filterInput).on('change', () => fetchCustomers(1));
                } else {
                    filterInput.addEventListener('change', () => setTimeout(() => fetchCustomers(1), 0));
                }
            }
        });

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
                
                if (filterFromDate) filterFromDate.value = from;
                if (filterToDate) filterToDate.value = to;
                
                setTimeout(() => fetchCustomers(1), 0);
            });
        }

        const filterCountry = $('#filterCountry');
        const filterCity = $('#filterCity');
        if (filterCountry.length && filterCity.length) {
            filterCountry.on('change', function() {
                const countryId = $(this).val();
                filterCity.empty().append('<option value="">All Cities</option>');
                
                if (countryId) {
                    $.ajax({
                        url: (window.crmConfig?.baseUrl || '') + `/api/masters/cities-by-country/${countryId}`,
                        type: 'GET',
                        headers: authHeaders({
                            "X-Requested-With": "XMLHttpRequest",
                            "Accept": "application/json"
                        }),
                        success: function(response) {
                            if (response.success && response.data) {
                                response.data.forEach(city => {
                                    filterCity.append(new Option(city.name, city.id));
                                });
                            }
                        }
                    });
                }
            });
        }
        
        // clearFiltersBtn is already declared at the top
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                if (filterFromDate) filterFromDate.value = '';
                if (filterToDate) filterToDate.value = '';
                if (filterDateRange) filterDateRange.value = '';
                document.getElementById('filterType').value = '';
                document.getElementById('filterStatus').value = '';
                
                if ($('#filterCountry').length) {
                    $('#filterCountry').val('').trigger('change.select2');
                }
                if ($('#filterCity').length) {
                    $('#filterCity').empty().append('<option value="">All Cities</option>').trigger('change.select2');
                }
                
                if (searchInput) searchInput.value = '';
                fetchCustomers(1);
            });
        }

        // ✅ FETCH API
        function fetchCustomers(page = 1) {
            let url = (window.crmConfig?.baseUrl || '') + `/api/customers?page=${page}`;
            const search = searchInput?.value.trim() || "";
            const fromDate = filterFromDate?.value || "";
            const toDate = filterToDate?.value || "";
            const perPage = perPageSelect?.value || "";
            const type = document.getElementById('filterType')?.value || "";
            const countryId = document.getElementById('filterCountry')?.value || "";
            const cityId = document.getElementById('filterCity')?.value || "";
            const status = document.getElementById('filterStatus')?.value || "";

            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (fromDate) url += `&from_date=${encodeURIComponent(fromDate)}`;
            if (toDate) url += `&to_date=${encodeURIComponent(toDate)}`;
            if (perPage) url += `&per_page=${encodeURIComponent(perPage)}`;
            if (type) url += `&type=${encodeURIComponent(type)}`;
            if (countryId) url += `&country_id=${encodeURIComponent(countryId)}`;
            if (cityId) url += `&city_id=${encodeURIComponent(cityId)}`;
            if (status !== "") url += `&is_active=${encodeURIComponent(status)}`;

            const headers = authHeaders({
                "X-Requested-With": "XMLHttpRequest",
            });

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: headers,
                beforeSend: function () {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </td>
                    </tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        // Handle both paginated and non-paginated responses
                        const items = res.data.data || res.data;
                        renderRows(items);
                        if (res.data.current_page) {
                            renderPagination(res.data);
                        } else {
                            paginationContainer.innerHTML = "";
                        }
                    }
                },
                error: function (xhr) {
                    console.error('Error loading customers:', xhr);
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            Error loading customers
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
            document.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchCustomers(this.dataset.page);
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
                .replace(/\"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // ✅ INITIAL LOAD
        fetchCustomers();
    }
})();

function showImportDialog() {
    Swal.fire({
        html: `
            <div class="mt-4 mb-3">
                <i class="fa-solid fa-file-csv fa-4x text-dark-blue"></i>
            </div>
            <h2 class="fw-bold mb-3" style="font-size: 1.75rem; color: #333;">Import Customers</h2>
            <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.5;">
                Would you like to import a CSV or download the demo template?
            </p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa-solid fa-upload me-1"></i> Import CSV',
        denyButtonText: '<i class="fa-solid fa-download me-1"></i> Download Demo',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-outline-dark-blue me-2',
            denyButton: 'btn btn-dark-blue me-2',
            cancelButton: 'btn btn-outline-dark-blue'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('customersImportFile').click();
        } else if (result.isDenied) {
            downloadCustomerCsvDemo();
        }
    });
}

function downloadCustomerCsvDemo() {
    const csvContent = "data:text/csv;charset=utf-8,name,email,phone,whatsapp,company_name,address,type\n" +
        "John Customer,john@example.com,9876543210,9876543210,Global Corp,123 Street City,Corporate\n" +
        "Jane Client,jane@example.com,9876543211,9876543211,Individual,456 Avenue City,Individual";
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "customers_demo.csv");
    document.body.appendChild(link);
    link.click();
    link.remove();
}

$(document).ready(function () {
    // add auth headers
    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    // Wizard
    var $form = $('#customerForm');
    if ($form.length) {
        var $steps     = $form.find('.customer-form-step');
        var $stepBtns  = $('#customerFormSteps button[data-step]');
        var $prevBtn   = $form.find('.prev-step');
        var $nextBtn   = $form.find('.next-step');
        var $cancelBtn = $form.find('.cancel-step');
        var $submitBtn = $form.find('button[type="submit"]');
        var totalSteps = $steps.length;
        var currentStep = 1;
        var currentCustomerId = $form.data('id') || null;

        function showStep(step) {
            currentStep = step;
            $steps.addClass('d-none').filter('[data-step="' + step + '"]').removeClass('d-none');
            $stepBtns.removeClass('active').filter('[data-step="' + step + '"]').addClass('active');
            $prevBtn.toggleClass('d-none', step === 1);
            $cancelBtn.toggleClass('d-none', step !== 1);
            $nextBtn.toggleClass('d-none', step === totalSteps);
            $submitBtn.toggleClass('d-none', step !== totalSteps);
        }

        function scrollToFirstInvalid() {
            var $firstInvalid = $form.find('.is-invalid:visible').first();
            if ($firstInvalid.length) {
                $('html, body').animate({
                    scrollTop: $firstInvalid.offset().top - 100
                }, 300);
            }
        }

        function clearFieldError($field) {
            $field.removeClass('is-invalid');

            if ($field.hasClass('tomselected')) {
                $field.next('.ts-wrapper').removeClass('is-invalid');
            }

            var $container = $field.closest('.col-12, .col-md-4, .col-md-6, .col-md-12');
            $container.find('.invalid-feedback.ajax-error').remove();
            $container.find('.invalid-feedback').not('.ajax-error').text('');
        }

        function setFieldError($field, message) {
            if (!$field.length) {
                return;
            }

            $field.addClass('is-invalid');

            if ($field.hasClass('tomselected')) {
                $field.next('.ts-wrapper').addClass('is-invalid');
            }

            var $container = $field.closest('.col-12, .col-md-4, .col-md-6, .col-md-12');
            var $feedback = $container.find('.invalid-feedback').first();

            if ($feedback.length) {
                $feedback.addClass('d-block ajax-error').text(message);
                return;
            }

            $('<div class="invalid-feedback d-block ajax-error"></div>')
                .text(message)
                .insertAfter($field);
        }

        function goToFieldStep($field) {
            var $step = $field.closest('.customer-form-step');
            var stepNumber = Number($step.data('step'));

            if (stepNumber && stepNumber !== currentStep) {
                showStep(stepNumber);
            }
        }

        function validateCurrentStep() {
            var $currentStep = $steps.filter('[data-step="' + currentStep + '"]');
            var valid = true;

            // Validate only visible elements in the current step
            $currentStep.find('input, select, textarea').each(function () {
                var $element = $(this);
                // Exclude elements that shouldn't be validated directly like TomSelect wrappers
                if ($element.is(':visible') || $element.hasClass('tomselected')) {
                    // jquery-validate might throw if element has no name or we validate buttons
                    if ($element.attr('name') && !$element.is(':button, :submit, :reset')) {
                        if (!validator.element(this)) {
                            valid = false;
                        }
                    }
                }
            });

            return valid;
        }

        function checkDuplicateEmailPhone(email, phone, callback) {
            if (!email && !phone) {
                return callback(false);
            }

            var $emailField = $form.find('[name="email"]');
            var $phoneField = $form.find('[name="phone"]');
            clearFieldError($emailField);
            clearFieldError($phoneField);

            function applyErrors(customers) {
                var filteredCustomers = (customers || []).filter(function (customer) {
                    return !(currentCustomerId && customer.id == currentCustomerId);
                });

                var duplicateEmails = filteredCustomers.filter(function (customer) {
                    return email && customer.email && customer.email.toLowerCase() === email.toLowerCase();
                });
                var duplicatePhones = filteredCustomers.filter(function (customer) {
                    return phone && customer.phone && customer.phone === phone;
                });

                if (duplicateEmails.length) {
                    setFieldError($emailField, 'The email has already been taken.');
                }
                if (duplicatePhones.length) {
                    setFieldError($phoneField, 'The phone has already been taken.');
                }

                return duplicateEmails.length || duplicatePhones.length;
            }

            function searchQuery(query, done) {
                $.ajax({
                    url: '/api/customers/search',
                    type: 'GET',
                    cache: false,
                    headers: authHeaders({}),
                    data: {
                        q: query,
                        exclude_id: currentCustomerId
                    },
                    success: function (res) {
                        done(res || []);
                    },
                    error: function () {
                        done([]);
                    }
                });
            }

            if (email) {
                searchQuery(email, function (customers) {
                    if (applyErrors(customers)) {
                        return callback(true);
                    }
                    if (phone) {
                        searchQuery(phone, function (phoneCustomers) {
                            callback(applyErrors(phoneCustomers));
                        });
                        return;
                    }
                    callback(false);
                });
                return;
            }

            searchQuery(phone, function (customers) {
                callback(applyErrors(customers));
            });
        }

        $.validator.addMethod('strictEmail', function(value, element) {
            return this.optional(element) || /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i.test(value);
        }, 'Please enter a valid email address.');

        var validator = $form.validate({
            ignore: ':hidden',
            onkeyup: false,
            onfocusout: false,
            onclick: false,
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            errorElement: 'div',
            rules: {
                name: { required: true, maxlength: 255 },
                email: { strictEmail: true, maxlength: 255 },
                phone: { required: true, maxlength: 50 },
                whatsapp: { maxlength: 50 },
                company_name: { maxlength: 255 },
                website: { maxlength: 255 },
                tax_number: { maxlength: 100 },
                type: { maxlength: 100 },
                country_id: { digits: true },
                city_id: { digits: true },
                dob: { date: true },
                anniversary_date: { date: true }
            },
            messages: {
                name: { required: 'Please enter customer name.' },
                phone: {
                    required: 'Please enter phone number.',
                    maxlength: 'Phone number must not be greater than 50 characters.'
                },
                email: { strictEmail: 'Please enter a valid email address.' }
            },
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback d-block');
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        });

        $nextBtn.on('click', function (e) {
            e.preventDefault();
            showStep(Math.min(currentStep + 1, totalSteps));
        });

        $prevBtn.on('click', function () {
            showStep(Math.max(currentStep - 1, 1));
        });

        $stepBtns.on('click', function () {
            var targetStep = Number($(this).data('step'));
            showStep(targetStep);
        });

        showStep(1);
    }

        // Submit
        $('body').on('submit', '#customerForm', function (e) {
        e.preventDefault();
        var $form  = $(this);

        if (!$form.valid()) {
            var $firstInvalid = $form.find('.is-invalid:visible').length ? $form.find('.is-invalid:visible').first() : $form.find('.is-invalid').first();
            if ($firstInvalid.length) {
                goToFieldStep($firstInvalid);
                $('html, body').animate({
                    scrollTop: $firstInvalid.offset().top - 100
                }, 300);
            }
            return;
        }

        var id     = $form.data('id');
        var apiUrl = id ? '/api/customers/' + id : '/api/customers';
        var $btn   = $form.find('button[type="submit"]');
        var orig   = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.ts-wrapper.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        $form.find('.invalid-feedback').not('.ajax-error').text('');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        var formData = new FormData(this);
        if (id) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: authHeaders({
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            }),
            success: function (res) {
                if (window.showAlert) window.showAlert('success', res.message || 'Customer saved successfully.');
                setTimeout(function () { window.location.href = '/masters/customers'; }, 300);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(orig);
                
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstField = null;

                    $.each(xhr.responseJSON.errors, function (fieldName, msgs) {
                        var $field = $form.find('[name="' + fieldName + '"]');
                        if ($field.length) {
                            setFieldError($field, msgs[0]);

                            if (!firstField) {
                                firstField = $field;
                            }
                        }
                    });

                    if (firstField) {
                        goToFieldStep(firstField);
                        $('html, body').animate({
                            scrollTop: firstField.offset().top - 100
                        }, 300);
                    }
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.';
                    if (window.showAlert) {
                        window.showAlert('error', msg);
                    } else {
                        window.crmAlert(msg);
                    }
                }
            }
        });
    });

    // clear inline errors on input
    $('body').on('input change', '#customerForm input, #customerForm textarea, #customerForm select', function () {
        var $field = $(this);
        $field.removeClass('is-invalid');
        if ($field.hasClass('tomselected')) {
            $field.next('.ts-wrapper').removeClass('is-invalid');
        }
        var $container = $field.closest('.col-12, .col-md-4, .col-md-6, .col-md-12');
        $container.find('.invalid-feedback.ajax-error').remove();
        $container.find('.invalid-feedback').not('.ajax-error').text('');
    });
});
