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
        Schema::table('insurance_policy_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('insurance_policy_sub_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_policy_types', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('insurance_policy_sub_types', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
