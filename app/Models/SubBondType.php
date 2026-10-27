<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubBondType extends Model
{
    use HasFactory;

    protected $table = 'sub_bond_types';

    protected $fillable = [
        'bond_id',
        'sub_bond_type',
        'status',
    ];

    public function bond()
    {
        return $this->belongsTo(BondName::class, 'bond_id');
    }

    /**
     * Get active sub bond types
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orderBy('sub_bond_type', 'ASC');
    }

    /**
     * Get by bond id
     */
    public function scopeByBond($query, $bondId)
    {
        return $query->where('bond_id', $bondId)->where('status', 'active');
    }
}
