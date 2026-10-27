<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'area';

    protected $fillable = [
        'city_id',
        'area_name',
        'status',
        'created_by',
        'modified_by',
    ];

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_id');
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
