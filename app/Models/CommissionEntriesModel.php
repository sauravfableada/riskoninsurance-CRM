<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionEntriesModel extends Model
{
    protected $table = 'commission_entries';

    protected $fillable = [
        'commission_rule_id',
        'policy_id',
        'customer_id',
        'staff_id',
        'agent_id',
        'premium_amount',
        'commission_type',
        'commission_value',
        'commission_amount',
        'commission_percentage',
        'payment_status',
        'remarks',
        'entry_date',
        'created_at',
        'updated_at',
    ];
}
