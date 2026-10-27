<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\TourPackage;
use App\Models\TravelType;
use App\Models\Currency;

class TourPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = TourPackage::with(['travelType', 'currency'])->latest()->paginate(15);
        return view('crm.packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $travelTypes = TravelType::where('is_active', true)->get();
        $currencies = Currency::where('is_active', true)->get();
        return view('crm.packages.create', compact('travelTypes', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:tour_packages,code',
            'destination' => 'required|string|max:255',
            'duration_nights' => 'required|integer|min:0',
            'base_price' => 'required|numeric|min:0',
            'available_seats' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'highlights' => 'nullable|string',
            'travel_type_id' => 'nullable|exists:travel_types,id',
            'currency_id' => 'nullable|exists:currencies,id',
        ]);

        TourPackage::create($data);

        return redirect()->route('packages.index')->with('success', 'Tour package created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('packages.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $package = TourPackage::findOrFail($id);
        $travelTypes = TravelType::where('is_active', true)->get();
        $currencies = Currency::where('is_active', true)->get();
        return view('crm.packages.edit', compact('package', 'travelTypes', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $package = TourPackage::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:tour_packages,code,' . $package->id,
            'destination' => 'required|string|max:255',
            'duration_nights' => 'required|integer|min:0',
            'base_price' => 'required|numeric|min:0',
            'available_seats' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'highlights' => 'nullable|string',
            'travel_type_id' => 'nullable|exists:travel_types,id',
            'currency_id' => 'nullable|exists:currencies,id',
        ]);

        $package->update($data);

        return redirect()->route('packages.index')->with('success', 'Tour package updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $package = TourPackage::findOrFail($id);
        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Tour package deleted successfully.');
    }
}
