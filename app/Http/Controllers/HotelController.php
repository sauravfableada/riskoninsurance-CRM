<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hotels = Hotel::with(['country', 'city'])->orderBy('name')->paginate(15);

        return view('masters.hotels.index', compact('hotels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('masters.hotels.create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $data['is_active'] = $request->has('is_active');

        Hotel::create($data);

        return redirect()->route('masters.hotels.index')->with('success', 'Hotel created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.hotels.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $hotel = Hotel::findOrFail($id);
        $countries = Country::orderBy('name')->get();
        $cities = City::where('country_id', $hotel->country_id)->orderBy('name')->get();

        return view('masters.hotels.edit', compact('hotel', 'countries', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $hotel = Hotel::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $hotel->update($data);

        return redirect()->route('masters.hotels.index')->with('success', 'Hotel updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->delete();

        return redirect()->route('masters.hotels.index')->with('success', 'Hotel deleted successfully.');
    }
}
