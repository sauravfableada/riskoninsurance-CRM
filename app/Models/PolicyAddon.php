<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyAddon extends Model
{
    protected $fillable = [
        'policy_id',
        'cover_name',
        'premium_amount',
    ];

    protected $casts = [
        'premium_amount' => 'decimal:2',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}