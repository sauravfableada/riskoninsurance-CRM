@extends('layouts.app')

@section('page_title', 'Access Requests')

@push('styles')
<link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/main.css' : '/css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
<style>
    .ar-stat-pill {
        border-radius: 12px;
        padding: 0.55rem 0.9rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        min-width: 110px;
    }
    .ar-status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
    }
    .ar-tab-btn.active {
        background-color: #0f172a !important;
        color: #ffffff !important;
        border-color: #0f172a !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Staff Access Requests</h4>
                    <p class="text-muted small mb-0">Review, approve, reject, and revoke individual staff action permissions.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="ar-stat-pill">
                        <div class="text-muted small">Pending</div>
                        <div class="fw-bold fs-5 text-warning" id="statPendingCount">{{ $pendingCount }}</div>
                    </div>
                    <div class="ar-stat-pill">
                        <div class="text-muted small">Approved</div>
                        <div class="fw-bold fs-5 text-success" id="statApprovedCount">0</div>
                    </div>
                    <div class="ar-stat-pill">
                        <div class="text-muted small">Rejected</div>
                        <div class="fw-bold fs-5 text-danger" id="statRejectedCount">0</div>
                    </div>
                    <div class="ar-stat-pill">
                        <div class="text-muted small">Active Grants</div>
                        <div class="fw-bold fs-5 text-primary" id="statActiveGrantsCount">0</div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div class="btn-group" role="group" aria-label="Section tabs">
                    <button type="button" class="btn btn-sm btn-outline-dark ar-tab-btn active" data-section="requests">
                        <i class="bi bi-inbox me-1"></i>Requests
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark ar-tab-btn" data-section="grants">
                        <i class="bi bi-shield-check me-1"></i>Granted Permissions
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark ar-tab-btn" data-section="history">
                        <i class="bi bi-clock-history me-1"></i>Audit History
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 230px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="arSearchInput" class="form-control border-start-0" placeholder="Search staff, module, reason...">
                    </div>
                    <select id="arStatusFilter" class="form-select form-select-sm" style="width: 145px;">
                        <option value="all">All Statuses</option>
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="arModuleFilter" class="form-select form-select-sm" style="width: 170px;">
                        <option value="">All Modules</option>
                        @foreach($modules as $moduleKey => $meta)
                            <option value="{{ $moduleKey }}">{{ $meta['label'] ?? ucfirst($moduleKey) }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="arRefreshBtn" class="btn btn-sm btn-outline-secondary" title="Refresh">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            {{-- Requests Table --}}
            <div id="arSectionRequests">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Staff Member</th>
                                <th>Module &amp; Action</th>
                                <th>Reason</th>
                                <th>Requested At</th>
                                <th>Status</th>
                                <th>Admin Comment</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="arRequestsBody">
                            <tr><td colspan="7" class="text-center py-4 text-muted">Loading access requests...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Active & Revoked Grants Table --}}
            <div id="arSectionGrants" class="d-none">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Staff Member</th>
                                <th>Permission</th>
                                <th>Granted By</th>
                                <th>Granted At</th>
                                <th>Status</th>
                                <th>Revocation Details</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody id="arGrantsBody">
                            <tr><td colspan="7" class="text-center py-4 text-muted">Loading granted permissions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Audit History Table --}}
            <div id="arSectionHistory" class="d-none">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date &amp; Time</th>
                                <th>Event</th>
                                <th>Staff Member</th>
                                <th>Actor</th>
                                <th>Permission</th>
                                <th class="pe-4">Comment</th>
                            </tr>
                        </thead>
                        <tbody id="arHistoryBody">
                            <tr><td colspan="6" class="text-center py-4 text-muted">Loading audit history...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Review Modal (Approve / Reject) --}}
