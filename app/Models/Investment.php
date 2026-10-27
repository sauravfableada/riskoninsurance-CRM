<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $table = 'investments';

    protected $fillable = [
        // Step 1: Personal Details
        'transaction_date',
        'document_number',
        'family_group_id',
        'investment_holder_id',
        'first_name',
        'middle_name',
        'last_name',
        'address_line_1',
        'address_line_2',
        'city',
        'area',
        'mobile_number',
        'email_address',
        'gender',
        'date_of_birth',
        'family_head_name',
        'relation_with_head',
        'created_by',

        // Step 2: Investment Type
        'investment_type_main',

        // Mutual Fund Fields
        'mf_transaction_type_id',
        'mf_folio_number_id',
        'mf_investment_type_id',
        'mf_fund_name_id',
        'mf_fund_type_id',
        'mf_broker_id',
        'mf_mode_id',
        'mf_start_date',
        'mf_units_purchase',
        'mf_unit_price',
        'mf_investment_amount',
        'mf_total_balance_unit',
        'mf_total_fund_value',
        'mf_total_count',
        'mf_total_investment_balance',
        'mf_payment_bank_id',
        'mf_remarks',
        'mf_document',

        // Bank Fields
        'bank_transaction_type_id',
        'bank_account_type',
        'bank_name_id',
        'bank_investment_product_id',
        'bank_customer_no',
        'bank_certificate_no',
        'bank_investment_amount',
        'bank_total_invested_amount',
        'bank_mode_id',
        'bank_tenure_mode',
        'bank_tenure',
        'bank_deposit_date',
        'bank_mature_date',
        'bank_interest_rate',
        'bank_interest_amount',
        'bank_interest_frequency',
        'bank_payment_bank_id',
        'bank_paid_by_client',
        'bank_paid_by_agent',
        'bank_remarks',
        'bank_document',

        // Post Office Fields
        'po_transaction_type_id',
        'po_account_type',
        'po_certificate_account_no',
        'po_customer_name',
        'po_investment_amount',
        'po_total_invested_amount',
        'po_mode',
        'po_tenure_mode',
        'po_tenure',
        'po_opening_date',
        'po_mature_date',
        'po_interest_rate',
        'po_interest_amount',
        'po_interest_frequency',
        'po_payment_bank_id',
        'po_paid_by_client',
        'po_paid_by_agent',
        'po_remarks',
        'po_document',

        // Government Bond Fields
        'bond_transaction_type_id',
        'bond_id_isin',
        'bond_name',
        'bond_issuer',
        'bond_type',
        'bond_sub_type_id',
        'bond_issue_date',
        'bond_face_value',
        'bond_purchase_date',
        'bond_purchase_price',
        'bond_quantity',
        'bond_tax_status',
        'bond_callable_status',
        'bond_maturity_date',
        'bond_total_return',
        'bond_ytm',
        'bond_coupon_rate',
        'bond_coupon_frequency',
        'bond_credit_rating',
        'bond_investment_amount',
        'bond_total_invested_amount',
        'bond_payment_bank_id',
        'bond_paid_by_client',
        'bond_paid_by_agent',
        'bond_remarks',
        'bond_document',

        // Other Investment Fields
        'other_transaction_type_id',
        'other_investment_to_place',
        'other_invested_party_name',
        'other_investment_type_id',
        'other_document_no',
        'other_mode',
        'other_tenure_mode',
        'other_tenure',
        'other_deposit_date',
        'other_mature_date',
        'other_next_due_date',
        'other_investment_amount',
        'other_interest_rate',
        'other_payment_bank_id',
        'other_paid_by_client',
        'other_paid_by_agent',
        'other_remarks',
        'other_document',
    ];

    protected $casts = [
        'transaction_date'  => 'date',
        'date_of_birth'     => 'date',
        'mf_start_date'     => 'date',
        'bank_deposit_date' => 'date',
        'bank_mature_date'  => 'date',
        'po_opening_date'   => 'date',
        'po_mature_date'    => 'date',
        'bond_issue_date'   => 'date',
        'bond_purchase_date'=> 'date',
        'bond_maturity_date'=> 'date',
        'other_deposit_date'=> 'date',
        'other_mature_date' => 'date',
        'other_next_due_date'=> 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function familyGroup()
    {
        return $this->belongsTo(FamilyGroup::class, 'family_group_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function extraDocuments()
    {
        return $this->hasMany(InvestmentExtraDocument::class, 'investment_id');
    }
}
