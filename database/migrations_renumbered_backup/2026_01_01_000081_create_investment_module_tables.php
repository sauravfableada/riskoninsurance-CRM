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
        // 1. Investment Types
        Schema::create('investment_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name');
            $table->string('type_code')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Investment Transaction Types
        Schema::create('investment_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable(); // category like mutual_fund, bank
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 3. Bank Master
        Schema::create('bank_master', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // 4. Brokers
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->string('broker_name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 5. Investment Modes
        Schema::create('investment_modes', function (Blueprint $table) {
            $table->id();
            $table->string('mode_name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 6. Investment Fund Names
        Schema::create('investment_fund_names', function (Blueprint $table) {
            $table->id();
            $table->string('fund_name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 7. Master Fund Types
        Schema::create('fund_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // 8. Investment Folio Numbers
        Schema::create('investment_folio_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('folio_number');
            $table->string('investor_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 9. Bond Names
        Schema::create('bond_names', function (Blueprint $table) {
            $table->id();
            $table->string('bond_name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 10. Sub Bond Types
        Schema::create('sub_bond_types', function (Blueprint $table) {
            $table->id();
            $table->integer('bond_id')->nullable();
            $table->string('sub_bond_type');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 11. Investment Holders
        Schema::create('investment_holders', function (Blueprint $table) {
            $table->id();
            $table->integer('family_group_id')->nullable();
            $table->string('holder_name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 12. Main Investments Table
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            
            // Personal Details
            $table->date('transaction_date')->nullable();
            $table->string('document_number')->nullable();
            $table->integer('family_group_id')->nullable();
            $table->integer('investment_holder_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('family_head_name')->nullable();
            $table->string('relation_with_head')->nullable();

            // Investment Category
            $table->string('investment_type_main')->default('mutual_fund');

            // Mutual Fund Fields
            $table->integer('mf_transaction_type_id')->nullable();
            $table->integer('mf_folio_number_id')->nullable();
            $table->integer('mf_investment_type_id')->nullable();
            $table->integer('mf_fund_name_id')->nullable();
            $table->integer('mf_fund_type_id')->nullable();
            $table->integer('mf_broker_id')->nullable();
            $table->integer('mf_mode_id')->nullable();
            $table->date('mf_start_date')->nullable();
            $table->string('mf_units_purchase')->nullable();
            $table->string('mf_unit_price')->nullable();
            $table->decimal('mf_investment_amount', 15, 2)->nullable();
            $table->string('mf_total_balance_unit')->nullable();
            $table->decimal('mf_total_fund_value', 15, 2)->nullable();
            $table->integer('mf_total_count')->nullable();
            $table->decimal('mf_total_investment_balance', 15, 2)->nullable();
            $table->integer('mf_payment_bank_id')->nullable();
            $table->text('mf_remarks')->nullable();
            $table->string('mf_document')->nullable();

            // Bank Fields
            $table->integer('bank_transaction_type_id')->nullable();
            $table->string('bank_account_type')->nullable();
            $table->integer('bank_name_id')->nullable();
            $table->integer('bank_investment_product_id')->nullable();
            $table->string('bank_customer_no')->nullable();
            $table->string('bank_certificate_no')->nullable();
            $table->decimal('bank_investment_amount', 15, 2)->nullable();
            $table->decimal('bank_total_invested_amount', 15, 2)->nullable();
            $table->integer('bank_mode_id')->nullable();
            $table->string('bank_tenure_mode')->nullable();
            $table->string('bank_tenure')->nullable();
            $table->date('bank_deposit_date')->nullable();
            $table->date('bank_mature_date')->nullable();
            $table->string('bank_interest_rate')->nullable();
            $table->decimal('bank_interest_amount', 15, 2)->nullable();
            $table->string('bank_interest_frequency')->nullable();
            $table->integer('bank_payment_bank_id')->nullable();
            $table->decimal('bank_paid_by_client', 15, 2)->nullable();
            $table->decimal('bank_paid_by_agent', 15, 2)->nullable();
            $table->text('bank_remarks')->nullable();
            $table->string('bank_document')->nullable();

            // Post Office Fields
            $table->integer('po_transaction_type_id')->nullable();
            $table->string('po_account_type')->nullable();
            $table->string('po_certificate_account_no')->nullable();
            $table->string('po_customer_name')->nullable();
            $table->decimal('po_investment_amount', 15, 2)->nullable();
            $table->decimal('po_total_invested_amount', 15, 2)->nullable();
            $table->string('po_mode')->nullable();
            $table->string('po_tenure_mode')->nullable();
            $table->string('po_tenure')->nullable();
            $table->date('po_opening_date')->nullable();
            $table->date('po_mature_date')->nullable();
            $table->string('po_interest_rate')->nullable();
            $table->decimal('po_interest_amount', 15, 2)->nullable();
            $table->string('po_interest_frequency')->nullable();
            $table->integer('po_payment_bank_id')->nullable();
            $table->decimal('po_paid_by_client', 15, 2)->nullable();
            $table->decimal('po_paid_by_agent', 15, 2)->nullable();
            $table->text('po_remarks')->nullable();
            $table->string('po_document')->nullable();

            // Bond Fields
            $table->integer('bond_transaction_type_id')->nullable();
            $table->string('bond_id_isin')->nullable();
            $table->string('bond_name')->nullable();
            $table->string('bond_issuer')->nullable();
            $table->integer('bond_type')->nullable();
            $table->integer('bond_sub_type_id')->nullable();
            $table->date('bond_issue_date')->nullable();
            $table->decimal('bond_face_value', 15, 2)->nullable();
            $table->date('bond_purchase_date')->nullable();
            $table->decimal('bond_purchase_price', 15, 2)->nullable();
            $table->string('bond_quantity')->nullable();
            $table->string('bond_tax_status')->nullable();
            $table->string('bond_callable_status')->nullable();
            $table->date('bond_maturity_date')->nullable();
            $table->decimal('bond_total_return', 15, 2)->nullable();
            $table->string('bond_ytm')->nullable();
            $table->string('bond_coupon_rate')->nullable();
            $table->string('bond_coupon_frequency')->nullable();
            $table->string('bond_credit_rating')->nullable();
            $table->decimal('bond_investment_amount', 15, 2)->nullable();
            $table->decimal('bond_total_invested_amount', 15, 2)->nullable();
            $table->integer('bond_payment_bank_id')->nullable();
            $table->decimal('bond_paid_by_client', 15, 2)->nullable();
            $table->decimal('bond_paid_by_agent', 15, 2)->nullable();
            $table->text('bond_remarks')->nullable();
            $table->string('bond_document')->nullable();

            // Other Fields
            $table->integer('other_transaction_type_id')->nullable();
            $table->string('other_investment_to_place')->nullable();
            $table->string('other_invested_party_name')->nullable();
            $table->integer('other_investment_type_id')->nullable();
            $table->string('other_document_no')->nullable();
            $table->string('other_mode')->nullable();
            $table->string('other_tenure_mode')->nullable();
            $table->string('other_tenure')->nullable();
            $table->date('other_deposit_date')->nullable();
            $table->date('other_mature_date')->nullable();
            $table->date('other_next_due_date')->nullable();
            $table->decimal('other_investment_amount', 15, 2)->nullable();
            $table->string('other_interest_rate')->nullable();
            $table->integer('other_payment_bank_id')->nullable();
            $table->decimal('other_paid_by_client', 15, 2)->nullable();
            $table->decimal('other_paid_by_agent', 15, 2)->nullable();
            $table->text('other_remarks')->nullable();
            $table->string('other_document')->nullable();

            $table->integer('created_by')->nullable();
            $table->timestamps();
        });

        // 13. Investment Extra Documents
        Schema::create('investment_extra_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('investment_id');
            $table->integer('document_type_id')->nullable();
            $table->integer('document_name_id')->nullable();
            $table->string('document_nos')->nullable();
            $table->string('document_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_extra_documents');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investment_holders');
        Schema::dropIfExists('sub_bond_types');
        Schema::dropIfExists('bond_names');
        Schema::dropIfExists('investment_folio_numbers');
        Schema::dropIfExists('fund_type');
        Schema::dropIfExists('investment_fund_names');
        Schema::dropIfExists('investment_modes');
        Schema::dropIfExists('brokers');
        Schema::dropIfExists('bank_master');
        Schema::dropIfExists('investment_transaction_types');
        Schema::dropIfExists('investment_types');
    }
};
