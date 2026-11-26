@php
    $barUser = auth()->user();
    $isStaffUser = $barUser && !$barUser->isAdmin();
    $barActions = $actions ?? ['create', 'edit', 'delete', 'export'];
    $moduleLabel = config("crm_permissions.modules.{$module}.label") ?? ucfirst(str_replace('_', ' ', $module));
    $missingCount = 0;
    if ($isStaffUser) {
        foreach ($barActions as $act) {
            if (!$barUser->hasMatrixPermission("{$act}_{$module}")) {
                $missingCount++;
            }
        }
    }
@endphp

@if($isStaffUser)
    <span class="d-inline-flex align-items-center {{ $missingCount === 0 ? 'd-none' : '' }}"
          data-crm-module-bar="1"
          data-module="{{ $module }}"
          data-module-label="{{ $moduleLabel }}"
          data-actions='@json($barActions)'>
        <button type="button"
                class="btn btn-outline-warning d-inline-flex align-items-center gap-2"
                data-crm-request-access-modal="1"
                data-module="{{ $module }}"
                data-module-label="{{ $moduleLabel }}"
                data-actions='@json($barActions)'>
            <i class="bi bi-key"></i>
            <span>Request Access</span>
            <span class="badge rounded-pill bg-warning text-dark crm-bar-pending-badge d-none">0 Pending</span>
        </button>
    </span>
@endif
