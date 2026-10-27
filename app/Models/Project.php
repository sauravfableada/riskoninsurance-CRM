<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\OwnedByUser;
use App\Traits\RoleBasedFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Project extends Model
{
    use HasFactory, SoftDeletes, Blameable, OwnedByUser;

    protected $fillable = [
        'project_code',
        'customer_id',
        'assigned_user_id',
        'user_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = [
        'created_by_user',
        'updated_by_user',
        'deleted_by_user',
    ];

    // Relationships
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

    // Audit trail relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }

    // Accessors for API
    public function getCreatedByUserAttribute()
    {
        return $this->creator ? $this->creator->only(['id', 'name', 'email']) : null;
    }

    public function getUpdatedByUserAttribute()
    {
        return $this->updater ? $this->updater->only(['id', 'name', 'email']) : null;
    }

    public function getDeletedByUserAttribute()
    {
        return $this->deleter ? $this->deleter->only(['id', 'name', 'email']) : null;
    }

    protected static function booted(): void
    {
        // OwnedByUser trait handles the global scope
    }
}
