<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentHolder extends Model
{
    use HasFactory;

    protected $table = 'investment_holders';

    protected $fillable = [
        'holder_name',
        'family_group_id',
        'status',
    ];

    /**
     * Get active holders
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
