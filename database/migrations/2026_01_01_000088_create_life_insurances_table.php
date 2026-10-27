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
        Schema::create('life_insurances', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('document_number')->unique();
            $table->unsignedBigInteger('family_group_id')->nullable();
            $table->unsignedBigInteger('policy_holder_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('gender')->nullable();
            $table->string('email_address')->nullable();
            $table->date('dob')->nullable();
            $table->string('relation_with_head')->nullable();
            
            // Policy Details
            $table->unsignedBigInteger('insurance_company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('zone')->nullable();
            $table->boolean('is_policy_received')->default(false);
            $table->string('policy_number')->nullable();
            $table->string('insurance_plan_name')->nullable();
            $table->string('insurance_plan_type')->nullable();
            $table->string('premium_mode')->nullable();
            $table->string('risk_term')->nullable();
            $table->string('premium_paying_term')->nullable();
            $table->decimal('sum_assured', 15, 2)->nullable();
            $table->date('risk_start_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->date('last_premium_payment_date')->nullable();
            $table->date('next_premium_due_date')->nullable();
            
            // Premium Details
            $table->decimal('basic_premium_amount', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('total_premium_amount', 15, 2)->nullable();
            $table->decimal('commissionable_amount', 15, 2)->nullable();
            
            // Status and Agency
            $table->string('policy_status')->nullable();
            $table->unsignedBigInteger('agency_level_id')->nullable();
            $table->unsignedBigInteger('broker_id')->nullable();
            $table->unsignedBigInteger('sub_agent_id')->nullable();
            $table->unsignedBigInteger('nominee_id')->nullable();
            $table->string('nominee_name')->nullable();
            $table->string('nominee_contact_no')->nullable();
            
            // Payment Details
            $table->unsignedBigInteger('paid_by_client')->nullable();
            $table->decimal('client_amount', 15, 2)->nullable();
            $table->unsignedBigInteger('paid_by_agent')->nullable();
            $table->decimal('agent_amount', 15, 2)->nullable();
            
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('life_insurances');
    }
};
