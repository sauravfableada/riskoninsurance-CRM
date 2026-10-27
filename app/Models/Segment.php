<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Segment extends Model
{
    use SoftDeletes;

    protected $table = 'segments';

    protected $fillable = [
        'insurance_type_id',
        'name',
        'description',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    // Relation
    public function insuranceType()
    {
        return $this->belongsTo(InsurancePolicyType::class, 'insurance_type_id');
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