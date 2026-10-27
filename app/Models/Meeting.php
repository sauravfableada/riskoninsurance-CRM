<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\RoleBasedFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\OwnedByUser;

class Meeting extends Model
{
    use HasFactory, SoftDeletes, OwnedByUser, Blameable, RoleBasedFilter;

    protected $fillable = [
        'title',
        'customer_id',
        'assigned_user_id',
        'scheduled_at',
        'meeting_type',
        'status',
        'address',
        'agenda',
        'user_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'google_event_id',
        'is_synced',
        'synced_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'meeting_type' => 'string',
        'status' => 'string',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the customer associated with the meeting.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user assigned to the meeting.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who created the meeting.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the meeting.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusHistories()
    {
        return $this->hasMany(MeetingStatusHistory::class)->latest();
    }

    /**
     * Get the user who deleted the meeting.
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope a query to only include scheduled meetings.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include completed meetings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled meetings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include upcoming meetings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include past meetings.
     */
    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    /**
     * Check if the meeting is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the meeting is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the meeting is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the meeting is online.
     */
    public function isOnline(): bool
    {
        return $this->meeting_type === 'online';
    }

    /**
     * Check if the meeting is offline.
     */
    public function isOffline(): bool
    {
        return $this->meeting_type === 'offline';
    }

    /**
     * Check if the meeting is a phone call.
     */
    public function isPhoneCall(): bool
    {
        return $this->meeting_type === 'phone';
    }

    /**
     * Check if the meeting is a video call.
     */
    public function isVideoCall(): bool
    {
        return $this->meeting_type === 'video';
    }

    /**
     * Check if meeting is synced with Google Calendar.
     */
    public function isSynced(): bool
    {
        return $this->is_synced && !empty($this->google_event_id);
    }

    /**
     * Get the sync status label.
     */
    public function getSyncStatusLabelAttribute(): string
    {
        if ($this->isSynced()) {
            return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Synced</span>';
        }
        return '<span class="badge bg-secondary"><i class="bi bi-cloud-arrow-up me-1"></i>Not Synced</span>';
    }

    /**
     * Get the meeting type label.
     */
    public function getMeetingTypeLabelAttribute(): string
    {
        return match ($this->meeting_type) {
            'online' => 'Online',
            'offline' => 'Offline',
            'phone' => 'Phone Call',
            'video' => 'Video Call',
            default => 'Not specified'
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Not specified'
        };
    }

    /**
     * Get the formatted scheduled date.
     */
    public function getFormattedScheduledAtAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('M d, Y H:i') : 'Not scheduled';
    }

    protected static function booted(): void
    {
        static::saving(function ($model) {
            static::syncOwnedUserFromAssignee($model);
        });
    }
}
