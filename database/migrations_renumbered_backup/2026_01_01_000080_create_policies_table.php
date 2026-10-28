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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();

            // Step 1: Basic Info
            $table->string('policy_number')->unique();
            $table->string('staff_name')->nullable();
            $table->unsignedBigInteger('family_group_id')->nullable();
            $table->unsignedBigInteger('holder_id')->nullable();
            $table->unsignedBigInteger('policy_type_id');
            $table->unsignedBigInteger('policy_sub_type_id')->nullable();
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();

            // Step 2: Vehicle Details (if vehicle policy)
            $table->string('vehicle_registration_number')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('engine_capacity')->nullable();
            $table->string('gross_vehicle_weight')->nullable();
            $table->string('no_claim_bonus')->nullable();
            $table->string('year_of_manufacture')->nullable();

            // Step 3: Policy Details (Common)
            $table->string('payment_mode')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('nominee_name')->nullable();
            $table->string('nominee_contact')->nullable();

            // Step 4/5: Premium Details
            $table->decimal('vehicle_idv', 15, 2)->nullable();
            $table->decimal('sum_assured', 15, 2)->nullable();
            $table->decimal('premium_amount', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->text('cng_lpg_unit')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();

            // PDF Upload
            $table->string('policy_pdf')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('family_group_id')->references('id')->on('family_groups')->onDelete('set null');
            $table->foreign('holder_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('policy_type_id')->references('id')->on('insurance_policy_types');
            $table->foreign('policy_sub_type_id')->references('id')->on('insurance_policy_sub_types')->onDelete('set null');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('company_master');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
