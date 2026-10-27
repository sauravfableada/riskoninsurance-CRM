<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsurancePolicySubType extends Model
{
    use SoftDeletes;

    protected $table = 'insurance_policy_sub_types';

    protected $fillable = [
        'insurance_policy_types_id',
        'name',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    // Relation with Policy Type
    public function policyType()
    {
        return $this->belongsTo(InsurancePolicyType::class, 'insurance_policy_types_id');
    }

    // User tracking relations
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