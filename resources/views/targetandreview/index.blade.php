@extends('layouts.masters')

@section('page_title', 'Target & Review')

@push('styles')
<link rel="stylesheet"
    href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<link rel="stylesheet"
    href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
@endpush

@section('masters_content')

<style>
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container--default .select2-selection--single {
        border: 1px solid #d2d6da;
        border-radius: 0.375rem;
        height: 38px;
        display: flex;
        align-items: center;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
        padding-left: 12px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 10px;
    }
    
    .select2-dropdown {
        border: 1px solid #d2d6da;
        border-radius: 0.375rem;
    }
    
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #d2d6da;
        border-radius: 0.375rem;
        padding: 8px;
    }
    
    .select2-results__option {
        padding: 8px 12px;
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0d6efd;
    }

    #TargetReviewTable thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    #TargetReviewTable tbody tr {
        transition: background-color 0.2s ease;
    }

    #TargetReviewTable tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.04);
    }

    #TargetReviewTable_wrapper .dataTables_length,
    #TargetReviewTable_wrapper .dataTables_filter {
        display: none !important;
    }

    #TargetReviewTable_wrapper .dataTables_info {
        color: #64748b;
        font-size: 0.875rem;
    }

    #TargetReviewTable_wrapper .dataTables_paginate {
        padding: 0;
        float: none !important;
        margin: 0 0 0 auto !important;
        text-align: right;
    }

    #TargetReviewTable_wrapper .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #TargetReviewTable_wrapper .dataTables_paginate .paginate_button {
        border-radius: 10px !important;
        padding: 0.45rem 0.9rem !important;
        color: #475569 !important;
        background: #fff !important;
        border: 1px solid #e2e8f0 !important;
        margin: 0 3px !important;
    }

    #TargetReviewTable_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
        border-color: #0f172a !important;
    }

    #TargetReviewTable_wrapper .dataTables_paginate .paginate_button.current {
        background: #0f172a !important;
        color: #fff !important;
        border-color: #0f172a !important;
    }
</style>

<div class="card-header border-bottom-0 py-3 px-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold mb-0">Target and Review</h4>
            <p class="text-muted small mb-0">Manage target entries, achievements and month-wise performance review.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#addTargetModal">
                <i class="fas fa-plus me-1"></i>Add Target
            </button>
        </div>
    </div>
</div>

<div class="card-body p-0">
    <div class="px-4 pt-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h6 class="fw-bold mb-0">Target Entries</h6>
            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="targetSearch" class="form-control crm-search-input border-0"
                    placeholder="Search target entries...">
            </div>
        </div>
    </div>

    <div class="px-4 pt-3">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="from_month" class="form-label fw-semibold">From Month</label>
                <input type="date" id="from_month" class="form-control" placeholder="mm-yyyy">
            </div>
            <div class="col-md-3">
                <label for="to_month" class="form-label fw-semibold">To Month</label>
                <input type="date" id="to_month" class="form-control" placeholder="mm-yyyy">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-dark text-white w-100" id="clearFilters" style="background-color:#0f172a; border-color:#0f172a;">Clear Filters</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="TargetReviewTable" class="table table-hover align-middle mb-0" style="width:100%">
            <thead style="background-color: #fafafa;">
                <tr>
                    <th class="ps-4">Transaction Date</th>
                    <th>Transaction Num.</th>
                    <th class="d-none d-md-table-cell">Target Month</th>
                    <th class="d-none d-md-table-cell">Target Type</th>
                    <th class="d-none d-md-table-cell">Department</th>
                    <th class="d-none d-md-table-cell">Factor</th>
                    <th class="d-none d-md-table-cell">Target Amt.</th>
                    <th class="d-none d-md-table-cell">Achieved Amt.</th>
                    <th class="d-none d-md-table-cell">Difference Amt.</th>
                    <th class="pe-4 text-end d-none d-md-table-cell">Action</th>
                    <th class="text-center d-md-none" style="width: 80px;">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="px-4 py-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Total Target Amount</p>
                        <h4 class="fw-bold mb-0" id="total_target_amount">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Total Achieved Amount</p>
                        <h4 class="fw-bold mb-0" id="total_achieved_amount">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Total Difference Amount</p>
                        <h4 class="fw-bold mb-0" id="total_difference_amount">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Target Modal -->
