<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'refund_date',
        'payment_method',
        'transaction_id',
        'status',
        'notes',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
