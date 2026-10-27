<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Controller;
use App\Models\InsurancePlanName;
use Illuminate\Http\Request;

class InsurancePlanNameController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => InsurancePlanName::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $plan = InsurancePlanName::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Plan name created successfully',
            'data' => $plan
        ]);
    }
}
