<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->text('endpoint');
                $table->string('p256dh')->nullable();
                $table->string('auth')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
