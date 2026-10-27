<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicyType;
use Illuminate\Http\Request;

class InsurancePolicyTypeController extends Controller
{
    public function index()
    {
        $policies = InsurancePolicyType::all();
        return view('insurance-policy-type.index', compact('policies'));
    }

    public function create()
    {
        return view('insurance-policy-type.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $policy = InsurancePolicyType::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Policy Type Added Successfully!',
                'id'      => $policy->id,
                'name'    => $policy->name
            ]);
        }

        return redirect()->route('policy-type.index')->with('success', 'Policy Type Added Successfully!');
    }

    public function edit($id)
    {
        $policy = InsurancePolicyType::findOrFail($id);
        return view('insurance-policy-type.edit', compact('policy'));
    }

    public function update(Request $request, $id)
    {
        $policy = InsurancePolicyType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $policy->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Policy Type Updated Successfully!'
            ]);
        }

        return redirect()->route('policy-type.index')->with('success', 'Policy Type Updated Successfully!');
    }

    public function destroy($id)
    {
        try {
            $policy = InsurancePolicyType::findOrFail($id);
            $policy->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Policy Type Deleted!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Policy Type not found.'
            ]);
        }
    }

    public function getJson()
    {
        $policies = InsurancePolicyType::all();
        return response()->json($policies);
    }
}
