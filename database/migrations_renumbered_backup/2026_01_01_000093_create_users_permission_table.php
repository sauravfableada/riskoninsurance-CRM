<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users_permission')) {
            return;
        }

        Schema::create('users_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->boolean('view')->default(false);
            $table->boolean('create')->default(false);
            $table->boolean('edit')->default(false);
            $table->boolean('delete')->default(false);

            $table->index(['user_id', 'module_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_permission');
    }
};
