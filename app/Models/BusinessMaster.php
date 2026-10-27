<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'business_master';

    protected $fillable = [
        'name',
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
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: last modifier user
     */
    public function modifier()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
