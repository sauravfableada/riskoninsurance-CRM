<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_user_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscription_plan')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_user_plan');
    }
};
