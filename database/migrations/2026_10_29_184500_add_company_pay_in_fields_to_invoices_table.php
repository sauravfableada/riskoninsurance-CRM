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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'invoice_type')) {
                $table->string('invoice_type', 50)->default('company_pay_in')->after('id');
            }
            if (!Schema::hasColumn('invoices', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('invoices', 'account_name')) {
                $table->string('account_name')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('invoices', 'account_id_code')) {
                $table->string('account_id_code')->nullable()->after('account_name');
            }
            if (!Schema::hasColumn('invoices', 'account_type')) {
                $table->string('account_type')->nullable()->after('account_id_code');
            }
            if (!Schema::hasColumn('invoices', 'organization')) {
                $table->string('organization')->nullable()->after('account_type');
            }
            if (!Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('comment');
            }
            if (!Schema::hasColumn('invoices', 'tax_percentage')) {
                $table->decimal('tax_percentage', 8, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_percentage');
            }
            if (!Schema::hasColumn('invoices', 'grand_total')) {
                $table->decimal('grand_total', 15, 2)->default(0)->after('total_amount');
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'policy_id')) {
                $table->unsignedBigInteger('policy_id')->nullable()->after('invoice_id');
            }
            if (!Schema::hasColumn('invoice_items', 'policy_number')) {
                $table->string('policy_number')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('invoice_items', 'holder_name')) {
                $table->string('holder_name')->nullable()->after('policy_number');
            }
            if (!Schema::hasColumn('invoice_items', 'policy_type_name')) {
                $table->string('policy_type_name')->nullable()->after('holder_name');
            }
            if (!Schema::hasColumn('invoice_items', 'gross_premium')) {
                $table->decimal('gross_premium', 15, 2)->nullable()->after('policy_type_name');
            }
            if (!Schema::hasColumn('invoice_items', 'pay_in_percentage')) {
                $table->decimal('pay_in_percentage', 8, 2)->nullable()->after('gross_premium');
            }
            if (!Schema::hasColumn('invoice_items', 'pay_in_amount')) {
                $table->decimal('pay_in_amount', 15, 2)->nullable()->after('pay_in_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $cols = ['invoice_type', 'company_id', 'account_name', 'account_id_code', 'account_type', 'organization', 'subtotal', 'tax_percentage', 'tax_amount', 'grand_total'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $cols = ['policy_id', 'policy_number', 'holder_name', 'policy_type_name', 'gross_premium', 'pay_in_percentage', 'pay_in_amount'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('invoice_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

