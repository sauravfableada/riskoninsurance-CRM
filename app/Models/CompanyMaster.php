<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyMaster extends Model
{
    use SoftDeletes;

    protected $table = 'company_master';

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

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