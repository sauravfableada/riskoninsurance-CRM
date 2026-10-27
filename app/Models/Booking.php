<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_no',
        'lead_id',
        'quotation_id',
        'customer_id',
        'agent_id',
        'tour_package_id',
        'currency_id',
        'travel_start_date',
        'travel_end_date',
        'adults',
        'children',
        'rooms',
        'status',
        'total_amount',
        'notes',
        'cancellation_reason',
        'cancellation_fee',
        'is_active',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function tourPackage()
    {
        return $this->belongsTo(TourPackage::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function itinerary()
    {
        return $this->hasOne(Itinerary::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function payables()
    {
        return $this->hasMany(SupplierPayable::class);
    }

    public function checklists()
    {
        return $this->hasMany(BookingChecklist::class);
    }

    public function amendments()
    {
        return $this->hasMany(BookingAmendment::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public static function createDefaultChecklist(self $booking)
    {
        $tasks = [
            'Hotel Confirmation Received',
            'Travel Voucher Issued',
            'Driver/Transport Details Sent',
            'Welcome Call / Briefing Done',
            'Full Payment Collected',
            'Feedback Form Sent'
        ];

        foreach ($tasks as $task) {
            $booking->checklists()->create([
                'task_name' => $task,
                'is_completed' => false
            ]);
        }
    }
}

