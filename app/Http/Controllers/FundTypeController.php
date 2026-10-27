<?php

namespace App\Http\Controllers;

use App\Models\FundType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FundTypeController extends Controller
{
    /**
     * Show the Fund Type listing page.
     */
    public function index()
    {
        return view('masters.fund_type.index');
    }

    /**
     * API: Return all fund types for DataTable (JSON).
     */
    public function list()
    {
        $fundTypes = FundType::withTrashed()
            ->whereNull('deleted_at')
            ->with(['creator:id,name', 'modifier:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $fundTypes->values()->map(function ($item, $key) {
            return [
                'id'          => $item->id,
                'sr_no'       => $key + 1,
                'name'        => $item->name,
                'status'      => $item->status,
                'created_by'  => $item->creator ? strtoupper($item->creator->name) : '—',
                'modified_by' => $item->modifier ? strtoupper($item->modifier->name) : '—',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * API: Store new fund type.
     */
    public function store(Request $request)
    {
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Fund type name is required.']);
        }

        $exists = FundType::where('name', $name)->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'Fund type name already exists.']);
        }

        $userId = Auth::id();

        $fundType = FundType::create([
            'name'        => $name,
            'status'      => 1,
            'created_by'  => $userId,
            'modified_by' => $userId,
        ]);

        if ($fundType) {
            return response()->json([
                'status' => true, 
                'message' => 'Fund type added successfully.',
                'data' => [
                    'id' => $fundType->id,
                    'name' => $fundType->name
                ]
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add fund type.']);
    }

    /**
     * API: Get a single fund type for editing.
     */
    public function edit($id)
    {
        $fundType = FundType::find($id);

        if ($fundType) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'     => $fundType->id,
                    'name'   => $fundType->name,
                    'status' => $fundType->status,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Fund type not found.']);
    }

    /**
     * API: Update existing fund type.
     */
    public function update(Request $request)
    {
        $id   = $request->post('id');
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Fund type name is required.']);
        }

        $exists = FundType::where('name', $name)->where('id', '!=', $id)->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'Fund type name already exists.']);
        }

        $record = FundType::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Fund type not found.']);
        }

        $record->name        = $name;
        $record->modified_by = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Fund type updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update fund type.']);
    }

    /**
     * API: Soft-delete a fund type.
     */
    public function destroy($id)
    {
        $record = FundType::find($id);
        if ($record && $record->delete()) {
            return response()->json(['status' => true, 'message' => 'Fund type deleted successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete fund type.']);
    }

    /**
     * API: Toggle active/inactive status.
     */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = FundType::find($id);
        if ($record && $record->update(['status' => $status, 'modified_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
