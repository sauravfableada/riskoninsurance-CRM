<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Country;
use App\Models\City;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::with(['country', 'city'])->orderBy('name')->paginate(15);

        return view('masters.suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('masters.suppliers.create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $data['is_active'] = $request->has('is_active');

        Supplier::create($data);

        return redirect()->route('masters.suppliers.index')->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.suppliers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $countries = Country::orderBy('name')->get();
        $cities = City::where('country_id', $supplier->country_id)->orderBy('name')->get();

        return view('masters.suppliers.edit', compact('supplier', 'countries', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $supplier->update($data);

        return redirect()->route('masters.suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('masters.suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    public function payables(Supplier $supplier)
    {
        $payables = $supplier->payables()->with(['booking', 'payments'])->latest()->paginate(15);
        return view('masters.suppliers.payables', compact('supplier', 'payables'));
    }

    public function storePayment(Request $request, SupplierPayable $payable)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $payable->payments()->create($request->all());

        // Update payable status
        $totalPaid = $payable->payments()->sum('amount');
        if ($totalPaid >= $payable->amount) {
            $payable->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $payable->update(['status' => 'partially_paid']);
        }

        return response()->json(['status' => 'success', 'message' => 'Supplier payment recorded successfully.']);
    }
}
