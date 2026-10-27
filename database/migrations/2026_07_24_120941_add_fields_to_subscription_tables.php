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
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable();
            $table->string('billing_cycle')->nullable(); // e.g., 'month', 'year'
        });

        Schema::table('subscription_user_plan', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('auto_renew')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->dropColumn(['price', 'billing_cycle']);
        });

        Schema::table('subscription_user_plan', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'auto_renew']);
        });
    }
};
