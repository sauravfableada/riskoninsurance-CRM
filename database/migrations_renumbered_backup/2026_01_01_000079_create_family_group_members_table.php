<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_group_members', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->unsignedBigInteger('family_group_id');

            $table->string('member_name');
            $table->string('relation')->nullable();
            $table->string('contact')->nullable();
            $table->date('dob')->nullable();
            $table->date('anniversary')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('family_group_id')
                  ->references('id')
                  ->on('family_groups')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_group_members');
    }
};