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
        if (Schema::hasTable('kyc_documents')) {
            try {
                DB::statement('ALTER TABLE `kyc_documents` MODIFY `policy_id` BIGINT UNSIGNED NULL DEFAULT NULL');
            } catch (\Throwable $e) {
                try {
                    Schema::table('kyc_documents', function (Blueprint $table) {
                        $table->unsignedBigInteger('policy_id')->nullable()->default(null)->change();
                    });
                } catch (\Throwable $ex) {}
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kyc_documents')) {
            try {
                DB::statement('ALTER TABLE `kyc_documents` MODIFY `policy_id` BIGINT UNSIGNED NOT NULL');
            } catch (\Throwable $e) {}
        }
    }
};
