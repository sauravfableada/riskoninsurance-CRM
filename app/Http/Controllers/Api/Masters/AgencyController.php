<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Agency;
use App\Models\AgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgencyController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search  = $request->get('search', '');
        $levelId = $request->get('agency_levels_id');
        $activeOnly = $request->boolean('active_only');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100, 1000], true) ? $perPage : 10;

        $query = Agency::with(['agencyLevel', 'creator', 'modifier'])
            ->when($activeOnly, function ($q) {
                $q->where('status', true);
            })
            ->when($levelId, function ($q) use ($levelId) {
                $q->where('agency_levels_id', $levelId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('agencyLevel', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('id', 'desc');
        // dd($query);
        return $this->success($query->paginate($perPage), 'Agencies retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'min:2', 'max:255', Rule::unique('agencies', 'name')],
            'agency_levels_id' => ['required', 'exists:agency_levels,id'],
            'status'           => ['nullable', 'boolean'],
        ], [
            'name.required'             => 'Agency name is required',
            'name.unique'               => 'Agency name already exists',
            'agency_levels_id.required' => 'Agency level is required',
        ]);

        $validated['status']      = $request->boolean('status', true);
        $validated['created_by']  = auth()->id();
        $validated['modified_by'] = auth()->id();

        $agency = Agency::create($validated);

        return $this->success($agency->load('agencyLevel'), 'Agency added successfully', 201);
    }

    public function show($id)
    {
        $agency = Agency::with('agencyLevel')->findOrFail($id);
        return $this->success($agency, 'Agency retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $agency = Agency::findOrFail($id);

        $validated = $request->validate([
            'name'             => ['sometimes', 'required', 'string', 'min:2', 'max:255',
                                   Rule::unique('agencies', 'name')->ignore($agency->id)],
            'agency_levels_id' => ['sometimes', 'required', 'exists:agency_levels,id'],
            'status'           => ['sometimes', 'boolean'],
        ], [
            'name.required'             => 'Agency name is required',
            'name.unique'               => 'Agency name already exists',
            'agency_levels_id.required' => 'Agency level is required',
        ]);

        if ($request->has('status')) {
            $validated['status'] = $request->boolean('status');
        }

        $validated['modified_by'] = auth()->id();
        $agency->update($validated);

        return $this->success($agency->load('agencyLevel'), 'Agency updated successfully');
    }

    public function destroy($id)
    {
        Agency::findOrFail($id)->delete();
        return $this->success(null, 'Agency deleted successfully');
    }

    public function levels()
    {
        $levels = AgencyLevel::where('status', true)->orderBy('name')->get(['id', 'name']);
        return $this->success($levels, 'Agency levels retrieved successfully');
    }
}
