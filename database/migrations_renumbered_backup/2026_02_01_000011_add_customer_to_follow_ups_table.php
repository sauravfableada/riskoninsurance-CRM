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
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->change();
            $table->unsignedBigInteger('customer_id')->nullable()->after('lead_id');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable(false)->change();
            $table->dropColumn('customer_id');
        });
    }
};
