<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_source_id')->nullable()->after('notes');
            $table->unsignedBigInteger('lead_stage_id')->nullable()->after('lead_source_id');
            $table->index(['lead_source_id', 'lead_stage_id']);
        });

        Schema::table('tour_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('travel_type_id')->nullable()->after('highlights');
            $table->unsignedBigInteger('currency_id')->nullable()->after('travel_type_id');
            $table->index(['travel_type_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['lead_source_id', 'lead_stage_id']);
            $table->dropColumn(['lead_source_id', 'lead_stage_id']);
        });

        Schema::table('tour_packages', function (Blueprint $table) {
            $table->dropIndex(['travel_type_id', 'currency_id']);
            $table->dropColumn(['travel_type_id', 'currency_id']);
        });
    }
};

