<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvestmentHolder;
use Illuminate\Http\Request;

class InvestmentHolderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'family_group_id' => 'required|integer',
        ]);

        $holder = InvestmentHolder::create([
            'holder_name' => $request->name,
            'family_group_id' => $request->family_group_id,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Holder created successfully',
            'data' => [
                'id' => $holder->id,
                'name' => $holder->holder_name,
            ]
        ]);
    }

    public function byFamilyGroup(Request $request)
    {
        $holders = InvestmentHolder::where('family_group_id', $request->family_group_id)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $holders->map(fn($h) => [
                'id' => $h->id,
                'name' => $h->holder_name
            ])
        ]);
    }
}
