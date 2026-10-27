<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_logs')) {
            Schema::create('whatsapp_logs', function (Blueprint $table) {
                $table->id();
                $table->string('to_number', 50);
                $table->string('template_name')->nullable();
                $table->string('module', 100)->nullable();
                $table->unsignedBigInteger('module_id')->nullable();
                $table->json('variables')->nullable();
                $table->string('status', 50)->default('pending');
                $table->text('error_message')->nullable();
                $table->string('meta_message_id')->nullable()->index();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['module', 'module_id']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
