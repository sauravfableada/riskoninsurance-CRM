<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'customer_id',
        'ticket_name',
        'priority',
        'status',
        'description',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function replies()
    {
        return $this->hasMany(SupportReply::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }
}
