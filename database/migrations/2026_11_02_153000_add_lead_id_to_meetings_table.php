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
        if (Schema::hasTable('meetings')) {
            Schema::table('meetings', function (Blueprint $table) {
                if (!Schema::hasColumn('meetings', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->index()->after('customer_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('meetings')) {
            Schema::table('meetings', function (Blueprint $table) {
                if (Schema::hasColumn('meetings', 'lead_id')) {
                    $table->dropColumn('lead_id');
                }
            });
        }
    }
};
