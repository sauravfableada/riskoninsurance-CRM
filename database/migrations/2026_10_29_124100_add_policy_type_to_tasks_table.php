<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'policy_type_id')) {
                $table->unsignedBigInteger('policy_type_id')->nullable()->after('project_id');
                $table->index('policy_type_id');
            }
            if (!Schema::hasColumn('tasks', 'policy_sub_type_id')) {
                $table->unsignedBigInteger('policy_sub_type_id')->nullable()->after('policy_type_id');
                $table->index('policy_sub_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'policy_sub_type_id')) {
                $table->dropColumn('policy_sub_type_id');
            }
            if (Schema::hasColumn('tasks', 'policy_type_id')) {
                $table->dropColumn('policy_type_id');
            }
        });
    }
};

