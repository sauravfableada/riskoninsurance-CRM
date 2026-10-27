<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRulesModel extends Model
{
    protected $table = 'commission_rules';

    protected $fillable = [
        'company_rule_id',
        'company_id',
        'policy_type_id',
        'agent_id',
        'commission_for',
        'rule_name',
        'commission_type',
        'commission_value',
        'min_premium',
        'max_premium',
        'effective_from',
        'effective_to',
        'description',
        'is_active',
    ];
}
