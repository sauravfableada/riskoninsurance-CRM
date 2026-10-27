<?php

namespace App\Http\Controllers;

use App\Models\RoomCategory;
use Illuminate\Http\Request;

class RoomCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roomCategories = RoomCategory::orderBy('name')->paginate(15);

        return view('masters.room_categories.index', compact('roomCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.room_categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:room_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        RoomCategory::create($data);

        return redirect()->route('masters.room_categories.index')->with('success', 'Room category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.room_categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $roomCategory = RoomCategory::findOrFail($id);

        return view('masters.room_categories.edit', compact('roomCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $roomCategory = RoomCategory::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:room_categories,name,'.$id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $roomCategory->update($data);

        return redirect()->route('masters.room_categories.index')->with('success', 'Room category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $roomCategory = RoomCategory::findOrFail($id);
        $roomCategory->delete();

        return redirect()->route('masters.room_categories.index')->with('success', 'Room category deleted successfully.');
    }
}
