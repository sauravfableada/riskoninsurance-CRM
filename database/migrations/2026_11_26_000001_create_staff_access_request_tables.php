<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_access_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('module', 100);
            $table->string('action', 50);
            $table->string('permission', 150);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('admin_comment')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'sar_user_status_idx');
            $table->index(['status', 'created_at'], 'sar_status_created_idx');
            $table->index(['user_id', 'permission', 'status'], 'sar_user_perm_status_idx');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('staff_permission_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('module', 100);
            $table->string('action', 50);
            $table->string('permission', 150);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->unsignedBigInteger('access_request_id')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'permission', 'is_active'], 'spg_user_perm_active_idx');
            $table->index(['user_id', 'is_active'], 'spg_user_active_idx');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('access_request_id')
                ->references('id')
                ->on('staff_access_requests')
                ->nullOnDelete();

            $table->foreign('granted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('revoked_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('access_request_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('access_request_id')->nullable();
            $table->unsignedBigInteger('grant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event', 50);
            $table->string('permission', 150);
            $table->string('module', 100);
            $table->string('action', 50);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at'], 'arh_user_created_idx');
            $table->index(['access_request_id', 'created_at'], 'arh_request_created_idx');

            $table->foreign('access_request_id')
                ->references('id')
                ->on('staff_access_requests')
                ->cascadeOnDelete();

            $table->foreign('grant_id')
                ->references('id')
                ->on('staff_permission_grants')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('actor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_request_histories');
        Schema::dropIfExists('staff_permission_grants');
        Schema::dropIfExists('staff_access_requests');
    }
};
