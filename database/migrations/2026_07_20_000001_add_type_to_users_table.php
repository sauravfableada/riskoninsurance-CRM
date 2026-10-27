<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('type', 30)->nullable()->after('email')->index();
            });
        }

        if (Schema::hasTable('users_permission')) {
            DB::table('users')
                ->whereIn('id', DB::table('users_permission')->select('user_id')->distinct())
                ->update(['type' => 'staff']);
        }

        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $staffRoleId = DB::table('roles')->where('name', 'staff')->value('id');
            if ($staffRoleId) {
                $teamMemberIds = DB::table('users')->where('type', 'staff')->pluck('id');
                if ($teamMemberIds->isNotEmpty()) {
                    DB::table('model_has_roles')->whereIn('model_id', $teamMemberIds)->delete();
                    DB::table('model_has_roles')->insertOrIgnore(
                        $teamMemberIds->map(fn ($id) => [
                            'role_id' => $staffRoleId,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $id,
                        ])->all()
                    );
                }

                DB::table('users')
                    ->whereNull('type')
                    ->whereIn('id', DB::table('model_has_roles')->where('role_id', $staffRoleId)->select('model_id'))
                    ->update(['type' => 'sub_agents']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['type']);
                $table->dropColumn('type');
            });
        }
    }
};