<div class="modal fade" id="arReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="arReviewModalTitle">Review Access Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="arReviewRequestId">
                <input type="hidden" id="arReviewDecision">
                <div class="p-3 bg-light rounded-3 mb-3 small" id="arReviewSummary"></div>
                <div class="mb-2">
                    <label for="arReviewComment" class="form-label fw-semibold small">Admin Comment (Optional)</label>
                    <textarea id="arReviewComment" class="form-control" rows="3" maxlength="1000" placeholder="Add an optional note for the staff member..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="arReviewSubmitBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

{{-- Revoke Modal --}}
<div class="modal fade" id="arRevokeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Revoke Staff Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="arRevokeGrantId">
                <p class="text-muted small mb-3" id="arRevokeSummary"></p>
                <div class="mb-2">
                    <label for="arRevokeReason" class="form-label fw-semibold small">Reason for Revocation (Optional)</label>
                    <textarea id="arRevokeReason" class="form-control" rows="3" maxlength="1000" placeholder="Explain why this permission is being revoked..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="arRevokeSubmitBtn">Revoke Permission</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const listUrl = '{{ route("admin.access-requests.list") }}';
    let currentSection = 'requests';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function statusBadge(status) {
        if (status === 'approved') return '<span class="ar-status-badge bg-success-subtle text-success border border-success-subtle">Approved</span>';
        if (status === 'rejected') return '<span class="ar-status-badge bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>';
        return '<span class="ar-status-badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pending</span>';
    }

    function eventBadge(event) {
        const map = {
            requested: 'bg-info-subtle text-info-emphasis',
            approved: 'bg-success-subtle text-success',
            rejected: 'bg-danger-subtle text-danger',
            revoked: 'bg-secondary-subtle text-secondary',
        };
        const cls = map[event] || 'bg-light text-dark';
        return `<span class="badge ${cls} rounded-pill px-3 text-capitalize">${escapeHtml(event)}</span>`;
    }

    function fetchData() {
        const params = new URLSearchParams({
            status: document.getElementById('arStatusFilter').value || 'all',
            module: document.getElementById('arModuleFilter').value || '',
            search: document.getElementById('arSearchInput').value.trim(),
        });

        fetch(`${listUrl}?${params.toString()}`, {
            headers: window.crmApplyAuthHeaders({ 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }),
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const { requests, grants, histories, counts } = res.data;

            document.getElementById('statPendingCount').textContent = counts.pending ?? 0;
            document.getElementById('statApprovedCount').textContent = counts.approved ?? 0;
            document.getElementById('statRejectedCount').textContent = counts.rejected ?? 0;
            document.getElementById('statActiveGrantsCount').textContent = counts.active_grants ?? 0;

            const sidebarBadge = document.getElementById('sidebarPendingAccessCount');
            if (sidebarBadge) {
                sidebarBadge.textContent = counts.pending ?? 0;
                sidebarBadge.classList.toggle('d-none', !(counts.pending > 0));
            }

            renderRequests(requests || []);
            renderGrants(grants || []);
            renderHistory(histories || []);
        })
        .catch(() => {});
    }

    function renderRequests(items) {
        const tbody = document.getElementById('arRequestsBody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No access requests found for the selected filters.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(item => {
            let actionButtons = '';
            if (item.status === 'pending') {
                actionButtons = `
                    <div class="d-inline-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success ar-open-review" data-id="${item.id}" data-decision="approve" data-staff="${escapeHtml(item.staff_name)}" data-perm="${escapeHtml(item.permission_label)}">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger ar-open-review" data-id="${item.id}" data-decision="reject" data-staff="${escapeHtml(item.staff_name)}" data-perm="${escapeHtml(item.permission_label)}">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    </div>`;
            } else if (item.status === 'approved' && item.grant_id && item.grant_is_active) {
                actionButtons = `
                    <button type="button" class="btn btn-sm btn-outline-danger ar-open-revoke" data-grant-id="${item.grant_id}" data-staff="${escapeHtml(item.staff_name)}" data-perm="${escapeHtml(item.permission_label)}">
                        <i class="bi bi-shield-slash me-1"></i>Revoke
                    </button>`;
            } else {
                actionButtons = `<span class="text-muted small">${escapeHtml(item.reviewed_by_name ? 'By ' + item.reviewed_by_name : '--')}</span>`;
            }

            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold">${escapeHtml(item.staff_name)}</div>
                        <div class="text-muted small">${escapeHtml(item.staff_email)}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(item.action_label)}</div>
                        <div class="text-muted small">${escapeHtml(item.module_label)} <code class="ms-1">${escapeHtml(item.permission)}</code></div>
                    </td>
                    <td style="max-width: 260px;"><div class="small text-wrap">${escapeHtml(item.reason)}</div></td>
                    <td class="small text-muted">${escapeHtml(item.requested_at || '--')}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td style="max-width: 200px;"><div class="small text-muted text-wrap">${escapeHtml(item.admin_comment || '--')}</div></td>
                    <td class="text-end pe-4">${actionButtons}</td>
                </tr>`;
        }).join('');
    }

    function renderGrants(items) {
        const tbody = document.getElementById('arGrantsBody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No staff permission grants recorded yet.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(grant => {
            const status = grant.is_active
                ? '<span class="ar-status-badge bg-success-subtle text-success border border-success-subtle">Active</span>'
                : '<span class="ar-status-badge bg-secondary-subtle text-secondary border border-secondary-subtle">Revoked</span>';

            const revDetails = grant.is_active
                ? '<span class="text-muted small">--</span>'
                : `<div class="small text-muted">By ${escapeHtml(grant.revoked_by_name || 'Admin')} on ${escapeHtml(grant.revoked_at || '--')}${grant.revocation_reason ? '<br>' + escapeHtml(grant.revocation_reason) : ''}</div>`;

            const action = grant.is_active
                ? `<button type="button" class="btn btn-sm btn-outline-danger ar-open-revoke" data-grant-id="${grant.id}" data-staff="${escapeHtml(grant.staff_name)}" data-perm="${escapeHtml(grant.permission_label)}"><i class="bi bi-shield-slash me-1"></i>Revoke</button>`
                : '<span class="text-muted small">Revoked</span>';

            return `
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold">${escapeHtml(grant.staff_name)}</div>
                        <div class="text-muted small">${escapeHtml(grant.staff_email)}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(grant.permission_label)}</div>
                        <code class="small">${escapeHtml(grant.permission)}</code>
                    </td>
                    <td class="small">${escapeHtml(grant.granted_by_name)}</td>
                    <td class="small text-muted">${escapeHtml(grant.granted_at || '--')}</td>
                    <td>${status}</td>
                    <td>${revDetails}</td>
                    <td class="text-end pe-4">${action}</td>
                </tr>`;
        }).join('');
    }

    function renderHistory(items) {
        const tbody = document.getElementById('arHistoryBody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No audit history recorded yet.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(h => `
            <tr>
                <td class="ps-4 small text-muted">${escapeHtml(h.created_at || '--')}</td>
                <td>${eventBadge(h.event)}</td>
                <td class="fw-semibold small">${escapeHtml(h.staff_name)}</td>
                <td class="small">${escapeHtml(h.actor_name)}</td>
                <td><span class="small fw-semibold">${escapeHtml(h.permission_label)}</span> <code class="small ms-1">${escapeHtml(h.permission)}</code></td>
                <td class="pe-4 small text-muted">${escapeHtml(h.comment || '--')}</td>
            </tr>
        `).join('');
    }

    document.querySelectorAll('.ar-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.ar-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentSection = this.dataset.section;
            document.getElementById('arSectionRequests').classList.toggle('d-none', currentSection !== 'requests');
            document.getElementById('arSectionGrants').classList.toggle('d-none', currentSection !== 'grants');
            document.getElementById('arSectionHistory').classList.toggle('d-none', currentSection !== 'history');
            document.getElementById('arStatusFilter').classList.toggle('d-none', currentSection !== 'requests');
        });
    });

    document.getElementById('arStatusFilter').addEventListener('change', fetchData);
    document.getElementById('arModuleFilter').addEventListener('change', fetchData);
    document.getElementById('arRefreshBtn').addEventListener('click', fetchData);

    let searchTimer;
    document.getElementById('arSearchInput').addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchData, 250);
    });

    document.addEventListener('click', function (e) {
        const reviewBtn = e.target.closest('.ar-open-review');
        if (reviewBtn) {
            const decision = reviewBtn.dataset.decision;
            document.getElementById('arReviewRequestId').value = reviewBtn.dataset.id;
            document.getElementById('arReviewDecision').value = decision;
            document.getElementById('arReviewComment').value = '';
            document.getElementById('arReviewModalTitle').textContent = decision === 'approve' ? 'Approve Access Request' : 'Reject Access Request';
            document.getElementById('arReviewSummary').innerHTML = `<strong>Staff:</strong> ${escapeHtml(reviewBtn.dataset.staff)}<br><strong>Requested Permission:</strong> ${escapeHtml(reviewBtn.dataset.perm)}`;

            const submitBtn = document.getElementById('arReviewSubmitBtn');
            submitBtn.className = decision === 'approve' ? 'btn btn-success' : 'btn btn-danger';
            submitBtn.textContent = decision === 'approve' ? 'Approve & Grant Access' : 'Reject Request';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('arReviewModal')).show();
            return;
        }

        const revokeBtn = e.target.closest('.ar-open-revoke');
        if (revokeBtn) {
            document.getElementById('arRevokeGrantId').value = revokeBtn.dataset.grantId;
            document.getElementById('arRevokeReason').value = '';
            document.getElementById('arRevokeSummary').innerHTML = `Revoke <strong>${escapeHtml(revokeBtn.dataset.perm)}</strong> from <strong>${escapeHtml(revokeBtn.dataset.staff)}</strong>? The action will immediately become restricted again.`;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('arRevokeModal')).show();
        }
    });

    document.getElementById('arReviewSubmitBtn').addEventListener('click', function () {
        const id = document.getElementById('arReviewRequestId').value;
        const decision = document.getElementById('arReviewDecision').value;
        const comment = document.getElementById('arReviewComment').value;

        this.disabled = true;
        fetch(`/admin/access-requests/${id}/${decision}`, {
            method: 'POST',
            headers: window.crmApplyAuthHeaders({
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            }),
            body: JSON.stringify({ comment }),
        })
        .then(r => r.json())
        .then(res => {
            bootstrap.Modal.getInstance(document.getElementById('arReviewModal'))?.hide();
            if (typeof window.showAlert === 'function') {
                window.showAlert(res.success ? 'success' : 'error', res.message || 'Done');
            }
            fetchData();
        })
        .finally(() => {
            this.disabled = false;
        });
    });

    document.getElementById('arRevokeSubmitBtn').addEventListener('click', function () {
        const grantId = document.getElementById('arRevokeGrantId').value;
        const reason = document.getElementById('arRevokeReason').value;

        this.disabled = true;
        fetch(`/admin/access-requests/grants/${grantId}/revoke`, {
            method: 'POST',
            headers: window.crmApplyAuthHeaders({
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            }),
            body: JSON.stringify({ reason }),
        })
        .then(r => r.json())
        .then(res => {
            bootstrap.Modal.getInstance(document.getElementById('arRevokeModal'))?.hide();
            if (typeof window.showAlert === 'function') {
                window.showAlert(res.success ? 'success' : 'error', res.message || 'Done');
            }
            fetchData();
        })
        .finally(() => {
            this.disabled = false;
        });
    });

    fetchData();
})();
</script>
@endpush
