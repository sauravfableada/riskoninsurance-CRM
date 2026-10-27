<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'users_permission';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'module_id',
        'view',
        'create',
        'edit',
        'update',
        'delete',
    ];
}
