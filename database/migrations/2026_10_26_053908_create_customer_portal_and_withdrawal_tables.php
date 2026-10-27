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
        // Add password and remember_token to customers if not present
        if (!Schema::hasColumn('customers', 'password')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('password')->nullable()->after('email');
                $table->rememberToken()->after('password');
            });
        }

        // Create withdrawal_requests table
        if (!Schema::hasTable('withdrawal_requests')) {
            Schema::create('withdrawal_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // sub-agent / user
                $table->decimal('amount', 12, 2);
                $table->string('payout_method', 50)->default('bank_transfer'); // bank_transfer, upi, etc.
                $table->text('payout_details')->nullable(); // Account number, IFSC, UPI ID, etc.
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->text('subagent_note')->nullable();
                $table->text('admin_remarks')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');

        if (Schema::hasColumn('customers', 'password')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn(['password', 'remember_token']);
            });
        }
    }
};
