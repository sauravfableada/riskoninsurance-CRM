<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Throwable;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends ApiBaseController
{
    private function allowedMatrixPermissionNames(): array
    {
        $actions = array_keys(config('crm_permissions.actions', []));
        $names = [];

        foreach (config('crm_permissions.modules', []) as $module => $meta) {
            if (in_array($module, ['users', 'roles'], true)) {
                continue;
            }
            $moduleActions = $meta['actions'] ?? $actions;

            foreach ($moduleActions as $action) {
                $names[] = "{$action}_{$module}";
            }
        }

        return array_values(array_unique($names));
    }

    private function syncMatrixPermissions(User $user, array $permissions = []): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);

        $users = User::with('roles')
            ->where('type', 'sub_agents')
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super-admin']);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->get('status'));
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->where('created_at', '>=', $request->get('from_date') . ' 00:00:00');
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->where('created_at', '<=', $request->get('to_date') . ' 23:59:59');
            })
            ->latest()
            ->paginate($perPage);

        // dd($users);
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ]);
    }

    /**
     * Search users for dropdown/autocomplete.
     */
    public function search(Request $request)
    {
        $search = $request->get('q', $request->get('search', ''));
        
        $query = User::query()->where('is_active', 1);
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->limit(20)->get(['id', 'name', 'email']);
        
        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'role' => 'staff',
            'country_code' => $request->input('country_code', '+91'),
            'whatsapp_country_code' => $request->input('whatsapp_country_code', '+91'),
        ]);
        $allowedPermissions = $this->allowedMatrixPermissionNames();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'country_code' => ['required', 'regex:/^\+[0-9]{1,4}$/'],
            'phone' => ['required', 'regex:/^[0-9]{4,15}$/'],
            'whatsapp_country_code' => ['required_with:whatsapp', 'regex:/^\+[0-9]{1,4}$/'],
            'whatsapp' => ['nullable', 'regex:/^[0-9]{4,15}$/'],
            'address' => 'nullable|string',
            'image' => 'nullable|file|mimes:avif,webp,jpg,jpeg,png,gif,bmp,svg|max:2048',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ], [
            'country_code.regex' => 'Enter a valid country code, for example +91.',
            'phone.regex' => 'Enter a valid phone number using 4 to 15 digits.',
            'whatsapp_country_code.regex' => 'Enter a valid WhatsApp country code, for example +91.',
            'whatsapp.regex' => 'Enter a valid WhatsApp number using 4 to 15 digits.',
            'image.mimes' => 'Please select a valid image! Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Image size must not exceed 2MB.',
            'role.exists' => 'The selected role is invalid.',
        ]);

        $avatarPath = null;
        if ($request->hasFile('image')) {
            $avatarPath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'type' => 'sub_agents',
            'password' => Hash::make($data['password']),
            'phone' => $data['country_code'] . $data['phone'],
            'country_code' => $data['country_code'],
            'whatsapp' => !empty($data['whatsapp']) ? $data['whatsapp_country_code'] . $data['whatsapp'] : null,
            'whatsapp_country_code' => $data['whatsapp_country_code'] ?? '+91',
            'address' => $data['address'] ?? null,
            'avatar_path' => $avatarPath,
            'is_active' => true,
        ]);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $this->syncMatrixPermissions($user, $data['permissions'] ?? []);
        app(\App\Services\UserLogService::class)->created($user, 'Created a Sub-Agent ' . $user->name);

        try {
            $phone = $user->whatsapp ?: $user->phone;
            if ($phone) {
                $roleText = $user->roles()->pluck('name')->map(fn($role) => ucfirst($role))->implode(', ');
                if ($roleText === '') {
                    $roleText = 'Staff';
                }

                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'staff_account_created',
                    $phone,
                    [
                        $user->name ?? 'Staff',
                        $user->name ?? 'Staff',
                        $user->email ?? '--',
                        $roleText,
                    ],
                    $user->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Staff create WhatsApp block failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sub Agent created successfully.',
            'data' => $user->load(['roles', 'permissions']),
            'redirect' => route('users.index'),
        ], 201);
    }

    public function show(User $user)
    {
        abort_unless($user->type === 'sub_agents', 404);
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => $user->load(['roles', 'permissions']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->type === 'sub_agents', 404);
        $request->merge([
            'role' => 'staff',
            'country_code' => $request->input('country_code', $user->country_code ?: '+91'),
            'whatsapp_country_code' => $request->input('whatsapp_country_code', $user->whatsapp_country_code ?: '+91'),
        ]);
        $allowedPermissions = $this->allowedMatrixPermissionNames();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', Rule::in([$user->email])],
            'password' => 'nullable|string|min:8',
            'country_code' => ['required', 'regex:/^\+[0-9]{1,4}$/'],
            'phone' => ['required', 'regex:/^[0-9]{4,15}$/'],
            'whatsapp_country_code' => ['required_with:whatsapp', 'regex:/^\+[0-9]{1,4}$/'],
            'whatsapp' => ['nullable', 'regex:/^[0-9]{4,15}$/'],
            'address' => 'nullable|string',
            'image' => 'nullable|file|mimes:avif,webp,jpg,jpeg,png,gif,bmp,svg|max:2048',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ], [
            'email.in' => 'Email cannot be changed.',
            'country_code.regex' => 'Enter a valid country code, for example +91.',
            'phone.regex' => 'Enter a valid phone number using 4 to 15 digits.',
            'whatsapp_country_code.regex' => 'Enter a valid WhatsApp country code, for example +91.',
            'whatsapp.regex' => 'Enter a valid WhatsApp number using 4 to 15 digits.',
            'image.mimes' => 'Please select a valid image! Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Image size must not exceed 2MB.',
            'role.exists' => 'The selected role is invalid.',
        ]);

        if ($request->hasFile('image')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('image')->store('users', 'public');
        }

        $user->name = $data['name'];
        $user->type = 'sub_agents';
        $user->phone = $data['country_code'] . $data['phone'];
        $user->country_code = $data['country_code'];
        $user->whatsapp = !empty($data['whatsapp']) ? $data['whatsapp_country_code'] . $data['whatsapp'] : null;
        $user->whatsapp_country_code = $data['whatsapp_country_code'] ?? '+91';
        $user->address = $data['address'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $this->syncMatrixPermissions($user, $data['permissions'] ?? []);
        app(\App\Services\UserLogService::class)->updated($user, 'Updated a Sub-Agent ' . $user->name);

        try {
            $phone = $user->whatsapp ?: $user->phone;
            if ($phone) {
                $roleText = $user->roles()->pluck('name')->map(fn($role) => ucfirst($role))->implode(', ');
                if ($roleText === '') {
                    $roleText = 'Staff';
                }

                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'staff_account_updated',
                    $phone,
                    [
                        $user->name ?? 'Staff',
                        $user->name ?? 'Staff',
                        $user->email ?? '--',
                        $roleText,
                    ],
                    $user->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Staff update WhatsApp block failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sub Agent updated successfully.',
            'data' => $user->load(['roles', 'permissions']),
            'redirect' => route('users.index'),
        ]);
    }

    public function destroy(User $user)
    {
        abort_unless($user->type === 'sub_agents', 404);
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete yourself.',
            ], 422);
        }

        try {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            app(\App\Services\UserLogService::class)->deleted($user, 'Deleted a Sub-Agent ' . $user->name);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sub Agent deleted successfully.',
            ]);
        } catch (QueryException $exception) {
            // Foreign-key protected records exist for this user in other modules.
            $isConstraintError = (string) $exception->getCode() === '23000'
                || (int) ($exception->errorInfo[1] ?? 0) === 1451;

            if ($isConstraintError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module in use. This sub-agent is linked with existing records, so it cannot be deleted.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete sub-agent right now. Please try again.',
            ], 500);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete sub-agent right now. Please try again.',
            ], 500);
        }
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->type === 'sub_agents', 404);
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own status.',
            ], 422);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->is_active = (bool) $data['is_active'];
        $user->save();
        app(\App\Services\UserLogService::class)->updated($user, 'Updated a Sub-Agent ' . $user->name);

        return response()->json([
            'success' => true,
            'message' => $user->name . ' marked as ' . ($user->is_active ? 'active' : 'inactive') . '.',
            'is_active' => (bool) $user->is_active,
        ]);
    }

    public function updatePermissions(Request $request, User $user)
    {
        abort_unless($user->type === 'sub_agents', 404);
        $allowedPermissions = $this->allowedMatrixPermissionNames();

        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'string',
                Rule::in($allowedPermissions),
            ],
        ]);

        $this->syncMatrixPermissions($user, $data['permissions'] ?? []);
        app(\App\Services\UserLogService::class)->updated($user, 'Updated a Sub-Agent ' . $user->name);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
        ]);
    }
}
