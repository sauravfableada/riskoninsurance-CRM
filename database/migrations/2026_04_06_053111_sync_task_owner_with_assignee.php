<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tasks', 'user_id') || !Schema::hasColumn('tasks', 'assigned_user_id')) {
            return;
        }

        $validUserIds = User::query()->pluck('id')->map(fn($id) => (int) $id)->all();

        DB::table('tasks')
            ->whereIn('assigned_user_id', $validUserIds)
            ->update(['user_id' => DB::raw('assigned_user_id')]);
    }

    public function down(): void
    {
        // Intentional no-op.
    }
};
