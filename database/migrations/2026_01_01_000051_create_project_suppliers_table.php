<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('service_type');
            $table->string('reference')->nullable();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index('project_id');
            $table->index(['project_id', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_suppliers');
    }
};
