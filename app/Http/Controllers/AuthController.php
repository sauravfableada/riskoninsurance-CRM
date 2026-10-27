<?php

namespace App\Http\Controllers;

use App\Support\SanctumLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Validate input
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        $loginInput = trim($request->string('email')->toString());
        $password = $request->string('password')->toString();
        $digitsOnly = preg_replace('/[^0-9]/', '', $loginInput);

        // Check if customer account (by email, phone or whatsapp)
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
            // Default initial password support
            if (empty($customer->password)) {
                $defaultPassword = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : 'welcome123';
                if ($password === $defaultPassword || $password === 'welcome123') {
                    $customer->password = Hash::make($password);
                    $customer->save();
                }
            }

            if (Hash::check($password, $customer->password)) {
                Auth::guard('customer')->login($customer, $request->boolean('remember'));
                $request->session()->regenerate();

                if ($request->expectsJson()) {
                    $token = $customer->createToken('customer-session')->plainTextToken;
                    return response()->json([
                        'success' => true,
                        'redirect_url' => '/customer/dashboard',
                        'token' => $token,
                        'user' => [
                            'id' => $customer->id,
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'type' => 'customer',
                        ],
                    ]);
                }

                return redirect()->route('customer.portal.dashboard');
            }
        }

        // Find active user (by email or phone)
        $user = \App\Models\User::where('is_active', true)
            ->where(function ($query) use ($loginInput, $digitsOnly) {
                $query->where('email', $loginInput);
                if (!empty($digitsOnly)) {
                    $query->orWhere('phone', $loginInput)
                          ->orWhere('phone', 'like', '%' . $digitsOnly);
                }
            })->first();

        // Check credentials
        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('Login failed: Invalid credentials', ['login' => $loginInput]);
            
            // If API request, return JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            return back()->withErrors(['email' => 'Invalid credentials']);
        }

        // Authenticate user (session-based)
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        // The dashboard consumes this marker once. A new successful login sets it
        // again, so dismissing the renewal reminder never hides it on later logins.
        $request->session()->put('show_renewal_policies_modal', true);

        // Generate API token for this session
        $deviceName = SanctumLogin::defaultDeviceName($request, 'web-session');
        
        try {
            $token = SanctumLogin::issueToken($user, $deviceName);
            
            Log::info('Token generated successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'token_prefix' => substr($token, 0, 20),
            ]);
        } catch (\Exception $e) {
            Log::error('Token generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            // Continue without token - session auth still works
            $token = null;
        }

        // Set token in cookie (30 days) if token was generated
        $cookie = null;
        if ($token) {
            $date = new \DateTime();
            $date->modify('+30 days');
            $cookie = cookie('crm_auth_token', $token, $date->getTimestamp() / 60, '/', null, false, true);
        }

        Log::info('User logged in', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_roles' => $user->roles()->pluck('name')->toArray(),
            'token_generated' => $token !== null,
        ]);

        // If API request, return JSON with token
        if ($request->expectsJson()) {
            $response = response()->json([
                'success' => true,
                'token' => $token,
                'redirect_url' => route('dashboard'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'type' => $user->type ?? 'staff',
                    'is_active' => (bool) ($user->is_active ?? true),
                    'roles' => $user->roles()->pluck('name')->toArray(),
                ],
            ], 200);
            
            if ($cookie) {
                $response->cookie($cookie);
            }
            
            return $response;
        }

        $redirectUrl = session()->get('url.intended', $targetUrl);
        if (!$user->isAdmin() && (str_contains($redirectUrl, '/users') || str_contains($redirectUrl, '/roles'))) {
            $redirectUrl = $targetUrl;
        }

        $redirect = redirect()->to($redirectUrl);
        
        if ($cookie) {
            $redirect->cookie($cookie);
        }
        
        return $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->cookie('crm_auth_token');

        if ($token) {
            try {
                PersonalAccessToken::findToken($token)?->delete();
            } catch (\Exception $e) {
                Log::error('API logout failed', ['error' => $e->getMessage()]);
            }
        }

        // Also delete any tokens for current user's session
        if (Auth::check()) {
            try {
                Auth::user()->tokens()->delete();
            } catch (\Exception $e) {
                Log::error('User tokens deletion failed', ['error' => $e->getMessage()]);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear the auth token cookie
        $response = redirect()->route('login');
        $response->cookie(cookie()->forget('crm_auth_token'));
        
        return $response;
    }
}
