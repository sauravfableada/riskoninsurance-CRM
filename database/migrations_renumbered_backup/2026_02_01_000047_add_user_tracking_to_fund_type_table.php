<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations — add created_by and modified_by to fund_type table.
     */
    public function up(): void
    {
        Schema::table('fund_type', function (Blueprint $table) {
            if (!Schema::hasColumn('fund_type', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('fund_type', 'modified_by')) {
                $table->unsignedBigInteger('modified_by')->nullable()->after('created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_type', function (Blueprint $table) {
            $table->dropColumnIfExists('created_by');
            $table->dropColumnIfExists('modified_by');
        });
    }
};
