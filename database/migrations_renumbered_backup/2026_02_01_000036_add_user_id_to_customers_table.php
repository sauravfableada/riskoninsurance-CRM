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
            if (!Schema::hasColumn('customers', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('is_active');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('customers', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
            }
            
            if (!Schema::hasColumn('customers', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            
            if (!Schema::hasColumn('customers', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            
            if (Schema::hasColumn('customers', 'created_by')) {
                $table->dropColumn('created_by');
            }
            
            if (Schema::hasColumn('customers', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            
            if (Schema::hasColumn('customers', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
        });
    }
};
