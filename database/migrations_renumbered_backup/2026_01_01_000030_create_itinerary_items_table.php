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
        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('itinerary_day_id');
            $table->string('item_type')->nullable();
            $table->string('time')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();

            $table->index(['itinerary_day_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_items');
    }
};
