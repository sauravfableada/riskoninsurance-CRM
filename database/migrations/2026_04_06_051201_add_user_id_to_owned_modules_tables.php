<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addUserIdColumn('leads');
        $this->addUserIdColumn('follow_ups');
        $this->addUserIdColumn('meetings');
        $this->addUserIdColumn('deals');
        $this->addUserIdColumn('projects');
        $this->addUserIdColumn('tasks');

        $this->backfillTable('leads', ['assigned_user_id']);
        $this->backfillTable('follow_ups', ['created_by', 'assigned_user_id']);
        $this->backfillTable('meetings', ['created_by', 'assigned_user_id']);
        $this->backfillTable('deals', ['created_by', 'assigned_user_id']);
        $this->backfillTable('projects', ['created_by', 'assigned_user_id']);
        $this->backfillTable('tasks', ['assigned_user_id']);
    }

    public function down(): void
    {
        $this->dropUserIdColumn('tasks');
        $this->dropUserIdColumn('projects');
        $this->dropUserIdColumn('deals');
        $this->dropUserIdColumn('meetings');
        $this->dropUserIdColumn('follow_ups');
        $this->dropUserIdColumn('leads');
    }

    private function addUserIdColumn(string $table): void
    {
        if (Schema::hasColumn($table, 'user_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) {
            $tableBlueprint->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    private function dropUserIdColumn(string $table): void
    {
        if (!Schema::hasColumn($table, 'user_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) {
            $tableBlueprint->dropConstrainedForeignId('user_id');
        });
    }

    private function backfillTable(string $table, array $sourceColumns): void
    {
        if (!Schema::hasColumn($table, 'user_id')) {
            return;
        }

        $validUserIds = User::query()->pluck('id')->map(fn($id) => (int) $id)->all();

        foreach ($sourceColumns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereNull('user_id')
                ->whereIn($column, $validUserIds)
                ->update(['user_id' => DB::raw($column)]);
        }
    }
};
