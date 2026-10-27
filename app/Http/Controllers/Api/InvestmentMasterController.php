<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvestmentFolioNumber;
use App\Models\InvestmentFundName;
use App\Models\InvestmentMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentMasterController extends Controller
{
    /**
     * Store a new Folio Number
     */
    public function storeFolio(Request $request)
    {
        $request->validate([
            'folio_number' => 'required|string|max:255',
            'investor_name' => 'nullable|string|max:255',
        ]);

        $folio = InvestmentFolioNumber::create([
            'folio_number' => $request->folio_number,
            'investor_name' => $request->investor_name,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Folio created successfully',
            'data' => [
                'id' => $folio->id,
                'name' => $folio->folio_number,
            ]
        ]);
    }

    /**
     * Store a new Fund Name
     */
    public function storeFundName(Request $request)
    {
        $request->validate([
            'fund_name' => 'required|string|max:255',
        ]);

        $fund = InvestmentFundName::create([
            'fund_name' => $request->fund_name,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Fund Name created successfully',
            'data' => [
                'id' => $fund->id,
                'name' => $fund->fund_name,
            ]
        ]);
    }

    /**
     * Store a new Mode
     */
    public function storeMode(Request $request)
    {
        $request->validate([
            'mode_name' => 'required|string|max:255',
        ]);

        $mode = InvestmentMode::create([
            'mode_name' => $request->mode_name,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Mode created successfully',
            'data' => [
                'id' => $mode->id,
                'name' => $mode->mode_name,
            ]
        ]);
    }
}
