<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostOffice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'post_office';

    protected $fillable = [
        'name',
        'status',
        'created_by',
        'modified_by',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'modified_by');
    }
}
