<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SanctumLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        // If already authenticated, continue
        if (Auth::check()) {
            Log::debug('AuthenticateApi: Already authenticated', [
                'user_id' => auth()->id(),
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        // Try to get token from multiple sources
        $token = null;
        $source = null;

        // 1. Try Authorization header (Bearer token) - PREFERRED
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            $source = 'bearer';
            Log::debug('AuthenticateApi: Found Bearer token', ['token_start' => substr($token, 0, 20)]);
        }
        // 2. Try X-Auth-Token header
        elseif ($request->header('X-Auth-Token')) {
            $token = $request->header('X-Auth-Token');
            $source = 'header';
            Log::debug('AuthenticateApi: Found X-Auth-Token header', ['token_start' => substr($token, 0, 20)]);
        }
        // 3. Try cookie (fallback only)
        elseif ($request->cookie('crm_auth_token')) {
            $token = $request->cookie('crm_auth_token');
            $source = 'cookie';
            Log::debug('AuthenticateApi: Found cookie token', ['token_start' => substr($token, 0, 20)]);
        }

        // If we have a token, try to authenticate
        if ($token) {
            try {
                $accessToken = PersonalAccessToken::findToken($token);
                
                if (!$accessToken) {
                    Log::warning('AuthenticateApi: Token not found in database', [
                        'source' => $source, 
                        'token_start' => substr($token, 0, 20),
                        'path' => $request->path(),
                    ]);
                    // Clear invalid cookie
                    if ($source === 'cookie') {
                        Cookie::queue(Cookie::forget('crm_auth_token'));
                    }
                    return $this->unauthenticated($request, 'token_not_found');
                }

                // Check if token is expired
                if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                    Log::warning('AuthenticateApi: Token expired', [
                        'token_id' => $accessToken->id,
                        'expired_at' => $accessToken->expires_at,
                        'path' => $request->path(),
                    ]);
                    // Delete expired token
                    $accessToken->delete();
                    if ($source === 'cookie') {
                        Cookie::queue(Cookie::forget('crm_auth_token'));
                    }
                    return $this->unauthenticated($request, 'token_expired');
                }

                $user = $accessToken->tokenable;

                if ($user instanceof User) {
                    // Verify user is active
                    $activeUser = SanctumLogin::findActiveUserByEmail($user->email);
                    
                    if ($activeUser && $activeUser->is($user)) {
                        // Set the user with the access token
                        $resolvedUser = $user->withAccessToken($accessToken);
                        Auth::setUser($resolvedUser);
                        $request->setUserResolver(static fn () => $resolvedUser);
                        Log::info('AuthenticateApi: User authenticated successfully', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'source' => $source,
                            'path' => $request->path(),
                        ]);
                        return $next($request);
                    } else {
                        Log::warning('AuthenticateApi: User not active or not found', [
                            'email' => $user->email,
                            'path' => $request->path()
                        ]);
                        if ($source === 'cookie') {
                            Cookie::queue(Cookie::forget('crm_auth_token'));
                        }
                        return $this->unauthenticated($request, 'user_not_active');
                    }
                } elseif ($user instanceof \App\Models\Customer) {
                    $resolvedCustomer = $user->withAccessToken($accessToken);
                    Auth::guard('customer')->setUser($resolvedCustomer);
                    $request->setUserResolver(static fn () => $resolvedCustomer);
                    Log::info('AuthenticateApi: Customer authenticated successfully', [
                        'customer_id' => $user->id,
                        'customer_name' => $user->name,
                        'source' => $source,
                        'path' => $request->path(),
                    ]);
                    return $next($request);
                }
            } catch (\Exception $e) {
                Log::error('AuthenticateApi: Token validation error', [
                    'error' => $e->getMessage(),
                    'path' => $request->path()
                ]);
                return $this->unauthenticated($request, 'validation_error');
            }
        } else {
            Log::debug('AuthenticateApi: No token found', [
                'path' => $request->path(),
                'has_bearer' => $request->bearerToken() ? 'yes' : 'no',
                'has_cookie' => $request->cookie('crm_auth_token') ? 'yes' : 'no',
                'headers' => array_keys($request->headers->all()),
            ]);
        }

        return $this->unauthenticated($request, 'no_token');
    }

    private function unauthenticated(Request $request, string $reason): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated', 'reason' => $reason], 401);
        }
        return redirect()->route('login');
    }
}
