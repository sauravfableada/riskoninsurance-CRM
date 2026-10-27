<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProspectModel extends Model
{
    protected $table = 'prospects';

    protected $fillable = [
        'name',
        'mobile_no',
        'country_code',
        'area',
        'created_by',
        'status',
        'created_at',
        'updated_at',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