<div class="modal fade" id="addTargetModal" tabindex="-1" aria-labelledby="addTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTargetModalLabel">Add Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTargetForm" method="POST">
                <div class="modal-body">
                    <span id="add_msg"></span>
                    
                    <div class="row g-3">
                    <div class="col-md-6">
                        <label for="transaction_date" class="form-label fw-semibold">Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="transaction_number" class="form-label fw-semibold">Transaction Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="transaction_number" name="transaction_number" placeholder="Transaction Number" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Type <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="target_type_business" value="Business Target" checked>
                                <label class="form-check-label" for="target_type_business">Business Target</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="target_type_commission" value="Commission Income Target">
                                <label class="form-check-label" for="target_type_commission">Commission Income Target</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="target_month" class="form-label fw-semibold">Target Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="target_month" name="target_month" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="department_id" class="form-label fw-semibold">Select Department <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-start"><div class="flex-grow-1 overflow-hidden"><select class="form-select select2-department" id="department_id" name="department_id" required>
                            <option value="">Search for target department</option>
                            <option value="1">General Insurance Target</option>
                            <option value="2">Investment Target</option>
                            <option value="3">Life Insurance Target</option>
                        </select></div><button type="button" class="btn btn-dark-blue quick-add-department" data-select="department_id" title="Add department"><i class="fas fa-plus"></i></button></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="factor_id" class="form-label fw-semibold">Select Factor <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-start"><div class="flex-grow-1 overflow-hidden"><select class="form-select select2-factor" id="factor_id" name="factor_id" required>
                            <option value="">Search for target factor</option>
                            <option value="1" data-department="1">Gen. Ins. New Business</option>
                            <option value="4" data-department="1">Gen. Ins. Ren. Business</option>
                            <option value="5" data-department="2">MF-SIP New Business</option>
                            <option value="6" data-department="2">MF-Lumpsum New Business</option>
                            <option value="3" data-department="3">Life Ins. New Business</option>
                            <option value="2" data-department="3">Life Ins. Ren. Business</option>
                        </select></div><button type="button" class="btn btn-dark-blue quick-add-factor" data-department="department_id" data-select="factor_id" title="Add factor"><i class="fas fa-plus"></i></button></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="target_amount" class="form-label fw-semibold">Target Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="target_amount" name="target_amount" placeholder="Target Amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="achieved_amount" class="form-label fw-semibold">Achieved Amount</label>
                        <input type="number" class="form-control" id="achieved_amount" name="achieved_amount" placeholder="Achieved Amount" step="0.01" min="0" value="0">
                    </div>
                    
                    <div class="col-12">
                        <label for="reason" class="form-label fw-semibold">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason (if any)"></textarea>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark-blue" id="saveTargetBtn">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Target Modal -->
