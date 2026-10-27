<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fund_type';

    protected $fillable = [
        'name',
        'status',
        'created_by',
        'modified_by',
    ];

    /**
     * Get active fund types
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Relationship: creator user
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Relationship: modifier user
     */
    public function modifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'modified_by');
    }
}

