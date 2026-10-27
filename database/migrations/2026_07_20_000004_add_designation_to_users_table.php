<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'designation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('designation', 100)->nullable()->after('job_title');
            });

            DB::table('users')->whereNull('designation')->update([
                'designation' => DB::raw('job_title'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'designation')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('designation');
            });
        }
    }
};
