<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyGroupMember extends Model
{
    use SoftDeletes;

    protected $table = 'family_group_members';

    protected $fillable = [
        'family_group_id',
        'member_name',
        'relation',
        'contact',
        'dob',
        'anniversary',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    // Member belongs to a group
    public function group()
    {
        return $this->belongsTo(FamilyGroup::class, 'family_group_id');
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