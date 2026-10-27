<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $companyId = $request->get('company_id');

        $query = Branch::with(['company', 'creator', 'modifier'])
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('company', function ($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('id', 'desc');

        $data = $query->paginate($perPage);

        return $this->success($data, 'Branches retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:company_master,id',
            'name' => 'required|string|max:255',
        ], [
            'company_id.required' => 'Company is required',
            'company_id.exists' => 'Selected company does not exist',
            'name.required' => 'Branch name is required',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['modified_by'] = auth()->id();

        $item = Branch::create($validated);

        return $this->success($item->load(['company', 'creator', 'modifier']), 'Branch created successfully', 201);
    }

    public function show($id)
    {
        $item = Branch::with(['company', 'creator', 'modifier'])->findOrFail($id);
        return $this->success($item, 'Branch retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $item = Branch::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:company_master,id',
            'name' => 'required|string|max:255',
        ], [
            'company_id.required' => 'Company is required',
            'company_id.exists' => 'Selected company does not exist',
            'name.required' => 'Branch name is required',
        ]);

        $validated['modified_by'] = auth()->id();

        $item->update($validated);

        return $this->success($item->load(['company', 'creator', 'modifier']), 'Branch updated successfully');
    }

    public function destroy($id)
    {
        $item = Branch::findOrFail($id);
        $item->deleted_by = auth()->id();
        $item->save();
        $item->delete();

        return $this->success(null, 'Branch deleted successfully');
    }

    public function getCompanies()
    {
        $companies = \App\Models\CompanyMaster::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return $this->success($companies, 'Companies retrieved successfully');
    }
}
