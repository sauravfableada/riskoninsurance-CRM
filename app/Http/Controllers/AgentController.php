<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $agents = Agent::with(['country', 'city'])->orderBy('name')->paginate(15);

        return view('masters.agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('masters.agents.create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $data['is_active'] = $request->has('is_active');

        Agent::create($data);

        return redirect()->route('masters.agents.index')->with('success', 'Agent created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.agents.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $agent = Agent::findOrFail($id);
        $countries = Country::orderBy('name')->get();
        $cities = City::where('country_id', $agent->country_id)->orderBy('name')->get();

        return view('masters.agents.edit', compact('agent', 'countries', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $agent = Agent::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $agent->update($data);

        return redirect()->route('masters.agents.index')->with('success', 'Agent updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $agent = Agent::findOrFail($id);
        $agent->delete();

        return redirect()->route('masters.agents.index')->with('success', 'Agent deleted successfully.');
    }
}
