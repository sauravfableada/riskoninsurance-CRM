<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessRequestHistory;
use App\Models\StaffAccessRequest;
use App\Models\StaffPermissionGrant;
use App\Services\AccessRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessRequestAdminController extends Controller
{
    public function __construct(private readonly AccessRequestService $accessRequestService)
    {
    }

    public function index(): View
    {
        return view('crm.admin.access-requests.index', [
            'modules' => config('crm_permissions.modules', []),
            'pendingCount' => StaffAccessRequest::query()
                ->where('status', StaffAccessRequest::STATUS_PENDING)
                ->count(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $status = strtolower((string) $request->get('status', 'all'));
        $module = trim((string) $request->get('module', ''));
        $search = trim((string) $request->get('search', ''));

        $requestsQuery = StaffAccessRequest::query()
            ->with(['staff:id,name,email,designation,type', 'reviewer:id,name', 'grant'])
            ->when($status !== '' && $status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($module !== '', function ($q) use ($module) {
                $q->where('module', $module);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('permission', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('staff', function ($staffQuery) use ($search) {
                            $staffQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id');

        $requests = $requestsQuery->get()->map(function (StaffAccessRequest $item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'staff_name' => $item->staff?->name ?? 'Unknown Staff',
                'staff_email' => $item->staff?->email ?? '--',
                'staff_designation' => $item->staff?->designation ?? 'Staff',
                'module' => $item->module,
                'module_label' => config("crm_permissions.modules.{$item->module}.label") ?? ucfirst(str_replace('_', ' ', $item->module)),
                'action' => $item->action,
                'action_label' => ucfirst($item->action),
                'permission' => $item->permission,
                'permission_label' => $this->accessRequestService->formatPermissionLabel($item->module, $item->action),
                'record_id' => $item->record_id,
                'reason' => $item->reason,
                'status' => $item->status,
                'admin_comment' => $item->admin_comment,
                'reviewed_by_name' => $item->reviewer?->name,
                'requested_at' => optional($item->requested_at ?? $item->created_at)->format('d M Y, h:i A'),
                'reviewed_at' => optional($item->reviewed_at)->format('d M Y, h:i A'),
                'grant_id' => $item->grant?->id,
                'grant_is_active' => (bool) ($item->grant?->is_active ?? false),
            ];
        })->values();

        $activeGrants = StaffPermissionGrant::query()
            ->with(['staff:id,name,email,designation', 'granter:id,name', 'revoker:id,name'])
            ->when($module !== '', fn ($q) => $q->where('module', $module))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('permission', 'like', "%{$search}%")
                        ->orWhereHas('staff', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->get()
            ->map(function (StaffPermissionGrant $grant) {
                return [
                    'id' => $grant->id,
                    'user_id' => $grant->user_id,
                    'staff_name' => $grant->staff?->name ?? 'Unknown Staff',
                    'staff_email' => $grant->staff?->email ?? '--',
                    'module' => $grant->module,
                    'module_label' => config("crm_permissions.modules.{$grant->module}.label") ?? ucfirst(str_replace('_', ' ', $grant->module)),
                    'action' => $grant->action,
                    'permission' => $grant->permission,
                    'permission_label' => $this->accessRequestService->formatPermissionLabel($grant->module, $grant->action),
                    'is_active' => (bool) $grant->is_active,
                    'granted_by_name' => $grant->granter?->name ?? 'Admin',
                    'granted_at' => optional($grant->granted_at ?? $grant->created_at)->format('d M Y, h:i A'),
                    'revoked_by_name' => $grant->revoker?->name,
                    'revoked_at' => optional($grant->revoked_at)->format('d M Y, h:i A'),
                    'revocation_reason' => $grant->revocation_reason,
                ];
            })->values();

        $histories = AccessRequestHistory::query()
            ->with(['staff:id,name', 'actor:id,name'])
            ->when($module !== '', fn ($q) => $q->where('module', $module))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('permission', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%")
                        ->orWhereHas('staff', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->limit(150)
            ->get()
            ->map(function (AccessRequestHistory $history) {
                return [
                    'id' => $history->id,
                    'access_request_id' => $history->access_request_id,
                    'event' => $history->event,
                    'staff_name' => $history->staff?->name ?? 'Unknown',
                    'actor_name' => $history->actor?->name ?? 'System',
                    'permission' => $history->permission,
                    'permission_label' => $this->accessRequestService->formatPermissionLabel($history->module, $history->action),
                    'record_id' => $history->record_id,
                    'comment' => $history->comment,
                    'created_at' => optional($history->created_at)->format('d M Y, h:i A'),
                ];
            })->values();

        $counts = [
            'all' => StaffAccessRequest::query()->count(),
            'pending' => StaffAccessRequest::query()->where('status', StaffAccessRequest::STATUS_PENDING)->count(),
            'approved' => StaffAccessRequest::query()->where('status', StaffAccessRequest::STATUS_APPROVED)->count(),
            'rejected' => StaffAccessRequest::query()->where('status', StaffAccessRequest::STATUS_REJECTED)->count(),
            'active_grants' => StaffPermissionGrant::query()->where('is_active', true)->whereNull('revoked_at')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $requests,
                'grants' => $activeGrants,
                'histories' => $histories,
                'counts' => $counts,
            ],
        ]);
    }

    public function approve(Request $request, StaffAccessRequest $accessRequest): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updated = $this->accessRequestService->approveRequest(
                $accessRequest,
                $request->user(),
                $validated['comment'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Access request approved and permission granted to staff member.',
            'data' => [
                'id' => $updated->id,
                'status' => $updated->status,
            ],
        ]);
    }

    public function reject(Request $request, StaffAccessRequest $accessRequest): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updated = $this->accessRequestService->rejectRequest(
                $accessRequest,
                $request->user(),
                $validated['comment'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Access request rejected.',
            'data' => [
                'id' => $updated->id,
                'status' => $updated->status,
            ],
        ]);
    }

    public function revoke(Request $request, StaffPermissionGrant $grant): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updated = $this->accessRequestService->revokeGrant(
                $grant,
                $request->user(),
                $validated['reason'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission revoked successfully.',
            'data' => [
                'id' => $updated->id,
                'is_active' => $updated->is_active,
            ],
        ]);
    }
}
