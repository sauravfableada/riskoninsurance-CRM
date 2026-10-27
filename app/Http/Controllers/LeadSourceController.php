<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leadSources = LeadSource::orderBy('name')->paginate(15);

        return view('masters.lead_sources.index', compact('leadSources'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.lead_sources.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:lead_sources,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        LeadSource::create($data);

        return redirect()->route('masters.lead_sources.index')->with('success', 'Lead source created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.lead_sources.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $leadSource = LeadSource::findOrFail($id);

        return view('masters.lead_sources.edit', compact('leadSource'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $leadSource = LeadSource::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:lead_sources,name,'.$id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $leadSource->update($data);

        return redirect()->route('masters.lead_sources.index')->with('success', 'Lead source updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $leadSource = LeadSource::findOrFail($id);
        $leadSource->delete();

        return redirect()->route('masters.lead_sources.index')->with('success', 'Lead source deleted successfully.');
    }
}
