$(document).ready(function () {
    loadProductCategories();

    $('#add-product-category-btn').on('click', function () {
        $('#product-category-form')[0].reset();
        $('#product-category-id').val('');
        $('#product-category-name').removeClass('is-valid is-invalid');
        $('#product-category-name-error').text('');
        $('#productCategoryModalLabel').text('Add Product Category');
        $('#save-product-category-btn').text('Save');
        var modal = new bootstrap.Modal(document.getElementById('productCategoryModal'));
        modal.show();
    });

    $('#save-product-category-btn').on('click', function () {
        saveProductCategory();
    });

    $(document).on('click', '.edit-product-category-btn', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var description = $(this).data('description');

        $('#product-category-id').val(id);
        $('#product-category-name').val(name).removeClass('is-valid is-invalid');
        $('#product-category-description').val(description || '');
        $('#product-category-name-error').text('');
        $('#productCategoryModalLabel').text('Edit Product Category');
        $('#save-product-category-btn').text('Update');

        var modal = new bootstrap.Modal(document.getElementById('productCategoryModal'));
        modal.show();
    });

    $(document).on('click', '.delete-product-category-btn', function () {
        var id = $(this).data('id');
        var url = $(this).data('url');
        window.showDeleteConfirm('Delete this product category?').then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            deleteProductCategory(id, url);
        });
    });

    $(document).on('change', '.product-category-status-switch', function () {
        var id = $(this).data('id');
        var isActive = $(this).is(':checked');
        toggleProductCategoryStatus(id, isActive, this);
    });
});

function loadProductCategories() {
    $.ajax({
        url: '/masters/product-categories',
        type: 'GET',
        dataType: 'json',
        success: function (categories) {
            var $tbody = $('.table.table-hover tbody');
            $tbody.empty();

            if (!categories || categories.length === 0) {
                $tbody.append(
                    '<tr><td colspan="4" class="text-center text-muted small">No product categories added yet.</td></tr>'
                );
                return;
            }

            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            categories.forEach(function (category) {
                var deleteUrl = '/masters/product-categories/' + category.id;

                var statusHtml =
                    '<div class="form-check form-switch d-inline-flex align-items-center mb-0">' +
                    '<input class="form-check-input product-category-status-switch" type="checkbox" role="switch" ' +
                    'data-id="' + category.id + '" ' + (category.is_active ? 'checked' : '') + '>' +
                    '<span class="ms-2 badge ' + (category.is_active ? 'bg-success' : 'bg-secondary') + '">' +
                    (category.is_active ? 'Active' : 'Inactive') +
                    '</span>' +
                    '</div>';

                var actionsHtml =
                    '<button type="button" class="btn btn-sm crm-action-btn me-1 edit-product-category-btn" ' +
                    'data-id="' + category.id + '" ' +
                    'data-name="' + (category.name || '') + '" ' +
                    'data-description="' + (category.description || '') + '"' +
                    '>Edit</button>' +
                    '<button type="button" class="btn btn-sm crm-action-btn delete-product-category-btn" ' +
                    'data-id="' + category.id + '" ' +
                    'data-url="' + deleteUrl + '"' +
                    '>Delete</button>';

                var rowHtml =
                    '<tr>' +
                    '<td>' + (category.name || '') + '</td>' +
                    '<td>' + (category.description || '') + '</td>' +
                    '<td>' + statusHtml + '</td>' +
                    '<td class="text-end">' + actionsHtml + '</td>' +
                    '</tr>';

                $tbody.append(rowHtml);
            });
        },
        error: function () {
            var $tbody = $('.table.table-hover tbody');
            $tbody.empty();
            $tbody.append(
                '<tr><td colspan="4" class="text-center text-danger small">Failed to load product categories.</td></tr>'
            );
        }
    });
}

function saveProductCategory() {
    var $nameInput = $('#product-category-name');
    var $nameError = $('#product-category-name-error');
    var $descriptionInput = $('#product-category-description');
    var id = $('#product-category-id').val();
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Clear previous validation state
    $nameInput.removeClass('is-valid is-invalid');
    $nameError.text('');

    var url = '/masters/product-categories';
    var method = 'POST';

    if (id) {
        url = '/masters/product-categories/' + id;
        method = 'PUT';
    }

    $.ajax({
        url: url,
        type: method,
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            name: $nameInput.val(),
            description: $descriptionInput.val()
        },
        success: function (response) {
            if (response.success) {
                $nameInput.addClass('is-valid');

                if (typeof showToast === 'function') {
                    var msg = response.message;
                    if (!msg) {
                        msg = id
                            ? 'Product category updated successfully.'
                            : 'Product category created successfully.';
                    }
                    showToast(msg, 'success');
                }

                // Hide modal after short delay and reload table
                setTimeout(function () {
                    var modalEl = document.getElementById('productCategoryModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    loadProductCategories();
                }, 400);
            }
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;

                if (errors.name && errors.name.length) {
                    $nameInput.addClass('is-invalid');
                    $nameError.text(errors.name[0]);
                } else {
                    $nameInput.addClass('is-valid');
                }
            }
        }
    });
}

function deleteProductCategory(id, url) {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            _method: 'DELETE'
        },
        success: function () {
            if (typeof showToast === 'function') {
                showToast('Product category deleted successfully.', 'success');
            }
            loadProductCategories();
        },
        error: function () {
            if (typeof showToast === 'function') {
                showToast('Failed to delete product category.', 'error');
            }
        }
    });
}

function toggleProductCategoryStatus(id, isActive, checkboxEl) {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: '/masters/product-categories/' + id + '/toggle-status',
        type: 'PATCH',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            is_active: isActive ? 1 : 0
        },
        success: function (response) {
            if (typeof showToast === 'function') {
                showToast('Status updated successfully.', 'success');
            }

            // update badge text/color in same cell
            var $cell = $(checkboxEl).closest('td');
            var $badge = $cell.find('span.badge');
            if (isActive) {
                $badge.removeClass('bg-secondary').addClass('bg-success').text('Active');
            } else {
                $badge.removeClass('bg-success').addClass('bg-secondary').text('Inactive');
            }
        },
        error: function () {
            // revert switch state
            $(checkboxEl).prop('checked', !isActive);
            if (typeof showToast === 'function') {
                showToast('Failed to update status.', 'error');
            }
        }
    });
}
