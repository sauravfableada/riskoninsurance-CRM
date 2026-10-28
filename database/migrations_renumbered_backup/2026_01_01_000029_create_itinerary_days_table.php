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
        Schema::create('itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('itinerary_id');
            $table->integer('day_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('meals')->nullable();
            $table->timestamps();

            $table->index('itinerary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_days');
    }
};
