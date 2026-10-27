<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Segment;
use Illuminate\Http\Request;

class SegmentController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = Segment::with(['insuranceType', 'creator', 'modifier'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('insuranceType', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            })
            ->orderBy('id', 'desc');

        $data = $query->paginate($perPage);

        return $this->success($data, 'Segments retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'insurance_type_id' => 'required|exists:insurance_policy_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'insurance_type_id.required' => 'Insurance type is required',
            'insurance_type_id.exists' => 'Selected insurance type does not exist',
            'name.required' => 'Segment name is required',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['modified_by'] = auth()->id();

        $item = Segment::create($validated);

        return $this->success($item->load(['insuranceType', 'creator', 'modifier']), 'Segment created successfully', 201);
    }

    public function show($id)
    {
        $item = Segment::with(['insuranceType', 'creator', 'modifier'])->findOrFail($id);
        return $this->success($item, 'Segment retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $item = Segment::findOrFail($id);

        $validated = $request->validate([
            'insurance_type_id' => 'required|exists:insurance_policy_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'insurance_type_id.required' => 'Insurance type is required',
            'insurance_type_id.exists' => 'Selected insurance type does not exist',
            'name.required' => 'Segment name is required',
        ]);

        $validated['modified_by'] = auth()->id();

        $item->update($validated);

        return $this->success($item->load(['insuranceType', 'creator', 'modifier']), 'Segment updated successfully');
    }

    public function destroy($id)
    {
        $item = Segment::findOrFail($id);
        $item->deleted_by = auth()->id();
        $item->save();
        $item->delete();

        return $this->success(null, 'Segment deleted successfully');
    }

    public function getInsuranceTypes()
    {
        $types = \App\Models\InsurancePolicyType::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return $this->success($types, 'Insurance types retrieved successfully');
    }
}
