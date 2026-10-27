<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->enum('payment_type', ['customer', 'supplier']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index('project_id');
            $table->index(['project_id', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};
