<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\BankMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankMasterController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $banks = BankMaster::with(['creator', 'modifier'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->success($banks, 'Bank masters retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('bank_master', 'name')->whereNull('deleted_at'),
            ],
        ], [
            'name.required' => 'Bank Master name is required',
            'name.min' => 'Bank Master name must be at least 3 characters',
            'name.max' => 'Bank Master name cannot exceed 100 characters',
            'name.unique' => 'Bank master already exists',
        ]);

        $validated['status'] = true;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $bank = BankMaster::create($validated);

        return $this->success($bank->load(['creator', 'modifier']), 'Bank master added successfully', 201);
    }

    public function show($id)
    {
        $bank = BankMaster::with(['creator', 'modifier'])->findOrFail($id);

        return $this->success($bank, 'Bank master retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $bank = BankMaster::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('bank_master', 'name')->ignore($bank->id)->whereNull('deleted_at'),
            ],
            'status' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Bank Master name is required',
            'name.min' => 'Bank Master name must be at least 3 characters',
            'name.max' => 'Bank Master name cannot exceed 100 characters',
            'name.unique' => 'Bank master name already exists',
        ]);

        if ($request->has('status')) {
            $validated['status'] = $request->boolean('status');
        }

        $validated['updated_by'] = auth()->id();
        $bank->update($validated);

        return $this->success(
            $bank->load(['creator', 'modifier']),
            $request->has('name') ? 'Bank master updated successfully' : 'Status updated successfully'
        );
    }

    public function destroy($id)
    {
        BankMaster::findOrFail($id)->delete();

        return $this->success(null, 'Bank master deleted successfully');
    }
}
