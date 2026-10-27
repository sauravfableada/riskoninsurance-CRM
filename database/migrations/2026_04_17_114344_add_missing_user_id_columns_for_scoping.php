<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds missing user_id columns for proper data scoping.
     * Staff users should only see their own data.
     */
    public function up(): void
    {
        // Add user_id to support_tickets table
        Schema::table('support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('support_tickets', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add user_id to pipelines table
        Schema::table('pipelines', function (Blueprint $table) {
            if (!Schema::hasColumn('pipelines', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add user_id to services table
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add assigned_user_id to invoices table (for staff assignment)
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Backfill existing data - set user_id from created_by (only valid user IDs)
        DB::statement('UPDATE support_tickets SET user_id = created_by WHERE user_id IS NULL AND created_by IN (SELECT id FROM users)');
        DB::statement('UPDATE pipelines SET user_id = created_by WHERE user_id IS NULL AND created_by IN (SELECT id FROM users)');
        DB::statement('UPDATE services SET user_id = created_by WHERE user_id IS NULL AND created_by IN (SELECT id FROM users)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('pipelines', function (Blueprint $table) {
            if (Schema::hasColumn('pipelines', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'assigned_user_id')) {
                $table->dropForeign(['assigned_user_id']);
                $table->dropColumn('assigned_user_id');
            }
        });
    }
};
