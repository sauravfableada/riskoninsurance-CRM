<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $excludedTables = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'migrations',
        'password_reset_tokens',
        'password_resets',
        'personal_access_tokens',
        'sessions',
    ];

    public function up(): void
    {
        $validUserIds = User::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($validUserIds === []) {
            return;
        }

        foreach ($this->tablesToBackfill() as $table) {
            $this->backfillCreatedBy($table, $validUserIds);
            $this->backfillUpdatedBy($table, $validUserIds);
        }
    }

    public function down(): void
    {
        // Data backfill is intentionally not reversed.
    }

    protected function tablesToBackfill(): array
    {
        return collect(Schema::getTableListing())
            ->reject(fn (string $table) => in_array($table, $this->excludedTables, true))
            ->filter(fn (string $table) => Schema::hasColumn($table, 'created_by') || Schema::hasColumn($table, 'updated_by'))
            ->values()
            ->all();
    }

    protected function backfillCreatedBy(string $table, array $validUserIds): void
    {
        if (!Schema::hasColumn($table, 'created_by')) {
            return;
        }

        foreach ($this->createdBySourceColumns($table) as $column) {
            DB::table($table)
                ->whereNull('created_by')
                ->whereIn($column, $validUserIds)
                ->update(['created_by' => DB::raw($column)]);
        }
    }

    protected function backfillUpdatedBy(string $table, array $validUserIds): void
    {
        if (!Schema::hasColumn($table, 'updated_by')) {
            return;
        }

        foreach ($this->updatedBySourceColumns($table) as $column) {
            DB::table($table)
                ->whereNull('updated_by')
                ->whereIn($column, $validUserIds)
                ->update(['updated_by' => DB::raw($column)]);
        }
    }

    protected function createdBySourceColumns(string $table): array
    {
        $sources = [];

        if ($this->supportsUserIdBackfill($table) && Schema::hasColumn($table, 'user_id')) {
            $sources[] = 'user_id';
        }

        if (Schema::hasColumn($table, 'assigned_user_id')) {
            $sources[] = 'assigned_user_id';
        }

        if (Schema::hasColumn($table, 'updated_by')) {
            $sources[] = 'updated_by';
        }

        return array_values(array_unique($sources));
    }

    protected function updatedBySourceColumns(string $table): array
    {
        $sources = [];

        if ($this->supportsUserIdBackfill($table) && Schema::hasColumn($table, 'user_id')) {
            $sources[] = 'user_id';
        }

        if (Schema::hasColumn($table, 'assigned_user_id')) {
            $sources[] = 'assigned_user_id';
        }

        if (Schema::hasColumn($table, 'created_by')) {
            $sources[] = 'created_by';
        }

        return array_values(array_unique($sources));
    }

    protected function supportsUserIdBackfill(string $table): bool
    {
        return !in_array($table, ['send_email'], true);
    }
};
