<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('role')->nullable()->after('is_active');
                $table->index('role');
            });
        }

        $this->backfillRoleFromSpatie();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            });
        }
    }

    private function backfillRoleFromSpatie(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('model_has_roles')) {
            return;
        }

        $userModelType = config('auth.providers.users.model', 'App\\Models\\User');

        $roleNameMap = [
            3 => ['super-admin', 'admin'],
            1 => ['staff', 'agent', 'employee', 'executive', 'sales'],
            2 => ['customer', 'client'],
        ];

        foreach ($roleNameMap as $numericRole => $roleNames) {
            $userIds = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_type', $userModelType)
                ->whereIn('r.name', $roleNames)
                ->pluck('mhr.model_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!empty($userIds)) {
                DB::table('users')
                    ->whereIn('id', $userIds)
                    ->whereNull('role')
                    ->update(['role' => $numericRole]);
            }
        }
    }
};

