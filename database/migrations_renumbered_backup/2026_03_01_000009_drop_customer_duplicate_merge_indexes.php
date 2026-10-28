<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        $this->dropIndexIfExists('customers', 'customers_email_unique');
        $this->dropIndexIfExists('customers', 'customers_phone_unique');
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        if (Schema::hasColumn('customers', 'email') && !$this->hasIndex('customers', 'customers_email_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        if (Schema::hasColumn('customers', 'phone') && !$this->hasIndex('customers', 'customers_phone_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!$this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropUnique($index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
