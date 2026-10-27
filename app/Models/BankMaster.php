<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bank_master';

    protected $fillable = [
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get active banks
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
