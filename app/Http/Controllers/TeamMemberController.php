<?php

namespace App\Http\Controllers;

use App\Services\TeamMemberService;
use App\Models\User;
use App\Models\TeamMemberDesignation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function __construct(private readonly TeamMemberService $teamMemberService)
    {
    }

    public function index(): View
    {
        return view('TeamMember.index');
    }

    public function create(): View
    {
        return view('TeamMember.create', [
            'designations' => TeamMemberDesignation::where('is_active', true)->orderBy('name')->get(),
            'permissionMatrix' => $this->assignablePermissionMatrix(),
            'permissionActions' => config('crm_permissions.actions', []),
            'selectedPermissions' => [],
        ]);
    }

    public function edit(int $id): View
    {
        $memberData = $this->teamMemberService->getMemberWithPermissions($id);

        abort_if($memberData === null, 404, 'Team member not found');

        return view('TeamMember.edit', [
            'user' => $memberData['member'],
            'designations' => TeamMemberDesignation::orderBy('name')->get(),
            'permissionMatrix' => $this->assignablePermissionMatrix(),
            'permissionActions' => config('crm_permissions.actions', []),
            'selectedPermissions' => $this->teamMemberService->getFlatPermissions($id),
        ]);
    }

    private function assignablePermissionMatrix(): array
    {
        return collect(config('crm_permissions.modules', []))
            ->except(['users', 'roles'])
            ->all();
    }

    public function listAjax(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->teamMemberService->listMembers(true),
        ]);
    }

    public function view(int $id): JsonResponse
    {
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

    public function update(Request $request, int $id): JsonResponse
    {
        $memberData = $this->teamMemberService->getMemberWithPermissions($id);
        if ($memberData === null) {
            return response()->json([
                'status' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        $payload = $this->validatedPayload($request, true, $id);

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

    public function delete(int $id): JsonResponse
    {
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

    private function validatedPayload(Request $request, bool $isUpdate = false, ?int $memberId = null): array
    {
        $countryCode = $request->input('country_code', '+91');
        $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile', ''));
        $request->merge([
            'role' => 5,
            'country_code' => $countryCode,
            'mobile' => $mobile !== '' ? $countryCode . $mobile : null,
        ]);
        $passwordRule = $isUpdate
            ? ['nullable', 'string', 'min:6', 'max:20']
            : ['required', 'string', 'min:6', 'max:20'];

        $payload = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $passwordRule,
            'country_code' => ['required', 'regex:/^\+[0-9]{1,4}$/'],
            'mobile' => ['nullable', 'regex:/^\+[0-9]{5,19}$/'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'time_category' => ['nullable', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:100', Rule::exists('team_member_designations', 'name')],
            'role' => ['required', 'integer', Rule::in([3, 5])],
            'permissions' => ['nullable'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $payload['username'] = null;

        return $payload;
    }
}
