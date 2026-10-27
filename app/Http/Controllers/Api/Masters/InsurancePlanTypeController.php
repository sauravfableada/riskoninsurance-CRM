<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\InsurancePlanType;
use Illuminate\Http\Request;

class InsurancePlanTypeController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = InsurancePlanType::when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->where('status', 'active')
            ->orderBy('name', 'ASC');

        $data = $query->paginate($perPage);

        return $this->success($data, 'Insurance plan types retrieved successfully');
    }

    public function list()
    {
        $types = InsurancePlanType::where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:insurance_plan_types,name',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Insurance Plan Type is required',
            'name.unique' => 'Insurance Plan Type already exists',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'active';

        $type = InsurancePlanType::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Insurance Plan Type added successfully',
            'data' => $type
        ], 201);
    }
}
