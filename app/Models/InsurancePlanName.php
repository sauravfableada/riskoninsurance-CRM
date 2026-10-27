<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePlanName extends Model
{
    use HasFactory;

    protected $table = 'insurance_plan_names';

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
    ];
}
