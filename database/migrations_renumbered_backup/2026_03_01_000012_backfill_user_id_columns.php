<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Get valid user IDs
        $validUserIds = DB::table('users')->pluck('id')->toArray();

        // Backfill user_id from assigned_user_id or created_by
        $tables = [
            'leads' => ['assigned_user_id', 'created_by'],
            'follow_ups' => ['assigned_user_id', 'created_by'],
            'deals' => ['assigned_user_id', 'created_by'],
            'tasks' => ['assigned_user_id', 'created_by'],
            'projects' => ['assigned_user_id', 'created_by'],
            'meetings' => ['assigned_user_id', 'created_by'],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // First, try to use assigned_user_id (only if it's a valid user)
            if (Schema::hasColumn($table, 'assigned_user_id')) {
                DB::table($table)
                    ->whereNull('user_id')
                    ->whereNotNull('assigned_user_id')
                    ->whereIn('assigned_user_id', $validUserIds)
                    ->update(['user_id' => DB::raw('assigned_user_id')]);
            }

            // Then, use created_by for remaining records (only if it's a valid user)
            if (Schema::hasColumn($table, 'created_by')) {
                DB::table($table)
                    ->whereNull('user_id')
                    ->whereNotNull('created_by')
                    ->whereIn('created_by', $validUserIds)
                    ->update(['user_id' => DB::raw('created_by')]);
            }
        }
    }

    public function down(): void
    {
        // Reset user_id to NULL
        $tables = ['leads', 'follow_ups', 'deals', 'tasks', 'projects', 'meetings'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->update(['user_id' => null]);
            }
        }
    }
};

