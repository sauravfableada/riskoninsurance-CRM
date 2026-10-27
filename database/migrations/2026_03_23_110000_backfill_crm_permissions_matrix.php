<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $modules = config('crm_permissions.modules', []);
        $defaultActions = array_keys(config('crm_permissions.actions', []));

        foreach ($modules as $module => $meta) {
            $actions = $meta['actions'] ?? $defaultActions;

            foreach ($actions as $action) {
                Permission::findOrCreate("{$action}_{$module}", 'web');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
    }
};
