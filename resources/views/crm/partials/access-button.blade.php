@php
    $btnUser = auth()->user();
    $permName = $permission ?? "{$action}_{$module}";
    $hasPerm = $btnUser ? ($btnUser->isAdmin() || $btnUser->hasMatrixPermission($permName)) : false;
    $btnLabel = $label ?? ucfirst($action) . ' ' . ucfirst(str_replace('_', ' ', $module));
    $recId = $recordId ?? null;
    $compactBtn = $compact ?? false;
@endphp

<span class="d-inline-flex align-items-center"
      data-crm-perm-wrap="1"
      data-module="{{ $module }}"
      data-action="{{ $action }}"
      @if($recId) data-record-id="{{ $recId }}" @endif>
    <span class="crm-perm-allowed {{ $hasPerm ? '' : 'd-none' }}">
        {!! $slot ?? '' !!}
    </span>
    <span class="crm-perm-restricted {{ $hasPerm ? 'd-none' : '' }} d-inline-flex align-items-center gap-1">
        @if($compactBtn)
            <button type="button"
                    class="btn crm-action-btn btn-sm text-warning border-warning-subtle"
                    data-crm-request-access="1"
                    data-module="{{ $module }}"
                    data-action="{{ $action }}"
                    @if($recId) data-record-id="{{ $recId }}" @endif
                    data-label="{{ $btnLabel }}"
                    title="Request Access: {{ $btnLabel }}">
                <i class="bi bi-lock-fill"></i>
            </button>
            <button type="button"
                    class="btn crm-action-btn btn-sm text-warning bg-warning-subtle border-warning-subtle crm-perm-pending d-none"
                    disabled
                    title="Access Request Pending Approval">
                <i class="bi bi-hourglass-split"></i>
            </button>
        @else
            <button type="button"
                    class="btn btn-outline-warning btn-sm d-inline-flex align-items-center gap-1"
                    data-crm-request-access="1"
                    data-module="{{ $module }}"
                    data-action="{{ $action }}"
                    @if($recId) data-record-id="{{ $recId }}" @endif
                    data-label="{{ $btnLabel }}">
                <i class="bi bi-key"></i>
                <span>Request Access ({{ $buttonText ?? ucfirst($action) }})</span>
            </button>
            <button type="button"
                    class="btn btn-sm btn-warning-subtle text-warning-emphasis border border-warning-subtle crm-perm-pending d-none"
                    disabled>
                <i class="bi bi-hourglass-split me-1"></i>Pending Approval
            </button>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle crm-perm-rejected d-none" title="Previous request rejected">
                Rejected
            </span>
        @endif
    </span>
</span>
