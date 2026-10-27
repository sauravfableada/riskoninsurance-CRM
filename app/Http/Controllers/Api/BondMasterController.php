<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BondName;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BondMasterController extends Controller
{
    /**
     * Get all bonds for DataTable
     */
    public function list(): JsonResponse
    {
        try {
            \Log::info('Bond list called');
            $bonds = BondName::orderBy('id', 'DESC')->get();
            \Log::info('Bond query result count: ' . $bonds->count());

            $data = [];
            foreach ($bonds as $key => $bond) {
                // Handle both string and integer status values
                $isActive = ($bond->status == 1 || $bond->status === 'active' || $bond->status === '1');
                $data[] = [
                    'id' => $bond->id ?? null,
                    'sr_no' => $key + 1,
                    'bond_name' => $bond->bond_name ?? '--',
                    'status' => $isActive ? 1 : 0,
                    'created_at' => $bond->created_at ? $bond->created_at->format('Y-m-d H:i:s') : '--',
                    'updated_at' => $bond->updated_at ? $bond->updated_at->format('Y-m-d H:i:s') : '--'
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $data,
                'debug' => [
                    'total_records' => $bonds->count(),
                    'sample_record' => $bonds->first() ? $bonds->first()->toArray() : null
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Bond list error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error loading bonds: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Check if bond name exists
     */
    public function checkName(Request $request): JsonResponse
    {
        $request->validate([
            'bond_name' => 'required|string',
            'exclude_id' => 'nullable|integer'
        ]);

        try {
            $bondName = $request->get('bond_name');
            $excludeId = $request->get('exclude_id');

            $query = BondName::where('bond_name', $bondName);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json([
                'status' => true,
                'exists' => $exists
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error checking bond name: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new bond
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'bond_name' => 'required|string|max:255',
            'status' => 'required|in:0,1,active,inactive'
        ]);

        try {
            $bondName = $request->get('bond_name');

            // Check if bond name already exists
            $exists = BondName::where('bond_name', $bondName)->exists();
            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bond name already exists'
                ], 422);
            }

            // Convert status to string
            $statusValue = $request->get('status');
            $statusString = (in_array($statusValue, [1, '1', 'active'], true)) ? 'active' : 'inactive';

            $bond = BondName::create([
                'bond_name' => $bondName,
                'status' => $statusString
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Bond added successfully',
                'data' => $bond
            ]);
        } catch (\Exception $e) {
            \Log::error('Bond store error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to add bond: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bond for editing
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $bond = BondName::find($id);

            if ($bond) {
                return response()->json([
                    'status' => true,
                    'data' => $bond
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Bond not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching bond: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update bond
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'bond_name' => 'required|string|max:255',
            'status' => 'required|in:0,1,active,inactive'
        ]);

        try {
            $id = $request->get('id');
            $bondName = $request->get('bond_name');

            $bond = BondName::find($id);

            if (!$bond) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bond not found'
                ], 404);
            }

            // Check if bond name already exists (excluding current)
            $exists = BondName::where('bond_name', $bondName)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bond name already exists'
                ], 422);
            }

            // Convert status to string
            $statusValue = $request->get('status');
            $statusString = (in_array($statusValue, [1, '1', 'active'], true)) ? 'active' : 'inactive';

            $bond->update([
                'bond_name' => $bondName,
                'status' => $statusString
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Bond updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Bond update error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update bond: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete bond
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $bond = BondName::find($id);

            if (!$bond) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bond not found'
                ], 404);
            }

            $bond->delete();

            return response()->json([
                'status' => true,
                'message' => 'Bond deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete bond: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status only
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|boolean'
        ]);

        $id = $request->get('id');
        $status = $request->get('status');

        try {
            $bond = BondName::find($id);

            if (!$bond) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bond not found'
                ], 404);
            }

            $statusString = $status ? 'active' : 'inactive';
            $bond->update(['status' => $statusString]);

            return response()->json([
                'status' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
