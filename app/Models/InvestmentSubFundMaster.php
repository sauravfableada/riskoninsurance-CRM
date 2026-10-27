<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentSubFundMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'investment_sub_fund_master';

    protected $fillable = [
        'fund_name',
        'sub_fund_name',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Scope: active records only
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
     * Relationship: last modifier user
     */
    public function modifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Relationship: fund type lookup
     */
    public function fundType()
    {
        return $this->belongsTo(\App\Models\FundType::class, 'fund_name', 'name');
    }
}
