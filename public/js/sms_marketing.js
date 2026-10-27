(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    const config = window.SmsMarketingConfig || {};
    const authHeaders = function (extraHeaders) {
        if (typeof window.crmApplyAuthHeaders === 'function') {
            return window.crmApplyAuthHeaders(extraHeaders || {});
        }

        return extraHeaders || {};
    };

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function debounce(callback, delay) {
        let timer;

        return function () {
            const context = this;
            const args = arguments;

            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, delay);
        };
    }

    function renderPagination(container, data, onPageChange) {
        if (!container || !data || data.total === 0) {
            if (container) {
                container.innerHTML = '';
            }
            return;
        }

        const from = data.from || 0;
        const to = data.to || 0;
        const total = data.total || 0;
        const currentPage = data.current_page || 1;
        const lastPage = data.last_page || 1;

        let html = '\
            <div class="crm-pagination-container">\
                <div class="text-muted small">Showing ' + from + ' to ' + to + ' of ' + total + ' results</div>\
                <ul class="pagination crm-pagination mb-0">';

        html += data.prev_page_url
            ? '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '">Previous</a></li>'
            : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

        for (let i = 1; i <= lastPage; i += 1) {
            if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += i === currentPage
                    ? '<li class="page-item active"><span class="page-link">' + i + '</span></li>'
                    : '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += data.next_page_url
            ? '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '">Next</a></li>'
            : '<li class="page-item disabled"><span class="page-link">Next</span></li>';

        html += '</ul></div>';
        container.innerHTML = html;

        container.querySelectorAll('.page-link[data-page]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                onPageChange(this.dataset.page);
            });
        });
    }

    function toggleDetailsRow(button, idPrefix, idValue) {
        const detailsRow = document.getElementById(idPrefix + idValue);
        const icon = button.querySelector('i');

        if (!detailsRow || !icon) {
            return;
        }

        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
            icon.classList.replace('fa-plus', 'fa-minus');
            button.classList.add('active');
            return;
        }

        detailsRow.style.display = 'none';
        icon.classList.replace('fa-minus', 'fa-plus');
        button.classList.remove('active');
    }

    function confirmDelete(options) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                type: 'DELETE',
                url: options.url,
                data: {
                    _token: config.csrfToken
                },
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                }),
                success: function (response) {
                    showToast(response.message || options.successMessage, 'success');
                    options.onSuccess();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : options.errorMessage;

                    showToast(message, 'error');
                }
            });
        });
    }

    function initIndexPage() {
        const tableBody = document.getElementById('smsTemplatesTableBody');
        const paginationContainer = document.getElementById('smsTemplatesPagination');
        const searchInput = document.getElementById('smsTemplatesSearch');
        const credentialsForm = $('#saveCredentialsForm');

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        function renderStatus(status) {
            return status === 'active'
                ? '<span class="badge crm-status-pill bg-success rounded-pill">Active</span>'
                : '<span class="badge crm-status-pill bg-secondary rounded-pill">Inactive</span>';
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = '\
                    <tr>\
                        <td colspan="5" class="text-center py-5">\
                            <div class="text-muted mb-3"><i class="bi bi-chat-square-text display-1 opacity-25"></i></div>\
                            <p class="text-muted">No SMS templates found.</p>\
                        </td>\
                    </tr>';
                return;
            }

            tableBody.innerHTML = items.map(function (template, index) {
                const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                const statusHtml = renderStatus(template.status);

                return '\
                    <tr>\
                        <td class="ps-4">' + rowNumber + '</td>\
                        <td>\
                            <div class="fw-bold small">' + escapeHtml(template.name || '--') + '</div>\
                        </td>\
                        <td class="d-none d-md-table-cell">' + statusHtml + '</td>\
                        <td class="text-end pe-4 d-none d-md-table-cell">\
                            <div class="d-inline-flex align-items-center gap-2">\
                                <a href="' + config.templateEditBaseUrl + '/' + template.id + '/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>\
                                <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="' + config.templateDeleteBaseUrl + '/' + template.id + '" title="Delete"><i class="bi bi-trash text-danger"></i></button>\
                            </div>\
                        </td>\
                        <td class="text-center d-md-none">\
                            <button type="button" class="btn-user-expand" data-template-id="' + template.id + '">\
                                <i class="fa-solid fa-plus"></i>\
                            </button>\
                        </td>\
                    </tr>\
                    <tr class="details-row d-md-none border-0" id="details-' + template.id + '" style="display: none;">\
                        <td colspan="5" class="p-0 border">\
                            <div class="details-content">\
                                <div class="row g-3">\
                                    <div class="col-12 d-flex justify-content-between align-items-center">\
                                        <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>\
                                        <div class="expand-value">' + statusHtml + '</div>\
                                    </div>\
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">\
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>\
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">\
                                            <a href="' + config.templateEditBaseUrl + '/' + template.id + '/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>\
                                            <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="' + config.templateDeleteBaseUrl + '/' + template.id + '"><i class="bi bi-trash text-danger"></i></button>\
                                        </div>\
                                    </div>\
                                </div>\
                            </div>\
                        </td>\
                    </tr>';
            }).join('');

            tableBody.querySelectorAll('.delete-template').forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDelete({
                        url: this.dataset.url,
                        successMessage: 'SMS template deleted successfully',
                        errorMessage: 'Something went wrong',
                        onSuccess: function () {
                            fetchTemplates();
                        }
                    });
                });
            });

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    toggleDetailsRow(this, 'details-', this.dataset.templateId);
                });
            });
        }

        function fetchTemplates(page) {
            const url = new URL(config.indexUrl, window.location.origin);
            url.searchParams.set('page', page || 1);
            url.searchParams.set('tab', 'templates');

            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            }

            $.ajax({
                url: url.toString(),
                type: 'GET',
                dataType: 'json',
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                }),
                beforeSend: function () {
                    tableBody.innerHTML = '\
                        <tr>\
                            <td colspan="5" class="text-center py-5">\
                                <div class="spinner-border text-primary"></div>\
                            </td>\
                        </tr>';
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(paginationContainer, response.data, fetchTemplates);
                    }
                },
                error: function () {
                    tableBody.innerHTML = '\
                        <tr>\
                            <td colspan="5" class="text-center py-5">Error loading templates</td>\
                        </tr>';
                    paginationContainer.innerHTML = '';
                }
            });
        }

        if (credentialsForm.length) {
            credentialsForm.on('submit', function (event) {
                event.preventDefault();

                const btn = $('#btnSaveCredentials');
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...');

                $.ajax({
                    url: credentialsForm.attr('action'),
                    method: 'POST',
                    data: credentialsForm.serialize(),
                    headers: authHeaders({
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }),
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Something went wrong';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }

        searchInput.addEventListener('input', debounce(function () {
            fetchTemplates(1);
        }, 400));

        fetchTemplates();
    }

    function initLogsPage() {
        const tableBody = document.getElementById('smsLogsTableBody');
        const paginationContainer = document.getElementById('smsLogsPagination');
        const searchInput = document.getElementById('smsLogsSearch');
        const sendSmsForm = $('#sendSmsForm');
        const customerSelect = $('#customer_ids');
        const sendSmsModal = $('#sendSmsModal');

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        function formatDate(value) {
            if (!value) {
                return '--';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function renderStatus(status) {
            const statusClass = status === 'sent' ? 'bg-success' : 'bg-danger';
            return '<span class="badge crm-status-pill ' + statusClass + ' rounded-pill">' + escapeHtml((status || '--').toUpperCase()) + '</span>';
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = '\
                    <tr>\
                        <td colspan="8" class="text-center py-5">\
                            <div class="text-muted mb-3"><i class="bi bi-chat-dots display-1 opacity-25"></i></div>\
                            <p class="text-muted">No SMS logs found.</p>\
                        </td>\
                    </tr>';
                return;
            }

            tableBody.innerHTML = items.map(function (log, index) {
                const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                const customerName = log.customer ? log.customer.name : '--';
                const customerPhone = log.customer_phone || '--';
                const statusHtml = renderStatus(log.status);
                const service = log.service ? escapeHtml(log.service) : '--';
                const sendDate = formatDate(log.send_date);

                return '\
                    <tr>\
                        <td class="ps-4">' + rowNumber + '</td>\
                        <td>\
                            <div class="fw-bold small">' + escapeHtml(customerName) + '</div>\
                            <div class="text-muted small">' + escapeHtml(customerPhone) + '</div>\
                        </td>\
                        <td class="d-none d-md-table-cell">' + sendDate + '</td>\
                        <td>' + escapeHtml(log.template_name || '--') + '</td>\
                        <td class="d-none d-md-table-cell">' + statusHtml + '</td>\
                        <td class="d-none d-md-table-cell text-capitalize">' + service + '</td>\
                        <td class="text-end pe-4 d-none d-md-table-cell">\
                            <button type="button" class="btn crm-action-btn btn-sm text-danger delete-log" data-url="' + config.logDeleteBaseUrl + '/' + log.id + '" title="Delete"><i class="bi bi-trash text-danger"></i></button>\
                        </td>\
                        <td class="text-center d-md-none">\
                            <button type="button" class="btn-user-expand" data-log-id="' + log.id + '">\
                                <i class="fa-solid fa-plus"></i>\
                            </button>\
                        </td>\
                    </tr>\
                    <tr class="details-row d-md-none border-0" id="details-' + log.id + '" style="display: none;">\
                        <td colspan="8" class="p-0 border">\
                            <div class="details-content">\
                                <div class="row g-3">\
                                    <div class="col-12 d-flex justify-content-between align-items-center">\
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Send Date :</div>\
                                        <div class="expand-value">' + sendDate + '</div>\
                                    </div>\
                                    <div class="col-12 d-flex justify-content-between align-items-center">\
                                        <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>\
                                        <div class="expand-value">' + statusHtml + '</div>\
                                    </div>\
                                    <div class="col-12 d-flex justify-content-between align-items-center">\
                                        <div class="expand-label"><i class="fa-solid fa-tower-broadcast"></i> Service :</div>\
                                        <div class="expand-value text-capitalize">' + service + '</div>\
                                    </div>\
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">\
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>\
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">\
                                            <button type="button" class="btn crm-action-btn btn-sm text-danger delete-log" data-url="' + config.logDeleteBaseUrl + '/' + log.id + '"><i class="bi bi-trash text-danger"></i></button>\
                                        </div>\
                                    </div>\
                                </div>\
                            </div>\
                        </td>\
                    </tr>';
            }).join('');

            tableBody.querySelectorAll('.delete-log').forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDelete({
                        url: this.dataset.url,
                        successMessage: 'SMS log deleted successfully',
                        errorMessage: 'Error deleting SMS log',
                        onSuccess: function () {
                            fetchLogs();
                        }
                    });
                });
            });

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    toggleDetailsRow(this, 'details-', this.dataset.logId);
                });
            });
        }

        function fetchLogs(page) {
            const url = new URL(config.logsUrl, window.location.origin);
            url.searchParams.set('page', page || 1);

            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            }

            $.ajax({
                url: url.toString(),
                type: 'GET',
                dataType: 'json',
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                }),
                beforeSend: function () {
                    tableBody.innerHTML = '\
                        <tr>\
                            <td colspan="8" class="text-center py-5">\
                                <div class="spinner-border text-primary"></div>\
                            </td>\
                        </tr>';
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(paginationContainer, response.data, fetchLogs);
                    }
                },
                error: function () {
                    tableBody.innerHTML = '\
                        <tr>\
                            <td colspan="8" class="text-center py-5">Error loading SMS logs</td>\
                        </tr>';
                    paginationContainer.innerHTML = '';
                }
            });
        }

        if (customerSelect.length && $.fn.select2) {
            customerSelect.select2({
                theme: 'bootstrap-5',
                placeholder: '--Select-Customers--',
                allowClear: true,
                dropdownParent: sendSmsModal
            });
        }

        if (sendSmsForm.length) {
            sendSmsForm.on('submit', function (event) {
                event.preventDefault();

                const btn = $('#btnSendSms');
                const originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Sending...');

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                $.ajax({
                    url: config.sendSmsUrl,
                    method: 'POST',
                    data: sendSmsForm.serialize(),
                    headers: authHeaders({
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }),
                    success: function (response) {
                        showToast(response.message || 'SMS sending completed.', 'success');
                        sendSmsForm[0].reset();
                        customerSelect.val(null).trigger('change');
                        sendSmsModal.modal('hide');
                        fetchLogs();
                    },
                    error: function (error) {
                        if (error.status === 422) {
                            $.each(error.responseJSON.errors, function (key, value) {
                                let input = $('#' + key);

                                if (input.length === 0) {
                                    input = $('[name="' + key + '"]');
                                }

                                input.addClass('is-invalid');
                                input.parent().append('<div class="invalid-feedback">' + value[0] + '</div>');
                            });
                            return;
                        }

                        const message = error.responseJSON && error.responseJSON.message
                            ? error.responseJSON.message
                            : 'Something went wrong';

                        showToast(message, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            sendSmsModal.on('hidden.bs.modal', function () {
                sendSmsForm[0].reset();
                customerSelect.val(null).trigger('change');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
            });
        }

        searchInput.addEventListener('input', debounce(function () {
            fetchLogs(1);
        }, 400));

        fetchLogs();
    }

    function initTemplateFormPage() {
        const form = $('#templateForm');

        if (!form.length) {
            return;
        }

        form.on('submit', function (event) {
            event.preventDefault();

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const btn = $('#btnSubmit');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> ' + (config.submitLoadingText || 'Saving...'));

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }),
                success: function (response) {
                    showAlert('success', response.message, 'success');
                    window.location.href = config.redirectUrl;
                },
                error: function (error) {
                    if (error.status !== 422 || !error.responseJSON || !error.responseJSON.errors) {
                        return;
                    }

                    $.each(error.responseJSON.errors, function (key, value) {
                        const input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    $(function () {
        if (config.page === 'index') {
            initIndexPage();
            return;
        }

        if (config.page === 'logs') {
            initLogsPage();
            return;
        }

        if (config.page === 'template-form') {
            initTemplateFormPage();
        }
    });
}(window, document, window.jQuery));
