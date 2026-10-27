$(document).ready(function () {
    loadProducts();

    $('#add-product-btn').on('click', function () {
        resetProductForm();
        $('#productModalLabel').text('Add Product');
        $('#save-product-btn').text('Save');
        var modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    });

    $('#save-product-btn').on('click', function () {
        saveProduct();
    });

    $(document).on('click', '.delete-product-btn', function () {
        var id = $(this).data('id');
        window.showDeleteConfirm('Are you sure you want to delete this product?').then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            deleteProduct(id);
        });
    });
});

function resetProductForm() {
    $('#product-form')[0].reset();
    $('#product-id').val('');
    $('#product-name, #product-price').removeClass('is-valid is-invalid');
    $('#product-name-error, #product-price-error').text('');
}

function loadProducts() {
    $.ajax({
        url: '/products',
        type: 'GET',
        dataType: 'json',
        success: function (products) {
            var $tbody = $('#products-table-body');
            $tbody.empty();

            if (!products || products.length === 0) {
                $tbody.append(
                    '<tr><td colspan="6" class="text-center py-5 text-muted">No products found.</td></tr>'
                );
                return;
            }

            products.forEach(function (product) {
                var categoryName = product.category ? product.category.name : '--';
                var createdByName = product.creator ? (product.creator.name || '—') : '—';
                var createdAt = product.created_at ? new Date(product.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

                var viewUrl = '/products/' + product.id;
                var editUrl = '/products/' + product.id + '/edit';
                var actionsHtml =
                    '<a href="' + viewUrl + '" class="btn btn-sm btn-light rounded-circle me-1" title="View">' +
                    '<i class="bi bi-eye text-secondary"></i></a>' +
                    '<a href="' + editUrl + '" class="btn btn-sm btn-light rounded-circle me-1" title="Edit">' +
                    '<i class="bi bi-pencil-square text-primary"></i></a>' +
                    '<button type="button" class="btn btn-sm btn-light rounded-circle delete-product-btn" ' +
                    'title="Delete" data-id="' + product.id + '">' +
                    '<i class="bi bi-trash text-danger text-danger"></i></button>';

                var rowHtml =
                    '<tr>' +
                    '<td class="ps-4"><div class="fw-semibold text-dark">' + product.name + '</div></td>' +
                    '<td>' + categoryName + '</td>' +
                    '<td><span class="fw-semibold">₹' + parseFloat(product.price).toFixed(2) + '</span></td>' +
                    '<td><span class="text-muted">' + createdByName + '</span></td>' +
                    '<td><span class="text-muted small">' + createdAt + '</span></td>' +
                    '<td class="pe-4 text-end">' + actionsHtml + '</td>' +
                    '</tr>';

                $tbody.append(rowHtml);
            });
        },
        error: function () {
            var $tbody = $('#products-table-body');
            $tbody.empty();
            $tbody.append(
                '<tr><td colspan="6" class="text-center text-danger small">Failed to load products.</td></tr>'
            );
        }
    });
}

function saveProduct() {
    var id = $('#product-id').val();
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    var $nameInput = $('#product-name');
    var $priceInput = $('#product-price');
    var $categorySelect = $('#product-category');
    var $descriptionInput = $('#product-description');
    var $imageInput = $('#product-image');

    var $nameError = $('#product-name-error');
    var $priceError = $('#product-price-error');
    var $categoryError = $('#product-category-error');
    var $descriptionError = $('#product-description-error');
    var $imageError = $('#product-image-error');

    $nameInput.removeClass('is-valid is-invalid');
    $priceInput.removeClass('is-valid is-invalid');
    $categorySelect.removeClass('is-valid is-invalid');
    if ($descriptionInput.length) $descriptionInput.removeClass('is-valid is-invalid');
    if ($imageInput.length) $imageInput.removeClass('is-valid is-invalid');

    $nameError.text('');
    $priceError.text('');
    $categoryError.text('');
    $descriptionError.text('');
    $imageError.text('');

    var formData = new FormData();
    formData.append('name', $nameInput.val());
    formData.append('price', $priceInput.val());
    formData.append('description', $('#product-description').val());
    formData.append('product_category_id', $categorySelect.val());

    var imageFile = $('#product-image')[0].files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    var url = '/products';
    var method = 'POST';
    if (id) {
        url = '/products/' + id;
        formData.append('_method', 'PUT');
    }

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function (response) {
            if (response.success) {
                if (typeof showToast === 'function') {
                    showToast(response.message || (id ? 'Product updated successfully.' : 'Product created successfully.'), 'success');
                }

                setTimeout(function () {
                    var modalEl = document.getElementById('productModal');
                    if (modalEl) {
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                        loadProducts();
                    } else {
                        window.location.href = '/products';
                    }
                }, 300);
            }
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;

                if (errors.name && errors.name.length) {
                    $nameInput.addClass('is-invalid');
                    $nameError.text(errors.name[0]);
                }
                if (errors.price && errors.price.length) {
                    $priceInput.addClass('is-invalid');
                    $priceError.text(errors.price[0]);
                }
                if (errors.product_category_id && errors.product_category_id.length) {
                    $categorySelect.addClass('is-invalid');
                    $categoryError.text(errors.product_category_id[0]);
                }
                if (errors.description && errors.description.length && $descriptionInput.length) {
                    $descriptionInput.addClass('is-invalid');
                    $descriptionError.text(errors.description[0]);
                }
                if (errors.image && errors.image.length && $imageError.length) {
                    $imageInput.addClass('is-invalid');
                    $imageError.text(errors.image[0]);
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast('Failed to save product.', 'error');
                }
            }
        }
    });
}

function deleteProduct(id) {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: '/products/' + id,
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            _method: 'DELETE'
        },
        success: function (response) {
            if (typeof showToast === 'function') {
                showToast(response.message || 'Product deleted successfully.', 'success');
            }
            loadProducts();
        },
        error: function () {
            if (typeof showToast === 'function') {
                showToast('Failed to delete product.', 'error');
            }
        }
    });
}
