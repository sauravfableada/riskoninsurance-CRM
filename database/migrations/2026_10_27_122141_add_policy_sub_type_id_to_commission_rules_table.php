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
            if (!Schema::hasColumn('commission_rules', 'policy_sub_type_id')) {
                $table->unsignedBigInteger('policy_sub_type_id')->nullable()->after('policy_type_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            if (Schema::hasColumn('commission_rules', 'policy_sub_type_id')) {
                $table->dropColumn('policy_sub_type_id');
            }
        });
    }
};
