<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayable extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'booking_id',
        'amount',
        'due_date',
        'status',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
