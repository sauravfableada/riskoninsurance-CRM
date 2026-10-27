<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_day_id',
        'item_type',
        'time',
        'title',
        'description',
        'supplier_id',
    ];

    public function day()
    {
        return $this->belongsTo(ItineraryDay::class, 'itinerary_day_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
