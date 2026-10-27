<?php

namespace App\Http\Controllers;

use App\Models\TransportType;
use Illuminate\Http\Request;

class TransportTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transportTypes = TransportType::orderBy('name')->paginate(15);

        return view('masters.transport_types.index', compact('transportTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.transport_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:transport_types,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        TransportType::create($data);

        return redirect()->route('masters.transport_types.index')->with('success', 'Transport type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.transport_types.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $transportType = TransportType::findOrFail($id);

        return view('masters.transport_types.edit', compact('transportType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transportType = TransportType::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:transport_types,name,'.$id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $transportType->update($data);

        return redirect()->route('masters.transport_types.index')->with('success', 'Transport type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transportType = TransportType::findOrFail($id);
        $transportType->delete();

        return redirect()->route('masters.transport_types.index')->with('success', 'Transport type deleted successfully.');
    }
}
