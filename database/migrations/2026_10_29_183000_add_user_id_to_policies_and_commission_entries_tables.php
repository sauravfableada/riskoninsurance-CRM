<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('policies') && !Schema::hasColumn('policies', 'user_id')) {
            Schema::table('policies', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });

            DB::statement('UPDATE policies SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
        }

        if (Schema::hasTable('commission_entries') && !Schema::hasColumn('commission_entries', 'user_id')) {
            Schema::table('commission_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('policy_id');
            });

            if (Schema::hasColumn('commission_entries', 'agent_id')) {
                DB::statement('UPDATE commission_entries SET user_id = agent_id WHERE user_id IS NULL AND agent_id IS NOT NULL');
            }
        }

        if (Schema::hasTable('commission_entries')) {
            try {
                Schema::table('commission_entries', function (Blueprint $table) {
                    $table->dropForeign(['agent_id']);
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('policies') && Schema::hasColumn('policies', 'user_id')) {
            Schema::table('policies', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('commission_entries') && Schema::hasColumn('commission_entries', 'user_id')) {
            Schema::table('commission_entries', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
