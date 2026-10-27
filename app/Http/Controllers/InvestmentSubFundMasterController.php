<?php

namespace App\Http\Controllers;

use App\Models\InvestmentSubFundMaster;
use App\Models\FundType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentSubFundMasterController extends Controller
{
    /**
     * Show the Investment Sub Fund Master listing page.
     */
    public function index()
    {
        $fundNames = FundType::orderBy('name')->get();
        return view('masters.investment_sub_fund_master.index', compact('fundNames'));
    }

    /**
     * API: Return all sub fund records for DataTable (JSON).
     */
    public function list()
    {
        $records = InvestmentSubFundMaster::withTrashed()
            ->whereNull('deleted_at')
            ->with(['creator:id,name', 'modifier:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $records->values()->map(function ($item, $key) {
            return [
                'id'           => $item->id,
                'sr_no'        => $key + 1,
                'fund_name'    => $item->fund_name,
                'sub_fund_name'=> $item->sub_fund_name,
                'status'       => $item->status,
                'created_at'   => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '—',
                'created_by'   => $item->creator  ? strtoupper($item->creator->name)  : '—',
                'updated_by'   => $item->modifier ? strtoupper($item->modifier->name) : '—',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * API: Store new sub fund record.
     */
    public function store(Request $request)
    {
        $fundName    = trim($request->post('fund_name'));
        $subFundName = trim($request->post('sub_fund_name'));

        if (empty($fundName)) {
            return response()->json(['status' => false, 'message' => 'Fund name is required.']);
        }
        if (empty($subFundName)) {
            return response()->json(['status' => false, 'message' => 'Sub fund name is required.']);
        }

        $exists = InvestmentSubFundMaster::where('fund_name', $fundName)
            ->where('sub_fund_name', $subFundName)
            ->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'This sub fund already exists for the selected fund.']);
        }

        $userId = Auth::id();

        $record = InvestmentSubFundMaster::create([
            'fund_name'     => $fundName,
            'sub_fund_name' => $subFundName,
            'status'        => $request->post('status', 1),
            'created_by'    => $userId,
            'updated_by'    => $userId,
        ]);

        if ($record) {
            return response()->json(['status' => true, 'message' => 'Investment sub fund added successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add investment sub fund.']);
    }

    /**
     * API: Get a single record for editing.
     */
    public function edit($id)
    {
        $record = InvestmentSubFundMaster::find($id);

        if ($record) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'            => $record->id,
                    'fund_name'     => $record->fund_name,
                    'sub_fund_name' => $record->sub_fund_name,
                    'status'        => $record->status,
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
        $id          = $request->post('id');
        $fundName    = trim($request->post('fund_name'));
        $subFundName = trim($request->post('sub_fund_name'));

        if (empty($fundName)) {
            return response()->json(['status' => false, 'message' => 'Fund name is required.']);
        }
        if (empty($subFundName)) {
            return response()->json(['status' => false, 'message' => 'Sub fund name is required.']);
        }

        $exists = InvestmentSubFundMaster::where('fund_name', $fundName)
            ->where('sub_fund_name', $subFundName)
            ->where('id', '!=', $id)
            ->count();
        if ($exists > 0) {
            return response()->json(['status' => false, 'message' => 'This sub fund already exists for the selected fund.']);
        }

        $record = InvestmentSubFundMaster::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Record not found.']);
        }

        $record->fund_name     = $fundName;
        $record->sub_fund_name = $subFundName;
        $record->status        = $request->post('status', $record->status);
        $record->updated_by    = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Investment sub fund updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update investment sub fund.']);
    }

    /**
     * API: Soft-delete a record.
     */
    public function destroy($id)
    {
        $record = InvestmentSubFundMaster::find($id);
        if ($record) {
            $record->deleted_by = Auth::id();
            $record->save();
            if ($record->delete()) {
                return response()->json(['status' => true, 'message' => 'Investment sub fund deleted successfully.']);
            }
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete investment sub fund.']);
    }

    /**
     * API: Toggle active/inactive status.
     */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = InvestmentSubFundMaster::find($id);
        if ($record && $record->update(['status' => $status, 'updated_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
