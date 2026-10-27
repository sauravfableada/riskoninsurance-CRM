<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $table = 'insurance_companies';

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];
}
