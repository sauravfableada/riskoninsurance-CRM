<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->string('doc_type')->nullable();
            $table->string('file_path');
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index('project_id');
            $table->index('doc_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};
