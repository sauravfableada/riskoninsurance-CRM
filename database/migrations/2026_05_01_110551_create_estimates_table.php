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
        Schema::create('estimates', function (Blueprint $table) {
            $table->id('estimate_id');
            $table->integer('customer_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('estimate_no')->nullable();
            $table->date('estimate_date')->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('product_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('status')->default('draft');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
