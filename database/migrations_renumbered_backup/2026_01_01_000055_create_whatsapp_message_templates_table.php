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
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // template name
            $table->string('language', 10);           // e.g. "en", "en_US"
            $table->string('status')->default('pending'); // approved / rejected / pending, etc.
            $table->string('category')->nullable();   // marketing, utility, authentication...
            $table->string('use_for_module')->nullable(); // e.g. leads, bookings, invoices...
            $table->boolean('is_active')->default(true);
            $table->json('components_json')->nullable(); // raw JSON of components from Meta
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');
    }
};
