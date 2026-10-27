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
        Schema::create('target_reviews', function (Blueprint $table) {
            $table->id();

            $table->date('transaction_date');
            $table->string('transaction_number', 100);
            $table->string('target_type', 100);
            $table->string('target_month', 7); // mm-yyyy

            $table->unsignedTinyInteger('department');
            $table->unsignedTinyInteger('factor');

            $table->decimal('target_amount', 15, 2)->default(0);
            $table->decimal('achieved_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();

            $table->timestamps();

            $table->index('target_month');
            $table->index('target_type');
            $table->index('department');
            $table->index('factor');
            $table->index(['user_id', 'target_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_reviews');
    }
};
