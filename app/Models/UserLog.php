<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLog extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'action',
        'message',
        'details',
        'actioned_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
