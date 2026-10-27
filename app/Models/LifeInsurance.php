<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifeInsurance extends Model
{
    use HasFactory;

    protected $table = 'life_insurances';

    protected $fillable = [
        'transaction_date',
        'document_number',
        'family_group_id',
        'policy_holder_id',
        'first_name',
        'middle_name',
        'last_name',
        'address_line1',
        'address_line2',
        'city',
        'area',
        'mobile_number',
        'gender',
        'email_address',
        'dob',
        'relation_with_head',
        'insurance_company_id',
        'branch_id',
        'zone',
        'is_policy_received',
        'policy_number',
        'policy_documents',
        'insurance_plan_name',
        'insurance_plan_type',
        'premium_mode',
        'risk_term',
        'premium_paying_term',
        'sum_assured',
        'risk_start_date',
        'maturity_date',
        'last_premium_payment_date',
        'next_premium_due_date',
        'basic_premium_amount',
        'tax_amount',
        'total_premium_amount',
        'commissionable_amount',
        'policy_status',
        'agency_level_id',
        'broker_id',
        'sub_agent_id',
        'nominee_id',
        'nominee_name',
        'nominee_contact_no',
        'paid_by_client',
        'client_amount',
        'paid_by_agent',
        'agent_amount',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'is_policy_received' => 'boolean',
    ];

    public function familyGroup()
    {
        return $this->belongsTo(FamilyGroup::class);
    }

    public function policyHolder()
    {
        return $this->belongsTo(Customer::class, 'policy_holder_id');
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(\App\Models\CompanyMaster::class, 'insurance_company_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }

    public function policyRiders()
    {
        return $this->hasMany(\App\Models\PolicyRiderModel::class, 'policy_id');
    }

    public function policyDocuments()
    {
        return $this->hasMany(\App\Models\PolicyDocumentModel::class, 'policy_id');
    }

    public function policyDocumentRows()
    {
        return $this->hasMany(\App\Models\PolicyDocumentModel::class, 'policy_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function agencyLevel()
    {
        return $this->belongsTo(\App\Models\AgencyLevel::class, 'agency_level_id');
    }

    public function subAgent()
    {
        return $this->belongsTo(\App\Models\Agency::class, 'sub_agent_id');
    }

    public function paidByClientMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'paid_by_client');
    }

    public function paidByAgentMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'paid_by_agent');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
