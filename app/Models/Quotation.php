<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'tour_package_id',
        'reference',
        'status',
        'total_amount',
        'valid_until',
        'notes',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function tourPackage()
    {
        return $this->belongsTo(TourPackage::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function itinerary()
    {
        return $this->hasOne(Itinerary::class);
    }
}
