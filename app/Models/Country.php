<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_code',
        'is_active',
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
