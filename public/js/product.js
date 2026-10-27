(function () {
    const PRODUCT_API_BASE = '/api/products';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductIndex);
    } else {
        initProductIndex();
    }

    function initProductIndex() {
        const permissions = window.crmUserPermissions?.products || {};
        const tableBody = document.querySelector('#productsTable tbody');
        const paginationContainer = document.getElementById('productsPagination');
        const searchInput = document.getElementById('productsSearch');
        const importButton = document.getElementById('productsImportBtn');
        const importFileInput = document.getElementById('productsImportFile');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        const isIndexPage = !!(tableBody && paginationContainer && searchInput);

        function formatDate(dateValue) {
            if (!dateValue) return '--';

            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return '--';

            return date.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

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

        function showToast(message, type) {
            if (typeof window.showAlert === 'function') {
                window.showAlert(type || 'info', message);
                return;
            }

            window.crmAlert(message);
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No products found.</p>
                            ${permissions.create ? '<a href="/products/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Product</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (product, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const price = Number(product.price || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
                const category = escapeHtml(product.category?.name || '--');
                const productName = escapeHtml(product.name || '--');
                const createdAt = escapeHtml(formatDate(product.created_at));

                return `
                    <tr>
                        <td class="ps-4">
                            <span class="text-muted small fw-medium">${srNo}</span>
                        </td>
                        <td>
                            <div class="fw-bold small">${productName}</div>
                            <div class="text-muted small mt-1">${category}</div>
                        </td>
                        <td class="d-none d-md-table-cell">${price}</td>
                        <td class="d-none d-md-table-cell">${category}</td>
                        <td class="d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                ${permissions.edit ? `<a href="/products/${product.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/products/${product.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${product.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-product-id="${product.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${product.id}" style="display: none;">
                        <td colspan="7" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Price :</div>
                                        <div class="expand-value text-end">${price}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-regular fa-folder-open"></i> Category :</div>
                                        <div class="expand-value text-end">${category}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Created :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${permissions.edit ? `<a href="/products/${product.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                        ${permissions.view ? `<a href="/products/${product.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${product.id}" title="Delete"><i class="bi bi-trash text-danger"></i></button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            bindDeleteButtons();

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.productId;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector('i');

                    if (!detailsRow) {
                        return;
                    }

                    if (detailsRow.style.display === 'none') {
                        detailsRow.style.display = 'table-row';
                        icon.classList.replace('fa-plus', 'fa-minus');
                        this.classList.add('active');
                    } else {
                        detailsRow.style.display = 'none';
                        icon.classList.replace('fa-minus', 'fa-plus');
                        this.classList.remove('active');
                    }
                });
            });
        }

        function renderPagination(data) {
            if (!data || data.total === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
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

            document.querySelectorAll('#productsPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchProducts(this.dataset.page);
                });
            });
        }

        function fetchProducts(page) {
            const url = new URL(PRODUCT_API_BASE, window.location.origin);
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
                }),
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading products</td></tr>';
                    paginationContainer.innerHTML = '';
                },
            });
        }

        function deleteProduct(id, button) {
            window.showDeleteConfirm('This product will be deleted!').then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                $.ajax({
                    url: `${PRODUCT_API_BASE}/${id}`,
                    type: 'DELETE',
                    dataType: 'json',
                    headers: authHeaders({
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    }),
                    success: function (response) {
                        if (response.success) {
                            showToast(response.message || 'Product deleted successfully.', 'success');
                            fetchProducts(1);
                        } else {
                            showToast(response.message || 'Delete failed.', 'error');
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function () {
                        showToast('Something went wrong while deleting the product.', 'error');
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
            });
        }

        function bindDeleteButtons() {
            document.querySelectorAll('#productsTable .delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteProduct(this.dataset.id, this);
                });
            });
        }

        let searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchProducts(1);
            }, 400);
        });

        if (importButton && importFileInput) {
            importFileInput.addEventListener('change', function () {
                const file = this.files && this.files[0];

                if (!file) {
                    return;
                }

                const formData = new FormData();
                formData.append('import_file', file);

                const originalHtml = importButton.innerHTML;
                importButton.disabled = true;
                importButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Importing...';

                $.ajax({
                    url: '/api/products/import',
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
                    success: function (response) {
                        const summary = response.summary || {};
                        const extra = [];

                        if (summary.updated) {
                            extra.push(`${summary.updated} updated`);
                        }

                        if (summary.skipped) {
                            extra.push(`${summary.skipped} skipped`);
                        }

                        const message = extra.length
                            ? `${response.message} (${extra.join(', ')})`
                            : (response.message || 'Products imported successfully.');

                        showToast(message, 'success');
                        fetchProducts(1);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors?.import_file?.length) {
                            showToast(xhr.responseJSON.errors.import_file[0], 'error');
                            return;
                        }

                        showToast(xhr.responseJSON?.message || 'Unable to import products right now.', 'error');
                    },
                    complete: function () {
                        importButton.disabled = false;
                        importButton.innerHTML = originalHtml;
                        importFileInput.value = '';
                    },
                });
            });
        }

        if (isIndexPage) {
            fetchProducts(1);
        }
    }
})();

