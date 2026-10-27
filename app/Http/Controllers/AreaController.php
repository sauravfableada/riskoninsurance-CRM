<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    /** Show the Area listing page. */
    public function index()
    {
        return view('masters.area.index');
    }

    /** API: Return all cities for the dropdown. */
    public function getCities()
    {
        $cities = City::orderBy('name')->get(['id', 'name']);
        return response()->json(['status' => true, 'data' => $cities]);
    }

    /** API: Return all areas for DataTable (JSON). */
    public function list()
    {
        $records = Area::withTrashed()
            ->whereNull('deleted_at')
            ->with(['city:id,name', 'creator:id,name', 'modifier:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $records->values()->map(function ($item, $key) {
            return [
                'id'          => $item->id,
                'sr_no'       => $key + 1,
                'city_id'     => $item->city_id,
                'city_name'   => $item->city ? $item->city->name : '—',
                'area_name'   => $item->area_name,
                'status'      => $item->status,
                'created_at'  => $item->created_at ? $item->created_at->format('Y-m-d') : '—',
                'updated_at'  => $item->updated_at ? $item->updated_at->format('Y-m-d') : '—',
                'created_by'  => $item->creator  ? strtoupper($item->creator->name)  : '—',
                'modified_by' => $item->modifier ? strtoupper($item->modifier->name) : '—',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /** API: Store new area. */
    public function store(Request $request)
    {
        $cityId   = $request->post('city_id');
        $areaName = trim($request->post('area_name'));
        $status   = $request->post('status', 1);

        if (empty($cityId)) {
            return response()->json(['status' => false, 'message' => 'Please select a city.']);
        }
        if (empty($areaName)) {
            return response()->json(['status' => false, 'message' => 'Area name is required.']);
        }

        if (Area::where('city_id', $cityId)->where('area_name', $areaName)->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Area name already exists for this city.']);
        }

        $userId = Auth::id();

        $record = Area::create([
            'city_id'     => $cityId,
            'area_name'   => $areaName,
            'status'      => $status,
            'created_by'  => $userId,
            'modified_by' => $userId,
        ]);

        if ($record) {
            return response()->json(['status' => true, 'message' => 'Area added successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add area.']);
    }

    /** API: Get a single area for editing. */
    public function edit($id)
    {
        $record = Area::find($id);

        if ($record) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'        => $record->id,
                    'city_id'   => $record->city_id,
                    'area_name' => $record->area_name,
                    'status'    => $record->status,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Area not found.']);
    }

    /** API: Update existing area. */
    public function update(Request $request)
    {
        $id       = $request->post('id');
        $cityId   = $request->post('city_id');
        $areaName = trim($request->post('area_name'));
        $status   = $request->post('status', 1);

        if (empty($cityId)) {
            return response()->json(['status' => false, 'message' => 'Please select a city.']);
        }
        if (empty($areaName)) {
            return response()->json(['status' => false, 'message' => 'Area name is required.']);
        }

        if (Area::where('city_id', $cityId)->where('area_name', $areaName)->where('id', '!=', $id)->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Area name already exists for this city.']);
        }

        $record = Area::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Area not found.']);
        }

        $record->city_id     = $cityId;
        $record->area_name   = $areaName;
        $record->status      = $status;
        $record->modified_by = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Area updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update area.']);
    }

    /** API: Soft-delete an area. */
    public function destroy($id)
    {
        $record = Area::find($id);
        if ($record && $record->delete()) {
            return response()->json(['status' => true, 'message' => 'Area deleted successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete area.']);
    }

    /** API: Toggle active/inactive status. */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = Area::find($id);
        if ($record && $record->update(['status' => $status, 'modified_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
