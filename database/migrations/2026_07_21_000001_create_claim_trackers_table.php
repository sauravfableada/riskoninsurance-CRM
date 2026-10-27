<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->decimal('claim_amount', 15, 2);
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->string('claim_file')->nullable();
            $table->date('claim_file_received_date');
            $table->date('claim_file_submit_date')->nullable();
            $table->enum('claim_status', ['Processed', 'Query', 'Closed', 'Rejected'])->default('Processed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_trackers');
    }
};
