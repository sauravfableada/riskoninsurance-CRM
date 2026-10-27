<?php

namespace App\Http\Controllers;

use App\Models\PostOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostOfficeController extends Controller
{
    /** Show the Post Office listing page. */
    public function index()
    {
        return view('masters.post_office.index');
    }

    /** API: Return all post offices for DataTable (JSON). */
    public function list()
    {
        $records = PostOffice::withTrashed()
            ->whereNull('deleted_at')
            ->with(['creator:id,name', 'modifier:id,name'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $records->values()->map(function ($item, $key) {
            return [
                'id'          => $item->id,
                'sr_no'       => $key + 1,
                'name'        => $item->name,
                'status'      => $item->status,
                'created_by'  => $item->creator  ? strtoupper($item->creator->name)  : '—',
                'modified_by' => $item->modifier ? strtoupper($item->modifier->name) : '—',
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /** API: Store new post office. */
    public function store(Request $request)
    {
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Post office name is required.']);
        }

        if (PostOffice::where('name', $name)->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Post office name already exists.']);
        }

        $userId = Auth::id();

        $record = PostOffice::create([
            'name'        => $name,
            'status'      => 1,
            'created_by'  => $userId,
            'modified_by' => $userId,
        ]);

        if ($record) {
            return response()->json(['status' => true, 'message' => 'Post office added successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add post office.']);
    }

    /** API: Get a single post office for editing. */
    public function edit($id)
    {
        $record = PostOffice::find($id);

        if ($record) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'     => $record->id,
                    'name'   => $record->name,
                    'status' => $record->status,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Post office not found.']);
    }

    /** API: Update existing post office. */
    public function update(Request $request)
    {
        $id   = $request->post('id');
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Post office name is required.']);
        }

        if (PostOffice::where('name', $name)->where('id', '!=', $id)->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Post office name already exists.']);
        }

        $record = PostOffice::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Post office not found.']);
        }

        $record->name        = $name;
        $record->modified_by = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Post office updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update post office.']);
    }

    /** API: Soft-delete a post office. */
    public function destroy($id)
    {
        $record = PostOffice::find($id);
        if ($record && $record->delete()) {
            return response()->json(['status' => true, 'message' => 'Post office deleted successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete post office.']);
    }

    /** API: Toggle active/inactive status. */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = PostOffice::find($id);
        if ($record && $record->update(['status' => $status, 'modified_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
