<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('title');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('status_id');
            $table->date('expected_close_date')->nullable();
            $table->unsignedBigInteger('assigned_user_id');
            $table->timestamps();

            $table->index(['customer_id', 'status_id']);
            $table->index(['currency_id', 'assigned_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
