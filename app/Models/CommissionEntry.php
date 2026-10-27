<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionEntry extends Model
{
    use HasFactory;

    protected $table = 'commission_entries';

    protected $fillable = [
        'commission_rule_id',
        'policy_id',
        'user_id',
        'agent_id',
        'premium_amount',
        'pay_in_amount',
        'pay_in_percentage',
        'pay_out_amount',
        'pay_out_percentage',
        'net_admin_profit',
        'commission_amount',      // sub-agent/agent payout for backwards-compat
        'commission_percentage',  // payout %
        'payment_status',  // e.g. 'pending', 'paid', 'cancelled'
        'remarks',
        'entry_date',
    ];

    protected $casts = [
        'premium_amount'       => 'decimal:2',
        'pay_in_amount'        => 'decimal:2',
        'pay_in_percentage'    => 'decimal:2',
        'pay_out_amount'       => 'decimal:2',
        'pay_out_percentage'   => 'decimal:2',
        'net_admin_profit'     => 'decimal:2',
        'commission_amount'    => 'decimal:2',
        'commission_percentage'=> 'decimal:2',
        'entry_date'           => 'date',
    ];

    /**
     * Relationship: belongs to a commission rule.
     */
    public function commissionRule()
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    /**
     * Relationship: belongs to a policy.
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    /**
     * Relationship: belongs to an agent.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: filter by payment status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }
}
