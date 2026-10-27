@extends('layouts.app')

@section('content')
<style>
    .table-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e9ecef;
        padding: 12px 10px;
        white-space: nowrap;
    }
    
    .table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        font-size: 13px;
        color: #344767;
        border-bottom: 1px solid #e9ecef;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .form-control-sm, .form-select-sm {
        font-size: 12px;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
    }
    
    .action-icon-btn {
        height: 32px;
        width: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        margin-right: 4px;
        font-size: 14px;
    }

    .investment-actions-dropdown {
        position: relative;
        display: inline-block;
    }

    .investment-actions-toggle {
        width: 34px;
        min-width: 34px;
        height: 34px;
        padding: 0;
        border: 1px solid #dce2ea;
        border-radius: 7px;
        color: #0b2341;
        background: #fff;
    }

    .investment-actions-toggle:hover,
    .investment-actions-toggle:focus {
        color: #0b2341;
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .investment-actions-menu {
        min-width: 190px;
        padding: 6px;
        z-index: 2000;
    }

    .investment-action-item {
        display: flex;
        width: 100%;
        min-height: 36px;
        padding: 8px 10px;
        align-items: center;
        gap: 10px;
        border: 0;
        border-radius: 5px;
        color: #17243b;
        background: transparent;
        font-size: 13px;
        text-decoration: none;
        white-space: nowrap;
    }

    .investment-action-item:hover,
    .investment-action-item:focus {
        color: #0b2341;
        background: #f1f5f9;
    }

    .investment-action-item i {
        width: 18px;
        color: #52657d;
        text-align: center;
    }
    
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .filter-tabs .nav-link {
        color: #495057;
        font-weight: 500;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .filter-tabs .nav-link:hover {
        background-color: #f8f9fa;
        color: #5e72d8;
    }
    
    .filter-tabs .nav-link.active {
        background-color: #5e72d8;
        color: #fff;
    }
    
    .btn-dark-blue-custom {
        background: #5e72d8;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }
    
    .btn-dark-blue-custom:hover {
        background: #4e5bd6;
        box-shadow: 0 4px 15px rgba(94, 114, 216, 0.3);
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    
    @media (max-width: 768px) {
        .filter-tabs .nav-link {
            padding: 6px 12px;
            font-size: 12px;
        }
        .h6-cardheader{
            font-size: 14px;
        }
    }
</style>

<div class="container-fluid p-0">
    <div class="row mt-4 mb-4">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card table-card">
                <div class="card-header border-bottom-0 py-3 px-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h4 class="fw-bold mb-0">Investment Management</h4>
                            <p class="text-muted small mb-0">View and manage your investment portfolio details.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('investment.create') }}" class="btn btn-dark-blue">
                                <i class="fa-solid fa-plus me-1"></i>Add Investment
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Top filter tabs -->
                <div class="p-3 border-bottom">
                    <ul class="nav nav-pills mb-0 filter-tabs" role="tablist">
                        <li class="nav-item me-2">
                            <a class="nav-link {{ empty($currentType) ? 'active' : '' }}" href="{{ route('investment.index') }}">
                                <i class="fa-solid fa-list me-1"></i>All
                            </a>
                        </li>
                        <li class="nav-item me-2">
                            <a class="nav-link {{ $currentType === 'mutual_fund' ? 'active' : '' }}" href="{{ route('investment.index', 'mutual_fund') }}">
                                <i class="fa-solid fa-chart-line me-1"></i>Mutual Fund
                            </a>
                        </li>
                        <li class="nav-item me-2">
                            <a class="nav-link {{ $currentType === 'bank' ? 'active' : '' }}" href="{{ route('investment.index', 'bank') }}">
                                <i class="fa-solid fa-building-columns me-1"></i>Bank
                            </a>
                        </li>
                        <li class="nav-item me-2">
                            <a class="nav-link {{ $currentType === 'post_office' ? 'active' : '' }}" href="{{ route('investment.index', 'post_office') }}">
                                <i class="fa-solid fa-envelope-open-text me-1"></i>Post Office
                            </a>
                        </li>
                        <li class="nav-item me-2">
                            <a class="nav-link {{ $currentType === 'govt_bonds' ? 'active' : '' }}" href="{{ route('investment.index', 'govt_bonds') }}">
                                <i class="fa-solid fa-certificate me-1"></i>Bonds
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentType === 'other' ? 'active' : '' }}" href="{{ route('investment.index', 'other') }}">
                                <i class="fa-solid fa-sack-dollar me-1"></i>Other Investment
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Table -->
                <div class="table-responsive p-3">
                    <table class="table table-hover align-middle mb-0" id="investmentTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Action</th>
                                <th style="width: 80px;">Status</th>
                                <th style="width: 110px;">Transaction Date</th>
                                <th style="width: 100px;">Group</th>
                                <th style="width: 150px;">Investment Holder Name</th>
                                <th style="width: 110px;">Mobile No.</th>
                                <th style="width: 110px;">Whatsapp No.</th>
                                <th style="width: 180px;">Email</th>
                                <th style="width: 110px;">Investment Date</th>
                                <th style="width: 120px;">Investment Amt</th>
                                <th style="width: 110px;">Mature Date</th>
                                <th style="width: 120px;">Mature Amt.</th>
                                <th style="width: 120px;">Nominee</th>
                                <th style="width: 150px;">Remark</th>
                                <th style="width: 90px;">Interest %</th>
                                <th style="width: 110px;">Paid By Client</th>
                                <th style="width: 110px;">Paid By Agent</th>
                                <th style="width: 120px;">Created By</th>
                                <th style="width: 130px;">Created On</th>
                                <th style="width: 120px;">Modified By</th>
                                <th style="width: 130px;">Modified On</th>
                            </tr>
                            <!-- Filter row -->
                            <tr style="background-color: #fff;">
                                <th class="p-1">
                                    <input type="text" class="form-control form-control-sm" placeholder="">
                                </th>
                                <th class="p-1">
                                    <select id="statusFilter" class="form-select form-select-sm">
                                        <option value="">All</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                                <th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Search"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (empty($investments) || count($investments) == 0)
                                <tr>
                                    <td colspan="21" class="text-center py-5">
                                        <div style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                                            <i class="fa-solid fa-sack-dollar d-block" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                                            <p class="mb-3">No investments found.</p>
                                            <a href="{{ route('investment.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Investment</a>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @foreach ($investments as $investment)
                                <tr>
                                    <td class="text-center">
                                        <div class="dropdown investment-actions-dropdown">
                                            <button type="button" class="btn investment-actions-toggle"
                                                data-bs-toggle="dropdown" data-bs-boundary="viewport"
                                                aria-expanded="false" aria-label="Investment actions" title="Actions">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-start investment-actions-menu">
                                                <a href="{{ route('investment.show', $investment['id']) }}"
                                                    class="dropdown-item investment-action-item">
                                                    <i class="fa-solid fa-eye"></i>
                                                    <span>View Investment</span>
                                                </a>
                                                <a href="{{ route('investment.edit', $investment['id']) }}"
                                                    class="dropdown-item investment-action-item">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    <span>Edit Investment</span>
                                                </a>
                                                <form action="{{ route('investment.destroy', $investment['id']) }}"
                                                    method="post" class="delete-investment-form">
                                                    @csrf
                                                    <button type="button"
                                                        class="dropdown-item investment-action-item btn-delete-investment"
                                                        data-id="{{ $investment['id'] }}">
                                                        <i class="fa-solid fa-trash"></i>
                                                        <span>Delete Investment</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                    <td>{{ !empty($investment['transaction_date']) ? date('d-m-Y', strtotime($investment['transaction_date'])) : '--' }}</td>
                                    <td>{{ !empty($investment['family_group_name']) ? e($investment['family_group_name']) : (isset($familyGroups[$investment['family_group_id']]) ? e($familyGroups[$investment['family_group_id']]) : '--') }}</td>
                                    <td>{{ !empty(trim($investment['holder_name'] ?? '')) ? e($investment['holder_name']) : (trim(e($investment['first_name'] ?? '') . ' ' . e($investment['middle_name'] ?? '') . ' ' . e($investment['last_name'] ?? '')) ?: '--') }}</td>
                                    <td>{{ !empty(trim($investment['mobile_number'] ?? '')) ? e($investment['mobile_number']) : '--' }}</td>
                                    <td>{{ !empty(trim($investment['mobile_number'] ?? '')) ? e($investment['mobile_number']) : '--' }}</td>
                                    <td>{{ !empty(trim($investment['email_address'] ?? '')) ? e($investment['email_address']) : '--' }}</td>
                                    @php
                                    $investmentDate = $investment['mf_start_date'] ?? $investment['transaction_date'] ?? $investment['bank_deposit_date'] ?? $investment['po_opening_date'] ?? $investment['bond_purchase_date'] ?? $investment['other_deposit_date'] ?? null;
                                    @endphp
                                    <td>{{ !empty($investmentDate) ? date('d-m-Y', strtotime($investmentDate)) : '--' }}</td>
                                    <td>{{ !empty($investment['mf_investment_amount']) ? '₹' . number_format($investment['mf_investment_amount'], 2) : '--' }}</td>
                                    <td>{{ !empty($investment['bank_mature_date']) ? date('d-m-Y', strtotime($investment['bank_mature_date'])) : (!empty($investment['bond_maturity_date']) ? date('d-m-Y', strtotime($investment['bond_maturity_date'])) : '--') }}</td>
                                    <td>{{ !empty($investment['mf_total_fund_value']) ? '₹' . number_format($investment['mf_total_fund_value'], 2) : '--' }}</td>
                                    <td>--</td>
                                    <td>{{ e($investment['mf_remarks'] ?? '--') }}</td>
                                    <td>{{ !empty($investment['bank_interest_rate']) ? $investment['bank_interest_rate'] . '%' : '--' }}</td>
                                    <td>{{ !empty($investment['bank_paid_by_client']) ? '₹' . number_format($investment['bank_paid_by_client'], 2) : '--' }}</td>
                                    <td>{{ !empty($investment['bank_paid_by_agent']) ? '₹' . number_format($investment['bank_paid_by_agent'], 2) : '--' }}</td>
                                    <td>{{ !empty($investment['created_by_name']) ? e($investment['created_by_name']) : '--' }}</td>
                                    <td>{{ !empty($investment['created_at']) ? date('d-m-Y h:i A', strtotime($investment['created_at'])) : '--' }}</td>
                                    <td>{{ !empty($investment['modified_by_name']) ? e($investment['modified_by_name']) : '--' }}</td>
                                    <td>{{ !empty($investment['updated_at']) ? date('d-m-Y h:i A', strtotime($investment['updated_at'])) : '--' }}</td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Move an open action menu outside the horizontally scrolling table so
        // the scroll container cannot clip it. Put it back when it closes.
        document.addEventListener('show.bs.dropdown', function(event) {
            var toggle = event.target;
            if (!toggle.classList.contains('investment-actions-toggle')) return;

            var dropdown = toggle.closest('.investment-actions-dropdown');
            var menu = dropdown ? dropdown.querySelector('.investment-actions-menu') : null;
            if (!menu) return;

            toggle._investmentActionsMenu = menu;
            document.body.appendChild(menu);
        });

        document.addEventListener('hidden.bs.dropdown', function(event) {
            var toggle = event.target;
            if (!toggle.classList.contains('investment-actions-toggle')) return;

            var dropdown = toggle.closest('.investment-actions-dropdown');
            var menu = toggle._investmentActionsMenu;
            if (dropdown && menu) dropdown.appendChild(menu);
            delete toggle._investmentActionsMenu;
        });

        @if (!empty($investments) && count($investments) > 0)
        if (typeof $.fn.DataTable !== 'undefined') {
            // Initialize DataTable
            var table = $('#investmentTable').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: false,
                scrollX: true,
                pageLength: 10,
                language: {
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    },
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    emptyTable: `<div style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                        <i class="fa-solid fa-sack-dollar d-block" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                        <p class="mb-3">No investments found.</p>
                        <a href="{{ route('investment.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Investment</a>
                    </div>`,
                    zeroRecords: `<div style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                        <i class="fa-solid fa-sack-dollar d-block" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                        <p class="mb-3">No matching investments found.</p>
                        <a href="{{ route('investment.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Investment</a>
                    </div>`
                }
            });

            // Column-specific search
            $('#investmentTable thead input').on('keyup change', function() {
                var columnIndex = $(this).closest('th').index();
                table.column(columnIndex).search(this.value).draw();
            });

            // Status filter
            $('#statusFilter').on('change', function() {
                var status = $(this).val();
                var columnIndex = $('#statusFilter').closest('th').index();
                
                if (status === '') {
                    table.column(columnIndex).search('').draw();
                } else {
                    table.column(columnIndex).search('\\b' + status + '\\b', true, false).draw();
                }
            });

        }
        @endif

        // Keep deletion independent of DataTables so it works even when the
        // plugin is unavailable or the dropdown menu is moved outside the table.
        $(document).on('click', '.btn-delete-investment', function (e) {
            e.preventDefault();

            var form = this.closest('form.delete-investment-form');
            if (!form) {
                window.crmAlert('Unable to find the investment delete form.', 'error');
                return;
            }

            window.crmConfirm('This will permanently delete this investment.', {
                title: 'Are you sure?',
                confirmButtonText: 'Yes, delete it'
            }).then(function (result) {
                if (result.isConfirmed) {
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
        });
    });
</script>
@endsection
