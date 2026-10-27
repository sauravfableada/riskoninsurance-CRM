<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broker extends Model
{
    use HasFactory;

    protected $table = 'brokers';

    protected $fillable = [
        'broker_name',
        'status',
    ];

    /**
     * Get active brokers
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [1, '1', true, 'active']);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [1, '1', true, 'active'], true);
    }
}
