<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plan';

    protected $fillable = [
        'name',
        'staff_limit',
    ];

    public function userPlans(): HasMany
    {
        return $this->hasMany(SubscriptionUserPlan::class, 'subscription_id');
    }
}
