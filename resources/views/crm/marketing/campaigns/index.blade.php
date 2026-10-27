@extends('layouts.app')

@section('page_title', 'Marketing Campaigns')

@push('styles')
    <link rel="stylesheet"
        href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/users.css' : '/css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        @media (max-width: 767.98px) {
            .email-marketing-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }

            .email-marketing-actions {
                width: 100%;
                flex-direction: column;
            }

            .email-marketing-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .email-marketing-modal .modal-dialog {
                margin: 0.75rem;
            }

            .email-marketing-modal .modal-content {
                border-radius: 1rem;
            }

            .email-marketing-modal .text-end {
                text-align: left !important;
            }

            .email-marketing-modal .text-end .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0">

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 email-marketing-header">
                    <div>
                        <h4 class="fw-bold mb-0">Email Marketing</h4>
                        <p class="text-muted small mb-0">Manage sent campaigns and trigger bulk customer emails.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 email-marketing-actions">
                        <a href="{{ route('marketing.templates.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Create Template
                        </a>

                        <button class="btn btn-dark-blue" data-bs-toggle="modal" data-bs-target="#exampleModalToggle">
                            <i class="bi bi-send-fill me-1"></i>Send Email
                        </button>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Campaign History</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0"
                            placeholder="Search campaigns..." id="campaignsSearch">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="campaignsTable" class="table table-hover align-middle mb-0 responsive-table">
                        <thead>
                            <tr>
                                <th class="ps-4 d-none d-md-table-cell">Sr.No</th>
                                <th>Customer</th>
                                <th class="d-none d-md-table-cell">Audience</th>
                                <th class="d-none d-md-table-cell">Send Date</th>
                                <th>Template Name</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="campaignsTableBody"></tbody>
                    </table>
                </div>
                <div id="campaignsPagination" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade email-marketing-modal" id="exampleModalToggle" aria-hidden="true" aria-labelledby="exampleModalToggleLabel"
        tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalToggleLabel">Send Email</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="sendEmailForm">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label m-0">Email Subject</label>
                                <input type="text" name="subject" id="subject" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label m-0">Select Customers</label>
                                <select name="customers[]" id="customers" class="form-select select2" multiple>
                                    <option value="all">Select All</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label m-0">Select Template</label>
                                <select name="template_id" id="template_id" class="form-select">
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 text-end">
                                <button class="btn btn-dark-blue" type="submit">
                                    <i class="bi bi-send-fill me-1"></i>Send Email
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            const tableBody = document.getElementById('campaignsTableBody');
            const paginationContainer = document.getElementById('campaignsPagination');
            const searchInput = document.getElementById('campaignsSearch');

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

            function formatDate(value) {
                if (!value) {
                    return '--';
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return escapeHtml(value);
                }

                return date.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function renderStatus(status) {
                const normalized = (status || '').toLowerCase();
                const statusClass = normalized === 'sent'
                    ? 'bg-success text-white'
                    : normalized === 'sending'
                        ? 'bg-warning text-dark'
                        : 'bg-secondary text-white';

                return `<span class="badge rounded-pill px-3 ${statusClass}">${escapeHtml(status || '--')}</span>`;
            }

            function renderRows(items, meta) {
                if (!items || !items.length) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-envelope-paper display-1 opacity-25"></i></div>
                                <p class="text-muted">No campaigns found.</p>
                            </td>
                        </tr>`;
                    return;
                }

                tableBody.innerHTML = items.map((campaign, index) => {
                    const rowNumber = meta && meta.from ? meta.from + index : index + 1;
                    const templateName = campaign.template?.name || '--';
                    const sendDate = formatDate(campaign.sent_at || campaign.created_at);
                    const statusHtml = renderStatus(campaign.status);

                    return `
                        <tr>
                            <td class="ps-4 d-none d-md-table-cell">${rowNumber}</td>
                            <td>
                                <div class="fw-bold small">${escapeHtml(campaign.name || '--')}</div>
                            </td>
                            <td class="d-none d-md-table-cell">${escapeHtml(campaign.audience_type || '--')}</td>
                            <td class="d-none d-md-table-cell">${sendDate}</td>
                            <td>${escapeHtml(templateName)}</td>
                            <td class="d-none d-md-table-cell">${statusHtml}</td>
                            <td class="text-end pe-4 d-none d-md-table-cell">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn crm-action-btn btn-sm text-danger delete-campaign"
                                        data-url="{{ url('marketing/campaigns') }}/${campaign.id}" title="Delete">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center d-md-none">
                                <button type="button" class="btn-user-expand" data-campaign-id="${campaign.id}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="details-row d-md-none border-0" id="details-${campaign.id}" style="display: none;">
                            <td colspan="8" class="p-0 border-0">
                                <div class="details-content">
                                    <div class="row g-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-hashtag"></i> Sr.No :</div>
                                            <div class="expand-value">${rowNumber}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-users"></i> Audience :</div>
                                            <div class="expand-value">${escapeHtml(campaign.audience_type || '--')}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Send Date :</div>
                                            <div class="expand-value">${sendDate}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                            <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                            <div class="expand-value">${statusHtml}</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                            <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn crm-action-btn btn-sm text-danger delete-campaign"
                                                    data-url="{{ url('marketing/campaigns') }}/${campaign.id}">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                tableBody.querySelectorAll('.delete-campaign').forEach((button) => {
                    button.addEventListener('click', function () {
                        deleteCampaign(this.dataset.url);
                    });
                });

                tableBody.querySelectorAll('.btn-user-expand').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.campaignId;
                        const detailsRow = document.getElementById(`details-${id}`);
                        const icon = this.querySelector('i');

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
                if (!paginationContainer || !data || data.total === 0) {
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

                html += data.prev_page_url
                    ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`
                    : '<li class="page-item disabled"><span class="page-link">Previous</span></li>';

                for (let i = 1; i <= lastPage; i++) {
                    if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                        html += i === currentPage
                            ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                            : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    } else if (i === currentPage - 3 || i === currentPage + 3) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                html += data.next_page_url
                    ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`
                    : '<li class="page-item disabled"><span class="page-link">Next</span></li>';

                html += '</ul></div>';
                paginationContainer.innerHTML = html;

                paginationContainer.querySelectorAll('.page-link[data-page]').forEach((link) => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        fetchCampaigns(this.dataset.page);
                    });
                });
            }

            function fetchCampaigns(page = 1) {
                const url = new URL("{{ route('marketing.campaigns.index') }}", window.location.origin);
                url.searchParams.set('page', page);

                if (searchInput.value.trim()) {
                    url.searchParams.set('search', searchInput.value.trim());
                }

                $.ajax({
                    url: url.toString(),
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    beforeSend: function () {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-5">
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
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-5">Error loading campaigns</td>
                            </tr>`;
                        paginationContainer.innerHTML = '';
                    }
                });
            }

            function deleteCampaign(url) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "DELETE",
                            url: url,
                            data: {
                                "_token": "{{ csrf_token() }}",
                            },
                            success: function (response) {
                                showToast(response.message || 'Campaign deleted successfully', 'success');
                                fetchCampaigns();
                            },
                            error: function (error) {
                                showToast('Error deleting campaign', 'error');
                            }
                        });
                    }
                });
            }


            // send email to selected customers
            $('#sendEmailForm').on('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnContent = submitBtn.html();

                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending...');

                $.ajax({
                    type: "POST",
                    url: "{{ route('marketing.templates.bulk_send') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        submitBtn.prop('disabled', false).html(originalBtnContent);
                        if (response.status === 'success') {
                            showToast(response.message, 'success');
                            $('#sendEmailForm')[0].reset();
                            $('#customers').val(null).trigger('change');
                            $('#exampleModalToggle').modal('hide');
                            fetchCampaigns();
                        }
                    },
                    error: function (error) {
                        submitBtn.prop('disabled', false).html(originalBtnContent);
                        if (error.status === 422) {
                            $('.is-invalid').removeClass('is-invalid');
                            $('.invalid-feedback').remove();

                            $.each(error.responseJSON.errors, function (key, value) {
                                var input = $(`#${key}`);
                                input.addClass('is-invalid');
                                input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                            });
                        } else {
                            showToast('Something went wrong. Please try again.', 'error');
                        }
                    }
                });
            });


            $('#customers').select2({
                dropdownParent: $('#exampleModalToggle'),
                placeholder: "Select Customers",
                allowClear: true,
                width: '100%'
            });

            // Select All Logic
            $('#customers').on('select2:select', function (e) {
                if (e.params.data.id === 'all') {
                    let allOptions = $('#customers option[value!="all"]').map(function () {
                        return $(this).val();
                    }).get();
                    $(this).val(allOptions).trigger('change');
                }
            });

            // Reset modal on close
            $('#exampleModalToggle').on('hidden.bs.modal', function () {
                $('#sendEmailForm')[0].reset();
                $('#customers').val(null).trigger('change');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
            });

            let searchTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    fetchCampaigns(1);
                }, 400);
            });

            fetchCampaigns();

        });
    </script>
@endpush
