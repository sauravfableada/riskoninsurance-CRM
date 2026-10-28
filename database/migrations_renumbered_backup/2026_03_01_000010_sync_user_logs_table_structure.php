<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            // Add details column if missing
            if (!Schema::hasColumn('user_logs', 'details')) {
                $table->json('details')->nullable()->after('message');
            }

            // Add created_by if missing
            if (!Schema::hasColumn('user_logs', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('actioned_by');
            }

            // Add updated_by if missing
            if (!Schema::hasColumn('user_logs', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            // Add deleted_by if missing
            if (!Schema::hasColumn('user_logs', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }

            // Add deleted_at if missing
            if (!Schema::hasColumn('user_logs', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            if (Schema::hasColumn('user_logs', 'details')) {
                $table->dropColumn('details');
            }
            if (Schema::hasColumn('user_logs', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('user_logs', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('user_logs', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('user_logs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
