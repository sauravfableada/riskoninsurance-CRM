<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequestHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const EVENT_REQUESTED = 'requested';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_REVOKED = 'revoked';

    protected $fillable = [
        'access_request_id',
        'grant_id',
        'user_id',
        'actor_id',
        'event',
        'permission',
        'module',
        'action',
        'record_id',
        'comment',
        'created_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function accessRequest(): BelongsTo
    {
        return $this->belongsTo(StaffAccessRequest::class, 'access_request_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(StaffPermissionGrant::class, 'grant_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
