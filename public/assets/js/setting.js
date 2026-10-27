var waTemplatesCurrentPage = 1;

function notifySettings(message, type) {
    if (window.toastr && typeof window.toastr[type] === 'function') {
        window.toastr[type](message);
        return;
    }

    if (typeof window.showAlert === 'function') {
        window.showAlert(type, message);
        return;
    }

    window.crmAlert(message);
}

$(document).ready(function () {
    // when WhatsApp tab is shown, load existing config
    $('button[data-bs-target="#whatsapp-configure"]').on('shown.bs.tab', function () {
        loadWhatsappConfig();
    });

    // save button
    $('#wa_save_btn').on('click', function () {
        saveWhatsappConfig();
    });

    // refresh WhatsApp templates
    $('#wa_templates_refresh').on('click', function () {
        refreshWhatsappTemplates();
    });

    // change handler for "Use For Module" selects
    $('.wa-template-module-select').on('change', function () {
        const templateId = $(this).data('template-id');
        const value = $(this).val();
        $('.wa-template-module-select[data-template-id="' + templateId + '"]').val(value);
        updateTemplateModule(templateId, value);
    });

    // change handler for Active/Inactive selects
    $('.wa-template-status-select').on('change', function () {
        const templateId = $(this).data('template-id');
        const value = $(this).val();
        $('.wa-template-status-select[data-template-id="' + templateId + '"]').val(value);
        updateTemplateStatus(templateId, value);
    });

    // ensure module options are unique across templates
    refreshModuleSelectOptions();
    bindTemplateExpandButtons();

    // render initial table pagination/state on first load
    applyTemplateTableFilters();

    // data table-like filtering & page size
    $('#wa_templates_search').on('input', function () {
        waTemplatesCurrentPage = 1;
        applyTemplateTableFilters();
    });
    $('#wa_templates_show').on('change', function () {
        waTemplatesCurrentPage = 1;
        applyTemplateTableFilters();
    });

    // pagination click handler
    $('#wa_templates_pagination').on('click', '.page-link[data-page]', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'), 10);
        if (!isNaN(page)) {
            waTemplatesCurrentPage = page;
            applyTemplateTableFilters();
        }
    });
});

function loadWhatsappConfig() {
    $('#wa_status_msg').text('Loading...').removeClass('text-success text-danger');

    $.ajax({
        url: '/whatsapp-config',
        method: 'GET',
        success: function (res) {
            if (res) {
                $('#wa_app_id').val(res.app_id || '');
                $('#wa_app_secret').val(res.app_secret || '');
                $('#wa_phone_number_id').val(res.phone_number_id || '');
                $('#wa_business_account_id').val(res.business_account_id || '');
                $('#wa_access_token').val(res.access_token || '');
                $('#wa_webhook_url').val(res.webhook_url || '');
            }
            $('#wa_status_msg').text('Loaded').addClass('text-success');
        },
        error: function () {
            $('#wa_status_msg').text('Failed to load').addClass('text-danger');
        }
    });
}

function saveWhatsappConfig() {
    $('#wa_status_msg').text('Saving...').removeClass('text-success text-danger');

    // clear previous validation state
    $('#wa_app_id, #wa_app_secret, #wa_phone_number_id, #wa_business_account_id, #wa_access_token')
        .removeClass('is-invalid');
    $('#wa_app_id_error, #wa_app_secret_error, #wa_phone_number_id_error, #wa_business_account_id_error, #wa_access_token_error')
        .text('');

    $.ajax({
        url: '/whatsapp-config',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            app_id: $('#wa_app_id').val(),
            app_secret: $('#wa_app_secret').val(),
            phone_number_id: $('#wa_phone_number_id').val(),
            business_account_id: $('#wa_business_account_id').val(),
            access_token: $('#wa_access_token').val(),
            webhook_url: $('#wa_webhook_url').val()
        },
        success: function () {
            $('#wa_status_msg').text('Saved successfully').addClass('text-success');
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;

                if (errors.app_id) {
                    $('#wa_app_id').addClass('is-invalid');
                    $('#wa_app_id_error').text(errors.app_id[0]);
                }
                if (errors.app_secret) {
                    $('#wa_app_secret').addClass('is-invalid');
                    $('#wa_app_secret_error').text(errors.app_secret[0]);
                }
                if (errors.phone_number_id) {
                    $('#wa_phone_number_id').addClass('is-invalid');
                    $('#wa_phone_number_id_error').text(errors.phone_number_id[0]);
                }
                if (errors.business_account_id) {
                    $('#wa_business_account_id').addClass('is-invalid');
                    $('#wa_business_account_id_error').text(errors.business_account_id[0]);
                }
                if (errors.access_token) {
                    $('#wa_access_token').addClass('is-invalid');
                    $('#wa_access_token_error').text(errors.access_token[0]);
                }

                $('#wa_status_msg').text('Please fix the highlighted fields.').addClass('text-danger');
            } else {
                $('#wa_status_msg').text('Save failed').addClass('text-danger');
            }
        }
    });
}

