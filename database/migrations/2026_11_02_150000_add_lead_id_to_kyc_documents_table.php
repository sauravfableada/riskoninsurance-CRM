<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('kyc_documents')) {
            Schema::table('kyc_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('kyc_documents', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->index()->after('policy_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kyc_documents')) {
            Schema::table('kyc_documents', function (Blueprint $table) {
                if (Schema::hasColumn('kyc_documents', 'lead_id')) {
                    $table->dropColumn('lead_id');
                }
            });
        }
    }
};
