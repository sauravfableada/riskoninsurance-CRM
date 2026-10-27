<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Controller;
use App\Models\Broker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrokerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $brokers = Broker::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('broker_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'status' => true,
            'data' => $brokers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'broker_name' => ['required', 'string', 'max:255', Rule::unique('brokers', 'broker_name')],
            'status' => ['nullable'],
        ], [
            'broker_name.required' => 'Broker name is required',
            'broker_name.unique' => 'Broker name already exists',
        ]);

        $validated['status'] = $this->normalizeStatus($request->input('status', 1));
        $broker = Broker::create($validated);

        return response()->json([
            'success' => true,
            'status' => true,
            'message' => 'Broker added successfully',
            'data' => $broker
        ], 201);
    }

    public function show($id)
    {
        $broker = Broker::findOrFail($id);

        return response()->json([
            'success' => true,
            'status' => true,
            'data' => $this->formatBroker($broker),
        ]);
    }

    public function update(Request $request, $id)
    {
        $broker = Broker::findOrFail($id);

        $validated = $request->validate([
            'broker_name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('brokers', 'broker_name')->ignore($broker->id)],
            'status' => ['nullable'],
        ], [
            'broker_name.required' => 'Broker name is required',
            'broker_name.unique' => 'Broker name already exists',
        ]);

        if ($request->has('status')) {
            $validated['status'] = $this->normalizeStatus($request->input('status'));
        }

        $broker->update($validated);

        return response()->json([
            'success' => true,
            'status' => true,
            'message' => $request->has('broker_name') ? 'Broker updated successfully' : 'Status updated successfully',
            'data' => $this->formatBroker($broker->fresh()),
        ]);
    }

    public function destroy($id)
    {
        Broker::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'status' => true,
            'message' => 'Broker deleted successfully',
        ]);
    }

    private function normalizeStatus($value): int
    {
        return in_array($value, [1, '1', true, 'true', 'active', 'on'], true) ? 1 : 0;
    }

    private function formatBroker(Broker $broker): array
    {
        return [
            'id' => $broker->id,
            'broker_name' => $broker->broker_name,
            'status' => $broker->is_active,
            'created_at' => $broker->created_at,
            'updated_at' => $broker->updated_at,
        ];
    }
}
