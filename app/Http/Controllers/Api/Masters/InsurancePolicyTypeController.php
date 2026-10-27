<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\InsurancePolicyType;
use Illuminate\Http\Request;

class InsurancePolicyTypeController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = InsurancePolicyType::with(['creator', 'modifier'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc');

        $data = $query->paginate($perPage);

        return $this->success($data, 'Insurance policy types retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:insurance_policy_types,name',
            'status' => 'boolean',
        ], [
            'name.required' => 'Policy type name is required',
            'name.unique' => 'This policy type already exists',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['modified_by'] = auth()->id();

        $item = InsurancePolicyType::create($validated);

        return $this->success($item->load(['creator', 'modifier']), 'Insurance policy type created successfully', 201);
    }

    public function show($id)
    {
        $item = InsurancePolicyType::with(['creator', 'modifier'])->findOrFail($id);
        return $this->success($item, 'Insurance policy type retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $item = InsurancePolicyType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:insurance_policy_types,name,' . $id,
            'status' => 'boolean',
        ], [
            'name.required' => 'Policy type name is required',
            'name.unique' => 'This policy type already exists',
        ]);

        $validated['modified_by'] = auth()->id();

        $item->update($validated);

        return $this->success($item->load(['creator', 'modifier']), 'Insurance policy type updated successfully');
    }

    public function destroy($id)
    {
        $item = InsurancePolicyType::findOrFail($id);
        $item->deleted_by = auth()->id();
        $item->save();
        $item->delete();

        return $this->success(null, 'Insurance policy type deleted successfully');
    }
}
