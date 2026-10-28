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
        Schema::create('email_marketing_templates', function (Blueprint $table) {
            $table->id();                         // id
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('name');               // template name
            $table->longText('content');          // email HTML / text content
            $table->string('status')->default('draft'); // draft/sent/etc.

            // optional image urls
            $table->string('image_1')->nullable();
            $table->string('image_2')->nullable();
            $table->string('image_3')->nullable();

            // audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // timestamps & soft delete
            $table->timestamps();                 // created_at, updated_at
            $table->softDeletes();                // deleted_at

            // Optionally add foreign keys:
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
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
        Schema::dropIfExists('email_marketing_templates');
    }
};
