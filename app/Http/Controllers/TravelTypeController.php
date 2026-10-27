<?php

namespace App\Http\Controllers;

use App\Models\TravelType;
use Illuminate\Http\Request;

class TravelTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $travelTypes = TravelType::orderBy('name')->paginate(15);

        return view('masters.travel_types.index', compact('travelTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.travel_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:travel_types,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        TravelType::create($data);

        return redirect()->route('masters.travel_types.index')->with('success', 'Travel type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.travel_types.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $travelType = TravelType::findOrFail($id);

        return view('masters.travel_types.edit', compact('travelType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $travelType = TravelType::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:travel_types,name,'.$id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $travelType->update($data);

        return redirect()->route('masters.travel_types.index')->with('success', 'Travel type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $travelType = TravelType::findOrFail($id);
        $travelType->delete();

        return redirect()->route('masters.travel_types.index')->with('success', 'Travel type deleted successfully.');
    }
}
