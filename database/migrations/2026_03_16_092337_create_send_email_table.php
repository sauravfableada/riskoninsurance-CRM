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
        Schema::create('send_email', function (Blueprint $table) {
            $table->id();

            // IDs of recipients; can store multiple user IDs as JSON
            $table->json('user_id')->nullable();

            // Reference to email template
            $table->unsignedBigInteger('template_id')->nullable();

            // When the email was (or will be) sent
            $table->dateTime('send_date')->nullable();

            // Which user/sender triggered this email
            $table->unsignedBigInteger('sender_id')->nullable();

            // audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // timestamps & soft delete
            $table->timestamps();     // created_at, updated_at
            $table->softDeletes();    // deleted_at

            // Optionally add foreign keys:
            // $table->foreign('template_id')->references('id')->on('email_marketing_templates')->nullOnDelete();
            // $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('modified_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_email');
    }
};