<div class="modal fade" id="editTargetModal" tabindex="-1" aria-labelledby="editTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTargetModalLabel">Edit Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTargetForm" method="POST">
                <input type="hidden" id="edit_target_id" name="target_id">
                <div class="modal-body">
                    <span id="edit_msg"></span>
                    
                    <div class="row g-3">
                    <div class="col-md-6">
                        <label for="edit_transaction_date" class="form-label fw-semibold">Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_transaction_date" name="transaction_date" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_transaction_number" class="form-label fw-semibold">Transaction Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_transaction_number" name="transaction_number" placeholder="Transaction Number" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Type <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="edit_target_type_business" value="Business Target">
                                <label class="form-check-label" for="edit_target_type_business">Business Target</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="edit_target_type_commission" value="Commission Income Target">
                                <label class="form-check-label" for="edit_target_type_commission">Commission Income Target</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_target_month" class="form-label fw-semibold">Target Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="edit_target_month" name="target_month" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_department_id" class="form-label fw-semibold">Select Department <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-start"><div class="flex-grow-1 overflow-hidden"><select class="form-select select2-department" id="edit_department_id" name="department_id" required>
                            <option value="">Search for target department</option>
                            <option value="1">General Insurance Target</option>
                            <option value="2">Investment Target</option>
                            <option value="3">Life Insurance Target</option>
                        </select></div><button type="button" class="btn btn-dark-blue quick-add-department" data-select="edit_department_id" title="Add department"><i class="fas fa-plus"></i></button></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_factor_id" class="form-label fw-semibold">Select Factor <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-start"><div class="flex-grow-1 overflow-hidden"><select class="form-select select2-factor" id="edit_factor_id" name="factor_id" required>
                            <option value="">Search for target factor</option>
                            <option value="1" data-department="1">Gen. Ins. New Business</option>
                            <option value="4" data-department="1">Gen. Ins. Ren. Business</option>
                            <option value="5" data-department="2">MF-SIP New Business</option>
                            <option value="6" data-department="2">MF-Lumpsum New Business</option>
                            <option value="3" data-department="3">Life Ins. New Business</option>
                            <option value="2" data-department="3">Life Ins. Ren. Business</option>
                        </select></div><button type="button" class="btn btn-dark-blue quick-add-factor" data-department="edit_department_id" data-select="edit_factor_id" title="Add factor"><i class="fas fa-plus"></i></button></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_target_amount" class="form-label fw-semibold">Target Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_target_amount" name="target_amount" placeholder="Target Amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_achieved_amount" class="form-label fw-semibold">Achieved Amount</label>
                        <input type="number" class="form-control" id="edit_achieved_amount" name="achieved_amount" placeholder="Achieved Amount" step="0.01" min="0" value="0">
                    </div>
                    
                    <div class="col-12">
                        <label for="edit_reason" class="form-label fw-semibold">Reason</label>
                        <textarea class="form-control" id="edit_reason" name="reason" rows="3" placeholder="Enter reason (if any)"></textarea>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark-blue" id="updateTargetBtn">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Target Modal -->
