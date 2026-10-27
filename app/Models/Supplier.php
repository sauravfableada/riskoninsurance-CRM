<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'country_id',
        'city_id',
        'type',
        'is_active',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function itineraryItems()
    {
        return $this->hasMany(ItineraryItem::class);
    }

    public function payables()
    {
        return $this->hasMany(SupplierPayable::class);
    }
}
