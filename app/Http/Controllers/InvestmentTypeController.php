<?php

namespace App\Http\Controllers;

use App\Models\InvestmentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentTypeController extends Controller
{
    // View: Investment Type Listing Page
    public function index()
    {
        return view('masters.investment_type.index');
    }

    // API: Get all investment types for DataTable
    public function list()
    {
        $investmentTypes = InvestmentType::withTrashed()
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $investmentTypes->values()->map(function ($type, $key) {
            return [
                'id'         => $type->id,
                'sr_no'      => $key + 1,
                'name'       => $type->type_name,
                'type'       => $type->category,
                'status'     => $type->status,
                'created_at' => $type->created_at ? $type->created_at->format('Y-m-d H:i:s') : '',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    // API: Get static investment types (categories)
    public function getTypes()
    {
        return response()->json(['status' => true, 'data' => InvestmentType::$types]);
    }

    // API: Check if investment type name already exists
    public function checkName(Request $request)
    {
        $name      = $request->post('name');
        $excludeId = $request->post('exclude_id');

        $query = InvestmentType::where('type_name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json([
            'status' => true,
            'exists' => $query->count() > 0,
        ]);
    }

    // API: Add new investment type
    public function store(Request $request)
    {
        $data = [
            'type_name' => $request->post('name'),
            'category'  => $request->post('type'),
            'status'    => $request->post('status'),
            'created_by'=> Auth::id(),
            'updated_by'=> Auth::id(),
        ];

        if (empty($data['type_name'])) {
            return response()->json(['status' => false, 'message' => 'Investment type name is required']);
        }
        if (empty($data['category'])) {
            return response()->json(['status' => false, 'message' => 'Investment category is required']);
        }
        if (!in_array($data['category'], InvestmentType::$types)) {
            return response()->json(['status' => false, 'message' => 'Invalid investment category selected']);
        }

        $exists = InvestmentType::where('type_name', $data['type_name'])->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'Investment type name already exists']);
        }

        $type = InvestmentType::create($data);
        if ($type) {
            return response()->json([
                'status' => true,
                'message' => 'Investment type added successfully',
                'data' => $type
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add investment type']);
    }

    // API: Get investment type for editing
    public function edit($id)
    {
        $investmentType = InvestmentType::find($id);

        if ($investmentType) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'         => $investmentType->id,
                    'name'       => $investmentType->type_name,
                    'type'       => $investmentType->category,
                    'status'     => $investmentType->status,
                    'created_at' => $investmentType->created_at,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Investment type not found']);
    }

    // API: Update investment type
    public function update(Request $request)
    {
        $id   = $request->post('id');
        $data = [
            'type_name' => $request->post('name'),
            'category'  => $request->post('type'),
            'status'    => $request->post('status'),
            'updated_by'=> Auth::id(),
        ];

        if (empty($data['type_name'])) {
            return response()->json(['status' => false, 'message' => 'Investment type name is required']);
        }
        if (empty($data['category'])) {
            return response()->json(['status' => false, 'message' => 'Investment category is required']);
        }
        if (!in_array($data['category'], InvestmentType::$types)) {
            return response()->json(['status' => false, 'message' => 'Invalid investment category selected']);
        }

        $exists = InvestmentType::where('type_name', $data['type_name'])
            ->where('id', '!=', $id)
            ->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'Investment type name already exists']);
        }

        $record = InvestmentType::find($id);
        if ($record && $record->update($data)) {
            return response()->json(['status' => true, 'message' => 'Investment type updated successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update investment type']);
    }

    // API: Delete investment type
    public function destroy($id)
    {
        $record = InvestmentType::find($id);
        if ($record) {
            $record->deleted_by = Auth::id();
            $record->save();
            if ($record->delete()) {
                return response()->json(['status' => true, 'message' => 'Investment type deleted successfully']);
            }
        }
        return response()->json(['status' => false, 'message' => 'Failed to delete investment type']);
    }

    // API: Update status only
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = InvestmentType::find($id);
        if ($record && $record->update(['status' => $status, 'updated_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully']);
        }
        return response()->json(['status' => false, 'message' => 'Failed to update status']);
    }
}

