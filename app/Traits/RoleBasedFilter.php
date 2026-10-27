<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait RoleBasedFilter
{
    public function scopeVisibleToAuthUser(Builder $query)
    {
        return $query;
    }
}
