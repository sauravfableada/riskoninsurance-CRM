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
            if (!Schema::hasColumn('subscription_plan', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('subscription_plan', 'billing_cycle')) {
                $table->string('billing_cycle')->nullable(); // e.g., 'month', 'year'
            }
        });

        Schema::table('subscription_user_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_user_plan', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('subscription_user_plan', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            if (!Schema::hasColumn('subscription_user_plan', 'auto_renew')) {
                $table->boolean('auto_renew')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plan', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('subscription_plan', 'billing_cycle')) {
                $table->dropColumn('billing_cycle');
            }
        });

        Schema::table('subscription_user_plan', function (Blueprint $table) {
            $dropCols = [];
            if (Schema::hasColumn('subscription_user_plan', 'start_date')) $dropCols[] = 'start_date';
            if (Schema::hasColumn('subscription_user_plan', 'end_date')) $dropCols[] = 'end_date';
            if (Schema::hasColumn('subscription_user_plan', 'auto_renew')) $dropCols[] = 'auto_renew';
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
