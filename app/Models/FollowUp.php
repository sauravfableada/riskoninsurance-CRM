<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\RoleBasedFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\OwnedByUser;

class FollowUp extends Model
{
    use HasFactory, SoftDeletes, OwnedByUser, Blameable, RoleBasedFilter;

    protected static function booted(): void
    {
        static::saving(function ($followUp) {
            static::syncOwnedUserFromAssignee($followUp);
        });
    }

    protected $casts = [
        'follow_up_at' => 'datetime',
    ];

    protected $fillable = [
        'lead_id',
        'assigned_user_id',
        'purpose',
        'comment',
        'priority',
        'status',
        'follow_up_at',
        'user_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories()
    {
        return $this->hasMany(FollowUpStatusHistory::class)->latest();
    }
}
