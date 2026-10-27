<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('activity_type');
            $table->text('notes')->nullable();
            $table->dateTime('activity_at');
            $table->string('file_path')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index('project_id');
            $table->index(['project_id', 'activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activities');
    }
};
