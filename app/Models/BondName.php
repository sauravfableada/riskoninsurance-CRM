<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BondName extends Model
{
    use HasFactory;

    protected $table = 'bond_names';

    protected $fillable = [
        'bond_name',
        'status',
    ];

    /**
     * Get active bonds
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orderBy('bond_name', 'ASC');
    }
}
