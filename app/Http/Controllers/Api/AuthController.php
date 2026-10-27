<?php

namespace App\Http\Controllers\Api;

use App\Support\SanctumLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiBaseController
{
    public function login(Request $request)
    {
        // Accept both JSON and form data
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
            'device_name' => 'nullable|string|max:255',
        ]);

        $loginInput = trim($request->string('email')->toString());
        $password = $request->string('password')->toString();
        $digitsOnly = preg_replace('/[^0-9]/', '', $loginInput);

        // First check if user exists in users table (by email or phone)
        $userCheck = \App\Models\User::where(function ($query) use ($loginInput, $digitsOnly) {
            $query->where('email', $loginInput);
            if (!empty($digitsOnly)) {
                $query->orWhere('phone', $loginInput)
                      ->orWhere('phone', 'like', '%' . $digitsOnly);
            }
        })->first();
        
        if ($userCheck) {
            $user = $userCheck;
            if (!$user->is_active) {
                return $this->error('Account is inactive. Please contact administrator.', 401);
            }

            // Check password
            if (!Hash::check($password, $user->password)) {
                return $this->error('Incorrect password', 401);
            }

            // Generate token with device name
            $deviceName = $request->string('device_name')->toString() 
                ?: SanctumLogin::defaultDeviceName($request, 'web-app');
            
            $token = SanctumLogin::issueToken($user, $deviceName);

            return $this->success([
                'token' => $token,
                'redirect_url' => '/dashboard',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'type' => $user->type ?? 'admin',
                    'is_active' => (bool) ($user->is_active ?? true),
                ],
            ], 'Login successful');
        }

        // Check if customer exists (by email, phone, or whatsapp)
        $customer = \App\Models\Customer::where(function ($query) use ($loginInput, $digitsOnly) {
            $query->where('email', $loginInput);
            if (!empty($digitsOnly)) {
                $query->orWhere('phone', $loginInput)
                      ->orWhere('phone', 'like', '%' . $digitsOnly)
                      ->orWhere('whatsapp', $loginInput)
                      ->orWhere('whatsapp', 'like', '%' . $digitsOnly);
            }
        })->first();

        if ($customer) {
            $inputPassword = $password;

            // Default initial password support if not yet set
            if (empty($customer->password)) {
                $defaultPassword = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : 'welcome123';
                if ($inputPassword === $defaultPassword || $inputPassword === 'welcome123') {
                    $customer->password = Hash::make($inputPassword);
                    $customer->save();
                }
            }

            if (!Hash::check($inputPassword, $customer->password)) {
                return $this->error('Incorrect password', 401);
            }

            $deviceName = $request->string('device_name')->toString() ?: 'customer-web';
            $token = $customer->createToken($deviceName)->plainTextToken;

            // Log customer into customer session guard as well
            \Illuminate\Support\Facades\Auth::guard('customer')->login($customer);

            return $this->success([
                'token' => $token,
                'redirect_url' => '/customer/dashboard',
                'user' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'type' => 'customer',
                    'is_active' => true,
                ],
            ], 'Login successful');
        }

        return $this->error('No account found with this email', 401);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request)
    {
        return $this->success($request->user());
    }

    /**
     * Get current user permissions and roles
     */
    public function permissions(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $isAdmin = $user->isAdmin();

        // Keep this response in sync with the same matrix used by the staff
        // and sub-agent permission forms. A separate hard-coded list caused
        // view_policies to be omitted and the sidebar to hide Policy Center.
        $defaultActions = array_keys(config('crm_permissions.actions', []));
        $matrixPermissions = collect(config('crm_permissions.modules', []))
            ->flatMap(function (array $meta, string $module) use ($defaultActions) {
                $actions = $meta['actions'] ?? $defaultActions;

                return collect($actions)->map(
                    fn (string $action) => $action . '_' . $module
                );
            })
            ->unique()
            ->values()
            ->all();

        $permissions = [];
        foreach ($matrixPermissions as $permission) {
            $permissions[$permission] = $isAdmin || $user->hasMatrixPermission($permission);
        }

        return $this->success([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $isAdmin,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $permissions,
            'can_create' => [
                'customers' => $permissions['create_customers'] ?? false,
                'leads' => $permissions['create_leads'] ?? false,
                'deals' => $permissions['create_deals'] ?? false,
                'tasks' => $permissions['create_tasks'] ?? false,
                'bookings' => $permissions['create_bookings'] ?? false,
                'invoices' => $permissions['create_invoices'] ?? false,
                'projects' => $permissions['create_projects'] ?? false,
                'meetings' => $permissions['create_meetings'] ?? false,
            ],
            'can_edit' => [
                'customers' => $permissions['edit_customers'] ?? false,
                'leads' => $permissions['edit_leads'] ?? false,
                'deals' => $permissions['edit_deals'] ?? false,
                'tasks' => $permissions['edit_tasks'] ?? false,
            ],
            'can_delete' => [
                'customers' => $permissions['delete_customers'] ?? false,
                'leads' => $permissions['delete_leads'] ?? false,
                'deals' => $permissions['delete_deals'] ?? false,
                'tasks' => $permissions['delete_tasks'] ?? false,
            ],
        ], 'User permissions retrieved successfully');
    }
}
