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
        Schema::table('commission_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_rules', 'pay_in_type')) {
                $table->enum('pay_in_type', ['percentage', 'flat'])->default('percentage')->after('rule_name');
            }
            if (!Schema::hasColumn('commission_rules', 'pay_in_value')) {
                $table->decimal('pay_in_value', 12, 2)->default(0)->after('pay_in_type');
            }
            if (!Schema::hasColumn('commission_rules', 'pay_out_type')) {
                $table->enum('pay_out_type', ['percentage', 'flat'])->default('percentage')->after('pay_in_value');
            }
            if (!Schema::hasColumn('commission_rules', 'pay_out_value')) {
                $table->decimal('pay_out_value', 12, 2)->default(0)->after('pay_out_type');
            }
            if (!Schema::hasColumn('commission_rules', 'pay_out_based_on')) {
                // 'admin_commission' means % of admin's Pay-In (e.g. 10% of 10000 = 1000); 'premium' means % of gross policy premium
                $table->enum('pay_out_based_on', ['admin_commission', 'premium'])->default('admin_commission')->after('pay_out_value');
            }
        });

        Schema::table('commission_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_entries', 'pay_in_amount')) {
                $table->decimal('pay_in_amount', 15, 2)->default(0)->after('premium_amount');
            }
            if (!Schema::hasColumn('commission_entries', 'pay_in_percentage')) {
                $table->decimal('pay_in_percentage', 8, 2)->nullable()->after('pay_in_amount');
            }
            if (!Schema::hasColumn('commission_entries', 'pay_out_amount')) {
                $table->decimal('pay_out_amount', 15, 2)->default(0)->after('pay_in_percentage');
            }
            if (!Schema::hasColumn('commission_entries', 'pay_out_percentage')) {
                $table->decimal('pay_out_percentage', 8, 2)->nullable()->after('pay_out_amount');
            }
            if (!Schema::hasColumn('commission_entries', 'net_admin_profit')) {
                $table->decimal('net_admin_profit', 15, 2)->default(0)->after('pay_out_percentage');
            }
        });

        Schema::table('policies', function (Blueprint $table) {
            $afterCol = Schema::hasColumn('policies', 'commission_percentage')
                ? 'commission_percentage'
                : (Schema::hasColumn('policies', 'premium_amount') ? 'premium_amount' : null);

            if (!Schema::hasColumn('policies', 'pay_in_amount')) {
                $col = $table->decimal('pay_in_amount', 15, 2)->nullable();
                if ($afterCol) {
                    $col->after($afterCol);
                }
            }
            if (!Schema::hasColumn('policies', 'pay_in_percentage')) {
                $table->decimal('pay_in_percentage', 8, 2)->nullable()->after('pay_in_amount');
            }
            if (!Schema::hasColumn('policies', 'pay_out_amount')) {
                $table->decimal('pay_out_amount', 15, 2)->nullable()->after('pay_in_percentage');
            }
            if (!Schema::hasColumn('policies', 'pay_out_percentage')) {
                $table->decimal('pay_out_percentage', 8, 2)->nullable()->after('pay_out_amount');
            }
            if (!Schema::hasColumn('policies', 'commission_manual_override')) {
                $table->boolean('commission_manual_override')->default(false)->after('pay_out_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropColumn(['pay_in_type', 'pay_in_value', 'pay_out_type', 'pay_out_value', 'pay_out_based_on']);
        });

        Schema::table('commission_entries', function (Blueprint $table) {
            $table->dropColumn(['pay_in_amount', 'pay_in_percentage', 'pay_out_amount', 'pay_out_percentage', 'net_admin_profit']);
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn(['pay_in_amount', 'pay_in_percentage', 'pay_out_amount', 'pay_out_percentage', 'commission_manual_override']);
        });
    }
};
