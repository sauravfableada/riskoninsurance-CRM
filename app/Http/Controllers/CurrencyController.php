<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currencies = Currency::orderBy('code')->paginate(15);

        return view('masters.currencies.index', compact('currencies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.currencies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $data['is_default'] = $request->has('is_default');
        $data['is_active'] = $request->has('is_active');

        if ($data['is_default']) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        Currency::create($data);

        return redirect()->route('masters.currencies.index')->with('success', 'Currency created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.currencies.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $currency = Currency::findOrFail($id);

        return view('masters.currencies.edit', compact('currency'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $currency = Currency::findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:currencies,code,' . $currency->id],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $data['is_default'] = $request->has('is_default');
        $data['is_active'] = $request->has('is_active');

        if ($data['is_default']) {
            Currency::where('id', '!=', $currency->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $currency->update($data);

        return redirect()->route('masters.currencies.index')->with('success', 'Currency updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $currency = Currency::findOrFail($id);
        $currency->delete();

        return redirect()->route('masters.currencies.index')->with('success', 'Currency deleted successfully.');
    }
}
