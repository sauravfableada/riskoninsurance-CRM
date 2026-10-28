<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->constrained('users')->cascadeOnDelete();

            // Fields
            $table->string('purpose')->nullable();
            $table->text('comment')->nullable();

            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'resheduled', 'completed', 'cancelled'])->default('pending');

            $table->dateTime('follow_up_at')->nullable();

            // ✅ Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            // ✅ Soft Delete
            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};