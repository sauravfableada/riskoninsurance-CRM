<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Framework-managed tables are excluded to avoid altering Laravel internals.
     *
     * @var array<int, string>
     */
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
        foreach ($this->tablesToUpdate() as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablesToUpdate() as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_by')) {
                    $table->dropColumn('created_by');
                }

                if (Schema::hasColumn($tableName, 'updated_by')) {
                    $table->dropColumn('updated_by');
                }

                if (Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }

                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }

    /**
     * @return array<int, string>
     */
    protected function tablesToUpdate(): array
    {
        return collect(Schema::getTableListing())
            ->reject(fn(string $tableName) => in_array($tableName, $this->excludedTables, true))
            ->values()
            ->all();
    }
};
