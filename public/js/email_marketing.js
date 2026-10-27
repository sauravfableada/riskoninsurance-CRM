(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    const config = window.EmailMarketingConfig || {};
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

    function toggleDetailsRow(button, id) {
        const detailsRow = document.getElementById('details-' + id);
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

    function initIndexPage() {
        const tableBody = document.getElementById('templatesTableBody');
        const paginationContainer = document.getElementById('templatesPagination');
        const searchInput = document.getElementById('templatesSearch');

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        function renderStatus(status) {
            return status === 'active'
                ? '<span class="badge crm-status-pill bg-success rounded-pill">Active</span>'
                : '<span class="badge crm-status-pill bg-secondary rounded-pill">Inactive</span>';
        }

        function deleteTemplate(url) {
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
                    url: url,
                    data: {
                        _token: config.csrfToken
                    },
                    headers: authHeaders({
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json'
                    }),
                    success: function (response) {
                        showToast(response.message || 'Template deleted successfully', 'success');
                        fetchTemplates();
                    },
                    error: function () {
                        showToast('Error deleting template', 'error');
                    }
                });
            });
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = '\
                    <tr>\
                        <td colspan="5" class="text-center py-5">\
                            <div class="text-muted mb-3"><i class="bi bi-file-earmark-richtext display-1 opacity-25"></i></div>\
                            <p class="text-muted">No templates found.</p>\
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
                                <a href="' + config.templatesBaseUrl + '/' + template.id + '/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>\
                                <a href="' + config.templatesBaseUrl + '/' + template.id + '" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>\
                                <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="' + config.templatesBaseUrl + '/' + template.id + '" title="Delete"><i class="bi bi-trash text-danger"></i></button>\
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
                                </div>\
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">\
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>\
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">\
                                        <a href="' + config.templatesBaseUrl + '/' + template.id + '/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>\
                                        <a href="' + config.templatesBaseUrl + '/' + template.id + '" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>\
                                        <button type="button" class="btn crm-action-btn btn-sm text-danger delete-template" data-url="' + config.templatesBaseUrl + '/' + template.id + '"><i class="bi bi-trash text-danger"></i></button>\
                                    </div>\
                                </div>\
                            </div>\
                        </td>\
                    </tr>';
            }).join('');

            tableBody.querySelectorAll('.delete-template').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteTemplate(this.dataset.url);
                });
            });

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    toggleDetailsRow(this, this.dataset.templateId);
                });
            });
        }

        function fetchTemplates(page) {
            const url = new URL(config.indexUrl, window.location.origin);
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

        searchInput.addEventListener('input', debounce(function () {
            fetchTemplates(1);
        }, 400));

        fetchTemplates();
    }

    function initFormPage() {
        const carousel = $('#carouselExampleControlsNoTouching');
        const form = $('#marketingTemplateForm');

        if (!carousel.length || !form.length) {
            return;
        }

        function syncSelectedTemplate() {
            const activeItem = carousel.find('.carousel-item.active');
            $('.template-card.selected').removeClass('selected');
            activeItem.find('.template-card').addClass('selected');
        }

        syncSelectedTemplate();

        carousel.on('slid.bs.carousel', function () {
            syncSelectedTemplate();
        });

        $('.template-card').on('click', function () {
            const item = $(this).closest('.carousel-item');
            const itemIndex = item.index();
            const carouselInstance = bootstrap.Carousel.getOrCreateInstance(document.getElementById('carouselExampleControlsNoTouching'));
            carouselInstance.to(itemIndex);
            syncSelectedTemplate();
        });

        form.on('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(this);

            syncSelectedTemplate();
            const templateName = $('.carousel-item.active').data('template-name')
                || $('.template-card.selected').closest('.carousel-item').data('template-name');

            formData.append('template_name', templateName);

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            $.ajax({
                type: 'POST',
                url: config.submitUrl,
                data: formData,
                processData: false,
                contentType: false,
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
                    if (error.status === 422) {
                        $.each(error.responseJSON.errors, function (key, value) {
                            const input = $('[name="' + key + '"]');
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    }
                }
            });
        });
    }

    $(function () {
        if (config.page === 'index') {
            initIndexPage();
            return;
        }

        if (config.page === 'form') {
            initFormPage();
        }
    });
}(window, document, window.jQuery));
