<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('crm.calendar.index');
    }

    public function apiEvents(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $bookings = Booking::with(['customer', 'tourPackage'])
            ->where(function($query) use ($start, $end) {
                $query->whereBetween('travel_start_date', [$start, $end])
                      ->orWhereBetween('travel_end_date', [$start, $end]);
            })
            ->get();

        $events = [];

        foreach ($bookings as $booking) {
            // Arrival Event
            $events[] = [
                'id' => 'arr_' . $booking->id,
                'title' => 'ARR: ' . $booking->customer->name . ' (' . $booking->booking_no . ')',
                'start' => $booking->travel_start_date,
                'allDay' => true,
                'backgroundColor' => '#2196f3', // Blue for arrival
                'borderColor' => '#1976d2',
                'url' => route('bookings.show', $booking),
                'extendedProps' => [
                    'booking_id' => $booking->id,
                    'customer' => $booking->customer->name,
                    'pax' => $booking->adults + $booking->children,
                    'type' => 'Arrival'
                ]
            ];

            // Departure Event
            $events[] = [
                'id' => 'dep_' . $booking->id,
                'title' => 'DEP: ' . $booking->customer->name . ' (' . $booking->booking_no . ')',
                'start' => $booking->travel_end_date,
                'allDay' => true,
                'backgroundColor' => '#f44336', // Red for departure
                'borderColor' => '#d32f2f',
                'url' => route('bookings.show', $booking),
                'extendedProps' => [
                    'booking_id' => $booking->id,
                    'customer' => $booking->customer->name,
                    'pax' => $booking->adults + $booking->children,
                    'type' => 'Departure'
                ]
            ];
        }

        return response()->json($events);
    }
}
