<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentMode extends Model
{
    use HasFactory;

    protected $table = 'investment_modes';

    protected $fillable = [
        'mode_name',
        'mode_code',
        'type',
        'description',
        'status',
    ];

    /**
     * Get modes by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type)->where('status', 'active');
    }

    /**
     * Get all active modes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
