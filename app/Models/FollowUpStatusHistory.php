<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUpStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'follow_up_id',
        'status',
        'comment',
        'updated_by',
    ];

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
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
