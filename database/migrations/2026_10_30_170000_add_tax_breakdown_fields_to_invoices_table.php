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
            if (!Schema::hasColumn('invoices', 'cgst_percentage')) {
                $table->decimal('cgst_percentage', 8, 2)->nullable()->default(9.00)->after('tax_amount');
            }
            if (!Schema::hasColumn('invoices', 'sgst_percentage')) {
                $table->decimal('sgst_percentage', 8, 2)->nullable()->default(9.00)->after('cgst_percentage');
            }
            if (!Schema::hasColumn('invoices', 'igst_percentage')) {
                $table->decimal('igst_percentage', 8, 2)->nullable()->default(0.00)->after('sgst_percentage');
            }
            if (!Schema::hasColumn('invoices', 'cgst_amount')) {
                $table->decimal('cgst_amount', 15, 2)->nullable()->default(0.00)->after('igst_percentage');
            }
            if (!Schema::hasColumn('invoices', 'sgst_amount')) {
                $table->decimal('sgst_amount', 15, 2)->nullable()->default(0.00)->after('cgst_amount');
            }
            if (!Schema::hasColumn('invoices', 'igst_amount')) {
                $table->decimal('igst_amount', 15, 2)->nullable()->default(0.00)->after('sgst_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $cols = ['cgst_percentage', 'sgst_percentage', 'igst_percentage', 'cgst_amount', 'sgst_amount', 'igst_amount'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

