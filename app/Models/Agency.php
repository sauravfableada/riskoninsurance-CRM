<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    protected $table = 'agencies';

    protected $fillable = [
        'agency_levels_id',
        'name',
        'status',
        'created_by',
        'modified_by',
        'deleted_by'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function agencyLevel()
    {
        return $this->belongsTo(AgencyLevel::class, 'agency_levels_id');
    }

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
