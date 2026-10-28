<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_id')->nullable();

            $table->string('product_name')->nullable();

            $table->decimal('amount', 12, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_price', 12, 2);

            $table->timestamps();

            // Foreign key
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
