<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ItineraryItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OperationsController extends Controller
{
    public function roomingList(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date'
        ]);

        $date = $request->input('date', Carbon::today()->toDateString());
        
        $items = ItineraryItem::query()
            ->join('itinerary_days', 'itinerary_items.itinerary_day_id', '=', 'itinerary_days.id')
            ->join('itineraries', 'itinerary_days.itinerary_id', '=', 'itineraries.id')
            ->join('bookings', 'itineraries.booking_id', '=', 'bookings.id')
            ->where('itinerary_items.item_type', 'Hotel')
            ->whereRaw('DATE_ADD(bookings.travel_start_date, INTERVAL (itinerary_days.day_number - 1) DAY) = ?', [$date])
            ->select('itinerary_items.*')
            ->with(['day.itinerary.booking.customer', 'supplier'])
            ->get();

        return view('crm.operations.rooming_list', compact('items', 'date'));
    }

    public function driverSheet(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date'
        ]);

        $date = $request->input('date', Carbon::today()->toDateString());

        $items = ItineraryItem::query()
            ->join('itinerary_days', 'itinerary_items.itinerary_day_id', '=', 'itinerary_days.id')
            ->join('itineraries', 'itinerary_days.itinerary_id', '=', 'itineraries.id')
            ->join('bookings', 'itineraries.booking_id', '=', 'bookings.id')
            ->whereIn('itinerary_items.item_type', ['Transport', 'Transfer', 'Pickup'])
            ->whereRaw('DATE_ADD(bookings.travel_start_date, INTERVAL (itinerary_days.day_number - 1) DAY) = ?', [$date])
            ->select('itinerary_items.*')
            ->with(['day.itinerary.booking.customer', 'supplier'])
            ->get()
            ->sortBy('time');

        return view('crm.operations.driver_sheet', compact('items', 'date'));
    }
}
