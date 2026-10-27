<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionStaffLimitService;
use App\Services\TeamMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    public function __construct(private readonly TeamMemberService $teamMemberService)
    {
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->teamMemberService->listMembers(),
        ]);
    }

    public function view(Request $request, ?int $id = null): JsonResponse
    {
        $id ??= (int) $request->input('id');

        if ($id <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid team member ID',
            ], 422);
        }

        $memberData = $this->teamMemberService->getMemberWithPermissions($id);
        if ($memberData === null) {
            return response()->json([
                'status' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => array_merge($memberData['member'], [
                'module_permissions' => $memberData['module_permissions'],
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        if ($this->teamMemberService->emailExists($payload['email'])) {
            return response()->json([
                'status' => false,
                'message' => 'Email already exists',
            ], 422);
        }

        $limit = app(SubscriptionStaffLimitService::class)->reachedForUser(auth()->id());
        if ($limit) {
            return response()->json([
                'status' => false,
                'code' => 'subscription_staff_limit_reached',
                'message' => $limit['message'],
                'plan_name' => $limit['plan_name'],
                'staff_limit' => $limit['staff_limit'],
                'staff_count' => $limit['staff_count'],
            ], 422);
        }

        $newId = $this->teamMemberService->createMember(
            $payload,
            $request->file('profile_image'),
            auth()->id()
        );

        return response()->json([
            'status' => true,
            'message' => 'Team member created successfully',
            'id' => $newId,
        ]);
    }

    public function update(Request $request, ?int $id = null): JsonResponse
    {
        $id ??= (int) $request->input('id');

        if ($id <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid team member ID',
            ], 422);
        }

        $memberData = $this->teamMemberService->getMemberWithPermissions($id);
        if ($memberData === null) {
            return response()->json([
                'status' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        $payload = $this->validatedPayload($request, true);

        if ($this->teamMemberService->emailExists($payload['email'], $id)) {
            return response()->json([
                'status' => false,
                'message' => 'Email already exists',
            ], 422);
        }

        $this->teamMemberService->updateMember(
            $id,
            $payload,
            $request->file('profile_image')
        );

        return response()->json([
            'status' => true,
            'message' => 'Team member updated successfully',
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $updated = $this->teamMemberService->updateStatus((int) $validated['id'], (string) $validated['status']);

        if (!$updated) {
            return response()->json([
                'status' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully',
        ]);
    }

    public function delete(Request $request, ?int $id = null): JsonResponse
    {
        $id ??= (int) $request->input('id');

        if ($id <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid team member ID',
            ], 422);
        }

        $deleted = $this->teamMemberService->deleteMember($id);

        if (!$deleted) {
            return response()->json([
                'status' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Team member deleted successfully',
        ]);
    }

    private function validatedPayload(Request $request, bool $isUpdate = false): array
    {
        $request->merge(['role' => 5]);
        $passwordRule = $isUpdate
            ? ['nullable', 'string', 'min:6', 'max:20']
            : ['required', 'string', 'min:6', 'max:20'];

        $payload = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $passwordRule,
            'mobile' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'time_category' => ['nullable', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:100'],
            'role' => ['required', 'integer', Rule::in([3, 5])],
            'permissions' => ['nullable'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $payload['username'] = null;

        return $payload;
    }
}
