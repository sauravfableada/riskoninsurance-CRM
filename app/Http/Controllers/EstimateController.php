<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EstimateController extends Controller
{
    public function index()
    {
        $estimates = $this->scopeOwnedRecords(Estimate::with('customer'))
            ->latest()
            ->paginate(15);

        return view('estimate.index', compact('estimates'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('estimate.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'estimate_date' => 'required|date',
            'currency' => 'nullable|string',
            'products' => 'required|array',
            'comment' => 'nullable|string',
        ]);

        $products = $request->input('products');
        $total = 0;
        foreach ($products as $p) {
            $total += ($p['quantity'] * $p['amount']);
        }

        $estimate = Estimate::create([
            'customer_id' => $request->customer_id,
            'user_id' => Auth::id(),
            'estimate_no' => 'EST-' . mt_rand(1000, 9999),
            'estimate_date' => $request->estimate_date,
            'currency' => $request->currency,
            'total' => $total,
            'amount' => $total,
            'product_name' => json_encode($products),
            'status' => 'pending',
            'comment' => $request->comment,
        ]);

        return redirect()->route('estimates.index')->with('success', 'Estimate created successfully.');
    }

    public function show($id)
    {
        $estimate = Estimate::with('customer')->findOrFail($id);
        $products = json_decode($estimate->product_name, true) ?? [];
        $allProducts = Product::all()->keyBy('id');
        
        return view('estimate.show', compact('estimate', 'products', 'allProducts'));
    }

    public function edit($id)
    {
        $estimate = Estimate::findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $selectedProducts = json_decode($estimate->product_name, true) ?? [];

        return view('estimate.edit', compact('estimate', 'customers', 'products', 'selectedProducts'));
    }

    public function update(Request $request, $id)
    {
        $estimate = Estimate::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'estimate_date' => 'required|date',
            'currency' => 'nullable|string',
            'products' => 'required|array',
            'comment' => 'nullable|string',
        ]);

        $products = $request->input('products');
        $total = 0;
        foreach ($products as $p) {
            $total += ($p['quantity'] * $p['amount']);
        }

        $estimate->update([
            'customer_id' => $request->customer_id,
            'estimate_date' => $request->estimate_date,
            'currency' => $request->currency,
            'total' => $total,
            'amount' => $total,
            'product_name' => json_encode($products),
            'comment' => $request->comment,
        ]);

        return redirect()->route('estimates.index')->with('success', 'Estimate updated successfully.');
    }

    public function destroy($id)
    {
        $estimate = Estimate::findOrFail($id);
        $estimate->delete();

        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $estimate = Estimate::findOrFail($id);
        $estimate->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
