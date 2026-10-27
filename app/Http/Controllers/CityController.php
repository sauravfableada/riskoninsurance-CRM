<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;

class CityController extends Controller
{
/**
    * Display a listing of the resource.
    */
public function index()
{
$cities = City::with('country')->orderBy('name')->paginate(15);

return view('masters.cities.index', compact('cities'));
}

/**
    * Show the form for creating a new resource.
    */
public function create()
{
$countries = Country::orderBy('name')->get();

return view('masters.cities.create', compact('countries'));
}

/**
    * Store a newly created resource in storage.
    */
public function store(Request $request)
{
$data = $request->validate([
'name' => ['required', 'string', 'max:255', 'unique:cities,name'],
'country_id' => ['nullable', 'exists:countries,id'],
]);

        $data['is_active'] = $request->has('is_active');
        $data['is_active'] = $request->has('is_active') ? true : (bool) $request->input('is_active', true);

        $city = City::create($data);

        City::create($data);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'City created successfully.',
                'city' => $city
            ]);
        }

return redirect()->route('masters.cities.index')->with('success', 'City created successfully.');
}

/**
    * Display the specified resource.
    */
public function show(string $id)
{
return redirect()->route('masters.cities.index');
}

/**
    * Show the form for editing the specified resource.
    */
public function edit(string $id)
{
$city = City::findOrFail($id);
$countries = Country::orderBy('name')->get();

return view('masters.cities.edit', compact('city', 'countries'));
}

/**
    * Update the specified resource in storage.
    */
public function update(Request $request, string $id)
{
$city = City::findOrFail($id);

$data = $request->validate([
'name' => ['required', 'string', 'max:255', 'unique:cities,name,'.$id],
'country_id' => ['nullable', 'exists:countries,id'],
]);

$data['is_active'] = $request->has('is_active');

$city->update($data);

return redirect()->route('masters.cities.index')->with('success', 'City updated successfully.');
}

/**
    * Remove the specified resource from storage.
    */
public function destroy(string $id)
{
$city = City::findOrFail($id);
$city->delete();

return redirect()->route('masters.cities.index')->with('success', 'City deleted successfully.');
}

public function apiByCountry(Country $country)
{
$cities = City::where('country_id', $country->id)
->where('is_active', true)
->orderBy('name')
->get(['id', 'name']);

return response()->json($cities);
}
}