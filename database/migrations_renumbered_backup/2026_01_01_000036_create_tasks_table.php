<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('related_type')->nullable(); // lead, customer, deal, booking
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index(['assigned_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
