<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StageController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $data = Stage::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Stages retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $stage = Stage::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Stage retrieved successfully.',
            'data' => $stage,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:stages,name',
            'status' => 'nullable|in:in_progress,paused,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = $validated['status'] ?? 'in_progress';
        $validated['sort_order'] = 0;
        $validated['is_default'] = false;
        $validated['is_active'] = true;

        $stage = Stage::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stage created successfully.',
            'data' => $stage,
            'redirect' => route('masters.stages.index'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $stage = Stage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:stages,name,'.$stage->id,
            'status' => 'nullable|in:in_progress,paused,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = $validated['status'] ?? ($stage->status ?? 'in_progress');
        $validated['sort_order'] = $stage->sort_order ?? 0;
        $validated['is_default'] = $stage->is_default ?? false;
        $validated['is_active'] = $stage->is_active ?? true;

        $stage->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stage updated successfully.',
            'data' => $stage->fresh(),
            'redirect' => route('masters.stages.index'),
        ]);
    }

    public function destroy($id)
    {
        $stage = Stage::findOrFail($id);

        if ($stage->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Default stage cannot be deleted.',
            ], 422);
        }

        $hasLeads = Lead::query()->where('lead_stage_id', $stage->id)->exists();
        $hasDeals = Deal::query()->where('stage_id', $stage->id)->exists();
        $hasPipelines = Pipeline::query()->where('stage_id', $stage->id)->exists();

        if ($hasLeads || $hasDeals || $hasPipelines) {
            return response()->json([
                'success' => false,
                'message' => 'Stage is in use and cannot be deleted.',
            ], 422);
        }

        $stage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stage deleted successfully.',
        ]);
    }
}
