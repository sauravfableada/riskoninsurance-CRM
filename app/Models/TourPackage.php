<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'destination',
        'duration_nights',
        'base_price',
        'available_seats',
        'is_active',
        'highlights',
        'travel_type_id',
        'currency_id',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function travelType()
    {
        return $this->belongsTo(TravelType::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function itinerary()
    {
        return $this->hasOne(Itinerary::class);
    }
}
