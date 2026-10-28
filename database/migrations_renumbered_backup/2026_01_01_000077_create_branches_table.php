<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->unsignedBigInteger('company_id');

            $table->string('name');

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('company_id')
                  ->references('id')
                  ->on('company_master')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};