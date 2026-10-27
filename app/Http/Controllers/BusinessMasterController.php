<?php

namespace App\Http\Controllers;

use App\Models\BusinessMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessMasterController extends Controller
{
    /**
     * Show the Business Master listing page.
     */
    public function index()
    {
        return view('masters.business_master.index');
    }

    /**
     * API: Return all business master records for DataTable (JSON).
     */
    public function list()
    {
        $records = BusinessMaster::withTrashed()
            ->whereNull('deleted_at')
            ->with(['creator:id,name', 'modifier:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $records->values()->map(function ($item, $key) {
            return [
                'id'           => $item->id,
                'sr_no'        => $key + 1,
                'name'         => $item->name,
                'status'       => $item->status,
                'created_by'   => $item->creator ? strtoupper($item->creator->name) : '—',
                'updated_by'   => $item->modifier ? strtoupper($item->modifier->name) : '—',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * API: Store new business master record.
     */
    public function store(Request $request)
    {
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Business Master name is required.']);
        }

        $exists = BusinessMaster::where('name', $name)->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'This Business Master already exists.']);
        }

        $userId = Auth::id();

        $record = BusinessMaster::create([
            'name'       => $name,
            'status'     => $request->post('status', 1),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($record) {
            return response()->json(['status' => true, 'message' => 'Business Master added successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add Business Master.']);
    }

    /**
     * API: Get a single record for editing.
     */
    public function edit($id)
    {
        $record = BusinessMaster::find($id);

        if ($record) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'     => $record->id,
                    'name'   => $record->name,
                    'status' => $record->status,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Record not found.']);
    }

    /**
     * API: Update existing record.
     */
    public function update(Request $request)
    {
        $id   = $request->post('id');
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Business Master name is required.']);
        }

        $exists = BusinessMaster::where('name', $name)
            ->where('id', '!=', $id)
            ->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'This Business Master already exists.']);
        }

        $record = BusinessMaster::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Record not found.']);
        }

        $record->name       = $name;
        $record->status     = $request->post('status', $record->status);
        $record->updated_by = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Business Master updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update Business Master.']);
    }

    /**
     * API: Soft-delete a record.
     */
    public function destroy($id)
    {
        $record = BusinessMaster::find($id);
        if ($record) {
            $record->deleted_by = Auth::id();
            $record->save();
            if ($record->delete()) {
                return response()->json(['status' => true, 'message' => 'Business Master deleted successfully.']);
            }
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete Business Master.']);
    }

    /**
     * API: Toggle active/inactive status.
     */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = BusinessMaster::find($id);
        if ($record && $record->update(['status' => $status, 'updated_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
