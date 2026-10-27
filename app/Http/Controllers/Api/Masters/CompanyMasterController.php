<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\CompanyMaster;
use Illuminate\Http\Request;

class CompanyMasterController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = CompanyMaster::with(['creator', 'modifier'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc');

        $data = $query->paginate($perPage);

        return $this->success($data, 'Company master data retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ], [
            'name.required' => 'Company name is required',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['modified_by'] = auth()->id();
        $validated['status'] = $request->boolean('status', true);

        $item = CompanyMaster::create($validated);

        return $this->success($item->load(['creator', 'modifier']), 'Company master created successfully', 201);
    }

    public function show($id)
    {
        $item = CompanyMaster::with(['creator', 'modifier'])->findOrFail($id);
        return $this->success($item, 'Company master retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $item = CompanyMaster::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ], [
            'name.required' => 'Company name is required',
        ]);

        $validated['modified_by'] = auth()->id();
        $validated['status'] = $request->boolean('status', true);

        $item->update($validated);

        return $this->success($item->load(['creator', 'modifier']), 'Company master updated successfully');
    }

    public function destroy($id)
    {
        $item = CompanyMaster::findOrFail($id);
        $item->deleted_by = auth()->id();
        $item->save();
        $item->delete();

        return $this->success(null, 'Company master deleted successfully');
    }
}
