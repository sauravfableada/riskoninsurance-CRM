<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {

            if (!Schema::hasColumn('follow_ups', 'next_follow_up_at')) {

                if (Schema::hasColumn('follow_ups', 'scheduled_at')) {
                    $table->dateTime('next_follow_up_at')->nullable()->after('scheduled_at');
                } else {
                    $table->dateTime('next_follow_up_at')->nullable();
                }
            }

            if (!Schema::hasColumn('follow_ups', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            if (Schema::hasColumn('follow_ups', 'assigned_user_id')) {
                $table->dropIndex(['assigned_user_id']);
                $table->dropColumn('assigned_user_id');
            }
            if (Schema::hasColumn('follow_ups', 'next_follow_up_at')) {
                $table->dropColumn('next_follow_up_at');
            }
        });
    }

    private function ensureIndexExists(string $table, string $indexName, callable $create): void
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (empty($rows)) {
            $create();
        }
    }
};
