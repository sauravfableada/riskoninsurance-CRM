(function () {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const tableBody = document.getElementById('teamMembersTableBody');
        if (!tableBody) {
            return;
        }

        const paginationContainer = document.getElementById('teamMemberPaginationContainer');
        const searchInput = document.getElementById('teamMemberSearch');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const notify = function (type, message) {
            if (window.showAlert) {
                window.showAlert(type, message);
                return;
            }

            Swal.fire({
                icon: type === 'error' ? 'error' : 'success',
                title: type === 'error' ? 'Error' : 'Success',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { popup: "rounded-4 shadow" }
            });
        };

        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === 'function'
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        let allMembers = [];
        let filteredMembers = [];
        let currentPage = 1;
        let perPage = 10;

        function fetchTeamMembers() {
            $.ajax({
                url: '/api/team-members/list',
                type: 'GET',
                dataType: 'json',
                headers: authHeaders({
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }),
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (res) {
                    const data = Array.isArray(res.data) ? res.data : [];
                    allMembers = data;
                    applySearchAndRender(1);
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5 text-danger">
                                Unable to load team members.
                            </td>
                        </tr>`;
                }
            });
        }

        function applySearchAndRender(page) {
            const keyword = (searchInput?.value || '').trim().toLowerCase();
            const fromDate = document.getElementById('filterFromDate')?.value;
            const toDate = document.getElementById('filterToDate')?.value;
            const statusFilter = document.getElementById('filterStatus')?.value;
            
            const perPageSelect = document.getElementById('perPageSelect');
            if (perPageSelect) {
                perPage = parseInt(perPageSelect.value) || 10;
            }

            filteredMembers = allMembers.filter(function (member) {
                if (keyword) {
                    const fields = [
                        member.first_name,
                        member.last_name,
                        member.email,
                        member.mobile,
                        member.designation,
                        member.status,
                    ].join(' ').toLowerCase();
                    if (!fields.includes(keyword)) return false;
                }
                
                if (statusFilter && member.status !== statusFilter) {
                    return false;
                }
                
                if ((fromDate || toDate) && member.created_at) {
                    const memberDate = member.created_at.split(' ')[0];
                    if ((fromDate && memberDate < fromDate) || (toDate && memberDate > toDate)) {
                        return false;
                    }
                }

                return true;
            });

            currentPage = page;
            renderRows();
            renderPagination();
        }

        function renderRows() {
            if (!filteredMembers.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-people display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No team members found.</p>
                            <a href="/team-members/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Team Member</a>
                        </td>
                    </tr>`;
                return;
            }

            const start = (currentPage - 1) * perPage;
            const paginated = filteredMembers.slice(start, start + perPage);

            tableBody.innerHTML = paginated.map(function (member, index) {
                const absoluteIndex = start + index + 1;
                const fullName = `${member.first_name || ''} ${member.last_name || ''}`.trim() || '--';
                const email = String(member.email || '').trim();
                const mobile = String(member.mobile || '').trim();
                const phoneHref = mobile.replace(/[^\d+]/g, '');
                const emailLink = email
                    ? `<a href="mailto:${escapeHtml(email)}" class="link-primary text-decoration-none">${escapeHtml(email)}</a>`
                    : '--';
                const mobileLink = mobile && phoneHref
                    ? `<a href="tel:${escapeHtml(phoneHref)}" class="link-primary text-decoration-none">${escapeHtml(mobile)}</a>`
                    : '--';
                return `
                    <tr>
                        <td class="ps-4"><span class="text-muted small fw-medium">${absoluteIndex}</span></td>
                        <td><div class="fw-bold small">${escapeHtml(fullName)}</div></td>
                        <td class="d-none d-md-table-cell"><div class="small">${emailLink}</div></td>
                        <td class="d-none d-md-table-cell"><div class="small">${mobileLink}</div></td>
                        <td class="d-none d-md-table-cell">
                            ${getStatusBadge(member.status, member.id)}
                        </td>
                        <td class="d-none d-md-table-cell"><div class="small">${escapeHtml(member.designation || '--')}</div></td>
                        <td class="text-end pe-4 d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center gap-2">
                                <a href="/team-members/edit/${member.id}" class="btn crm-action-btn btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${member.id}" title="Delete">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-member-id="${member.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${member.id}" style="display:none;">
                        <td colspan="8" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-envelope"></i> Email :</div>
                                        <div class="expand-value">${emailLink}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-phone"></i> Mobile :</div>
                                        <div class="expand-value">${mobileLink}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-briefcase"></i> Designation :</div>
                                        <div class="expand-value">${escapeHtml(member.designation || '--')}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-circle-check"></i> Status :</div>
                                        <div class="expand-value">${getStatusBadge(member.status, member.id)}</div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex gap-2">
                                        <a href="/team-members/edit/${member.id}" class="btn crm-action-btn btn-sm" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${member.id}" title="Delete">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            attachRowActions();
        }

        function attachRowActions() {
            tableBody.querySelectorAll('.delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteTeamMember(Number(this.dataset.id), this);
                });
            });

            tableBody.querySelectorAll('.btn-user-expand').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.memberId;
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

            // Inline Edit Event Listeners
            document.querySelectorAll('.status-display').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const row = this.closest('td') || this.closest('div');
                    if (!row) return;

                    const dropdown = row.querySelector('.status-dropdown');
                    const display = this;

                    // Close all other dropdowns
                    document.querySelectorAll('.status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                    document.querySelectorAll('.status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });

                    if (dropdown) {
                        dropdown.classList.remove('d-none');
                        dropdown.style.display = 'block';
                        display.classList.add('d-none');
                        display.style.display = 'none';
                        dropdown.focus();
                    }
                });
            });

            document.querySelectorAll('.status-dropdown').forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
                element.addEventListener('change', function () {
                    updateStatusField(this);
                });
            });

        }

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.status-dropdown, .status-display')) {
                document.querySelectorAll('.status-dropdown').forEach((item) => { item.classList.add('d-none'); item.style.display = 'none'; });
                document.querySelectorAll('.status-display').forEach((item) => { item.classList.remove('d-none'); item.style.display = ''; });
            }
        });

        function getStatusBadge(status, id) {
            const badgeHtml = `<span class="badge ${status === 'Active' ? 'bg-success' : 'bg-secondary'}">${escapeHtml(status || '--')}</span>`;

            return `
                <select class="form-select form-select-sm status-dropdown d-none" data-id="${id}">
                    <option value="Active" ${status === 'Active' ? 'selected' : ''}>Active</option>
                    <option value="Inactive" ${status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                </select>
                <span class="status-display" style="cursor: pointer;" title="Click to edit status">
                    ${badgeHtml}
                </span>
            `;
        }

        function updateStatusField(dropdown) {
            const id = dropdown.dataset.id;
            const value = dropdown.value;
            const row = dropdown.closest('td') || dropdown.closest('div');
            const display = row ? row.querySelector('.status-display') : null;
            const originalValue = dropdown.dataset.originalValue || dropdown.value;

            Swal.fire({
                title: 'Change Status',
                text: "Are you sure you want to change the status?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performStatusUpdate(id, value, display, dropdown, originalValue);
                } else {
                    dropdown.value = originalValue;
                    dropdown.classList.add('d-none');
                    dropdown.style.display = 'none';
                    if (display) {
                        display.classList.remove('d-none');
                        display.style.display = '';
                    }
                }
            });
        }

        function performStatusUpdate(memberId, status, display, dropdown, originalValue) {
            dropdown.dataset.originalValue = originalValue;
            dropdown.disabled = true;

            $.ajax({
                url: '/api/team-members/update-status',
                type: 'POST',
                dataType: 'json',
                headers: authHeaders({
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }),
                data: {
                    id: memberId,
                    status: status,
                },
                success: function (res) {
                    const ok = res && (res.status === true || res.success === true);
                    if (!ok) {
                        dropdown.value = originalValue;
                        dropdown.disabled = false;
                        notify('error', res.message || 'Unable to update status.');
                        return;
                    }

                    const target = allMembers.find(function (member) {
                        return Number(member.id) === Number(memberId);
                    });
                    if (target) {
                        target.status = status;
                    }
                    
                    if (display) {
                        display.innerHTML = getStatusBadge(status, memberId).match(/<span class="status-display"[^>]*>([\s\S]*?)<\/span>/)[1];
                        display.classList.remove('d-none');
                        display.style.display = '';
                    }
                    dropdown.classList.add('d-none');
                    dropdown.style.display = 'none';
                    dropdown.dataset.originalValue = status;

                    // Instead of full re-render which closes the row, we just applied the DOM update!
                    // But if we want to be safe, applySearchAndRender is fine.
                    // applySearchAndRender(currentPage);

                    notify('success', res.message || 'Status updated successfully.');
                },
                error: function () {
                    dropdown.value = originalValue;
                    notify('error', 'Unable to update status.');
                },
                complete: function () {
                    dropdown.disabled = false;
                }
            });
        }

        function deleteTeamMember(memberId, button) {
            const runDelete = function () {
                const originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                button.disabled = true;

                $.ajax({
                    url: '/api/team-members/delete/' + memberId,
                    type: 'POST',
                    headers: authHeaders({
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }),
                    success: function (res) {
                        const ok = res && (res.status === true || res.success === true);
                        if (!ok) {
                            notify('error', res.message || 'Delete failed');
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                            return;
                        }

                        allMembers = allMembers.filter(function (member) {
                            return Number(member.id) !== memberId;
                        });

                        applySearchAndRender(currentPage);

                        notify('success', res.message || 'Team member deleted successfully');
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || 'Unable to delete team member';
                        notify('error', message);
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }
                });
            };

            if (window.showDeleteConfirm) {
                window.showDeleteConfirm('This team member will be deleted permanently.').then(function (result) {
                    if (result.isConfirmed) {
                        runDelete();
                    }
                });
                return;
            }

            window.crmConfirm('Are you sure you want to delete this team member?').then(function (result) {
                if (result.isConfirmed) {
                    runDelete();
                }
            });
        }

        function renderPagination() {
            if (!paginationContainer) {
                return;
            }

            const total = filteredMembers.length;
            const lastPage = Math.max(1, Math.ceil(total / perPage));

            if (total === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            const from = (currentPage - 1) * perPage + 1;
            const to = Math.min(currentPage * perPage, total);

            let html = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">`;

            if (currentPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let page = 1; page <= lastPage; page++) {
                if (page === 1 || page === lastPage || (page >= currentPage - 2 && page <= currentPage + 2)) {
                    if (page === currentPage) {
                        html += `<li class="page-item active"><span class="page-link">${page}</span></li>`;
                    } else {
                        html += `<li class="page-item"><a class="page-link" href="#" data-page="${page}">${page}</a></li>`;
                    }
                } else if (page === currentPage - 3 || page === currentPage + 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            if (currentPage < lastPage) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll('.page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    applySearchAndRender(Number(this.dataset.page));
                });
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

        let timer;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    applySearchAndRender(1);
                }, 300);
            });
        }

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => applySearchAndRender(1));
        }
        
        ['filterFromDate', 'filterToDate', 'filterDateRange', 'filterStatus'].forEach((filterId) => {
            const filterInput = document.getElementById(filterId);
            if (filterInput) {
                filterInput.addEventListener('change', () => setTimeout(() => applySearchAndRender(1), 0));
            }
        });
        
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                document.getElementById('filterFromDate').value = '';
                document.getElementById('filterToDate').value = '';
                document.getElementById('filterDateRange').value = '';
                document.getElementById('filterStatus').value = '';
                if (searchInput) searchInput.value = '';
                applySearchAndRender(1);
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

        fetchTeamMembers();
    }
})();
