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
        Schema::table('life_insurances', function (Blueprint $table) {
            $table->string('nominee_name')->nullable()->after('nominee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('life_insurances', function (Blueprint $table) {
            //
        });
    }
};
