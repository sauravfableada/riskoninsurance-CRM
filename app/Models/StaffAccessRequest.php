<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StaffAccessRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'permission',
        'record_id',
        'reason',
        'status',
        'admin_comment',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function grant(): HasOne
    {
        return $this->hasOne(StaffPermissionGrant::class, 'access_request_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AccessRequestHistory::class, 'access_request_id')->latest('id');
    }
}
