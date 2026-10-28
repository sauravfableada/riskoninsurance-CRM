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
        Schema::create('follow_up_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_id')->constrained('follow_ups')->cascadeOnDelete();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_status_histories');
    }
};
