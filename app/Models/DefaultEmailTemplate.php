<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefaultEmailTemplate extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'default_email_template';

    protected $fillable = [
        'name',
        'content',
        'created_by',
        'modified_by',
        'deleted_by',
        'default_email_template',
    ];

    protected $casts = [
        'default_email_template' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
