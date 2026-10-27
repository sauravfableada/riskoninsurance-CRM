<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentFundName extends Model
{
    use HasFactory;

    protected $table = 'investment_fund_names';

    protected $fillable = [
        'fund_name',
        'fund_code',
        'fund_house',
        'category',
        'amc_name',
        'status',
    ];

    /**
     * Get funds by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category)->where('status', 'active');
    }

    /**
     * Get all active funds
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
