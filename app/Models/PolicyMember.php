<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyMember extends Model
{
    protected $fillable = [
        'policy_id',
        'name',
        'relation',
        'dob',
        'gender',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}