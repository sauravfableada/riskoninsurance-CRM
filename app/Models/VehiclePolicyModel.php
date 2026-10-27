<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePolicyModel extends Model
{
    protected $table = 'vehicle_policies';

    protected $fillable = [
        'policy_id',
        'vehicle_reg_no',
        'vehicle_model',
        'engine_no',
        'chassis_no',
        'fuel_type',
        'registration_date',
        'cc',
        'gvw',
        'ncb',
        'year_of_manufacture',
    ];
}
