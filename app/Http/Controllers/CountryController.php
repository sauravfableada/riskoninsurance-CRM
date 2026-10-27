<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
/**
    * Display a listing of the resource.
    */
public function index()
{
$countries = Country::orderBy('name')->paginate(15);

return view('masters.countries.index', compact('countries'));
}

/**
    * Show the form for creating a new resource.
    */
public function create()
{
return view('masters.countries.create');
}

/**
    * Store a newly created resource in storage.
    */
public function store(Request $request)
{
$data = $request->validate([
'name' => ['required', 'string', 'max:255', 'unique:countries,name'],
'iso_code' => ['required', 'string', 'min:2', 'max:3'],
]);

        $data['is_active'] = $request->has('is_active');
        $data['is_active'] = $request->has('is_active') ? true : (bool) $request->input('is_active', true);

        $country = Country::create($data);

        Country::create($data);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Country created successfully.',
                'country' => $country
            ]);
        }

return redirect()->route('masters.countries.index')->with('success', 'Country created successfully.');
}

/**
    * Display the specified resource.
    */
public function show(string $id)
{
return redirect()->route('masters.countries.index');
}

/**
    * Show the form for editing the specified resource.
    */
public function edit(string $id)
{
$country = Country::findOrFail($id);

return view('masters.countries.edit', compact('country'));
}

/**
    * Update the specified resource in storage.
    */
public function update(Request $request, string $id)
{
$country = Country::findOrFail($id);

$data = $request->validate([
'name' => ['required', 'string', 'max:255', 'unique:countries,name,'.$id],
'iso_code' => ['required', 'string', 'min:2', 'max:3'],
]);

$data['is_active'] = $request->has('is_active');

$country->update($data);

return redirect()->route('masters.countries.index')->with('success', 'Country updated successfully.');
}

/**
    * Remove the specified resource from storage.
    */
public function destroy(string $id)
{
$country = Country::findOrFail($id);

$cityCount = $country->cities()->count();
$country->cities()->delete();
$country->delete();

$message = 'Country deleted successfully.';
if ($cityCount > 0) {
$message .= " {$cityCount} related cit" . ($cityCount === 1 ? 'y was' : 'ies were') . ' also deleted.';
}

return redirect()
->route('masters.countries.index')
->with('success', $message);
}
}