function refreshWhatsappTemplates() {
    var $btn = $('#wa_templates_refresh');
    var originalHtml = $btn.html();

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Refreshing...');

    $.ajax({
        url: '/whatsapp-templates/refresh',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (!response || !response.templates) {
                $btn.prop('disabled', false).html(originalHtml);
                return;
            }

            var templates = response.templates;
            var $table = $('#wa_templates_table');
            var moduleOptions = $table.data('module-options') || {};
            var $tbody = $table.find('tbody');
            $tbody.empty();

            if (templates.length === 0) {
                $tbody.append(
                    '<tr><td colspan="5" class="text-center text-muted py-4">No WhatsApp message templates found in database.</td></tr>'
                );
            } else {
                templates.forEach(function (tpl) {
                    var moduleSelect = '<select class="form-select form-select-sm wa-template-module-select" data-template-id="' + tpl.id + '">';
                    moduleSelect += '<option value="">— Select —</option>';
                    Object.keys(moduleOptions).forEach(function (key) {
                        var selected = tpl.use_for_module === key ? ' selected' : '';
                        moduleSelect += '<option value="' + key + '"' + selected + '>' + moduleOptions[key] + '</option>';
                    });
                    moduleSelect += '</select>';

                    var statusBadge = '<span class="wa-templates-status-badge">'
                        + (tpl.status || '') + '</span>';

                    var activeSelect = '<select class="form-select form-select-sm wa-template-status-select" data-template-id="' + tpl.id + '">';
                    activeSelect += '<option value="1"' + (tpl.is_active ? ' selected' : '') + '>Active</option>';
                    activeSelect += '<option value="0"' + (!tpl.is_active ? ' selected' : '') + '>Inactive</option>';
                    activeSelect += '</select>';

                    var mainRow = '<tr data-template-row="main" data-template-id="' + tpl.id + '">' +
                        '<td class="wa-templates-name">' + tpl.name + '</td>' +
                        '<td class="d-none d-md-table-cell">' + moduleSelect + '</td>' +
                        '<td class="d-none d-md-table-cell">' + statusBadge + '</td>' +
                        '<td class="d-none d-md-table-cell">' + activeSelect + '</td>' +
                        '<td class="text-center d-md-none">' +
                        '<button type="button" class="btn-user-expand" data-template-id="' + tpl.id + '">' +
                        '<i class="fa-solid fa-plus"></i>' +
                        '</button>' +
                        '</td>' +
                        '</tr>';

                    var detailsRow = '<tr class="details-row d-md-none border-0" data-template-row="details" id="wa-template-details-' + tpl.id + '" style="display: none;">' +
                        '<td colspan="5" class="p-0">' +
                        '<div class="details-content">' +
                        '<div class="row g-3">' +
                        '<div class="col-12 d-flex justify-content-between align-items-center">' +
                        '<div class="expand-label"><i class="fa-solid fa-puzzle-piece"></i> Use For Module :</div>' +
                        '<div class="expand-value">' + moduleSelect + '</div>' +
                        '</div>' +
                        '<div class="col-12 d-flex justify-content-between align-items-center">' +
                        '<div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>' +
                        '<div class="expand-value">' + statusBadge + '</div>' +
                        '</div>' +
                        '<div class="col-12 d-flex justify-content-between align-items-center">' +
                        '<div class="expand-label"><i class="fa-solid fa-toggle-on"></i> Active / Inactive :</div>' +
                        '<div class="expand-value">' + activeSelect + '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</td>' +
                        '</tr>';

                    $tbody.append(mainRow + detailsRow);
                });

                // re-bind change handlers for new elements
                $('.wa-template-module-select').off('change').on('change', function () {
                    const templateId = $(this).data('template-id');
                    const value = $(this).val();
                    $('.wa-template-module-select[data-template-id="' + templateId + '"]').val(value);
                    updateTemplateModule(templateId, value);
                });

                $('.wa-template-status-select').off('change').on('change', function () {
                    const templateId = $(this).data('template-id');
                    const value = $(this).val();
                    $('.wa-template-status-select[data-template-id="' + templateId + '"]').val(value);
                    updateTemplateStatus(templateId, value);
                });

                refreshModuleSelectOptions();
                bindTemplateExpandButtons();
                applyTemplateTableFilters();
            }

            $btn.prop('disabled', false).html(originalHtml);

            if (response.warning) {
                notifySettings(response.message || 'Showing cached WhatsApp templates from database.', 'warning');
            } else {
                notifySettings(response.message || 'WhatsApp templates refreshed successfully.', 'success');
            }
        },
        error: function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Failed to refresh templates.';

            notifySettings(msg, 'error');
            $btn.prop('disabled', false).html(originalHtml);
        }
    });
}

function bindTemplateExpandButtons() {
    $('#wa_templates_table').find('.btn-user-expand').off('click').on('click', function () {
        var templateId = $(this).data('template-id');
        var $detailsRow = $('#wa-template-details-' + templateId);
        var $icon = $(this).find('i');

        if ($detailsRow.is(':visible')) {
            $detailsRow.hide();
            $icon.removeClass('fa-minus').addClass('fa-plus');
            $(this).removeClass('active');
        } else {
            $detailsRow.show();
            $icon.removeClass('fa-plus').addClass('fa-minus');
            $(this).addClass('active');
        }
    });
}

