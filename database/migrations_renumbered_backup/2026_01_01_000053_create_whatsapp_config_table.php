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
        Schema::create('whatsapp_config', function (Blueprint $table) {
            $table->id();
            $table->string('app_id')->nullable();
            $table->string('app_secret')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('business_account_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('webhook_url')->nullable();

            // created_by / modified_by
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();

            // If you want foreign keys to users, uncomment:
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('modified_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_config');
    }
};
