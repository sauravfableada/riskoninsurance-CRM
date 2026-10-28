<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();

            // Denormalized data for quick viewing
            $table->string('template_name')->nullable()->comment('Denormalized template name for logs');

            // Core SMS data
            $table->string('customer_phone');                    // Important: actual phone number
            $table->text('message_body');
            $table->dateTime('send_date');                       // When the SMS was sent

            $table->string('service')->default('twilio');        // twilio, msg91, etc.

            // Status tracking
            $table->string('status')->default('pending');        // pending, sent, delivered, failed

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('sms_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better performance
            $table->index(['customer_id', 'send_date']);
            $table->index('status');
            $table->index('service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};