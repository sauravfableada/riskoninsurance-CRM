<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\InsurancePolicySubType;
use Illuminate\Http\Request;

class InsurancePolicySubTypeController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = InsurancePolicySubType::with(['policyType', 'creator', 'modifier'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('policyType', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            })
            ->orderBy('id', 'desc');

        $data = $query->paginate($perPage);

        return $this->success($data, 'Insurance policy sub types retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'insurance_policy_types_id' => 'required|exists:insurance_policy_types,id',
            'name' => 'required|string|max:255',
        ], [
            'insurance_policy_types_id.required' => 'Policy type is required',
            'insurance_policy_types_id.exists' => 'Selected policy type does not exist',
            'name.required' => 'Policy sub type name is required',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['modified_by'] = auth()->id();

        $item = InsurancePolicySubType::create($validated);

        return $this->success($item->load(['policyType', 'creator', 'modifier']), 'Insurance policy sub type created successfully', 201);
    }

    public function show($id)
    {
        $item = InsurancePolicySubType::with(['policyType', 'creator', 'modifier'])->findOrFail($id);
        return $this->success($item, 'Insurance policy sub type retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $item = InsurancePolicySubType::findOrFail($id);

        $validated = $request->validate([
            'insurance_policy_types_id' => 'required|exists:insurance_policy_types,id',
            'name' => 'required|string|max:255',
        ], [
            'insurance_policy_types_id.required' => 'Policy type is required',
            'insurance_policy_types_id.exists' => 'Selected policy type does not exist',
            'name.required' => 'Policy sub type name is required',
        ]);

        $validated['modified_by'] = auth()->id();

        $item->update($validated);

        return $this->success($item->load(['policyType', 'creator', 'modifier']), 'Insurance policy sub type updated successfully');
    }

    public function destroy($id)
    {
        $item = InsurancePolicySubType::findOrFail($id);
        $item->deleted_by = auth()->id();
        $item->save();
        $item->delete();

        return $this->success(null, 'Insurance policy sub type deleted successfully');
    }

    /**
     * Get all policy types for dropdown
     */
    public function getPolicyTypes()
    {
        $types = \App\Models\InsurancePolicyType::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return $this->success($types, 'Policy types retrieved successfully');
    }
}
