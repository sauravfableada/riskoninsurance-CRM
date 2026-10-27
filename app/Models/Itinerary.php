<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_package_id',
        'booking_id',
        'quotation_id',
        'title',
        'description',
        'is_active',
    ];

    public function tourPackage()
    {
        return $this->belongsTo(TourPackage::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function days()
    {
        return $this->hasMany(ItineraryDay::class)->orderBy('day_number');
    }
}
