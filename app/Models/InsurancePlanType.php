<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePlanType extends Model
{
    use HasFactory;

    protected $table = 'insurance_plan_types';

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
    ];
}
