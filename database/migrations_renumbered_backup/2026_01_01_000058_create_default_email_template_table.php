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
        Schema::create('default_email_template', function (Blueprint $table) {
            $table->id();                       // id
            $table->string('name');             // template name
            $table->longText('content');        // email HTML / text content

            // audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // timestamps & soft delete
            $table->timestamps();   // created_at, updated_at
            $table->softDeletes();  // deleted_at

            // If you want foreign keys to users, uncomment:
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
        Schema::dropIfExists('default_email_template');
    }
};
