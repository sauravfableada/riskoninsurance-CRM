<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyRiderModel extends Model
{
    protected $table = 'policy_riders';

    protected $fillable = [
        'policy_id',
        'rider_name',
        'sum_assured',
        'risk_term',
        'premium_paying_term',
        'created_at',
        'updated_at',
    ];
}