function showImportDialog() {
    Swal.fire({
        html: `
            <div class="mt-4 mb-3">
                <i class="fa-solid fa-file-csv fa-4x text-dark-blue"></i>
            </div>
            <h2 class="fw-bold mb-3" style="font-size: 1.75rem; color: #333;">Import Products</h2>
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
            document.getElementById('productsImportFile').click();
        } else if (result.isDenied) {
            downloadProductCsvDemo();
        }
    });
}

function downloadProductCsvDemo() {
    const csvContent = "data:text/csv;charset=utf-8,name,category_name,price,description,sku,stock_quantity\n" +
        "Sample Product,Electronics,199.99,High quality electronic item,SKU12345,50\n" +
        "Sample Product 2,Furniture,450.00,Modern wooden chair,SKU67890,20";
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "products_demo.csv");
    document.body.appendChild(link);
    link.click();
    link.remove();
}

$(document).ready(function () {
    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === 'function'
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    function showToast(message, type) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type || 'info', message);
            return;
        }

        window.crmAlert(message);
    }

    function handleValidationErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            const fieldEl = $form.find('[name="' + field + '"]');
            if (!fieldEl.length) {
                return;
            }

            fieldEl.addClass('is-invalid');
            fieldEl.closest('.product-category-inline').addClass('is-invalid');
            const feedback = $form.find('#' + field + '-error');
            if (feedback.length) {
                feedback.html(errors[field][0]);
            }
        });
    }

    $('.ajax-product-form').each(function () {
        const $form = $(this);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.product-category-inline.is-invalid').removeClass('is-invalid');
        $form.find('.ajax-alert').remove();
        $form.find('.invalid-feedback').html('');
    });

    $('body').on('submit', '.ajax-product-form', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const originalHtml = $button.html();
        const formData = new FormData(this);

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.product-category-inline.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').html('');
        $form.find('.ajax-alert').remove();

        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: authHeaders({
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            }),
            success: function (response) {
                if (typeof window.showAlert === 'function') {
                    window.showAlert('success', response.message || 'Product saved successfully.', 'Success!', response.redirect || '/products');
                    return;
                }

                showToast(response.message || 'Product saved successfully.', 'success');
                setTimeout(function () {
                    window.location.href = response.redirect || '/products';
                }, 3000);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    handleValidationErrors($form, xhr.responseJSON.errors);
                    return;
                }

                const message = xhr.responseJSON?.message || 'Something went wrong while saving the product.';
                $form.prepend('<div class="alert alert-danger ajax-alert" role="alert">' + message + '</div>');
            },
            complete: function () {
                $button.prop('disabled', false).html(originalHtml);
            },
        });
    });

    $('#add-category-btn').on('click', function () {
        const form = document.getElementById('add-category-form');
        if (form) {
            form.reset();
        }

        $('#new-category-name').removeClass('is-valid is-invalid');
        $('#new-category-name-error').text('');
        const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        modal.show();
    });

    $('#save-category-btn').on('click', function () {
        const $name = $('#new-category-name');
        const $error = $('#new-category-name-error');
        const $select = $('#product_category_id');

        $name.removeClass('is-valid is-invalid');
        $error.text('');

        $.ajax({
            url: '/api/products/categories',
            type: 'POST',
            dataType: 'json',
            headers: authHeaders({
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            }),
            data: {
                name: $name.val(),
                description: $('#new-category-description').val(),
            },
            success: function (response) {
                if (response.success && response.data) {
                    const option = $('<option></option>').attr('value', response.data.id).text(response.data.name);
                    $select.append(option).val(response.data.id);
                    bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                    showToast(response.message || 'Product category created successfully.', 'success');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors?.name?.length) {
                    $name.addClass('is-invalid');
                    $error.text(xhr.responseJSON.errors.name[0]);
                    return;
                }

                showToast('Unable to create category right now.', 'error');
            },
        });
    });

    $('body').on('input change', 'input, select, textarea', function () {
        $(this).removeClass('is-invalid');
        $(this).closest('.product-category-inline').removeClass('is-invalid');
        $('#' + $(this).attr('id') + '-error').html('');
    });

    $('#image').on('change', function () {
        const file = this.files && this.files[0];
        const previewWrap = document.getElementById('product-image-preview-wrap');
        const previewImage = document.getElementById('product-image-preview');

        if (!previewWrap || !previewImage) {
            return;
        }

        if (!file) {
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        previewImage.src = objectUrl;
        previewWrap.classList.remove('d-none');

        previewImage.onload = function () {
            URL.revokeObjectURL(objectUrl);
        };
    });
});
