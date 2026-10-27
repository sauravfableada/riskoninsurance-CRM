<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('historable_type');
            $table->unsignedBigInteger('historable_id');
            $table->string('status')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['historable_type', 'historable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_status_histories');
    }
};
