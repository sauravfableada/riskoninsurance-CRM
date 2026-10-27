<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\HasCustomFields;
use App\Traits\OwnedByUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Task extends Model
{
    use SoftDeletes, Blameable, HasFactory, HasCustomFields, OwnedByUser, \App\Traits\RoleBasedFilter;

    protected $fillable = [
        'title',
        'description',
        'related_type',
        'related_id',
        'project_id',
        'assigned_user_id',
        'user_id',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            static::syncOwnedUserFromAssignee($task);
        });
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'related_id');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }
}
