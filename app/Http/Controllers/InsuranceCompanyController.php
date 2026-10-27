<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;

class InsuranceCompanyController extends Controller
{
    public function index()
    {
        $companies = InsuranceCompany::all();
        return view('insurance-company.index', compact('companies'));
    }

    public function create()
    {
        return view('insurance-company.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();

        $company = InsuranceCompany::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Company added successfully.',
                'id'      => $company->id,
                'name'    => $company->name
            ]);
        }

        return redirect()->route('insurance-company.index')->with('success', 'Company added successfully.');
    }

    public function edit($id)
    {
        $company = InsuranceCompany::findOrFail($id);
        return view('insurance-company.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $company = InsuranceCompany::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $company->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Company updated successfully.'
            ]);
        }

        return redirect()->route('insurance-company.index')->with('success', 'Company updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $company = InsuranceCompany::findOrFail($id);
            $company->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Company deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete company.'
            ]);
        }
    }

    public function getJson()
    {
        $data = InsuranceCompany::all();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}