<div class="modal fade" id="viewTargetModal" tabindex="-1" aria-labelledby="viewTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTargetModalLabel">View Target Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Transaction Date:</strong>
                        <p id="view_transaction_date">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Transaction Number:</strong>
                        <p id="view_transaction_number">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Target Type:</strong>
                        <p id="view_target_type">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Target Month:</strong>
                        <p id="view_target_month">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Department:</strong>
                        <p id="view_department">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Factor:</strong>
                        <p id="view_factor">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Target Amount:</strong>
                        <p id="view_target_amount">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Achieved Amount:</strong>
                        <p id="view_achieved_amount">--</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Difference Amount:</strong>
                        <p id="view_difference_amount">--</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
    const baseUrl = '{{ url('/') }}/';
    
    // Initialize DataTable
    const table = $('#TargetReviewTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: baseUrl + 'targetandreview/list',
            data: function(d) {
                // Send filter parameters to server
                d.from_month = $('#from_month').val() || '';
                d.to_month = $('#to_month').val() || '';
            },
            dataSrc: function(json) {
                // console.log('Received data:', json);
                // console.log('Total records:', json.data ? json.data.length : 0);
                return json.data;
            }
        },
        columns: [
            { 
                data: 'transaction_date',
                render: function(data) {
                    if (!data || data === '--') return '--';
                    const date = new Date(data);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    return `${day}-${month}-${year}`;
                }
            },
            { data: 'transaction_num' },
            { data: 'target_month', className: 'd-none d-md-table-cell' },
            { data: 'target_type', className: 'd-none d-md-table-cell' },
            { data: 'department', className: 'd-none d-md-table-cell' },
            { data: 'factor', className: 'd-none d-md-table-cell' },
            { 
                data: 'target_amount',
                className: 'd-none d-md-table-cell',
                render: function(data) {
                    return data ? parseFloat(data).toLocaleString('en-IN') : '0';
                }
            },
            { 
                data: 'achieved_amount',
                className: 'd-none d-md-table-cell',
                render: function(data) {
                    return data ? parseFloat(data).toLocaleString('en-IN') : '0';
                }
            },
            { 
                data: null,
                className: 'd-none d-md-table-cell',
                render: function(data, type, row) {
                    const target = parseFloat(row.target_amount || 0);
                    const achieved = parseFloat(row.achieved_amount || 0);
                    const difference = achieved - target;
                    return difference.toLocaleString('en-IN');
                }
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-end pe-4 d-none d-md-table-cell',
                render: function(id, type, row) {
                    return `
                        <div class="d-inline-flex align-items-center gap-2">
                            <button type="button" class="btn crm-action-btn btn-sm viewTarget" data-id="${id}" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button type="button" class="btn crm-action-btn btn-sm text-danger deleteTarget" data-id="${id}" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <button type="button" class="btn crm-action-btn btn-sm editTarget" data-id="${id}" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
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
                        <button type="button" class="btn-user-expand" data-id="${id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    `;
                }
            }
        ],
        ordering: false,
        language: {
            emptyTable: `<div style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                <i class="fa-solid fa-bullseye d-block" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                <p class="mb-3">No targets found.</p>
                <button type="button" class="btn btn-dark-blue btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addTargetModal">Add Target</button>
            </div>`
        },
        order: [],
        pageLength: 10,
        lengthChange: false,
        info: true,
        autoWidth: false,
        dom: 'rt<"card-footer border-top-0 py-3 px-4 d-flex justify-content-between align-items-center"ip>',
        drawCallback: function(settings) {
            // Calculate totals
            let totalTarget = 0;
            let totalAchieved = 0;
            let totalDifference = 0;

            // Create DataTable API instance from settings
            const api = new $.fn.dataTable.Api(settings);
            api.rows({ search: 'applied' }).data().each(function(row) {
                totalTarget += parseFloat(row.target_amount || 0);
                totalAchieved += parseFloat(row.achieved_amount || 0);
            });

            totalDifference = totalAchieved - totalTarget;

            $('#total_target_amount').text(totalTarget.toLocaleString('en-IN'));
            $('#total_achieved_amount').text(totalAchieved.toLocaleString('en-IN'));
            $('#total_difference_amount').text(totalDifference.toLocaleString('en-IN'));
            
            // Re-bind expand button click
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
                    const target = parseFloat(data.target_amount || 0);
                    const achieved = parseFloat(data.achieved_amount || 0);
                    const difference = achieved - target;
                    
                    const childHtml = `
                        <div class="details-content mb-3 mx-2">
                            <div class="row g-2">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar me-1"></i> Target Month:</div>
                                    <div class="expand-value text-muted fw-normal">${data.target_month || '--'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-bullseye me-1"></i> Target Type:</div>
                                    <div class="expand-value fw-normal">${data.target_type || '--'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-building me-1"></i> Department:</div>
                                    <div class="expand-value">${data.department || '--'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-layer-group me-1"></i> Factor:</div>
                                    <div class="expand-value">${data.factor || '--'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign me-1"></i> Target Amt:</div>
                                    <div class="expand-value fw-normal">${target ? target.toLocaleString('en-IN') : '0'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-chart-line me-1"></i> Achieved Amt:</div>
                                    <div class="expand-value fw-normal">${achieved ? achieved.toLocaleString('en-IN') : '0'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div class="expand-label"><i class="fa-solid fa-calculator me-1"></i> Difference Amt:</div>
                                    <div class="expand-value text-primary">${difference.toLocaleString('en-IN')}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center mt-1">
                                    <div class="expand-label text-uppercase fw-bold"><i class="fa-solid fa-gear me-1"></i> ACTIONS :</div>
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button type="button" class="btn crm-action-btn btn-sm viewTarget" data-id="${data.id}" title="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn crm-action-btn btn-sm editTarget" data-id="${data.id}" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn crm-action-btn btn-sm text-danger deleteTarget" data-id="${data.id}" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
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

    // Search
    $('#targetSearch').on('keyup', function() {
        table.search(this.value).draw();
    });


    // Function to reload table with filters (server-side filtering)
    function applyMonthFilter() {
        // Reload DataTable which will send filter parameters to server
        table.ajax.reload();
    }

    // Month filter on change and input (for better compatibility)
    $('#from_month, #to_month').on('change input', function() {
        // Small delay to ensure value is set
        setTimeout(function() {
            applyMonthFilter();
        }, 100);
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#from_month').val('');
        $('#to_month').val('');
        $('#targetSearch').val('');
        
        // Reload table without filters
        table.search('').ajax.reload();
    });

    // Add Target Form Submit
    $('#saveTargetBtn').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        let formData = $('#addTargetForm').serialize();
        
        // Add CSRF token
                
        $.ajax({
            url: baseUrl + 'targetandreview/store',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                $('#add_msg').html('').removeClass('alert alert-danger');
                $('#addTargetForm .is-invalid').removeClass('is-invalid');
                $('#addTargetForm .invalid-feedback').remove();
            },
            success: function(res) {
                if (res.status === true) {
                    window.showAlert('success', res.message || 'Target added successfully');
                    $('#addTargetModal').modal('hide');
                    $('#addTargetForm')[0].reset();
                    table.ajax.reload();
                } else {
                    let errorMsg = res.message || 'Validation failed';
                    $('#add_msg').html(errorMsg).addClass('alert alert-danger');
                    if (res.errors) {
                        $.each(res.errors, function(key, value) {
                            const $el = $('#addTargetForm [name="' + key + '"]');
                            if($el.length) {
                                $el.addClass('is-invalid');
                                if ($el.hasClass('select2-hidden-accessible')) {
                                    $el.next('.select2-container').addClass('is-invalid');
                                }
                                $el.parent().append('<div class="invalid-feedback d-block">' + (Array.isArray(value) ? value[0] : value) + '</div>');
                            }
                        });
                    }
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred while adding the target.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#add_msg').html(errorMsg).addClass('alert alert-danger');
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        const $el = $('#addTargetForm [name="' + key + '"]');
                        if($el.length) {
                            $el.addClass('is-invalid');
                            if ($el.hasClass('select2-hidden-accessible')) {
                                $el.next('.select2-container').addClass('is-invalid');
                            }
                            $el.parent().append('<div class="invalid-feedback d-block">' + (Array.isArray(value) ? value[0] : value) + '</div>');
                        }
                    });
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
            }
        });
    });

    // View button
    $(document).on('click', '.viewTarget', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: baseUrl + 'targetandreview/view/' + id,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === true && res.data) {
                    const data = res.data;
                    $('#view_transaction_date').text(data.transaction_date || '--');
                    $('#view_transaction_number').text(data.transaction_number || '--');
                    $('#view_target_type').text(data.target_type || '--');
                    $('#view_target_month').text(data.target_month || '--');
                    $('#view_department').text(data.department || '--');
                    $('#view_factor').text(data.factor || '--');
                    $('#view_target_amount').text(data.target_amount ? parseFloat(data.target_amount).toLocaleString('en-IN') : '--');
                    $('#view_achieved_amount').text(data.achieved_amount ? parseFloat(data.achieved_amount).toLocaleString('en-IN') : '--');
                    
                    const target = parseFloat(data.target_amount || 0);
                    const achieved = parseFloat(data.achieved_amount || 0);
                    const difference = achieved - target;
                    $('#view_difference_amount').text(difference.toLocaleString('en-IN'));
                    
                    $('#viewTargetModal').modal('show');
                } else {
                    window.showAlert('error', res.message || 'Failed to load target details');
                }
            },
            error: function() {
                window.showAlert('error', 'An error occurred while loading target details.');
            }
        });
    });

    // Edit button
    $(document).on('click', '.editTarget', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: baseUrl + 'targetandreview/edit/' + id,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === true && res.data) {
                    const data = res.data;
                    $('#edit_target_id').val(data.id);
                    $('#edit_transaction_date').val(data.transaction_date || '');
                    $('#edit_transaction_number').val(data.transaction_number || '');
                    
                    // Set target type radio button
                    $('input[name="target_type"]').prop('checked', false);
                    if (data.target_type === 'Business Target') {
                        $('#edit_target_type_business').prop('checked', true);
                    } else if (data.target_type === 'Commission Income Target') {
                        $('#edit_target_type_commission').prop('checked', true);
                    }
                    
                    $('#edit_target_month').val(data.target_month || '');
                    
                    const departmentId = data.department_id || '';
                    const factorId = data.factor_id || '';
                    $('#edit_factor_id').data('selectedFactor', factorId);
                    populateDepartments('edit_department_id', departmentId);
                    
                    $('#edit_target_amount').val(data.target_amount || '');
                    $('#edit_achieved_amount').val(data.achieved_amount || '0');
                    $('#edit_reason').val(data.reason || '');
                    
                    $('#editTargetModal').modal('show');
                } else {
                    window.showAlert('error', res.message || 'Failed to load target details');
                }
            },
            error: function() {
                window.showAlert('error', 'An error occurred while loading target details.');
            }
        });
    });

    // Edit Target Form Submit
    $('#editTargetForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        const targetId = $('#edit_target_id').val();
        const $btn = $('#updateTargetBtn');
        
        // Add CSRF token
                
        $.ajax({
            url: baseUrl + 'targetandreview/update/' + targetId,
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                $('#edit_msg').html('').removeClass('alert alert-danger');
                $('#editTargetForm .is-invalid').removeClass('is-invalid');
                $('#editTargetForm .invalid-feedback').remove();
            },
            success: function(res) {
                if (res.status === true) {
                    window.showAlert('success', res.message || 'Target updated successfully');
                    $('#editTargetModal').modal('hide');
                    $('#editTargetForm')[0].reset();
                    table.ajax.reload();
                } else {
                    let errorMsg = res.message || 'Validation failed';
                    $('#edit_msg').html(errorMsg).addClass('alert alert-danger');
                    if (res.errors) {
                        $.each(res.errors, function(key, value) {
                            const $el = $('#editTargetForm [name="' + key + '"]');
                            if($el.length) {
                                $el.addClass('is-invalid');
                                if ($el.hasClass('select2-hidden-accessible')) {
                                    $el.next('.select2-container').addClass('is-invalid');
                                }
                                $el.parent().append('<div class="invalid-feedback d-block">' + (Array.isArray(value) ? value[0] : value) + '</div>');
                            }
                        });
                    }
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred while updating the target.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#edit_msg').html(errorMsg).addClass('alert alert-danger');
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        const $el = $('#editTargetForm [name="' + key + '"]');
                        if($el.length) {
                            $el.addClass('is-invalid');
                            if ($el.hasClass('select2-hidden-accessible')) {
                                $el.next('.select2-container').addClass('is-invalid');
                            }
                            $el.parent().append('<div class="invalid-feedback d-block">' + (Array.isArray(value) ? value[0] : value) + '</div>');
                        }
                    });
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
            }
        });
    });

    // Reset form when modal is closed
    $('#addTargetModal').on('hidden.bs.modal', function() {
        $('#addTargetForm')[0].reset();
        $('#add_msg').html('').removeClass('alert alert-danger');
        // Clear Select2 selections
        $('#department_id, #factor_id').val(null).trigger('change');
    });

    $('#editTargetModal').on('hidden.bs.modal', function() {
        $('#editTargetForm')[0].reset();
        $('#edit_msg').html('').removeClass('alert alert-danger');
        // Clear Select2 selections
        $('#edit_department_id, #edit_factor_id').val(null).trigger('change');
    });

    // Delete button
    $(document).on('click', '.deleteTarget', function() {
        const id = $(this).data('id');
        const $btn = $(this);

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this target? This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + 'targetandreview/delete/' + id,
                    type: "POST",
                                        dataType: "json",
                    beforeSend: function() {
                        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(res) {
                        if (res.status === true) {
                            window.showAlert('success', res.message || 'Target deleted');
                            table.ajax.reload();
                        } else {
                            window.showAlert('error', res.message || 'Error deleting target');
                            $btn.prop('disabled', false).html('<i class="bi bi-trash text-danger"></i>');
                        }
                    },
                    error: function() {
                        window.showAlert('error', 'An error occurred while deleting the target. Please try again.');
                        $btn.prop('disabled', false).html('<i class="bi bi-trash text-danger"></i>');
                    }
                });
            }
        });
    });

    // View Reason button
    $(document).on('click', '.viewReason', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: baseUrl + 'targetandreview/view/' + id,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === true && res.data) {
                    const reason = res.data.reason || 'No reason provided';
                    Swal.fire({
                        icon: 'info',
                        title: 'Reason',
                        text: reason,
                        confirmButtonText: 'Close'
                    });
                } else {
                    window.showAlert('error', res.message || 'Failed to load reason');
                }
            },
            error: function() {
                window.showAlert('error', 'An error occurred while loading the reason.');
            }
        });
    });

    function initSelect2(selector, placeholder, dropdownParentSelector) {
        const $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            placeholder: placeholder,
            allowClear: true,
            minimumInputLength: 0,
            dropdownParent: $(dropdownParentSelector),
            language: {
                noResults: function() {
                    return 'No results found';
                }
            }
        });
    }

    function populateDepartments(selectId, selectedValue = '') {
        const $department = $('#' + selectId);
        $department.html('<option value="">Search for target department</option>');

        $.ajax({
            url: baseUrl + 'targetandreview/departments',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === true && Array.isArray(res.data)) {
                    res.data.forEach(function(item) {
                        $department.append(`<option value="${item.id}">${item.name}</option>`);
                    });
                }

                if (selectedValue) {
                    $department.val(String(selectedValue));
                } else {
                    $department.val('');
                }
                $department.trigger('change');
            },
            error: function() {
                $department.trigger('change');
            }
        });
    }

    function populateFactors(departmentId, factorSelectId, selectedFactor = null) {
        const $factor = $('#' + factorSelectId);
        const pendingSelectedFactor = selectedFactor !== null ? selectedFactor : ($factor.data('selectedFactor') || '');
        $factor.removeData('selectedFactor');
        $factor.html('<option value="">Search for target factor</option>');

        if (!departmentId) {
            $factor.trigger('change');
            return;
        }

        $.ajax({
            url: baseUrl + 'targetandreview/factors',
            type: 'GET',
            data: { department_id: departmentId },
            dataType: 'json',
            success: function(res) {
                if (res.status === true && Array.isArray(res.data)) {
                    res.data.forEach(function(item) {
                        $factor.append(`<option value="${item.id}">${item.name}</option>`);
                    });
                }

                if (pendingSelectedFactor) {
                    $factor.val(String(pendingSelectedFactor));
                } else {
                    $factor.val('');
                }
                $factor.trigger('change');
            },
            error: function() {
                $factor.trigger('change');
            }
        });
    }

    // Initialize Select2 and load dynamic options
    initSelect2('#department_id', 'Search for target department', '#addTargetModal');
    initSelect2('#factor_id', 'Search for target factor', '#addTargetModal');
    initSelect2('#edit_department_id', 'Search for target department', '#editTargetModal');
    initSelect2('#edit_factor_id', 'Search for target factor', '#editTargetModal');

    populateDepartments('department_id');
    populateDepartments('edit_department_id');

    $('#department_id').on('change', function() {
        populateFactors($(this).val(), 'factor_id');
    });

    $('#edit_department_id').on('change', function() {
        populateFactors($(this).val(), 'edit_factor_id');
    });

    $(document).on('click', '.quick-add-department', async function() {
        const selectId = $(this).data('select');
        const activeModal = $(this).closest('.modal')[0];
        const result = await Swal.fire({
            target: activeModal,
            title: 'Add Target Department',
            input: 'text',
            inputLabel: 'Department name',
            inputPlaceholder: 'Enter department name',
            showCancelButton: true,
            confirmButtonText: 'Add',
            inputValidator: value => !(value || '').trim() ? 'Department name is required.' : undefined
        });

        if (!result.isConfirmed) return;

        $.post(baseUrl + 'targetandreview/departments', {
            _token: '{{ csrf_token() }}',
            name: result.value.trim()
        }).done(function(res) {
            populateDepartments(selectId, res.data.id);
            Swal.fire({ target: activeModal, icon: 'success', title: 'Added', text: res.message, timer: 1400, showConfirmButton: false });
        }).fail(function(xhr) {
            Swal.fire({ target: activeModal, title: 'Unable to add', text: xhr.responseJSON?.message || 'Please check the department name.', icon: 'error' });
        });
    });

    $(document).on('click', '.quick-add-factor', async function() {
        const departmentId = $('#' + $(this).data('department')).val();
        const factorSelectId = $(this).data('select');
        const activeModal = $(this).closest('.modal')[0];

        if (!departmentId) {
            Swal.fire({ target: activeModal, title: 'Select department', text: 'Please select a department before adding a factor.', icon: 'warning' });
            return;
        }

        const result = await Swal.fire({
            target: activeModal,
            title: 'Add Target Factor',
            input: 'text',
            inputLabel: 'Factor name',
            inputPlaceholder: 'Enter factor name',
            showCancelButton: true,
            confirmButtonText: 'Add',
            inputValidator: value => !(value || '').trim() ? 'Factor name is required.' : undefined
        });

        if (!result.isConfirmed) return;

        $.post(baseUrl + 'targetandreview/factors', {
            _token: '{{ csrf_token() }}',
            department_id: departmentId,
            name: result.value.trim()
        }).done(function(res) {
            populateFactors(departmentId, factorSelectId, res.data.id);
            Swal.fire({ target: activeModal, icon: 'success', title: 'Added', text: res.message, timer: 1400, showConfirmButton: false });
        }).fail(function(xhr) {
            Swal.fire({ target: activeModal, title: 'Unable to add', text: xhr.responseJSON?.message || 'Please check the factor name.', icon: 'error' });
        });
    });
    
    // Auto-generate transaction date and number when Add modal opens
    $('#addTargetModal').on('show.bs.modal', function() {
        // Set today's date
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        const dateStr = `${year}-${month}-${day}`;
        $('#transaction_date').val(dateStr);
        
        // Generate transaction number (format: DDMMYY + random 5 digits)
        const transNum = day + month + String(year).substring(2) + Math.floor(10000 + Math.random() * 90000);
        $('#transaction_number').val(transNum);
        
        populateDepartments('department_id');
        populateFactors('', 'factor_id');
    });
    
    $('#editTargetModal').on('hidden.bs.modal', function() {
        populateDepartments('edit_department_id');
        populateFactors('', 'edit_factor_id');
    });

});
</script>


@endpush
