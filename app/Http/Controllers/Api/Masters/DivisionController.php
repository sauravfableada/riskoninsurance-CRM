<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $divisions = Division::with('creator')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->success($divisions, 'Divisions retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('division', 'name')->whereNull('deleted_at'),
            ],
        ], [
            'name.required' => 'Division name is required',
            'name.min' => 'Division name must be at least 3 characters',
            'name.unique' => 'Division already exists',
        ]);

        $validated['status'] = true;
        $validated['created_by'] = auth()->id();

        $division = Division::create($validated);

        return $this->success($division->load('creator'), 'Division added successfully', 201);
    }

    public function show($id)
    {
        $division = Division::with('creator')->findOrFail($id);

        return $this->success($division, 'Division retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $division = Division::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('division', 'name')->ignore($division->id)->whereNull('deleted_at'),
            ],
            'status' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Division name is required',
            'name.min' => 'Division name must be at least 3 characters',
            'name.unique' => 'Division name already exists',
        ]);

        if ($request->has('status')) {
            $validated['status'] = $request->boolean('status');
        }

        $division->update($validated);

        return $this->success(
            $division->load('creator'),
            $request->has('name') ? 'Division updated successfully' : 'Status updated successfully'
        );
    }

    public function destroy($id)
    {
        Division::findOrFail($id)->delete();

        return $this->success(null, 'Division deleted successfully');
    }
}
