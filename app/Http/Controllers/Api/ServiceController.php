<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\ModuleStatusHistory;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends ApiBaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $search = $request->get('search');

        $query = Service::with(['product', 'creator', 'updater']);
        
        // Apply ownership filter for non-admin users
        $this->scopeOwnedRecords($query);
        
        $services = $query
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('service_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Services retrieved successfully',
            'data' => $services,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'service_name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'service_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'status_comment' => 'nullable|string|max:2000',
        ], [
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist in our records.',
            'service_name.required' => 'Please enter the service name.',
            'service_name.max' => 'Service name cannot exceed 255 characters.',
            'description.required' => 'Please enter the service description.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'service_price.required' => 'Please enter the service price.',
            'service_price.numeric' => 'Service price must be a valid number.',
            'service_price.min' => 'Service price cannot be negative.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be either Active or Inactive.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $service = Service::create($data);
        $historyEntry = $this->recordStatusHistory($service, $data['status'] ?? null, $data['status_comment'] ?? null);
        app(\App\Services\UserLogService::class)->created($service);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'data' => $service->fresh(['product', 'creator', 'updater']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('services.index'),
        ], 201);
    }

    public function show(string $id)
    {
        $service = Service::with(['product', 'creator', 'updater'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Service retrieved successfully',
            'data' => $service,
        ], 200);

    }

    public function update(Request $request, string $id)
    {
        $service = Service::findOrFail($id);
        $originalStatus = $service->status;
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'service_name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'service_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'status_comment' => 'nullable|string|max:2000',
        ], [
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist in our records.',
            'service_name.required' => 'Please enter the service name.',
            'service_name.max' => 'Service name cannot exceed 255 characters.',
            'description.required' => 'Please enter the service description.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'service_price.required' => 'Please enter the service price.',
            'service_price.numeric' => 'Service price must be a valid number.',
            'service_price.min' => 'Service price cannot be negative.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be either Active or Inactive.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        $service->update($data);
        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($service, $data['status'] ?? $service->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($service);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'data' => $service->fresh(['product', 'creator', 'updater']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
            'redirect' => route('services.index', $service->id),
        ]);
    }

    public function destroy(string $id)
    {
        $service = Service::findOrFail($id);
        $service->deleted_by = Auth::id();
        $service->save();
        app(\App\Services\UserLogService::class)->deleted($service);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ]);
    }

    private function recordStatusHistory(Service $service, ?string $status, ?string $comment): ?ModuleStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        return ModuleStatusHistory::create([
            'historable_type' => Service::class,
            'historable_id' => $service->id,
            'status' => $status,
            'comment' => filled($comment) ? $comment : null,
            'updated_by' => Auth::id(),
        ]);
    }

    private function serializeHistoryEntry(?ModuleStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        return [
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? Auth::user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }
}
