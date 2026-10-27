@extends('layouts.app')

@section('page_title', 'Life Insurance Policies')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/leads.css' : '/css/leads.css') }}?v={{ filemtime(public_path('css/leads.css')) }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Life Insurance</h4>
                        <p class="text-muted small mb-0">Manage life insurance policies and related actions.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('life-insurance.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Add Life Insurance Policy
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="lifeInsuranceSearch" class="form-control border-start-0" placeholder="Search policies...">
                        </div>
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                            <i class="bi bi-funnel"></i> Filters
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2 small">
                        <span class="text-muted">Show per page:</span>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: 70px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="collapse show mt-3" id="filterCollapse">
                    <div class="card card-body bg-light border-0 rounded-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Start Date</label>
                                <input type="date" id="filterFromDate" class="form-control form-control-sm" name="from_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">End Date</label>
                                <input type="date" id="filterToDate" class="form-control form-control-sm" name="to_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Date Range</label>
                                <select id="filterDateRange" class="form-select form-select-sm">
                                    <option value="">Custom</option>
                                    <option value="today">Today</option>
                                    <option value="this_week">This Week</option>
                                    <option value="this_month">This Month</option>
                                    <option value="this_year">This Year</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select id="filterStatus" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="Inforce">Inforce</option>
                                    <option value="Lapsed">Lapsed</option>
                                    <option value="Matured">Matured</option>
                                    <option value="Surrendered">Surrendered</option>
                                </select>
                            </div>
                            <div class="col-md-3">
<button type="button" id="clearFiltersBtn" class="btn btn-sm btn-dark text-white px-3" style="background-color: #0f172a; border-color: #0f172a;">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="LifeInsuranceTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th class="d-none d-md-table-cell">Policy Number</th>
                                <th>Holder Name</th>
                                <th class="d-none d-md-table-cell">Company</th>
                                <th class="d-none d-md-table-cell">Branch</th>
                                <th class="d-none d-md-table-cell">Sum Assured</th>
                                <th class="d-none d-md-table-cell">Premium</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                                <th class="d-none d-md-table-cell">Start Date</th>
                                <th class="d-none d-md-table-cell">End Date</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="min-width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer border-top-0 py-4 px-4">
                    <div id="lifeInsuranceTableFooter"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            const listUrl = "{{ route('life-insurance.list') }}";
            const viewBase = "{{ url('/life-insurance/view') }}/";
            const editBase = "{{ url('/life-insurance/edit-page') }}/";
            const deleteBase = "{{ url('/life-insurance/delete') }}/";
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            const table = $('#LifeInsuranceTable').DataTable({
                processing: true,
                serverSide: false,
                paging: true,
                searching: true,
                ordering: false,
                info: true,
                scrollX: false,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                orderMulti: false,
                deferRender: true,
                dom: 'rt<"crm-pagination-container"ip>',
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    zeroRecords: "No matching records found",
                    paginate: { previous: "Previous", next: "Next" }
                },
                ajax: {
                    url: listUrl,
                    dataSrc: 'data',
                    data: function(d) {
                        d.from_date = $('#filterFromDate').val();
                        d.to_date = $('#filterToDate').val();
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [
                    { data: 'sr_no', className: 'ps-4' },
                    { data: 'policy_number', className: 'd-none d-md-table-cell' },
                    { data: 'holder_name' },
                    { data: 'company_name', className: 'd-none d-md-table-cell' },
                    { data: 'branch_name', className: 'd-none d-md-table-cell' },
                    {
                        data: 'sum_assured',
                        className: 'd-none d-md-table-cell',
                        render: function(data) {
                            return data ? '&#8377;' + parseFloat(data).toLocaleString('en-IN') : '--';
                        }
                    },
                    {
                        data: 'premium',
                        className: 'd-none d-md-table-cell',
                        render: function(data) {
                            return data ? '&#8377;' + parseFloat(data).toLocaleString('en-IN') : '--';
                        }
                    },
                    {
                        data: 'policy_status',
                        className: 'text-center d-none d-md-table-cell',
                        render: function(data) {
                            const statusClass = data === 'Inforce' ? 'success' :
                                data === 'Lapsed' ? 'danger' :
                                data === 'Matured' ? 'info' : 'warning';
                            return `<span class="badge rounded-pill px-3 bg-${statusClass}">${data || 'Inforce'}</span>`;
                        }
                    },
                    { data: 'start_date', className: 'd-none d-md-table-cell' },
                    { data: 'end_date', className: 'd-none d-md-table-cell' },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4 d-none d-md-table-cell',
                        render: function(id) {
                            return `
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button class="btn btn-sm crm-action-btn editLifeInsurance" data-id="${id}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="${viewBase}${id}" class="btn btn-sm crm-action-btn" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-sm crm-action-btn text-danger deleteLifeInsurance" data-id="${id}" title="Delete">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center d-md-none',
                        render: function(id) {
                            return `
                                <button type="button" class="btn-user-expand" data-policy-id="${id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            `;
                        }
                    }
                ],
                order: [[1, 'desc']],
                drawCallback: function(settings) {
                    $('.dataTables_paginate ul.pagination').addClass('crm-pagination mb-0');
                    $('#LifeInsuranceTable_wrapper .dataTables_info').addClass('text-muted small');
                    
                    $('.btn-user-expand').off('click').on('click', function() {
                        const tr = $(this).closest('tr');
                        const row = table.row(tr);
                        const icon = $(this).find('i');
                        const data = row.data();

                        if (row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown border-0');
                            icon.removeClass('fa-minus').addClass('fa-plus');
                            $(this).removeClass('active');
                        } else {
                            const statusVal = data.policy_status || 'Inforce';
                            const statusClass = statusVal === 'Inforce' ? 'success' :
                                statusVal === 'Lapsed' ? 'danger' :
                                statusVal === 'Matured' ? 'info' : 'warning';
                            const statusHtml = `<span class="badge rounded-pill px-3 bg-${statusClass}">${statusVal}</span>`;

                            const childHtml = `
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-file-invoice"></i> Policy No :</div>
                                            <div class="expand-value">${data.policy_number || '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                            <div class="expand-value">${statusHtml}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-building"></i> Company :</div>
                                            <div class="expand-value">${data.company_name || '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-code-branch"></i> Branch :</div>
                                            <div class="expand-value">${data.branch_name || '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-shield-halved"></i> Sum Assured :</div>
                                            <div class="expand-value">${data.sum_assured ? '&#8377;' + parseFloat(data.sum_assured).toLocaleString('en-IN') : '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-money-bill"></i> Premium :</div>
                                            <div class="expand-value">${data.premium ? '&#8377;' + parseFloat(data.premium).toLocaleString('en-IN') : '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                                            <div class="expand-value">${data.start_date || '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                                            <div class="expand-value">${data.end_date || '--'}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                            <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm crm-action-btn editLifeInsurance" data-id="${data.id}" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="${viewBase}${data.id}" class="btn btn-sm crm-action-btn" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button class="btn btn-sm crm-action-btn text-danger deleteLifeInsurance" data-id="${data.id}" title="Delete">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            row.child(childHtml).show();
                            row.child().find('td').addClass('p-0 border');
                            row.child().addClass('details-row d-md-none border-0');
                            tr.addClass('shown border-0');
                            icon.removeClass('fa-plus').addClass('fa-minus');
                            $(this).addClass('active');
                        }
                    });
                }
            });

            $('#lifeInsuranceSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#perPageSelect').on('change', function() {
                table.page.len($(this).val()).draw();
            });

            $('#filterFromDate, #filterToDate, #filterDateRange, #filterStatus').on('change', function() {
                window.setTimeout(function() { table.ajax.reload(); }, 0);
            });

            $('#clearFiltersBtn').on('click', function() {
                $('#filterFromDate').val('');
                $('#filterToDate').val('');
                $('#filterDateRange').val('');
                $('#filterStatus').val('');
                $('#lifeInsuranceSearch').val('');
                table.search('').draw();
                table.ajax.reload();
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

            // Place DataTables footer controls in card footer to match users page design
            const $wrapper = $('#LifeInsuranceTable_wrapper');
            const $footerControls = $wrapper.find('.dataTables_length, .dataTables_info, .dataTables_paginate').parent();
            if ($footerControls.length) {
                $('#lifeInsuranceTableFooter').append($footerControls);
            }

            $(document).on('click', '.editLifeInsurance', function() {
                const policyId = $(this).data('id');
                window.location.href = editBase + policyId;
            });

            $(document).on('click', '.deleteLifeInsurance', function() {
                const policyId = $(this).data('id');
                const $btn = $(this);

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to delete this Life Insurance policy? This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteBase + policyId,
                            type: 'POST',
                            dataType: 'json',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            beforeSend: function() {
                                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                            },
                            success: function(res) {
                                if (res.status === true) {
                                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1800, showConfirmButton: false });
                                    table.ajax.reload();
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error!', text: res.message || 'Delete failed.' });
                                }
                            },
                            error: function() {
                                Swal.fire({ icon: 'error', title: 'Server Error!', text: 'An error occurred while deleting the policy.' });
                            },
                            complete: function() {
                                $btn.prop('disabled', false).html('<i class="bi bi-trash text-danger"></i>');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
