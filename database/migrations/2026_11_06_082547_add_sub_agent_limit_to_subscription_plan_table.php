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
            if (!Schema::hasColumn('subscription_plan', 'sub_agent_limit')) {
                $table->string('sub_agent_limit')->nullable()->after('staff_limit');
            }
        });

        // Set default values for existing plans if needed
        \Illuminate\Support\Facades\DB::table('subscription_plan')
            ->where('name', 'Basic Plan')
            ->whereNull('sub_agent_limit')
            ->update(['sub_agent_limit' => '5']);

        \Illuminate\Support\Facades\DB::table('subscription_plan')
            ->where('name', 'Premium Plan')
            ->whereNull('sub_agent_limit')
            ->update(['sub_agent_limit' => '10']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plan', 'sub_agent_limit')) {
                $table->dropColumn('sub_agent_limit');
            }
        });
    }
};
