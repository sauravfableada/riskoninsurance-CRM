<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyGroup extends Model
{
    use SoftDeletes;

    protected $table = 'family_groups';

    protected $fillable = [
        'group_name',
        'holder_id',
        'remarks',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    // One group has many members
    public function members()
    {
        return $this->hasMany(FamilyGroupMember::class, 'family_group_id');
    }

    // Policy Holder (Customer)
    public function holder()
    {
        return $this->belongsTo(Customer::class, 'holder_id');
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