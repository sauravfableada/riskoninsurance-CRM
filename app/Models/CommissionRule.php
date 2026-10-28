<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionRule extends Model
{
    use HasFactory;

    protected $table = 'commission_rules';

    protected $fillable = [
        'company_rule_id',
        'company_id',
        'policy_type_id',
        'policy_sub_type_id',
        'policy_id',
        'agent_id',
        'rule_name',
        'pay_in_type',       // 'percentage' or 'flat' (admin commission from insurance company)
        'pay_in_value',      // percentage or flat amount
        'pay_out_type',      // 'percentage' or 'flat' (sub-agent/staff payout)
        'pay_out_value',     // percentage or flat amount
        'pay_out_based_on',  // 'admin_commission' or 'premium'
        'commission_type',   // legacy fallback
        'commission_value',  // legacy fallback
        'commission_on',
        'min_premium',
        'max_premium',
        'effective_from',
        'effective_to',
        'description',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'pay_in_value'     => 'decimal:2',
        'pay_out_value'    => 'decimal:2',
        'commission_value' => 'decimal:2',
        'commission_on'    => 'date',
        'min_premium'      => 'decimal:2',
        'max_premium'      => 'decimal:2',
        'effective_from'   => 'date',
        'effective_to'     => 'date',
        'is_active'        => 'boolean',
    ];

    /**
     * Relationship: belongs to a user (staff).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: belongs to a company rule.
     */
    public function companyRule()
    {
        return $this->belongsTo(CompanyRule::class, 'company_rule_id');
    }

    /**
     * Relationship: belongs to an insurance company.
     */
    public function company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id');
    }

    /**
     * Relationship: belongs to a policy.
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    /**
     * Relationship: belongs to an insurance policy type.
     */
    public function policyType()
    {
        return $this->belongsTo(InsurancePolicyType::class, 'policy_type_id');
    }

    /**
     * Relationship: belongs to an insurance policy sub type.
     */
    public function policySubType()
    {
        return $this->belongsTo(InsurancePolicySubType::class, 'policy_sub_type_id');
    }

    /**
     * Relationship: belongs to an agent.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Relationship: has many commission entries.
     */
    public function commissionEntries()
    {
        return $this->hasMany(CommissionEntry::class, 'commission_rule_id');
    }

    /**
     * Scope: only active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