function updateTemplateModule(templateId, value) {
    $.ajax({
        url: '/whatsapp-templates/' + templateId + '/module',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            use_for_module: value
        },
        success: function () {
            refreshModuleSelectOptions();
        },
        error: function () {
            notifySettings('Failed to update module mapping.', 'error');
        }
    });
}

function refreshModuleSelectOptions() {
    const selectedValues = [];
    const seenTemplateIds = new Set();

    $('.wa-template-module-select').each(function () {
        const templateId = $(this).data('template-id');
        const current = $(this).val();
        if (templateId && seenTemplateIds.has(templateId)) {
            return;
        }
        if (templateId) {
            seenTemplateIds.add(templateId);
        }
        if (current) {
            selectedValues.push(current);
        }
    });

    $('.wa-template-module-select').each(function () {
        const current = $(this).val();
        $(this).find('option').each(function () {
            const v = $(this).val();
            if (!v) return;

            if (v === current) {
                $(this).prop('disabled', false);
            } else {
                $(this).prop('disabled', selectedValues.includes(v));
            }
        });
    });
}

function applyTemplateTableFilters() {
    var $table = $('#wa_templates_table');
    if ($table.length === 0) return;

    var search = ($('#wa_templates_search').val() || '').toString().toLowerCase();
    var limit = parseInt($('#wa_templates_show').val() || '10', 10);
    if (isNaN(limit) || limit <= 0) limit = 10;

    var $tbody = $table.find('tbody');
    var $rows = $tbody.find('tr');
    if ($rows.length === 0) return;

    var matching = [];
    var $mainRows = $tbody.find('tr[data-template-row="main"]');
    var $noDataRow = $rows.filter(function () {
        return $(this).find('td').length === 1;
    }).first();

    $rows.hide();

    $mainRows.each(function () {
        var $row = $(this);
        var templateId = $row.data('template-id');
        var $detailsRow = $('#wa-template-details-' + templateId);
        var text = ($row.text() + ' ' + $detailsRow.text()).toLowerCase();
        if (!search || text.indexOf(search) !== -1) {
            matching.push({
                main: $row,
                details: $detailsRow
            });
        }
    });

    // show only up to limit of matching rows
    var total = matching.length;
    var totalPages = Math.max(1, Math.ceil(total / limit));
    if (waTemplatesCurrentPage > totalPages) waTemplatesCurrentPage = totalPages;
    if (waTemplatesCurrentPage < 1) waTemplatesCurrentPage = 1;

    var start = (waTemplatesCurrentPage - 1) * limit;
    var end = start + limit;

    matching.forEach(function (rowPair, idx) {
        if (idx >= start && idx < end) {
            rowPair.main.show();
        } else {
            rowPair.main.hide();
            rowPair.details.hide();
            rowPair.main.find('.btn-user-expand').removeClass('active').find('i').removeClass('fa-minus').addClass('fa-plus');
        }
    });

    // if nothing matches, show "no data" message row if exists
    var $pagination = $('#wa_templates_pagination');
    if (total === 0) {
        if ($noDataRow.length) {
            $noDataRow.show();
        }
        if ($pagination.length) {
            $pagination.html('');
        }
        return;
    }

    // build pagination UI
    if ($pagination.length) {
        var showingFrom = start + 1;
        var showingTo = Math.min(end, total);

        var infoHtml = '<div class="text-muted small">Showing ' + showingFrom + ' to ' + showingTo + ' of ' + total + ' entries</div>';

        var ul = '<ul class="pagination crm-pagination mb-0">';
        var prevDisabled = waTemplatesCurrentPage === 1 ? ' disabled' : '';
        ul += '<li class="page-item' + prevDisabled + '"><a class="page-link" href="#" data-page="' + (waTemplatesCurrentPage - 1) + '">Previous</a></li>';

        for (var p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= waTemplatesCurrentPage - 2 && p <= waTemplatesCurrentPage + 2)) {
                var active = p === waTemplatesCurrentPage ? ' active' : '';
                ul += '<li class="page-item' + active + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
            } else if (p === waTemplatesCurrentPage - 3 || p === waTemplatesCurrentPage + 3) {
                ul += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        var nextDisabled = waTemplatesCurrentPage === totalPages ? ' disabled' : '';
        ul += '<li class="page-item' + nextDisabled + '"><a class="page-link" href="#" data-page="' + (waTemplatesCurrentPage + 1) + '">Next</a></li>';
        ul += '</ul>';

        $pagination.html('<div class="crm-pagination-container">' + infoHtml + '<div>' + ul + '</div></div>');
    }
}

function updateTemplateStatus(templateId, value) {
    $.ajax({
        url: '/whatsapp-templates/' + templateId + '/status',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            is_active: value
        },
        success: function () {
            notifySettings('Template status updated.', 'success');
        },
        error: function () {
            notifySettings('Failed to update active status.', 'error');
        }
    });
}
