<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\AgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgencyLevelController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = AgencyLevel::with(['creator', 'modifier'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc');

        return $this->success($query->paginate($perPage), 'Agency levels retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('agency_levels', 'name')->whereNull('deleted_at'),
            ],
        ], [
            'name.required' => 'Agency level name is required',
            'name.min' => 'Agency level name must be at least 3 characters',
            'name.unique' => 'Agency Level already exists',
        ]);

        $validated['status'] = true;
        $validated['created_by'] = auth()->id();
        $validated['modified_by'] = auth()->id();

        $agencyLevel = AgencyLevel::create($validated);

        return $this->success($agencyLevel->load(['creator', 'modifier']), 'Agency Level added successfully', 201);
    }

    public function show($id)
    {
        $agencyLevel = AgencyLevel::with(['creator', 'modifier'])->findOrFail($id);

        return $this->success($agencyLevel, 'Agency Level retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $agencyLevel = AgencyLevel::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('agency_levels', 'name')->ignore($agencyLevel->id)->whereNull('deleted_at'),
            ],
            'status' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Agency level name is required',
            'name.min' => 'Agency level name must be at least 3 characters',
            'name.unique' => 'Agency Level name already exists',
        ]);

        if ($request->has('status')) {
            $validated['status'] = $request->boolean('status');
        }

        $validated['modified_by'] = auth()->id();

        $agencyLevel->update($validated);

        return $this->success($agencyLevel->load(['creator', 'modifier']), 'Agency Level updated successfully');
    }

    public function destroy($id)
    {
        $agencyLevel = AgencyLevel::findOrFail($id);
        $agencyLevel->delete();

        return $this->success(null, 'Agency Level deleted successfully');
    }
}
