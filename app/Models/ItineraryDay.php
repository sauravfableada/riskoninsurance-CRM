<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'day_number',
        'title',
        'description',
        'meals',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function items()
    {
        return $this->hasMany(ItineraryItem::class);
    }
}
