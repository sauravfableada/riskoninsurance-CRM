<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('model_has_permissions') || !Schema::hasTable('permissions') || !Schema::hasColumn('users', 'type')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where(function ($query) {
                $query->where('name', 'like', '%_users')
                    ->orWhere('name', 'like', '%_roles');
            })
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('model_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('model_id', DB::table('users')->where('type', 'sub_agents')->select('id'))
            ->delete();
    }

    public function down(): void
    {
        // Removed assignments cannot be reconstructed reliably.
    }
};
