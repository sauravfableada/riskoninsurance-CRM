<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMemberDesignation extends Model
{
    protected $fillable = ['name', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];
}
