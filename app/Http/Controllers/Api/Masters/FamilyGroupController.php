<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\FamilyGroup;
use App\Models\FamilyGroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyGroupController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        $query = FamilyGroup::with(['holder', 'creator', 'modifier', 'members'])
            ->when($search, function ($q) use ($search) {
                $q->where('group_name', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('holder', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            })
            ->orderBy('id', 'desc');

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->get('from_date') . ' 00:00:00');
        }
        if ($request->filled('holder_id')) {
            $query->where('holder_id', $request->integer('holder_id'));
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->get('to_date') . ' 23:59:59');
        }
        if ($request->filled('group_name')) {
            $query->where('group_name', 'like', '%' . $request->get('group_name') . '%');
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->get('created_by'));
        }
        if ($request->filled('modified_by')) {
            $query->where('modified_by', $request->get('modified_by'));
        }

        $data = $query->paginate((int)$perPage);

        return $this->success($data, 'Family groups retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'holder_id' => 'nullable|exists:customers,id',
            'remarks' => 'nullable|string',
            'members' => 'nullable|array',
            'members.*.member_name' => 'required_with:members|string|max:255',
            'members.*.relation' => 'nullable|string|max:100',
            'members.*.contact' => 'nullable|string|max:20',
            'members.*.dob' => 'nullable|date',
            'members.*.anniversary' => 'nullable|date',
        ], [
            'group_name.required' => 'Group name is required',
            'holder_id.exists' => 'Selected policy holder does not exist',
        ]);

        DB::beginTransaction();
        try {
            $groupData = [
                'group_name' => $validated['group_name'],
                'holder_id' => $validated['holder_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id(),
                'modified_by' => auth()->id(),
            ];

            $group = FamilyGroup::create($groupData);

            // Create members if provided
            if (!empty($validated['members'])) {
                foreach ($validated['members'] as $member) {
                    FamilyGroupMember::create([
                        'family_group_id' => $group->id,
                        'member_name' => $member['member_name'],
                        'relation' => $member['relation'] ?? null,
                        'contact' => $member['contact'] ?? null,
                        'dob' => $member['dob'] ?? null,
                        'anniversary' => $member['anniversary'] ?? null,
                        'created_by' => auth()->id(),
                        'modified_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return $this->success($group->load(['holder', 'creator', 'modifier', 'members']), 'Family group created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create family group: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $group = FamilyGroup::with(['holder', 'creator', 'modifier', 'members'])->findOrFail($id);
        return $this->success($group, 'Family group retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $group = FamilyGroup::findOrFail($id);

        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'holder_id' => 'nullable|exists:customers,id',
            'remarks' => 'nullable|string',
            'members' => 'nullable|array',
            'members.*.id' => 'nullable|integer',
            'members.*.member_name' => 'required_with:members|string|max:255',
            'members.*.relation' => 'nullable|string|max:100',
            'members.*.contact' => 'nullable|string|max:20',
            'members.*.dob' => 'nullable|date',
            'members.*.anniversary' => 'nullable|date',
        ], [
            'group_name.required' => 'Group name is required',
            'holder_id.exists' => 'Selected policy holder does not exist',
        ]);

        DB::beginTransaction();
        try {
            $groupData = [
                'group_name' => $validated['group_name'],
                'holder_id' => $validated['holder_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'modified_by' => auth()->id(),
            ];

            $group->update($groupData);

            // Handle members
            if (isset($validated['members'])) {
                $existingIds = $group->members->pluck('id')->toArray();
                $updatedIds = [];

                foreach ($validated['members'] as $member) {
                    if (!empty($member['id'])) {
                        // Update existing member
                        $memberModel = FamilyGroupMember::find($member['id']);
                        if ($memberModel && $memberModel->family_group_id == $group->id) {
                            $memberModel->update([
                                'member_name' => $member['member_name'],
                                'relation' => $member['relation'] ?? null,
                                'contact' => $member['contact'] ?? null,
                                'dob' => $member['dob'] ?? null,
                                'anniversary' => $member['anniversary'] ?? null,
                                'modified_by' => auth()->id(),
                            ]);
                            $updatedIds[] = $member['id'];
                        }
                    } else {
                        // Create new member
                        $newMember = FamilyGroupMember::create([
                            'family_group_id' => $group->id,
                            'member_name' => $member['member_name'],
                            'relation' => $member['relation'] ?? null,
                            'contact' => $member['contact'] ?? null,
                            'dob' => $member['dob'] ?? null,
                            'anniversary' => $member['anniversary'] ?? null,
                            'created_by' => auth()->id(),
                            'modified_by' => auth()->id(),
                        ]);
                        $updatedIds[] = $newMember->id;
                    }
                }

                // Delete members not in the updated list
                $toDelete = array_diff($existingIds, $updatedIds);
                if (!empty($toDelete)) {
                    FamilyGroupMember::whereIn('id', $toDelete)->update(['deleted_by' => auth()->id()]);
                    FamilyGroupMember::whereIn('id', $toDelete)->delete();
                }
            }

            DB::commit();

            return $this->success($group->fresh()->load(['holder', 'creator', 'modifier', 'members']), 'Family group updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update family group: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $group = FamilyGroup::findOrFail($id);

        DB::beginTransaction();
        try {
            // Soft delete members first
            FamilyGroupMember::where('family_group_id', $id)->update(['deleted_by' => auth()->id()]);
            FamilyGroupMember::where('family_group_id', $id)->delete();

            // Soft delete group
            $group->deleted_by = auth()->id();
            $group->save();
            $group->delete();

            DB::commit();

            return $this->success(null, 'Family group deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to delete family group: ' . $e->getMessage(), 500);
        }
    }

    public function getCustomers()
    {
        $customers = \App\Models\Customer::orderBy('name')
            ->get(['id', 'name']);
        
        return $this->success($customers, 'Customers retrieved successfully');
    }
}
