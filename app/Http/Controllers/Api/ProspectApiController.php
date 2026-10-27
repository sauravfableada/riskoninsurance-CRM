<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProspectModel;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProspectApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $query = ProspectModel::query()->orderByDesc('created_at');

        if ($this->shouldRestrictToOwnProspects($user)) {
            $query->where('created_by', $user->id);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_no', 'like', '%' . $search . '%')
                    ->orWhere('area', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date') . ' 23:59:59');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        if ($request->filled('mobile_no')) {
            $query->where('mobile_no', 'like', '%' . $request->input('mobile_no') . '%');
        }
        if ($request->filled('area')) {
            $query->where('area', 'like', '%' . $request->input('area') . '%');
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        $permission = UserPermission::query()
            ->where('user_id', $user->id)
            ->where('module_id', 15)
            ->first();

        $legacyRole = data_get($user, 'role');
        $canUpdate = (string) $legacyRole === '1' ? (int) data_get($permission, 'edit', 0) === 1 : true;
        $canDelete = (string) $legacyRole === '1' ? (int) data_get($permission, 'delete', 0) === 1 : true;

        $perPage = max(1, min(50, (int) $request->input('per_page', 10)));
        $prospects = $query->paginate($perPage)->appends($request->query());

        $prospects->getCollection()->transform(function ($prospect) use ($canUpdate, $canDelete) {
            $prospect->created_by_name = User::query()->whereKey($prospect->created_by)->value('name') ?? 'Unknown';
            $prospect->can_update = $canUpdate;
            $prospect->can_delete = $canDelete;
            return $prospect;
        });

        return response()->json([
            'status' => true,
            'message' => 'Prospects fetched successfully',
            'data' => $prospects,
        ]);
    }

    public function show(?int $id = null): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $prospect = ProspectModel::find($id);

        if (!$prospect) {
            return response()->json([
                'status' => false,
                'message' => 'Prospect not found',
            ], 404);
        }

        $prospect->created_by_name = User::query()->whereKey($prospect->created_by)->value('name') ?? 'Unknown';

        return response()->json([
            'status' => true,
            'message' => 'Prospect fetched successfully',
            'data' => $prospect,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $status = $request->input('status') ?: 'prospect';
        $validStatuses = ['prospect', 'client'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'prospect';
        }

        $contact = $this->internationalMobile($request);
        $saved = ProspectModel::query()->create([
            'name' => $request->input('name'),
            'mobile_no' => $contact['mobile_no'],
            'country_code' => $contact['country_code'],
            'area' => $request->input('area'),
            'created_by' => $userId,
            'status' => $status,
        ]);

        if ($saved) {
            return response()->json([
                'status' => true,
                'message' => 'Prospect added successfully.',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to save prospect.',
        ], 500);
    }

    public function update(Request $request, ?int $id = null): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $prospect = ProspectModel::find($id);
        if (!$prospect) {
            return response()->json([
                'status' => false,
                'message' => 'Prospect not found',
            ], 404);
        }

        $status = $request->input('status');
        $validStatuses = ['prospect', 'client'];
        if ($status !== null && !in_array($status, $validStatuses, true)) {
            $status = 'prospect';
        }

        $data = [];

        if ($request->input('name') !== null) {
            $data['name'] = $request->input('name');
        }
        if ($request->input('mobile_no') !== null) {
            $contact = $this->internationalMobile($request, $prospect);
            $data['mobile_no'] = $contact['mobile_no'];
            $data['country_code'] = $contact['country_code'];
        }
        if ($request->input('area') !== null) {
            $data['area'] = $request->input('area');
        }
        if ($status !== null) {
            $data['status'] = $status;
        }

        try {
            if (!empty($data)) {
                $prospect->update($data);
            }

            return response()->json([
                'status' => true,
                'message' => 'Prospect updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete(?int $id = null): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Prospect ID.',
            ], 400);
        }

        try {
            ProspectModel::query()->whereKey($id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Prospect deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function shouldRestrictToOwnProspects(object $user): bool
    {
        $legacyRole = data_get($user, 'role');

        if ($legacyRole !== null) {
            return (string) $legacyRole === '1';
        }

        if (method_exists($user, 'isAdmin')) {
            return !$user->isAdmin();
        }

        return true;
    }

    private function internationalMobile(Request $request, ?ProspectModel $prospect = null): array
    {
        $countryCode = (string) $request->input('country_code', $prospect?->country_code ?: '+91');
        if (!preg_match('/^\+[0-9]{1,4}$/', $countryCode)) {
            $countryCode = '+91';
        }
        $rawMobile = trim((string) $request->input('mobile_no', ''));
        $mobile = preg_replace('/\D+/', '', $rawMobile);

        return [
            'country_code' => $countryCode,
            'mobile_no' => str_starts_with($rawMobile, '+') ? '+' . $mobile : ($mobile !== '' ? $countryCode . $mobile : ''),
        ];
    }
}
