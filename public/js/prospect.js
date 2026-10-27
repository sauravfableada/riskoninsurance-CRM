(function () {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const tableBody = document.getElementById('prospectsTable');

        if (!tableBody) {
            return;
        }

        const paginationContainer = document.getElementById('prospectPaginationContainer');
        const searchInput = document.getElementById('prospectSearch');
        const baseUrl = document.getElementById('base_url')?.value || '/';
        const csrfToken = document.getElementById('csrf_token')?.value || '';
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === 'function'
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        let timer;
        let currentPage = 1;

        function renderStatusBadge(status) {
            const mapping = {
                prospect: { label: 'Prospect', bg: 'bg-primary' },
                client: { label: 'Client', bg: 'bg-success' },
            };

            const info = mapping[status] || { label: 'Unknown', bg: 'bg-secondary' };
            return '<span class="badge ' + info.bg + ' px-3 py-2">' + info.label + '</span>';
        }

        function renderActions(prospect) {
            const editAction = prospect.can_update
                ? '<a href="/prospects/edit/' + prospect.id + '" class="btn crm-action-btn btn-sm" title="Edit">' +
                  '<i class="bi bi-pencil"></i></a>'
                : '<button class="btn crm-action-btn btn-sm prospect-action-disabled" title="Edit" disabled aria-disabled="true">' +
                  '<i class="bi bi-pencil"></i></button>';

            const deleteAction = prospect.can_delete
                ? '<button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="' + prospect.id + '" title="Delete">' +
                  '<i class="bi bi-trash text-danger"></i></button>'
                : '<button class="btn crm-action-btn btn-sm text-danger prospect-action-disabled" title="Delete" disabled aria-disabled="true">' +
                  '<i class="bi bi-trash text-danger"></i></button>';

            return editAction + deleteAction;
        }

        function renderRows(items, from) {
            if (!items || items.length === 0) {
                tableBody.innerHTML =
                    '<tr><td colspan="7" class="text-center py-5">' +
                    '<div class="text-muted mb-3"><i class="bi bi-people display-1 opacity-25"></i></div>' +
                    '<p class="text-muted">No prospects found.</p>' +
                    '<a href="/prospects/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Prospect</a>' +
                    '</td></tr>';
                return;
            }

            tableBody.innerHTML = items.map((prospect, index) => {
                const actions = renderActions(prospect);
                const statusBadge = renderStatusBadge(prospect.status);
                const serial = Number(from || 0) + index;

                return '<tr>' +
                    '<td class="ps-4"><span class="text-muted small fw-medium">' + serial + '</span></td>' +
                    '<td><div class="fw-bold small">' + escapeHtml(prospect.name) + '</div></td>' +
                    '<td class="d-none d-md-table-cell"><div class="small">' + escapeHtml(prospect.mobile_no || '--') + '</div></td>' +
                    '<td class="d-none d-md-table-cell"><div class="small">' + escapeHtml(prospect.area || '--') + '</div></td>' +
                    '<td class="text-center d-none d-md-table-cell">' +
                        '<select class="form-select form-select-sm status-dropdown d-none" data-id="' + prospect.id + '">' +
                            '<option value="prospect" ' + (prospect.status === 'prospect' ? 'selected' : '') + '>Prospect</option>' +
                            '<option value="client" ' + (prospect.status === 'client' ? 'selected' : '') + '>Client</option>' +
                        '</select>' +
                        '<span class="status-display" style="cursor: pointer;">' + statusBadge + '</span>' +
                    '</td>' +
                    '<td class="text-end pe-4 d-none d-md-table-cell"><div class="d-inline-flex align-items-center gap-2">' + actions + '</div></td>' +
                    '<td class="text-center d-md-none">' +
                        '<button type="button" class="btn-user-expand" data-prospect-id="' + prospect.id + '">' +
                            '<i class="fa-solid fa-plus"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>' +
                '<tr class="details-row d-md-none border-0" id="details-' + prospect.id + '" style="display: none;">' +
                    '<td colspan="7" class="p-0 border">' +
                        '<div class="details-content">' +
                            '<div class="row g-3">' +
                                '<div class="col-12 d-flex justify-content-between align-items-center">' +
                                    '<div class="expand-label"><i class="fa-solid fa-phone"></i> Mobile :</div>' +
                                    '<div class="expand-value">' + escapeHtml(prospect.mobile_no || '--') + '</div>' +
                                '</div>' +
                                '<div class="col-12 d-flex justify-content-between align-items-center">' +
                                    '<div class="expand-label"><i class="fa-solid fa-location-dot"></i> Area :</div>' +
                                    '<div class="expand-value">' + escapeHtml(prospect.area || '--') + '</div>' +
                                '</div>' +
                                '<div class="col-12 d-flex justify-content-between align-items-center">' +
                                    '<div class="expand-label"><i class="fa-solid fa-flag"></i> Status :</div>' +
                                    '<div class="expand-value">' + statusBadge + '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">' +
                                '<div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>' +
                                '<div class="d-flex gap-2">' + actions + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                '</tr>';
            }).join('');

            tableBody.querySelectorAll('.delete-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    deleteProspect(this.dataset.id, this);
                });
            });

            tableBody.querySelectorAll('.btn-user-expand').forEach((button) => {
                button.addEventListener('click', function () {
                    const id = this.dataset.prospectId;
                    const detailsRow = document.getElementById('details-' + id);
                    const icon = this.querySelector('i');

                    if (!detailsRow || !icon) {
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

            tableBody.querySelectorAll('.status-display').forEach((element) => {
                element.addEventListener('click', function () {
                    toggleStatusDropdown(this);
                });
            });

            tableBody.querySelectorAll('.status-dropdown').forEach((element) => {
                element.addEventListener('change', function () {
                    updateStatus(this);
                });
            });
        }

        function toggleStatusDropdown(element) {
            const row = element.closest('tr');
            if (!row) {
                return;
            }

            const dropdown = row.querySelector('.status-dropdown');
            const display = row.querySelector('.status-display');

            document.querySelectorAll('.status-dropdown').forEach((item) => item.classList.add('d-none'));
            document.querySelectorAll('.status-display').forEach((item) => item.classList.remove('d-none'));

            if (dropdown && display) {
                dropdown.classList.remove('d-none');
                display.classList.add('d-none');
                dropdown.focus();
            }
        }

        function updateStatus(dropdown) {
            const id = dropdown.dataset.id;
            const status = dropdown.value;
            const row = dropdown.closest('tr');
            const display = row ? row.querySelector('.status-display') : null;
            const originalValue = dropdown.dataset.originalValue || dropdown.value;

            dropdown.dataset.originalValue = originalValue;
            dropdown.disabled = true;

            $.ajax({
                url: baseUrl + 'prospects/update-status',
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: csrfToken,
                    id: id,
                    status: status,
                },
                success: function (response) {
                    if (response.status === 'success') {
                        if (display) {
                            display.innerHTML = renderStatusBadge(status);
                            display.classList.remove('d-none');
                        }

                        dropdown.classList.add('d-none');
                        dropdown.dataset.originalValue = status;

                        if (window.showAlert) {
                            window.showAlert('success', response.message || 'Status updated successfully.', 'Success');
                        }
                    } else {
                        dropdown.value = originalValue;
                        if (window.showAlert) {
                            window.showAlert('error', response.message || 'Failed to update status.', 'Error');
                        }
                    }
                },
                error: function () {
                    dropdown.value = originalValue;
                    if (window.showAlert) {
                        window.showAlert('error', 'Something went wrong! Please try again.', 'Error');
                    }
                },
                complete: function () {
                    dropdown.disabled = false;
                },
            });
        }

        function deleteProspect(id, button) {
            if (window.showDeleteConfirm) {
                window.showDeleteConfirm('This prospect will be deleted permanently!').then((result) => {
                    if (result.isConfirmed) {
                        performDelete(id, button);
                    }
                });
                return;
            }

            window.crmConfirm('Are you sure you want to delete this prospect?').then((result) => {
                if (result.isConfirmed) {
                    performDelete(id, button);
                }
            });
        }

        function performDelete(id, button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

            $.ajax({
                url: baseUrl + 'prospects/delete/' + id,
                type: 'GET',
                dataType: 'json',
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                }),
                success: function (response) {
                    if (response.status === 'success') {
                        if (window.showAlert) {
                            window.showAlert('success', response.message || 'Prospect deleted successfully.', 'Success');
                        }
                        fetchProspects(currentPage);
                    } else {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                        if (window.showAlert) {
                            window.showAlert('error', response.message || 'Delete failed.', 'Error');
                        }
                    }
                },
                error: function () {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                    if (window.showAlert) {
                        window.showAlert('error', 'Something went wrong! Please try again.', 'Error');
                    }
                },
            });
        }

        function renderPagination(data) {
            if (!paginationContainer) {
                return;
            }

            if (data.total === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const current = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html =
                '<div class="crm-pagination-container">' +
                    '<div class="text-muted small">Showing ' + from + ' to ' + to + ' of ' + total + ' results</div>' +
                    '<ul class="pagination crm-pagination mb-0">';

            if (data.prev_page_url) {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (current - 1) + '">Previous</a></li>';
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= current - 2 && i <= current + 2)) {
                    html += i === current
                        ? '<li class="page-item active"><span class="page-link">' + i + '</span></li>'
                        : '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
                } else if (i === current - 3 || i === current + 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            if (data.next_page_url) {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (current + 1) + '">Next</a></li>';
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('.page-link[data-page]').forEach((link) => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    fetchProspects(Number(this.dataset.page));
                });
            });
        }

        function fetchProspects(page = 1) {
            currentPage = page;

            const fromDate = document.getElementById('filterFromDate')?.value;
            const toDate = document.getElementById('filterToDate')?.value;
            const status = document.getElementById('filterStatus')?.value;
            const filterName = document.getElementById('filterName')?.value;
            const filterMobile = document.getElementById('filterMobile')?.value;
            const filterArea = document.getElementById('filterArea')?.value;
            const filterCreatedBy = document.getElementById('filterCreatedBy')?.value;
            const perPageSelect = document.getElementById('perPageSelect')?.value;
            const perPage = perPageSelect || 10;

            let url = '/api/prospects?page=' + page + '&per_page=' + perPage;
            if (searchInput && searchInput.value.trim()) {
                url += '&search=' + encodeURIComponent(searchInput.value.trim());
            }
            if (fromDate) url += '&from_date=' + encodeURIComponent(fromDate);
            if (toDate) url += '&to_date=' + encodeURIComponent(toDate);
            if (status) url += '&status=' + encodeURIComponent(status);
            
            if (filterName && filterName.trim()) url += '&name=' + encodeURIComponent(filterName.trim());
            if (filterMobile && filterMobile.trim()) url += '&mobile_no=' + encodeURIComponent(filterMobile.trim());
            if (filterArea && filterArea.trim()) url += '&area=' + encodeURIComponent(filterArea.trim());
            if (filterCreatedBy) url += '&created_by=' + encodeURIComponent(filterCreatedBy);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                }),
                beforeSend: function () {
                    tableBody.innerHTML =
                        '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
                },
                success: function (response) {
                    if (response.status && response.data) {
                        const items = response.data.data || [];
                        renderRows(items, response.data.from || 0);
                        renderPagination(response.data);
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading prospects</td></tr>';
                        paginationContainer.innerHTML = '';
                    }
                },
                error: function () {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Error loading prospects</td></tr>';
                    paginationContainer.innerHTML = '';
                },
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
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetchProspects(1);
                }, 400);
            });
        }
        
        ['filterName', 'filterMobile', 'filterArea'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(() => fetchProspects(1), 400);
                });
            }
        });

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchProspects(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus', 'filterCreatedBy'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => fetchProspects(1), 0));
            }
        });
        
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                if (document.getElementById('filterFromDate')) document.getElementById('filterFromDate').value = '';
                if (document.getElementById('filterToDate')) document.getElementById('filterToDate').value = '';
                if (document.getElementById('filterDateRange')) document.getElementById('filterDateRange').value = '';
                if (document.getElementById('filterStatus')) document.getElementById('filterStatus').value = '';
                if (document.getElementById('filterCreatedBy')) document.getElementById('filterCreatedBy').value = '';
                if (searchInput) searchInput.value = '';
                fetchProspects(1);
            });
        }
        
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
                
                document.getElementById('filterFromDate').value = from;
                document.getElementById('filterToDate').value = to;
            });
        }

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.status-dropdown, .status-display')) {
                document.querySelectorAll('.status-dropdown').forEach((item) => item.classList.add('d-none'));
                document.querySelectorAll('.status-display').forEach((item) => item.classList.remove('d-none'));
            }
        });

        fetchProspects(1);
    }
})();
