<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\TourPackage;
use App\Models\Booking;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItineraryController extends Controller
{
    public function editByPackage(TourPackage $package)
    {
        $itinerary = $package->itinerary()->with('days.items')->first();
        
        if (!$itinerary) {
            $itinerary = $package->itinerary()->create([
                'title' => 'Itinerary for ' . $package->name,
            ]);
        }

        return view('crm.itineraries.edit', [
            'itinerary' => $itinerary,
            'owner' => $package,
            'ownerType' => 'package'
        ]);
    }

    public function editByBooking(Booking $booking)
    {
        $itinerary = $booking->itinerary()->with('days.items')->first();
        
        if (!$itinerary) {
            // Check if package has an itinerary to clone
            if ($booking->tourPackage && $booking->tourPackage->itinerary) {
                $itinerary = $this->cloneItinerary($booking->tourPackage->itinerary, ['booking_id' => $booking->id]);
            } else {
                $itinerary = $booking->itinerary()->create([
                    'title' => 'Itinerary for Booking #' . $booking->booking_no,
                ]);
            }
        }

        return view('crm.itineraries.edit', [
            'itinerary' => $itinerary,
            'owner' => $booking,
            'ownerType' => 'booking'
        ]);
    }

    public function updateByPackage(Request $request, TourPackage $package)
    {
        $itinerary = $package->itinerary()->firstOrCreate(['title' => 'Itinerary for ' . $package->name]);
        return $this->update($request, $itinerary);
    }

    public function editByQuotation(Quotation $quotation)
    {
        $itinerary = $quotation->itinerary()->with('days.items')->first();
        
        if (!$itinerary) {
            // Check if package has an itinerary to clone
            if ($quotation->tourPackage && $quotation->tourPackage->itinerary) {
                $itinerary = $this->cloneItinerary($quotation->tourPackage->itinerary, ['quotation_id' => $quotation->id]);
            } else {
                $itinerary = $quotation->itinerary()->create([
                    'title' => 'Itinerary for Quotation ' . $quotation->reference,
                ]);
            }
        }

        return view('crm.itineraries.edit', [
            'itinerary' => $itinerary,
            'owner' => $quotation,
            'ownerType' => 'quotation'
        ]);
    }

    public function updateByQuotation(Request $request, Quotation $quotation)
    {
        $itinerary = $quotation->itinerary()->firstOrCreate(['title' => 'Itinerary for Quotation ' . $quotation->reference]);
        return $this->update($request, $itinerary);
    }

    public function updateByBooking(Request $request, Booking $booking)
    {
        $itinerary = $booking->itinerary()->firstOrCreate(['title' => 'Itinerary for Booking #' . $booking->booking_no]);
        return $this->update($request, $itinerary);
    }

    public function update(Request $request, Itinerary $itinerary)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'days' => 'array',
            'days.*.title' => 'required|string|max:255',
            'days.*.description' => 'nullable|string',
            'days.*.meals' => 'nullable|string',
            'days.*.items' => 'array',
        ]);

        DB::transaction(function () use ($itinerary, $data) {
            $itinerary->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            // Sync Days
            $existingDayIds = $itinerary->days->pluck('id')->toArray();
            $newDayIds = [];

            foreach ($data['days'] as $index => $dayData) {
                $day = $itinerary->days()->updateOrCreate(
                    ['day_number' => $index + 1],
                    [
                        'title' => $dayData['title'],
                        'description' => $dayData['description'] ?? null,
                        'meals' => $dayData['meals'] ?? null,
                    ]
                );
                $newDayIds[] = $day->id;

                // Sync Items for this day
                if (isset($dayData['items'])) {
                    $day->items()->delete();
                    foreach ($dayData['items'] as $itemData) {
                        $day->items()->create($itemData);
                    }
                }
            }

            // Delete days that weren't in the request
            $itinerary->days()->whereNotIn('id', $newDayIds)->delete();
        });

        return response()->json(['message' => 'Itinerary updated successfully.']);
    }

    private function cloneItinerary(Itinerary $source, array $overrides)
    {
        return DB::transaction(function () use ($source, $overrides) {
            $newItinerary = $source->replicate();
            $newItinerary->fill($overrides);
            $newItinerary->save();

            foreach ($source->days as $day) {
                $newDay = $day->replicate();
                $newDay->itinerary_id = $newItinerary->id;
                $newDay->save();

                foreach ($day->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->itinerary_day_id = $newDay->id;
                    $newItem->save();
                }
            }

            return $newItinerary;
        });
    }
}
