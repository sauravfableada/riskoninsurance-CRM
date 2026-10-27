<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'is_kyc')) {
                $table->boolean('is_kyc')->default(false)->after('file_type');
            }

            if (!Schema::hasColumn('documents', 'meta')) {
                $table->json('meta')->nullable()->after('is_kyc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            foreach (['meta', 'is_kyc'] as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
