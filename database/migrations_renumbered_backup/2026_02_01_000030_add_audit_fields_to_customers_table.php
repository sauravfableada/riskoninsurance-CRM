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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('customers', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('customers', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes()->after('deleted_by');
            }

            // Optional: Add indexes for better performance on audit queries
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['deleted_by']);
            
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by', 'deleted_at']);
        });
    }
};
