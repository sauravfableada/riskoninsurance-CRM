<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\TourPackage;
use App\Models\Itinerary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::with(['lead', 'tourPackage'])
            ->latest()
            ->paginate(15);

        return view('crm.quotations.index', compact('quotations'));
    }

    public function create(Request $request)
    {
        $leads = Lead::orderBy('name')->get();
        $packages = TourPackage::orderBy('name')->get();

        $selectedLead = null;
        if ($request->has('lead_id')) {
            $selectedLead = $request->lead_id;
        }

        return view('crm.quotations.create', compact('leads', 'packages', 'selectedLead'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
            'tour_package_id' => 'nullable|integer|exists:tour_packages,id',
            'reference' => 'nullable|string|max:50|unique:quotations,reference',
            'status' => 'required|in:quotation,estimate,confirmed,cancelled',
            'total_amount' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        if (empty($data['reference'])) {
            $data['reference'] = 'QT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        }

        try {
            DB::transaction(function () use ($data) {
                $quotation = Quotation::create(\Illuminate\Support\Arr::except($data, ['items']));

                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $quotation->items()->create($item);
                    }
                }

                // Copy itinerary from Tour Package if selected
                if ($quotation->tour_package_id) {
                    $package = TourPackage::with('itinerary.days.items')->find($quotation->tour_package_id);
                    if ($package && $package->itinerary) {
                        $this->cloneItinerary($package->itinerary, ['quotation_id' => $quotation->id, 'tour_package_id' => null]);
                    }
                }
            });

            return response()->json(['message' => 'Quotation created successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $quotation = Quotation::with(['lead', 'tourPackage', 'items', 'itinerary.days.items'])->findOrFail($id);
        return view('crm.quotations.show', compact('quotation'));
    }

    public function edit(string $id)
    {
        $quotation = Quotation::with('items')->findOrFail($id);
        $leads = Lead::orderBy('name')->get();
        $packages = TourPackage::orderBy('name')->get();

        return view('crm.quotations.edit', compact('quotation', 'leads', 'packages'));
    }

    public function update(Request $request, string $id)
    {
        $quotation = Quotation::findOrFail($id);

        $data = $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
            'tour_package_id' => 'nullable|integer|exists:tour_packages,id',
            'reference' => 'nullable|string|max:50|unique:quotations,reference,' . $quotation->id,
            'status' => 'required|in:quotation,estimate,confirmed,cancelled',
            'total_amount' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($quotation, $data) {
                $quotation->update(\Illuminate\Support\Arr::except($data, ['items']));

                if (isset($data['items'])) {
                    $quotation->items()->delete();
                    foreach ($data['items'] as $item) {
                        $quotation->items()->create($item);
                    }
                } else {
                    $quotation->items()->delete();
                }
            });

            return response()->json(['message' => 'Quotation updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $quotation = Quotation::findOrFail($id);
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
    }

    public function convertToBooking(string $id)
    {
        $quotation = Quotation::with(['lead.customer', 'itinerary.days.items'])->findOrFail($id);

        if ($quotation->status !== 'confirmed') {
            return redirect()->back()->with('error', 'Only confirmed quotations can be converted to bookings.');
        }

        // We need a customer to create a booking. If the lead doesn't have a linked customer, we can't auto-convert easily without user input.
        if (!$quotation->lead || !$quotation->lead->converted_customer_id) {
             return redirect()->back()->with('error', 'The Lead must be converted to a Customer first before booking.');
        }

        try {
            $booking = DB::transaction(function () use ($quotation) {
                $b = Booking::create([
                    'booking_no' => 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'lead_id' => $quotation->lead_id,
                    'quotation_id' => $quotation->id,
                    'customer_id' => $quotation->lead->converted_customer_id,
                    'tour_package_id' => $quotation->tour_package_id,
                    // Defaulting some fields, user can edit later
                    'currency_id' => 1, // Fallback, assuming 1 exists
                    'travel_start_date' => now()->addDays(30), // Placeholder
                    'travel_end_date' => now()->addDays(35), // Placeholder
                    'adults' => 1,
                    'children' => 0,
                    'rooms' => 1,
                    'status' => 'pending',
                    'total_amount' => $quotation->total_amount,
                    'notes' => 'Converted from Quotation ' . $quotation->reference,
                    'is_active' => true,
                ]);

                // Clone itinerary if it exists on quotation
                if ($quotation->itinerary) {
                    $this->cloneItinerary($quotation->itinerary, ['booking_id' => $b->id, 'quotation_id' => null, 'tour_package_id' => null]);
                }

                Booking::createDefaultChecklist($b);

                return $b;
            });

            return redirect()->route('bookings.edit', $booking->id)->with('success', 'Quotation converted into a Booking successfully. Please review the booking details.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to convert quotation: ' . $e->getMessage());
        }
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
