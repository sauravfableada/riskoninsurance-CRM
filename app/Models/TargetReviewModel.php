<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetReviewModel extends Model
{
    use HasFactory;

    protected $table = 'target_reviews';

    protected $fillable = [
        'transaction_date',
        'transaction_number',
        'target_type',
        'target_month',
        'department',
        'factor',
        'target_amount',
        'achieved_amount',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'target_amount' => 'float',
        'achieved_amount' => 'float',
    ];
}
