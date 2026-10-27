
$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    let pageSize = parseInt(urlParams.get('per_page')) || 10;
    let currentPage = 1;
    let filteredRows = [];

    // Get all initial table rows (excluding the empty row if present)
    let $allRows = $('#commissionRulesTable tbody tr.main-row');

    function updatePagination() {
        const q = $('#commissionSearch').val().toLowerCase().trim();
        
        // Filter rows based on search
        filteredRows = [];
        $allRows.each(function () {
            const text = $(this).text().toLowerCase();
            const childRow = $(this).next('.child-row');
            if (!q || text.includes(q)) {
                filteredRows.push({ main: $(this), child: childRow });
            } else {
                $(this).hide();
                childRow.addClass('d-none').hide();
                $(this).find('.btn-user-expand').removeClass('active').find('i').removeClass('fa-minus').addClass('fa-plus');
                $(this).removeClass('border-0');
            }
        });

        const total = filteredRows.length;

        // If no rows found
        if (total === 0) {
            $('#commissionRulesTable tbody tr.empty-row').remove();
            $('#commissionRulesTable tbody').append(`
                <tr class="empty-row">
                    <td colspan="8" class="text-center py-5">
                        <div class="doc-empty-state">
                            <i class="fa fa-file-invoice-dollar d-block mb-2"></i>
                            No matching records found.
                        </div>
                    </td>
                </tr>
            `);
            $('#commissionPaginationContainer').html('');
            return;
        } else {
            $('#commissionRulesTable tbody tr.empty-row').remove();
        }

        const lastPage = Math.ceil(total / pageSize);
        if (currentPage > lastPage) {
            currentPage = lastPage || 1;
        }

        const from = total > 0 ? (currentPage - 1) * pageSize + 1 : 0;
        const to = Math.min(currentPage * pageSize, total);

        // Hide all rows, then show only the ones on current page
        $allRows.hide();
        $('#commissionRulesTable tbody tr.child-row').addClass('d-none').hide();

        for (let i = from - 1; i < to; i++) {
            if (filteredRows[i]) {
                filteredRows[i].main.show();
                filteredRows[i].main.find('.btn-user-expand').removeClass('active').find('i').removeClass('fa-minus').addClass('fa-plus');
                filteredRows[i].main.removeClass('border-0');
            }
        }

        // Render Pagination UI
        let html = `
        <div class="crm-pagination-container d-flex justify-content-between align-items-center w-100">
            <div class="text-muted small">
                Showing ${from} to ${to} of ${total} results
            </div>
            <ul class="pagination crm-pagination mb-0">
        `;

        // Previous
        if (currentPage > 1) {
            html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
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
        if (currentPage < lastPage) {
            html += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
        }

        html += `</ul></div>`;

        $('#commissionPaginationContainer').html(html);

        // Attach click handlers
        $('#commissionPaginationContainer .page-link[data-page]').on('click', function (e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            updatePagination();
        });
    }

    // Trigger initial pagination
    updatePagination();

    // Client-side search trigger
    $('#commissionSearch').on('input', function () {
        currentPage = 1;
        updatePagination();
    });

    $('#perPageSelect').on('change', function() {
        pageSize = parseInt($(this).val()) || 10;
        currentPage = 1;
        updatePagination();
    });

    $('#applyFiltersBtn').on('click', function() {
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: {
                from_date: $('#filterFromDate').val(),
                to_date: $('#filterToDate').val()
            },
            success: function(response) {
                let tbodyHtml = '';
                if(response.data.length === 0) {
                    tbodyHtml = `
                    <tr class="empty-row">
                        <td colspan="8" class="text-center py-5">
                            <div class="doc-empty-state">
                                <i class="fa fa-file-invoice-dollar d-block mb-2"></i>
                                No matching records found.
                            </div>
                        </td>
                    </tr>
                    `;
                } else {
                    response.data.forEach((rule, index) => {
                        let typeBadge = rule.commission_type === 'percentage' ? 
                            '<span class="comm-type-badge percentage">Percentage</span>' : 
                            '<span class="comm-type-badge flat">Flat</span>';
                        let valStr = rule.commission_type === 'percentage' ? 
                            Number(rule.commission_value).toFixed(2) + '%' : 
                            '₹' + Number(rule.commission_value).toFixed(2);
                            
                        tbodyHtml += `
                        <tr class="main-row">
                            <td class="ps-4 text-muted fw-semibold">${index + 1}</td>
                            <td class="fw-semibold">${rule.policy_type}</td>
                            <td class="text-muted small d-none d-md-table-cell">${rule.policy_holder}</td>
                            <td class="text-muted small d-none d-md-table-cell">₹${Number(rule.policy_price).toFixed(2)}</td>
                            <td class="d-none d-md-table-cell">${typeBadge}</td>
                            <td class="fw-semibold d-none d-md-table-cell">${valStr}</td>
                            <td class="fw-semibold text-primary d-none d-md-table-cell">₹${Number(rule.total_commission).toFixed(2)}</td>
                            <td class="pe-4 d-none d-md-table-cell">
                                <div class="doc-action-group">
                                    <a href="/commission-rules/${rule.id}/edit"
                                       class="doc-icon-btn btn-edit" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <button class="doc-icon-btn btn-del delete-rule"
                                            data-id="${rule.id}" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="child-row d-none d-md-none">
                            <td colspan="9" class="p-0 border-0">
                                <div class="details-content mb-3 mx-2">
                                    <div class="row g-2">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-user me-1"></i> Policy:</div>
                                            <div class="expand-value text-muted fw-normal">${rule.policy_holder}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign me-1"></i> Policy Price:</div>
                                            <div class="expand-value fw-normal">₹${Number(rule.policy_price).toFixed(2)}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-tag me-1"></i> Comm. Type:</div>
                                            <div class="expand-value">${typeBadge}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-percent me-1"></i> Comm. Value:</div>
                                            <div class="expand-value fw-normal">${valStr}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                            <div class="expand-label"><i class="fa-solid fa-sack-dollar me-1"></i> Total Comm:</div>
                                            <div class="expand-value text-primary">₹${Number(rule.total_commission).toFixed(2)}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                            <a href="/commission-rules/${rule.id}/edit" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa fa-pencil me-1"></i> Edit
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-rule" data-id="${rule.id}">
                                                <i class="fa fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        `;
                    });
                }
                $('#commissionRulesTable tbody').html(tbodyHtml);
                
                $allRows = $('#commissionRulesTable tbody tr.main-row');
                currentPage = 1;
                updatePagination();
            }
        });
    });

    $('#clearFiltersBtn').on('click', function() {
        $('#filterFromDate').val('');
        $('#filterToDate').val('');
        $('#filterDateRange').val('');
        $('#commissionSearch').val('');
        $('#applyFiltersBtn').click();
    });

    $('#filterDateRange').on('change', function() {
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
        
        $('#filterFromDate').val(from);
        $('#filterToDate').val(to);
    });

    /* ── Delete ────────────────────────────────────────────── */
    let deleteId = null;

    $(document).on('click', '.delete-rule', function () {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('commissionDeleteModal')).show();
    });

    $('#commission-confirm-delete').on('click', function () {
        if (!deleteId) return;

        const $btn = $('[data-id="' + deleteId + '"].delete-rule');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url:  "{{ url('commission-rules') }}/" + deleteId,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function (response) {
                bootstrap.Modal.getInstance(document.getElementById('commissionDeleteModal')).hide();
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: 'Deleted!',
                        text: response.message, timer: 2000, showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                    Swal.fire({ icon: 'error', title: 'Error!',
                        text: response.message || 'Failed to delete.',
                        confirmButtonColor: '#0f172a' });
                }
            },
            error: function () {
                bootstrap.Modal.getInstance(document.getElementById('commissionDeleteModal')).hide();
                $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                Swal.fire({ icon: 'error', title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#0f172a' });
            }
        });
        });
    });

    $(document).on('click', '.btn-user-expand', function() {
        const icon = $(this).find('i');
        const childRow = $(this).closest('tr').next('.child-row');
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            $(this).closest('tr').removeClass('border-0');
            icon.removeClass('fa-minus').addClass('fa-plus');
            childRow.addClass('d-none').hide();
        } else {
            $(this).addClass('active');
            $(this).closest('tr').addClass('border-0');
            icon.removeClass('fa-plus').addClass('fa-minus');
            childRow.removeClass('d-none d-md-none').show();
        }
    });
});
