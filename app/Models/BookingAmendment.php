<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingAmendment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'old_data',
        'new_data',
        'reason',
        'amendment_fee',
        'created_by',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'amendment_fee' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
