<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    /** Show the Payment Method listing page. */
    public function index()
    {
        return view('masters.payment_method.index');
    }

    /** API: Return all payment methods for DataTable (JSON). */
    public function list()
    {
        $records = PaymentMethod::withTrashed()
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

    /** API: Store new payment method. */
    public function store(Request $request)
    {
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Payment method name is required.']);
        }

        if (PaymentMethod::where('name', $name)->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Payment method name already exists.']);
        }

        $userId = Auth::id();

        $record = PaymentMethod::create([
            'name'        => $name,
            'status'      => 1,
            'created_by'  => $userId,
            'modified_by' => $userId,
        ]);

        if ($record) {
            return response()->json([
                'status' => true,
                'message' => 'Payment method added successfully.',
                'data' => [
                    'id' => $record->id,
                    'name' => $record->name,
                ],
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Failed to add payment method.']);
    }

    /** API: Get a single payment method for editing. */
    public function edit($id)
    {
        $record = PaymentMethod::find($id);

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

        return response()->json(['status' => false, 'message' => 'Payment method not found.']);
    }

    /** API: Update existing payment method. */
    public function update(Request $request)
    {
        $id   = $request->post('id');
        $name = trim($request->post('name'));

        if (empty($name)) {
            return response()->json(['status' => false, 'message' => 'Payment method name is required.']);
        }

        if (PaymentMethod::where('name', $name)->where('id', '!=', $id)->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Payment method name already exists.']);
        }

        $record = PaymentMethod::find($id);
        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Payment method not found.']);
        }

        $record->name        = $name;
        $record->modified_by = Auth::id();

        if ($record->save()) {
            return response()->json(['status' => true, 'message' => 'Payment method updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update payment method.']);
    }

    /** API: Soft-delete a payment method. */
    public function destroy($id)
    {
        $record = PaymentMethod::find($id);
        if ($record && $record->delete()) {
            return response()->json(['status' => true, 'message' => 'Payment method deleted successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to delete payment method.']);
    }

    /** API: Toggle active/inactive status. */
    public function updateStatus(Request $request)
    {
        $id     = $request->post('id');
        $status = $request->post('status') ? 1 : 0;

        $record = PaymentMethod::find($id);
        if ($record && $record->update(['status' => $status, 'modified_by' => Auth::id()])) {
            return response()->json(['status' => true, 'message' => 'Status updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to update status.']);
    }
}
