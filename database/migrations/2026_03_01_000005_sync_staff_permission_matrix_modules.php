<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $defaultActions = array_keys(config('crm_permissions.actions', []));

        foreach (config('crm_permissions.modules', []) as $module => $meta) {
            $moduleActions = $meta['actions'] ?? $defaultActions;

            foreach ($moduleActions as $action) {
                \Illuminate\Support\Facades\DB::table('permissions')->updateOrInsert(
                    ['name' => "{$action}_{$module}", 'guard_name' => 'web'],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // Keep permissions intact on rollback to avoid removing assigned staff permissions unexpectedly.
    }
};
