<?php

namespace App\Http\Controllers;

use App\Models\InvestedParty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestedPartyController extends Controller
{
    /**
     * Show the Invested Party listing page.
     */
    public function index()
    {
        return view('masters.invested_party.index');
    }

    /**
     * API: Return all invested parties for DataTable (JSON).
     */
    public function list()
    {
        $parties = InvestedParty::withTrashed()
            ->whereNull('deleted_at')
            ->with(['creator:id,name', 'modifier:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $parties->values()->map(function ($item, $key) {
            return [
                'id'          => $item->id,
                'sr_no'       => $key + 1,
                'name'        => $item->name,
                'city'        => $item->city ?? '—',
                'remark'      => $item->remark ?? '—',
                'status'      => $item->status,
                'created_by'  => $item->creator  ? strtoupper($item->creator->name)  : '—',
                'modified_by' => $item->modifier ? strtoupper($item->modifier->name) : '—',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * API: Store new invested party.
     */
    public function store(Request $request)
    {
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Invested party name is required.']);
        }

        $exists = InvestedParty::where('name', $name)->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'Invested party name already exists.']);
        }

        $userId = Auth::id();

        $party = InvestedParty::create([
            'name'        => $name,
            'city'        => trim($request->post('city')) ?: null,
            'remark'      => trim($request->post('remark')) ?: null,
            'status'      => 1,
            'created_by'  => $userId,
            'modified_by' => $userId,
        ]);

        if ($party) {
            return response()->json(['status' => true, 'message' => 'Invested party added successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add invested party.']);
    }

    /**
     * API: Get a single invested party for editing.
     */
    public function edit($id)
    {
        $party = InvestedParty::find($id);

        if ($party) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'     => $party->id,
                    'name'   => $party->name,
                    'city'   => $party->city,
                    'remark' => $party->remark,
                    'status' => $party->status,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Invested party not found.']);
    }

    /**
     * API: Update existing invested party.
     */
    public function update(Request $request)
    {
        $id   = $request->post('id');
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Invested party name is required.']);
        }

        $exists = InvestedParty::where('name', $name)->where('id', '!=', $id)->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'Invested party name already exists.']);
        }

        $record = InvestedParty::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Invested party not found.']);
        }

        $record->name        = $name;
        $record->city        = trim($request->post('city')) ?: null;
        $record->remark      = trim($request->post('remark')) ?: null;
        $record->modified_by = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Invested party updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update invested party.']);
    }

    /**
     * API: Soft-delete an invested party.
     */
    public function destroy($id)
    {
        $record = InvestedParty::find($id);
        if ($record && $record->delete()) {
            return response()->json(['status' => true, 'message' => 'Invested party deleted successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete invested party.']);
    }

    /**
     * API: Toggle active/inactive status.
     */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = InvestedParty::find($id);
        if ($record && $record->update(['status' => $status, 'modified_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
