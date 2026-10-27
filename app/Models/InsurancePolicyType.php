<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class InsurancePolicyType extends Model
{
    use SoftDeletes;

    protected $table = 'insurance_policy_types';

    protected $fillable = [
        'name',
        'created_by',
        'modified_by',
        'deleted_by',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
    public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function modifier()
{
    return $this->belongsTo(User::class, 'modified_by');
}

public function deleter()
{
    return $this->belongsTo(User::class, 'deleted_by');
}
}