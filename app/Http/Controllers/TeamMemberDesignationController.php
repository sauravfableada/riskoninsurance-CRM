<?php

namespace App\Http\Controllers;

use App\Models\TeamMemberDesignation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamMemberDesignationController extends Controller
{
    public function index(): View
    {
        return view('masters.team_member_designations.index');
    }

    public function list(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => TeamMemberDesignation::orderBy('name')->get()]);
    }

    public function options(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => TeamMemberDesignation::where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'not_regex:/^super\s+admin$/i', 'unique:team_member_designations,name']]);
        $designation = TeamMemberDesignation::create($data + ['is_active' => true, 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);

        return response()->json(['status' => true, 'message' => 'Designation added successfully.', 'data' => $designation]);
    }

    public function update(Request $request, TeamMemberDesignation $designation): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'not_regex:/^super\s+admin$/i', Rule::unique('team_member_designations', 'name')->ignore($designation->id)],
            'is_active' => ['required', 'boolean'],
        ]);
        $designation->update($data + ['updated_by' => auth()->id()]);

        return response()->json(['status' => true, 'message' => 'Designation updated successfully.', 'data' => $designation->fresh()]);
    }

    public function destroy(TeamMemberDesignation $designation): JsonResponse
    {
        if (\App\Models\User::where('designation', $designation->name)->exists()) {
            return response()->json(['status' => false, 'message' => 'This designation is assigned to a team member and cannot be deleted.'], 422);
        }
        $designation->delete();
        return response()->json(['status' => true, 'message' => 'Designation deleted successfully.']);
    }
}
