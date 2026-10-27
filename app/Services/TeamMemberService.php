<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TeamMemberService
{
    private ?array $usersColumns = null;

    private ?array $usersPermissionColumns = null;

    private ?array $moduleTableMeta = null;

    public function listMembers(bool $withSerial = false): array
    {
        $members = $this->memberRows();

        if ($withSerial) {
            foreach ($members as $index => &$member) {
                $member['sr_no'] = $index + 1;
            }
            unset($member);
        }

        return $members;
    }

    public function getMemberWithPermissions(int $id): ?array
    {
        $row = DB::table('users')->where('id', $id)->first();

        if (!$row) {
            return null;
        }

        $member = $this->formatMember((array) $row);

        if (($member['type'] ?? null) !== 'staff') {
            return null;
        }

        $modulePermissions = $this->buildModulePermissions($id);

        return [
            'member' => $member,
            'module_permissions' => $modulePermissions,
        ];
    }

    public function createMember(array $input, ?UploadedFile $profileImage, ?int $createdBy): int
    {
        $payload = $this->buildCreatePayload($input, $profileImage, $createdBy);

        $id = (int) DB::table('users')->insertGetId($payload);

        $this->syncSpatieRole($id, 5);

        if ((int) ($input['role'] ?? 0) === 5) {
            $this->syncPermissions($id, $input['permissions'] ?? []);
        }

        return $id;
    }

    public function updateMember(int $id, array $input, ?UploadedFile $profileImage): bool
    {
        $existing = DB::table('users')->where('id', $id)->first();

        if (!$existing) {
            return false;
        }

        $existingRow = (array) $existing;
        $payload = $this->buildUpdatePayload($existingRow, $input, $profileImage);

        if (!empty($payload)) {
            DB::table('users')->where('id', $id)->update($payload);
        }

        $resolvedRole = 5;
        $this->syncSpatieRole($id, $resolvedRole);

        if ($resolvedRole === 5) {
            $this->syncPermissions($id, $input['permissions'] ?? []);
        } else {
            UserPermission::query()->where('user_id', $id)->delete();
        }

        return true;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $existing = DB::table('users')->where('id', $id)->first();

        if (!$existing) {
            return false;
        }

        $updates = [];

        if ($this->hasUsersColumn('Status')) {
            $updates['Status'] = $status;
        }

        if ($this->hasUsersColumn('is_active')) {
            $updates['is_active'] = strtolower($status) === 'active';
        }

        if (empty($updates)) {
            return false;
        }

        DB::table('users')->where('id', $id)->update($updates);

        return true;
    }

    public function deleteMember(int $id): bool
    {
        $existing = DB::table('users')->where('id', $id)->first();

        if (!$existing) {
            return false;
        }

        $row = (array) $existing;

        $profileName = $this->resolveProfileName($row);
        if ($profileName !== '') {
            $this->deleteProfileImageByName($profileName);
        }

        UserPermission::query()->where('user_id', $id)->delete();
        DB::table('users')->where('id', $id)->delete();

        return true;
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $query = DB::table('users')->where('email', $email);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function emptyModulePermissions(): array
    {
        $result = [];

        foreach ($this->modules() as $module) {
            $result[] = [
                'module' => $module,
                'permission' => [
                    'id' => null,
                    'view' => 0,
                    'create' => 0,
                    'update' => 0,
                    'delete' => 0,
                ],
            ];
        }

        return $result;
    }

    private function memberRows(): array
    {
        if ($this->hasUsersColumn('role')) {
            $rows = DB::table('users')
                ->where('type', 'staff')
                ->where('role', 5)
                ->orderByDesc('id')
                ->get();

            return $rows->map(fn ($row) => $this->formatMember((array) $row))->all();
        }

        $rows = User::query()
            ->where('type', 'staff')
            ->orderByDesc('id')
            ->get();

        return $rows->map(function (User $user) {
            $row = $user->toArray();
            $row['role'] = 5;
            return $this->formatMember($row);
        })->all();
    }

    private function modules(): array
    {
        $tableMeta = $this->moduleTableMeta();

        if ($tableMeta !== null) {
            $rows = DB::table($tableMeta['table'])->orderBy('id')->get();

            $modules = [];
            foreach ($rows as $row) {
                $asArray = (array) $row;
                $name = (string) ($asArray[$tableMeta['name_column']] ?? '');
                if ($name === '') {
                    continue;
                }

                $modules[] = [
                    'id' => (int) ($asArray['id'] ?? 0),
                    'module_name' => $name,
                ];
            }

            if (!empty($modules)) {
                return $modules;
            }
        }

        return $this->fallbackModules();
    }

    private function buildModulePermissions(int $userId): array
    {
        $permissions = UserPermission::query()->where('user_id', $userId)->get();
        $permissionEditColumn = $this->permissionEditColumn();

        $permissionMap = [];
        foreach ($permissions as $perm) {
            $permissionMap[(int) $perm->module_id] = [
                'id' => (int) $perm->id,
                'view' => (int) ($perm->view ?? 0),
                'create' => (int) ($perm->create ?? 0),
                'update' => (int) (data_get($perm, $permissionEditColumn, 0) ?? 0),
                'delete' => (int) ($perm->delete ?? 0),
            ];
        }

        $result = [];
        foreach ($this->modules() as $module) {
            $moduleId = (int) $module['id'];

            $result[] = [
                'module' => $module,
                'permission' => $permissionMap[$moduleId] ?? [
                    'id' => null,
                    'view' => 0,
                    'create' => 0,
                    'update' => 0,
                    'delete' => 0,
                ],
            ];
        }

        return $result;
    }

    public function getFlatPermissions(int $userId): array
    {
        $modulePermissions = $this->buildModulePermissions($userId);
        $flat = [];
        $moduleAliases = [
            'followup' => 'followups',
            'invoice' => 'invoices',
            'sms-marketing' => 'sms_marketing',
        ];

        foreach ($modulePermissions as $mp) {
            $module = $mp['module'];
            $perm = $mp['permission'];
            
            $moduleKey = Str::of((string) $module['module_name'])->lower()->replace(' ', '-')->toString();
            $moduleKey = $moduleAliases[$moduleKey] ?? str_replace('-', '_', $moduleKey);
            
            if (!array_key_exists($moduleKey, config('crm_permissions.modules', []))) {
                continue;
            }

            if (!empty($perm['view'])) $flat[] = 'view_' . $moduleKey;
            if (!empty($perm['create'])) $flat[] = 'create_' . $moduleKey;
            if (!empty($perm['update'])) $flat[] = 'edit_' . $moduleKey;
            if (!empty($perm['delete'])) $flat[] = 'delete_' . $moduleKey;
        }

        return $flat;
    }

    private function syncPermissions(int $userId, mixed $rawPermissions): void
    {
        $moduleAliases = [
            'followup' => 'followups',
            'invoice' => 'invoices',
            'sms-marketing' => 'sms_marketing',
        ];

        $permissionMap = [];

        // Check if format is array of strings (like ['view_customers', 'edit_customers'])
        if (is_array($rawPermissions) && ($rawPermissions === [] || is_string(reset($rawPermissions)))) {
            foreach ($this->modules() as $module) {
                $moduleId = (int) $module['id'];
                $moduleKey = Str::of((string) $module['module_name'])->lower()->replace(' ', '-')->toString();
                $moduleKey = $moduleAliases[$moduleKey] ?? str_replace('-', '_', $moduleKey);

                $permissionMap[$moduleId] = [
                    'view' => in_array('view_' . $moduleKey, $rawPermissions, true) ? 1 : 0,
                    'create' => in_array('create_' . $moduleKey, $rawPermissions, true) ? 1 : 0,
                    'update' => in_array('edit_' . $moduleKey, $rawPermissions, true) ? 1 : 0,
                    'delete' => in_array('delete_' . $moduleKey, $rawPermissions, true) ? 1 : 0,
                ];
            }
        } else {
            $permissionMap = $this->normalizePermissions($rawPermissions);
        }

        $permissionEditColumn = $this->permissionEditColumn();
        $matrixPermissions = [];

        UserPermission::query()->where('user_id', $userId)->delete();

        foreach ($this->modules() as $module) {
            $moduleId = (int) $module['id'];
            $perm = $permissionMap[$moduleId] ?? [
                'view' => 0,
                'create' => 0,
                'update' => 0,
                'delete' => 0,
            ];

            UserPermission::create([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'view' => $perm['view'],
                'create' => $perm['create'],
                $permissionEditColumn => $perm['update'],
                'delete' => $perm['delete'],
            ]);

            $moduleKey = Str::of((string) $module['module_name'])->lower()->replace(' ', '-')->toString();
            $moduleKey = $moduleAliases[$moduleKey] ?? str_replace('-', '_', $moduleKey);
            if (!array_key_exists($moduleKey, config('crm_permissions.modules', []))) {
                continue;
            }

            foreach (['view' => 'view', 'create' => 'create', 'update' => 'edit', 'delete' => 'delete'] as $source => $action) {
                if (!empty($perm[$source])) {
                    $matrixPermissions[] = $action . '_' . $moduleKey;
                }
            }
        }

        $user = User::query()->find($userId);
        if ($user) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            foreach (array_unique($matrixPermissions) as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }
            $user->syncPermissions(array_values(array_unique($matrixPermissions)));
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function normalizePermissions(mixed $permissions): array
    {
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($permissions)) {
            return [];
        }

        $map = [];

        if (isset($permissions[0]) && is_array($permissions[0])) {
            foreach ($permissions as $perm) {
                $moduleId = (int) ($perm['module_id'] ?? 0);
                if ($moduleId <= 0) {
                    continue;
                }

                if (isset($perm['type'])) {
                    $type = (string) $perm['type'];
                    $status = (int) ($perm['status'] ?? 0);

                    if (!isset($map[$moduleId])) {
                        $map[$moduleId] = ['view' => 0, 'create' => 0, 'update' => 0, 'delete' => 0];
                    }

                    if (array_key_exists($type, $map[$moduleId])) {
                        $map[$moduleId][$type] = $status ? 1 : 0;
                    }

                    continue;
                }

                $map[$moduleId] = [
                    'view' => !empty($perm['view']) ? 1 : 0,
                    'create' => !empty($perm['create']) ? 1 : 0,
                    'update' => !empty($perm['update']) ? 1 : 0,
                    'delete' => !empty($perm['delete']) ? 1 : 0,
                ];
            }

            return $map;
        }

        foreach ($permissions as $moduleId => $perm) {
            $moduleId = (int) $moduleId;
            if ($moduleId <= 0 || !is_array($perm)) {
                continue;
            }

            $map[$moduleId] = [
                'view' => !empty($perm['view']) ? 1 : 0,
                'create' => !empty($perm['create']) ? 1 : 0,
                'update' => !empty($perm['update']) ? 1 : 0,
                'delete' => !empty($perm['delete']) ? 1 : 0,
            ];
        }

        return $map;
    }

    private function buildCreatePayload(array $input, ?UploadedFile $profileImage, ?int $createdBy): array
    {
        $payload = [];

        $fullName = trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? ''));
        $payload['name'] = $fullName;
        $payload['email'] = (string) ($input['email'] ?? '');
        if ($this->hasUsersColumn('type')) {
            $payload['type'] = 'staff';
        }
        if ($this->hasUsersColumn('username')) {
            $payload['username'] = null;
        }
        $payload['password'] = Hash::make((string) ($input['password'] ?? ''));

        if ($this->hasUsersColumn('pass_dec')) {
            $payload['pass_dec'] = (string) ($input['password'] ?? '');
        }

        $mobile = trim((string) ($input['mobile'] ?? ''));
        if ($this->hasUsersColumn('contact')) {
            $payload['contact'] = $mobile !== '' ? $mobile : null;
        }
        if ($this->hasUsersColumn('phone')) {
            $payload['phone'] = $mobile !== '' ? $mobile : null;
        }
        if ($this->hasUsersColumn('country_code')) {
            $payload['country_code'] = (string) ($input['country_code'] ?? '+91');
        }

        $designation = trim((string) ($input['designation'] ?? ''));
        if ($this->hasUsersColumn('designation')) {
            $payload['designation'] = $designation !== '' ? $designation : null;
        }
        if ($this->hasUsersColumn('position')) {
            $payload['position'] = $designation !== '' ? $designation : null;
        }
        if ($this->hasUsersColumn('job_title')) {
            $payload['job_title'] = $designation !== '' ? $designation : null;
        }

        $status = (string) ($input['status'] ?? 'Active');
        if ($this->hasUsersColumn('Status')) {
            $payload['Status'] = $status;
        }
        if ($this->hasUsersColumn('is_active')) {
            $payload['is_active'] = strtolower($status) === 'active';
        }

        if ($this->hasUsersColumn('role')) {
            $payload['role'] = (int) ($input['role'] ?? 5);
        }

        if ($createdBy !== null && $this->hasUsersColumn('created_by')) {
            $payload['created_by'] = $createdBy;
        }

        $profileName = $this->storeProfileImage($profileImage);
        if ($profileName !== null) {
            if ($this->hasUsersColumn('profile')) {
                $payload['profile'] = $profileName;
            }

            if ($this->hasUsersColumn('avatar_path')) {
                $payload['avatar_path'] = 'assets/img/profile/' . $profileName;
            }
        }

        if ($this->hasUsersColumn('email_verified_at')) {
            $payload['email_verified_at'] = now();
        }

        if ($this->hasUsersColumn('remember_token') && !array_key_exists('remember_token', $payload)) {
            $payload['remember_token'] = null;
        }

        if ($this->hasUsersColumn('created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->hasUsersColumn('updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function buildUpdatePayload(array $existing, array $input, ?UploadedFile $profileImage): array
    {
        $payload = [];

        $fullName = trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? ''));
        $payload['name'] = $fullName;
        $payload['email'] = (string) ($input['email'] ?? '');
        if ($this->hasUsersColumn('type')) {
            $payload['type'] = 'staff';
        }
        if ($this->hasUsersColumn('username')) {
            $payload['username'] = null;
        }

        $password = (string) ($input['password'] ?? '');
        if ($password !== '') {
            $payload['password'] = Hash::make($password);

            if ($this->hasUsersColumn('pass_dec')) {
                $payload['pass_dec'] = $password;
            }
        }

        $mobile = trim((string) ($input['mobile'] ?? ''));
        if ($this->hasUsersColumn('contact')) {
            $payload['contact'] = $mobile !== '' ? $mobile : null;
        }
        if ($this->hasUsersColumn('phone')) {
            $payload['phone'] = $mobile !== '' ? $mobile : null;
        }
        if ($this->hasUsersColumn('country_code')) {
            $payload['country_code'] = (string) ($input['country_code'] ?? '+91');
        }

        $designation = trim((string) ($input['designation'] ?? ''));
        if ($this->hasUsersColumn('designation')) {
            $payload['designation'] = $designation !== '' ? $designation : null;
        }
        if ($this->hasUsersColumn('position')) {
            $payload['position'] = $designation !== '' ? $designation : null;
        }
        if ($this->hasUsersColumn('job_title')) {
            $payload['job_title'] = $designation !== '' ? $designation : null;
        }

        $status = (string) ($input['status'] ?? 'Active');
        if ($this->hasUsersColumn('Status')) {
            $payload['Status'] = $status;
        }
        if ($this->hasUsersColumn('is_active')) {
            $payload['is_active'] = strtolower($status) === 'active';
        }

        if ($this->hasUsersColumn('role') && isset($input['role'])) {
            $payload['role'] = (int) $input['role'];
        }

        $newImageName = $this->storeProfileImage($profileImage);
        if ($newImageName !== null) {
            $existingImage = $this->resolveProfileName($existing);
            if ($existingImage !== '' && $existingImage !== $newImageName) {
                $this->deleteProfileImageByName($existingImage);
            }

            if ($this->hasUsersColumn('profile')) {
                $payload['profile'] = $newImageName;
            }

            if ($this->hasUsersColumn('avatar_path')) {
                $payload['avatar_path'] = 'assets/img/profile/' . $newImageName;
            }
        }

        if ($this->hasUsersColumn('updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function storeProfileImage(?UploadedFile $file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $directory = public_path('assets/img/profile');
        if (!is_dir($directory)) {
            File::ensureDirectoryExists($directory);
        }

        $fileName = Str::uuid()->toString() . '.' . $extension;
        $file->move($directory, $fileName);

        return $fileName;
    }

    private function deleteProfileImageByName(string $fileName): void
    {
        $path = public_path('assets/img/profile/' . $fileName);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function resolveProfileName(array $row): string
    {
        $profile = trim((string) ($row['profile'] ?? ''));
        if ($profile !== '') {
            return basename($profile);
        }

        $avatarPath = trim((string) ($row['avatar_path'] ?? ''));

        return $avatarPath !== '' ? basename($avatarPath) : '';
    }

    private function formatMember(array $row): array
    {
        $name = trim((string) ($row['name'] ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [];

        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        $role = (int) ($row['role'] ?? 5);
        $email = (string) ($row['email'] ?? '');

        $status = 'Active';
        if (array_key_exists('Status', $row) && $row['Status'] !== null) {
            $status = (string) $row['Status'];
        } elseif (array_key_exists('is_active', $row) && $row['is_active'] !== null) {
            $status = (int) $row['is_active'] === 1 ? 'Active' : 'Inactive';
        }

        $mobile = $row['contact'] ?? $row['phone'] ?? '';
        $designation = $row['designation'] ?? $row['position'] ?? $row['job_title'] ?? '';

        $profile = '';
        if (!empty($row['profile'])) {
            $profile = basename((string) $row['profile']);
        } elseif (!empty($row['avatar_path'])) {
            $profile = basename((string) $row['avatar_path']);
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => null,
            'email' => $email,
            'type' => $row['type'] ?? null,
            'mobile' => $mobile !== '' ? (string) $mobile : '--',
            'country_code' => (string) ($row['country_code'] ?? '+91'),
            'profile' => $profile,
            'role' => $role,
            'role_name' => 'Staff',
            'status' => $status,
            'designation' => $designation !== '' ? (string) $designation : '--',
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    private function isTeamMemberRole(int $role): bool
    {
        return in_array($role, [3, 5], true);
    }

    private function hasUsersColumn(string $column): bool
    {
        if ($this->usersColumns === null) {
            $this->usersColumns = Schema::getColumnListing('users');
        }

        return in_array($column, $this->usersColumns, true);
    }

    private function moduleTableMeta(): ?array
    {
        if ($this->moduleTableMeta !== null) {
            return $this->moduleTableMeta;
        }

        $candidates = ['module_master', 'modules', 'crm_modules'];

        foreach ($candidates as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (!in_array('id', $columns, true)) {
                continue;
            }

            $nameColumn = null;
            foreach (['module_name', 'name', 'title', 'module'] as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    $nameColumn = $candidate;
                    break;
                }
            }

            if ($nameColumn === null) {
                continue;
            }

            $this->moduleTableMeta = [
                'table' => $table,
                'name_column' => $nameColumn,
            ];

            return $this->moduleTableMeta;
        }

        $this->moduleTableMeta = null;

        return null;
    }

    private function usersPermissionColumns(): array
    {
        if ($this->usersPermissionColumns !== null) {
            return $this->usersPermissionColumns;
        }

        if (!Schema::hasTable('users_permission')) {
            $this->usersPermissionColumns = [];
            return $this->usersPermissionColumns;
        }

        $this->usersPermissionColumns = Schema::getColumnListing('users_permission');

        return $this->usersPermissionColumns;
    }

    private function permissionEditColumn(): string
    {
        $columns = $this->usersPermissionColumns();

        if (in_array('edit', $columns, true)) {
            return 'edit';
        }

        if (in_array('update', $columns, true)) {
            return 'update';
        }

        return 'edit';
    }

    private function fallbackModules(): array
    {
        $fixed = [
            1 => 'Followup',
            2 => 'Leads',
            3 => 'Customers',
            4 => 'Stages',
            5 => 'Pipeline',
            6 => 'Products',
            7 => 'Services',
            8 => 'Invoice',
            9 => 'Tickets',
            10 => 'Tasks',
            11 => 'Projects',
            12 => 'Deals',
            13 => 'Meetings',
            14 => 'Email',
            15 => 'Prospects',
            16 => 'Reports',
            17 => 'SMS Marketing',
            18 => 'Family Groups',
            19 => 'Investments',
            20 => 'Policies',
            21 => 'Commission Rules',
            22 => 'Masters',
            23 => 'Endorsements',
            24 => 'Documents',
        ];

        $modules = [];
        foreach ($fixed as $id => $name) {
            $modules[] = [
                'id' => $id,
                'module_name' => $name,
            ];
        }

        return $modules;
    }

    private function syncSpatieRole(int $userId, int $role): void
    {
        if ($this->hasUsersColumn('role')) {
            return;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return;
        }

        $roleName = 'staff';

        try {
            $user->syncRoles([$roleName]);
        } catch (\Throwable) {
            // Intentionally no-op when permission tables are unavailable.
        }
    }
}
