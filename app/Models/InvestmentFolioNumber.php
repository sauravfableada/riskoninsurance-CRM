<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentFolioNumber extends Model
{
    use HasFactory;

    protected $table = 'investment_folio_numbers';

    protected $fillable = [
        'folio_number',
        'investor_name',
        'fund_name_id',
        'broker_id',
        'status',
    ];

    /**
     * Get all active folios
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
