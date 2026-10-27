<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'status',
        'comment',
        'updated_by',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updatedBy()
    {
        return $this->updater();
    }
}
