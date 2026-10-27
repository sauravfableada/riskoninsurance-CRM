<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\TourPackage;
use App\Models\Passenger;
use App\Models\Refund;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\BookingChecklist;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['customer', 'agent', 'lead', 'tourPackage', 'currency', 'quotation'])
            ->latest()
            ->paginate(15);

        return view('crm.bookings.index', compact('bookings'));
    }

    public function create(Request $request)
    {
        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = Quotation::with(['lead', 'tourPackage'])->find($request->input('quotation_id'));
        }

        $leads = Lead::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $agents = Agent::orderBy('name')->get();
        $packages = TourPackage::orderBy('name')->get();
        $currencies = Currency::orderBy('code')->get();
        $quotations = Quotation::all();

        // dd($quotation);
        return view('crm.bookings.create', compact('quotation', 'leads', 'customers', 'agents', 'packages', 'currencies', 'quotations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_no' => 'nullable|string|max:50|unique:bookings,booking_no',
            'lead_id' => 'nullable|integer|exists:leads,id',
            'quotation_id' => 'nullable|integer|exists:quotations,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'tour_package_id' => 'nullable|integer|exists:tour_packages,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'travel_start_date' => 'required|date',
            'travel_end_date' => 'required|date|after_or_equal:travel_start_date',
            'adults' => 'required|integer|min:1|max:50',
            'children' => 'nullable|integer|min:0|max:50',
            'rooms' => 'nullable|integer|min:1|max:50',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $data['children'] = (int) ($data['children'] ?? 0);
        $data['rooms'] = (int) ($data['rooms'] ?? 1);
        $data['is_active'] = $request->has('is_active');

        if (empty($data['booking_no'])) {
            $data['booking_no'] = $this->generateBookingNo();
        }

        $booking = Booking::create($data);
        Booking::createDefaultChecklist($booking);
        $this->createOrUpdateProjectFromBooking($booking);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Booking created successfully.', 'data' => $booking], 201);
        }

        return redirect()->route('bookings.index')->with('success', 'Booking created successfully.');
    }

    public function edit(string $id)
    {
        $booking = Booking::findOrFail($id);

        $leads = Lead::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $agents = Agent::orderBy('name')->get();
        $packages = TourPackage::orderBy('name')->get();
        $currencies = Currency::orderBy('code')->get();
        $quotations = Quotation::all();

        return view('crm.bookings.edit', compact('booking', 'leads', 'customers', 'agents', 'packages', 'currencies', 'quotations'));
    }

    public function update(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $data = $request->validate([
            'booking_no' => 'nullable|string|max:50|unique:bookings,booking_no,' . $booking->id,
            'lead_id' => 'nullable|integer|exists:leads,id',
            'quotation_id' => 'nullable|integer|exists:quotations,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'tour_package_id' => 'nullable|integer|exists:tour_packages,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'travel_start_date' => 'required|date',
            'travel_end_date' => 'required|date|after_or_equal:travel_start_date',
            'adults' => 'required|integer|min:1|max:50',
            'children' => 'nullable|integer|min:0|max:50',
            'rooms' => 'nullable|integer|min:1|max:50',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $data['children'] = (int) ($data['children'] ?? 0);
        $data['rooms'] = (int) ($data['rooms'] ?? 1);
        $data['is_active'] = $request->has('is_active');

        $booking->update($data);

        // Sync Passengers
        if ($request->has('passengers')) {
            $booking->passengers()->delete();
            foreach ($request->input('passengers') as $pax) {
                if (!empty($pax['first_name'])) {
                    $booking->passengers()->create($pax);
                }
            }
        }

        $this->createOrUpdateProjectFromBooking($booking);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Booking updated successfully.'], 200);
        }

        return redirect()->route('bookings.index')->with('success', 'Booking updated successfully.');
    }

    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return redirect()->route('bookings.index')->with('success', 'Booking deleted successfully.');
    }

    public function show(string $id)
    {
        $booking = Booking::with(['customer', 'agent', 'lead', 'tourPackage', 'currency', 'quotation', 'passengers', 'itinerary.days', 'amendments.creator'])->findOrFail($id);

        return view('crm.bookings.show', compact('booking'));
    }

    public function voucher(string $id)
    {
        $booking = Booking::with(['customer', 'passengers', 'itinerary.days.items.supplier', 'tourPackage'])->findOrFail($id);
        return view('crm.bookings.voucher', compact('booking'));

    }
    public function getBooking(Request $request)
    {
        $booking = Booking::with(['customer', 'agent', 'lead', 'tourPackage', 'currency', 'quotation'])->findOrFail($request->id);

        return response()->json($booking);
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        
        $request->validate([
            'cancellation_reason' => 'required|string',
            'cancellation_fee' => 'required|numeric|min:0',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
            'cancellation_fee' => $request->cancellation_fee,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Booking cancelled successfully.']);
    }

    public function refunds(string $id)
    {
        $booking = Booking::with('refunds')->findOrFail($id);
        return view('crm.bookings.refunds', compact('booking'));
    }

    public function storeRefund(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'refund_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $booking->refunds()->create($request->all());

        return response()->json(['status' => 'success', 'message' => 'Refund recorded successfully.']);
    }

    public function costs(string $id)
    {
        $booking = Booking::with(['payables.supplier', 'payables.payments'])->findOrFail($id);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        return view('crm.bookings.costs', compact('booking', 'suppliers'));
    }

    public function storeCost(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        SupplierPayable::create([
            'booking_id' => $booking->id,
            'supplier_id' => $request->supplier_id,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'unpaid',
            'notes' => $request->notes,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Supplier cost recorded successfully.']);
    }

    public function destroyCost(string $payableId)
    {
        $payable = SupplierPayable::findOrFail($payableId);

        if ($payable->payments()->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete cost that has associated payments.'], 422);
        }
        
        $payable->delete();
        return response()->json(['status' => 'success', 'message' => 'Supplier cost deleted successfully.']);
    }

    public function toggleChecklist(Request $request, string $id)
    {
        $item = BookingChecklist::findOrFail($id);
        
        $item->is_completed = !$item->is_completed;
        $item->completed_at = $item->is_completed ? now() : null;
        $item->save();

        return response()->json([
            'status' => 'success', 
            'is_completed' => $item->is_completed,
            'completed_at' => $item->completed_at ? $item->completed_at->format('d M Y H:i') : null
        ]);
    }

    public function amend(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $request->validate([
            'type' => 'required|in:reschedule,pax_change,itinerary_update,other',
            'reason' => 'required|string',
            'amendment_fee' => 'nullable|numeric|min:0',
            // Allow updating core fields
            'travel_start_date' => 'nullable|date',
            'travel_end_date' => 'nullable|date|after_or_equal:travel_start_date',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'rooms' => 'nullable|integer|min:1',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $oldData = [];
        $newData = [];
        $fieldsToTrack = ['travel_start_date', 'travel_end_date', 'adults', 'children', 'rooms', 'total_amount'];

        foreach ($fieldsToTrack as $field) {
            if ($request->filled($field) && $booking->$field != $request->$field) {
                $oldData[$field] = $booking->$field;
                $newData[$field] = $request->$field;
            }
        }

        if (empty($newData) && !($request->amendment_fee > 0)) {
            // If nothing changed and no fee, we just log the reason/type or skip
            // But let's assume if they sent it, they want to log something.
        }

        DB::transaction(function () use ($booking, $request, $oldData, $newData) {
            // Update booking with new data
            if (!empty($newData)) {
                $booking->update($newData);
            }

            // Also add amendment fee to total if provided
            if ($request->amendment_fee > 0) {
                $booking->increment('total_amount', $request->amendment_fee);
            }

            // Create amendment log
            $booking->amendments()->create([
                'type' => $request->type,
                'old_data' => $oldData,
                'new_data' => $newData,
                'reason' => $request->reason,
                'amendment_fee' => $request->amendment_fee ?? 0,
                'created_by' => auth()->id(),
            ]);
        });

        return response()->json(['status' => 'success', 'message' => 'Booking amended successfully.']);
    }

    private function generateBookingNo(): string
    {
        do {
            $code = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Booking::where('booking_no', $code)->exists());

        return $code;
    }

    private function createOrUpdateProjectFromBooking(Booking $booking): void
    {
        if ($booking->status !== 'confirmed') {
            return;
        }

        $project = Project::where('booking_id', $booking->id)->first();
        if (!$project) {
            $project = Project::create([
                'project_code' => $this->generateProjectCode(),
                'name' => $this->projectNameFromBooking($booking),
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'tour_package_id' => $booking->tour_package_id,
                'start_date' => $booking->travel_start_date,
                'end_date' => $booking->travel_end_date,
                'total_travelers' => (int) $booking->adults + (int) $booking->children,
                'assigned_user_id' => auth()->id(),
                'status' => 'planning',
            ]);
        } else {
            $project->update([
                'name' => $this->projectNameFromBooking($booking),
                'customer_id' => $booking->customer_id,
                'tour_package_id' => $booking->tour_package_id,
                'start_date' => $booking->travel_start_date,
                'end_date' => $booking->travel_end_date,
                'total_travelers' => (int) $booking->adults + (int) $booking->children,
            ]);
        }
    }

    private function projectNameFromBooking(Booking $booking): string
    {
        $customerName = $booking->customer?->name ?? 'Customer';
        $packageName = $booking->tourPackage?->name;
        if ($packageName) {
            return $packageName.' - '.$customerName;
        }

        $start = $booking->travel_start_date ? Carbon::parse($booking->travel_start_date)->format('M Y') : null;
        return ($start ? $start.' Trip' : 'Trip').' - '.$customerName;
    }

    private function generateProjectCode(): string
    {
        do {
            $code = 'PRJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Project::where('project_code', $code)->exists());

        return $code;
    }
}
