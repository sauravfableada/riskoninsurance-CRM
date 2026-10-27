<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->id();

            // Foreign key (insurance type)
            $table->unsignedBigInteger('insurance_type_id');

            $table->string('name');
            $table->text('description')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint (optional but recommended)
            $table->foreign('insurance_type_id')
                  ->references('id')
                  ->on('insurance_policy_types') // change if different table
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};
