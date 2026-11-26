(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const statusUrl = '/access-requests/my-status';
    const storeUrl = '/access-requests';

    const ACTION_LABELS = {
        create: 'Add / Create',
        edit: 'Edit',
        delete: 'Delete',
        export: 'Export',
        view: 'View',
        approve: 'Approve',
    };

    let state = {
        isAdmin: false,
        permissions: window.crmUserPermissions || {},
        requests: [],
        loaded: false,
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeKey(value) {
        return String(value || '').toLowerCase().trim().replace(/-/g, '_');
    }

    function hasPermission(module, action) {
        if (state.isAdmin) return true;
        const mod = normalizeKey(module);
        const act = normalizeKey(action);
        return Boolean(state.permissions?.[mod]?.[act]);
    }

    function findLatestRequest(module, action, recordId) {
        const mod = normalizeKey(module);
        const act = normalizeKey(action);
        const rec = recordId !== undefined && recordId !== null && recordId !== '' ? Number(recordId) : null;

        return state.requests.find(function (req) {
            if (normalizeKey(req.module) !== mod || normalizeKey(req.action) !== act) {
                return false;
            }
            if (rec !== null) {
                return req.record_id === rec || req.record_id === null;
            }
            return true;
        }) || null;
    }

    function syncDeclarativeWrappers() {
        document.querySelectorAll('[data-crm-perm-wrap]').forEach(function (wrap) {
            const module = wrap.getAttribute('data-module');
            const action = wrap.getAttribute('data-action');
            const recordId = wrap.getAttribute('data-record-id') || null;
            const allowedEl = wrap.querySelector('.crm-perm-allowed');
            const restrictedEl = wrap.querySelector('.crm-perm-restricted');

            if (!allowedEl || !restrictedEl) return;

            const allowed = hasPermission(module, action);
            allowedEl.classList.toggle('d-none', !allowed);
            restrictedEl.classList.toggle('d-none', allowed);

            if (!allowed) {
                const latest = findLatestRequest(module, action, recordId);
                const reqBtn = restrictedEl.querySelector('[data-crm-request-access]');
                const pendingBadge = restrictedEl.querySelector('.crm-perm-pending');
                const rejectedBadge = restrictedEl.querySelector('.crm-perm-rejected');

                if (latest && latest.status === 'pending') {
                    reqBtn?.classList.add('d-none');
                    pendingBadge?.classList.remove('d-none');
                    rejectedBadge?.classList.add('d-none');
                } else {
                    reqBtn?.classList.remove('d-none');
                    pendingBadge?.classList.add('d-none');
                    if (rejectedBadge) {
                        const isRejected = latest && latest.status === 'rejected';
                        rejectedBadge.classList.toggle('d-none', !isRejected);
                        if (isRejected) {
                            rejectedBadge.setAttribute('title', latest.admin_comment || 'Request rejected');
                        }
                    }
                }
            }
        });

        document.querySelectorAll('[data-crm-module-bar]').forEach(function (bar) {
            const module = bar.getAttribute('data-module');
            let actions = [];
            try {
                actions = JSON.parse(bar.getAttribute('data-actions') || '[]');
            } catch (e) {
                actions = [];
            }

            const missingActions = actions.filter(function (act) {
                return !hasPermission(module, act);
            });

            bar.classList.toggle('d-none', missingActions.length === 0);

            const pendingCount = missingActions.filter(function (act) {
                const latest = findLatestRequest(module, act, null);
                return latest && latest.status === 'pending';
            }).length;

            const badge = bar.querySelector('.crm-bar-pending-badge');
            if (badge) {
                badge.textContent = `${pendingCount} Pending`;
                badge.classList.toggle('d-none', pendingCount === 0);
            }
        });
    }

    function refreshPermissions() {
        if (!document.getElementById('sidebar-nav')) {
            return Promise.resolve();
        }

        return fetch(statusUrl, {
            headers: window.crmApplyAuthHeaders({
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            }),
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (res) {
            if (!res || !res.success || !res.data) return;

            const prevSignature = JSON.stringify(state.permissions) + '|' + JSON.stringify(state.requests.map(r => `${r.id}:${r.status}`));

            state.isAdmin = Boolean(res.data.is_admin);
            state.permissions = res.data.permissions || {};
            state.requests = Array.isArray(res.data.requests) ? res.data.requests : [];
            state.loaded = true;

            window.crmUserPermissions = {
                ...(window.crmUserPermissions || {}),
                ...state.permissions,
            };

            const sidebarBadge = document.getElementById('sidebarPendingAccessCount');
            if (sidebarBadge && typeof res.data.pending_admin_count === 'number') {
                sidebarBadge.textContent = res.data.pending_admin_count;
                sidebarBadge.classList.toggle('d-none', res.data.pending_admin_count <= 0);
            }

            syncDeclarativeWrappers();

            const nextSignature = JSON.stringify(state.permissions) + '|' + JSON.stringify(state.requests.map(r => `${r.id}:${r.status}`));
            if (prevSignature !== nextSignature) {
                window.dispatchEvent(new CustomEvent('crm:permissions-updated', {
                    detail: {
                        permissions: window.crmUserPermissions,
                        requests: state.requests,
                    },
                }));
            }
        })
        .catch(function () {});
    }

    function openModuleCheckboxModal(options) {
        const modalEl = document.getElementById('crmAccessRequestModal');
        if (!modalEl) return;

        const module = options.module || '';
        const moduleLabel = options.moduleLabel || module;
        const actions = Array.isArray(options.actions) ? options.actions : [];
        const missingActions = actions.filter(function (act) {
            return !hasPermission(module, act);
        });

        document.getElementById('crmArModule').value = module;
        document.getElementById('crmArAction').value = '';
        document.getElementById('crmArPermission').value = '';
        document.getElementById('crmArRecordId').value = '';
        document.getElementById('crmArReason').value = '';
        document.getElementById('crmArError').classList.add('d-none');
        document.getElementById('crmArPreviousNotice').classList.add('d-none');

        const summaryEl = document.getElementById('crmArTargetSummary');
        summaryEl.innerHTML = `<strong>Module:</strong> ${escapeHtml(moduleLabel)}<br><span class="text-muted">Select one or more restricted actions to request access for this module.</span>`;

        const wrapper = document.getElementById('crmArActionsWrapper');
        const listEl = document.getElementById('crmArActionsList');
        wrapper.classList.remove('d-none');

        if (missingActions.length === 0) {
            listEl.innerHTML = '<div class="text-success small fw-semibold">All actions are already enabled for this module.</div>';
        } else {
            listEl.innerHTML = missingActions.map(function (act) {
                const label = ACTION_LABELS[act] || (act.charAt(0).toUpperCase() + act.slice(1));
                const latest = findLatestRequest(module, act, null);
                const isPending = latest && latest.status === 'pending';
                const isRejected = latest && latest.status === 'rejected';

                let statusHtml = '';
                if (isPending) {
                    statusHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-2">Pending Approval</span>';
                } else if (isRejected) {
                    const comment = latest.admin_comment ? `: ${escapeHtml(latest.admin_comment)}` : '';
                    statusHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-2" title="Last request rejected${comment}">Rejected</span>`;
                }

                return `
                    <label class="d-flex align-items-center justify-content-between p-2 border rounded-3 bg-white ${isPending ? 'opacity-75' : ''}" style="cursor: ${isPending ? 'not-allowed' : 'pointer'};">
                        <span class="d-flex align-items-center gap-2">
                            <input type="checkbox" class="form-check-input mt-0 crm-ar-action-checkbox" value="${escapeHtml(act)}" ${isPending ? 'disabled' : ''}>
                            <span class="fw-semibold small">${escapeHtml(label)}</span>
                            <code class="small text-muted">${escapeHtml(act)}_${escapeHtml(normalizeKey(module))}</code>
                        </span>
                        <span>${statusHtml}</span>
                    </label>`;
            }).join('');
        }

        const submitBtn = document.getElementById('crmArSubmitBtn');
        const hasSelectable = missingActions.some(function (act) {
            const latest = findLatestRequest(module, act, null);
            return !(latest && latest.status === 'pending');
        });
        submitBtn.disabled = !hasSelectable;

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function openModal(options) {
        const modalEl = document.getElementById('crmAccessRequestModal');
        if (!modalEl) return;

        const module = options.module || '';
        const action = options.action || '';
        const permission = options.permission || `${normalizeKey(action)}_${normalizeKey(module)}`;
        const recordId = options.recordId || '';
        const label = options.label || `${action} (${module})`;

        document.getElementById('crmArModule').value = module;
        document.getElementById('crmArAction').value = action;
        document.getElementById('crmArPermission').value = permission;
        document.getElementById('crmArRecordId').value = recordId;
        document.getElementById('crmArReason').value = '';
        document.getElementById('crmArError').classList.add('d-none');

        document.getElementById('crmArActionsWrapper').classList.add('d-none');
        document.getElementById('crmArActionsList').innerHTML = '';
        document.getElementById('crmArSubmitBtn').disabled = false;

        const summaryEl = document.getElementById('crmArTargetSummary');
        summaryEl.innerHTML = `<strong>Action:</strong> ${escapeHtml(label)}${recordId ? `<br><strong>Record ID:</strong> #${escapeHtml(recordId)}` : ''}`;

        const latest = findLatestRequest(module, action, recordId || null);
        const prevNotice = document.getElementById('crmArPreviousNotice');
        if (latest && latest.status === 'rejected') {
            prevNotice.classList.remove('d-none');
            prevNotice.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>Previous request was rejected${latest.admin_comment ? ': <strong>' + escapeHtml(latest.admin_comment) + '</strong>' : '.'}`;
        } else {
            prevNotice.classList.add('d-none');
            prevNotice.innerHTML = '';
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (event) {
        const modalBtn = event.target.closest('[data-crm-request-access-modal]');
        if (modalBtn) {
            event.preventDefault();
            event.stopPropagation();

            let actions = [];
            try {
                actions = JSON.parse(modalBtn.getAttribute('data-actions') || '[]');
            } catch (e) {
                actions = [];
            }

            openModuleCheckboxModal({
                module: modalBtn.getAttribute('data-module'),
                moduleLabel: modalBtn.getAttribute('data-module-label'),
                actions: actions,
            });
            return;
        }

        const btn = event.target.closest('[data-crm-request-access]');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();

        openModal({
            module: btn.getAttribute('data-module'),
            action: btn.getAttribute('data-action'),
            permission: btn.getAttribute('data-permission'),
            recordId: btn.getAttribute('data-record-id'),
            label: btn.getAttribute('data-label'),
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const submitBtn = document.getElementById('crmArSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                const reasonEl = document.getElementById('crmArReason');
                const errorEl = document.getElementById('crmArError');
                const reason = (reasonEl.value || '').trim();
                const isChecklistMode = !document.getElementById('crmArActionsWrapper').classList.contains('d-none');

                const selectedActions = isChecklistMode
                    ? Array.from(document.querySelectorAll('.crm-ar-action-checkbox:checked')).map(cb => cb.value)
                    : [];

                if (isChecklistMode && selectedActions.length === 0) {
                    errorEl.textContent = 'Please check at least one action to request.';
                    errorEl.classList.remove('d-none');
                    return;
                }

                if (reason.length < 3) {
                    errorEl.textContent = 'Please provide a brief reason (at least 3 characters).';
                    errorEl.classList.remove('d-none');
                    return;
                }

                errorEl.classList.add('d-none');
                submitBtn.disabled = true;

                const payload = isChecklistMode
                    ? {
                        module: document.getElementById('crmArModule').value,
                        actions: selectedActions,
                        reason: reason,
                    }
                    : {
                        module: document.getElementById('crmArModule').value,
                        action: document.getElementById('crmArAction').value,
                        permission: document.getElementById('crmArPermission').value,
                        record_id: document.getElementById('crmArRecordId').value || null,
                        reason: reason,
                    };

                fetch(storeUrl, {
                    method: 'POST',
                    headers: window.crmApplyAuthHeaders({
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    }),
                    body: JSON.stringify(payload),
                })
                .then(function (r) { return r.json().then(data => ({ ok: r.ok, data })); })
                .then(function (res) {
                    if (!res.ok || !res.data.success) {
                        errorEl.textContent = res.data?.message || 'Unable to submit access request.';
                        errorEl.classList.remove('d-none');
                        return;
                    }

                    bootstrap.Modal.getInstance(document.getElementById('crmAccessRequestModal'))?.hide();
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', res.data.message || 'Access request submitted.');
                    }
                    refreshPermissions();
                })
                .catch(function () {
                    errorEl.textContent = 'Network error while submitting request.';
                    errorEl.classList.remove('d-none');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
            });
        }

        if (document.getElementById('sidebar-nav')) {
            refreshPermissions();
            setInterval(refreshPermissions, 8000);
            window.addEventListener('focus', refreshPermissions);
        }
    });

    window.crmAccessRequest = {
        openModal,
        openModuleCheckboxModal,
        hasPermission,
        findLatestRequest,
        refreshPermissions,
        syncDeclarativeWrappers,
    };
})();
