<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'historable_type',
        'historable_id',
        'status',
        'comment',
        'updated_by',
    ];

    public function historable()
    {
        return $this->morphTo();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updatedBy()
    {
        return $this->updater();
    }
}
