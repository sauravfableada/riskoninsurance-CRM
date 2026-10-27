<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentTransactionType extends Model
{
    use HasFactory;

    protected $table = 'investment_transaction_types';

    protected $fillable = [
        'name',
        'type',
        'status',
    ];

    /**
     * Get transaction types by category (mutual_fund, bank, post_office, etc.)
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('type', $category)->where('status', 'active');
    }

    /**
     * Get all active transaction types
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
