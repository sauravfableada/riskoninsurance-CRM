<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('team_member_designations')) {
            DB::table('team_member_designations')->whereRaw('LOWER(name) = ?', ['super admin'])->delete();
        }

        if (Schema::hasColumn('users', 'designation')) {
            DB::table('users')->where('type', 'staff')->whereRaw('LOWER(designation) = ?', ['super admin'])->update(['designation' => null]);
        }
    }

    public function down(): void
    {
        // Reserved administrator labels are intentionally not restored.
    }
};
