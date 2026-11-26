<?php

namespace App\Services;

use App\Models\AccessRequestHistory;
use App\Models\StaffAccessRequest;
use App\Models\StaffPermissionGrant;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AccessRequestService
{
    public const SUPPORTED_ACTIONS = [
        'view',
        'create',
        'edit',
        'delete',
        'export',
        'approve',
        'import',
        'convert',
        'revert',
        'renew',
        'manage',
    ];

    public function normalizePermission(string $module, string $action): string
    {
        $cleanModule = Str::of($module)->lower()->trim()->replace('-', '_')->toString();
        $cleanAction = Str::of($action)->lower()->trim()->replace('-', '_')->toString();

        return "{$cleanAction}_{$cleanModule}";
    }

    public function createRequest(User $staff, array $data): StaffAccessRequest
    {
        if ($staff->isAdmin()) {
            throw new \InvalidArgumentException('Administrators already have full access.');
        }

        $module = Str::of((string) $data['module'])->lower()->trim()->replace('-', '_')->toString();
        $action = Str::of((string) $data['action'])->lower()->trim()->replace('-', '_')->toString();
        $permission = !empty($data['permission'])
            ? Str::of((string) $data['permission'])->lower()->trim()->toString()
            : $this->normalizePermission($module, $action);

        $recordId = isset($data['record_id']) && $data['record_id'] !== '' && $data['record_id'] !== null
            ? (int) $data['record_id']
            : null;

        if ($staff->hasMatrixPermission($permission)) {
            throw new \InvalidArgumentException('You already have permission for this action.');
        }

        $duplicateQuery = StaffAccessRequest::query()
            ->where('user_id', $staff->id)
            ->where('permission', $permission)
            ->where('status', StaffAccessRequest::STATUS_PENDING);

        if ($recordId !== null) {
            $duplicateQuery->where('record_id', $recordId);
        } else {
            $duplicateQuery->whereNull('record_id');
        }

        if ($duplicateQuery->exists()) {
            throw new \InvalidArgumentException('A pending access request already exists for this permission.');
        }

        $request = DB::transaction(function () use ($staff, $module, $action, $permission, $recordId, $data) {
            $accessRequest = StaffAccessRequest::create([
                'user_id' => $staff->id,
                'module' => $module,
                'action' => $action,
                'permission' => $permission,
                'record_id' => $recordId,
                'reason' => trim((string) $data['reason']),
                'status' => StaffAccessRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);

            AccessRequestHistory::create([
                'access_request_id' => $accessRequest->id,
                'user_id' => $staff->id,
                'actor_id' => $staff->id,
                'event' => AccessRequestHistory::EVENT_REQUESTED,
                'permission' => $permission,
                'module' => $module,
                'action' => $action,
                'record_id' => $recordId,
                'comment' => trim((string) $data['reason']),
                'created_at' => now(),
            ]);

            return $accessRequest;
        });

        $this->notifyAdminsOfRequest($staff, $request);

        return $request->load(['staff', 'reviewer']);
    }

    public function approveRequest(StaffAccessRequest $accessRequest, User $admin, ?string $comment = null): StaffAccessRequest
    {
        if (!$admin->isAdmin()) {
            throw new \RuntimeException('Only administrators can approve access requests.');
        }

        if ($accessRequest->status !== StaffAccessRequest::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending access requests can be approved.');
        }

        $cleanComment = $comment !== null ? trim($comment) : null;

        DB::transaction(function () use ($accessRequest, $admin, $cleanComment) {
            $accessRequest->update([
                'status' => StaffAccessRequest::STATUS_APPROVED,
                'admin_comment' => $cleanComment !== '' ? $cleanComment : null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $staff = $accessRequest->staff()->firstOrFail();

            $grant = $this->grantPermissionToStaff(
                $staff,
                $accessRequest->module,
                $accessRequest->action,
                $accessRequest->permission,
                $admin,
                $accessRequest->id
            );

            AccessRequestHistory::create([
                'access_request_id' => $accessRequest->id,
                'grant_id' => $grant->id,
                'user_id' => $staff->id,
                'actor_id' => $admin->id,
                'event' => AccessRequestHistory::EVENT_APPROVED,
                'permission' => $accessRequest->permission,
                'module' => $accessRequest->module,
                'action' => $accessRequest->action,
                'record_id' => $accessRequest->record_id,
                'comment' => $cleanComment !== '' ? $cleanComment : 'Access request approved.',
                'created_at' => now(),
            ]);
        });

        $staff = $accessRequest->staff;
        if ($staff && function_exists('sendNotification')) {
            $label = $this->formatPermissionLabel($accessRequest->module, $accessRequest->action);
            $msg = "Your access request for {$label} has been approved.";
            if ($cleanComment) {
                $msg .= " Admin note: {$cleanComment}";
            }
            sendNotification($staff->id, $msg);
        }

        return $accessRequest->fresh(['staff', 'reviewer', 'grant']);
    }

    public function rejectRequest(StaffAccessRequest $accessRequest, User $admin, ?string $comment = null): StaffAccessRequest
    {
        if (!$admin->isAdmin()) {
            throw new \RuntimeException('Only administrators can reject access requests.');
        }

        if ($accessRequest->status !== StaffAccessRequest::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending access requests can be rejected.');
        }

        $cleanComment = $comment !== null ? trim($comment) : null;

        DB::transaction(function () use ($accessRequest, $admin, $cleanComment) {
            $accessRequest->update([
                'status' => StaffAccessRequest::STATUS_REJECTED,
                'admin_comment' => $cleanComment !== '' ? $cleanComment : null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AccessRequestHistory::create([
                'access_request_id' => $accessRequest->id,
                'user_id' => $accessRequest->user_id,
                'actor_id' => $admin->id,
                'event' => AccessRequestHistory::EVENT_REJECTED,
                'permission' => $accessRequest->permission,
                'module' => $accessRequest->module,
                'action' => $accessRequest->action,
                'record_id' => $accessRequest->record_id,
                'comment' => $cleanComment !== '' ? $cleanComment : 'Access request rejected.',
                'created_at' => now(),
            ]);
        });

        $staff = $accessRequest->staff;
        if ($staff && function_exists('sendNotification')) {
            $label = $this->formatPermissionLabel($accessRequest->module, $accessRequest->action);
            $msg = "Your access request for {$label} was rejected.";
            if ($cleanComment) {
                $msg .= " Reason: {$cleanComment}";
            }
            sendNotification($staff->id, $msg);
        }

        return $accessRequest->fresh(['staff', 'reviewer']);
    }

    public function revokeGrant(StaffPermissionGrant $grant, User $admin, ?string $reason = null): StaffPermissionGrant
    {
        if (!$admin->isAdmin()) {
            throw new \RuntimeException('Only administrators can revoke permissions.');
        }

        if (!$grant->is_active) {
            throw new \InvalidArgumentException('This permission grant is already revoked.');
        }

        $cleanReason = $reason !== null ? trim($reason) : null;

        DB::transaction(function () use ($grant, $admin, $cleanReason) {
            $grant->update([
                'is_active' => false,
                'revoked_by' => $admin->id,
                'revoked_at' => now(),
                'revocation_reason' => $cleanReason !== '' ? $cleanReason : null,
            ]);

            $staff = $grant->staff;
            if ($staff) {
                $this->syncRevocationToBackend($staff, $grant->module, $grant->action, $grant->permission);

                AccessRequestHistory::create([
                    'access_request_id' => $grant->access_request_id,
                    'grant_id' => $grant->id,
                    'user_id' => $staff->id,
                    'actor_id' => $admin->id,
                    'event' => AccessRequestHistory::EVENT_REVOKED,
                    'permission' => $grant->permission,
                    'module' => $grant->module,
                    'action' => $grant->action,
                    'record_id' => $grant->record_id,
                    'comment' => $cleanReason !== '' ? $cleanReason : 'Permission revoked by administrator.',
                    'created_at' => now(),
                ]);
            }
        });

        $staff = $grant->staff;
        if ($staff && function_exists('sendNotification')) {
            $label = $this->formatPermissionLabel($grant->module, $grant->action);
            $msg = "Your granted permission for {$label} has been revoked.";
            if ($cleanReason) {
                $msg .= " Reason: {$cleanReason}";
            }
            sendNotification($staff->id, $msg);
        }

        return $grant->fresh(['staff', 'granter', 'revoker']);
    }

    public function grantPermissionToStaff(
        User $staff,
        string $module,
        string $action,
        string $permission,
        User $admin,
        ?int $accessRequestId = null
    ): StaffPermissionGrant {
        $grant = StaffPermissionGrant::query()
            ->where('user_id', $staff->id)
            ->where('permission', $permission)
            ->whereNull('record_id')
            ->first();

        if ($grant) {
            $grant->update([
                'module' => $module,
                'action' => $action,
                'access_request_id' => $accessRequestId ?? $grant->access_request_id,
                'granted_by' => $admin->id,
                'granted_at' => now(),
                'is_active' => true,
                'revoked_by' => null,
                'revoked_at' => null,
                'revocation_reason' => null,
            ]);
        } else {
            $grant = StaffPermissionGrant::create([
                'user_id' => $staff->id,
                'module' => $module,
                'action' => $action,
                'permission' => $permission,
                'record_id' => null,
                'access_request_id' => $accessRequestId,
                'granted_by' => $admin->id,
                'granted_at' => now(),
                'is_active' => true,
            ]);
        }

        $this->syncGrantToBackend($staff, $module, $action, $permission);

        return $grant;
    }

    public function getEffectivePermissionsForUser(User $user): array
    {
        $modules = array_keys(config('crm_permissions.modules', []));
        $baseActions = array_keys(config('crm_permissions.actions', []));
        $extraActions = ['export', 'approve'];
        $allActions = array_values(array_unique(array_merge($baseActions, $extraActions)));

        $matrix = [];
        $flat = [];

        foreach ($modules as $module) {
            $matrix[$module] = [];
            foreach ($allActions as $action) {
                $perm = $this->normalizePermission($module, $action);
                $allowed = $user->hasMatrixPermission($perm);
                $matrix[$module][$action] = $allowed;
                if ($allowed) {
                    $flat[] = $perm;
                }
            }
        }

        // Include any active custom grants outside crm_permissions.modules
        if (Schema::hasTable('staff_permission_grants')) {
            $activeGrants = StaffPermissionGrant::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->get();

            foreach ($activeGrants as $grant) {
                $matrix[$grant->module][$grant->action] = true;
                if (!in_array($grant->permission, $flat, true)) {
                    $flat[] = $grant->permission;
                }
            }
        }

        return [
            'is_admin' => $user->isAdmin(),
            'matrix' => $matrix,
            'flat' => $flat,
        ];
    }

    public function formatPermissionLabel(string $module, string $action): string
    {
        $moduleMeta = config("crm_permissions.modules.{$module}");
        $moduleLabel = $moduleMeta['label'] ?? Str::headline($module);
        $actionLabel = Str::headline($action);

        return "{$actionLabel} ({$moduleLabel})";
    }

    private function syncGrantToBackend(User $staff, string $module, string $action, string $permission): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            Permission::findOrCreate($permission, 'web');
            if (!$staff->hasDirectPermission($permission)) {
                $staff->givePermissionTo($permission);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Spatie tables may be absent in minimal environments.
        }

        $this->updateUserPermissionMatrixColumn($staff->id, $module, $action, true);
    }

    private function syncRevocationToBackend(User $staff, string $module, string $action, string $permission): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            if ($staff->hasDirectPermission($permission)) {
                $staff->revokePermissionTo($permission);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Spatie tables may be absent in minimal environments.
        }

        $this->updateUserPermissionMatrixColumn($staff->id, $module, $action, false);
    }

    private function updateUserPermissionMatrixColumn(int $userId, string $module, string $action, bool $grant): void
    {
        if (!Schema::hasTable('users_permission')) {
            return;
        }

        $columnMap = [
            'view' => 'view',
            'create' => 'create',
            'edit' => Schema::hasColumn('users_permission', 'edit') ? 'edit' : 'update',
            'delete' => 'delete',
        ];

        if (!isset($columnMap[$action])) {
            return;
        }

        $moduleId = $this->resolveModuleId($module);
        if ($moduleId === null) {
            return;
        }

        $column = $columnMap[$action];
        $row = UserPermission::query()
            ->where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->first();

        if ($row) {
            $row->{$column} = $grant ? 1 : 0;
            $row->save();
        } elseif ($grant) {
            $editCol = $columnMap['edit'];
            UserPermission::create([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'view' => $column === 'view' ? 1 : 0,
                'create' => $column === 'create' ? 1 : 0,
                $editCol => $column === $editCol ? 1 : 0,
                'delete' => $column === 'delete' ? 1 : 0,
            ]);
        }
    }

    private function resolveModuleId(string $targetModuleKey): ?int
    {
        $aliases = [
            'followup' => 'followups',
            'invoice' => 'invoices',
            'sms-marketing' => 'sms_marketing',
        ];

        try {
            $modules = app(TeamMemberService::class)->emptyModulePermissions();
            foreach ($modules as $item) {
                $module = $item['module'] ?? [];
                $name = (string) ($module['module_name'] ?? '');
                $key = Str::of($name)->lower()->replace(' ', '-')->toString();
                $key = $aliases[$key] ?? str_replace('-', '_', $key);

                if ($key === $targetModuleKey) {
                    return (int) ($module['id'] ?? 0) ?: null;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function notifyAdminsOfRequest(User $staff, StaffAccessRequest $request): void
    {
        if (!function_exists('sendNotification')) {
            return;
        }

        $admins = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']))
            ->where('is_active', true)
            ->get();

        $label = $this->formatPermissionLabel($request->module, $request->action);
        $message = "{$staff->name} requested access for {$label}.";

        foreach ($admins as $admin) {
            sendNotification($admin->id, $message);
        }
    }
}
