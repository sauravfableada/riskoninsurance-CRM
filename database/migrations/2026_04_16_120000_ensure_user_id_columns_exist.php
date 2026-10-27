<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = ['leads', 'follow_ups', 'deals', 'tasks', 'projects', 'meetings'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $table_blueprint) {
                    $table_blueprint->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['leads', 'follow_ups', 'deals', 'tasks', 'projects', 'meetings'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $table_blueprint) {
                    $table_blueprint->dropConstrainedForeignId('user_id');
                });
            }
        }
    }
};
