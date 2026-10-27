<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SanctumLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckLocalStorageToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // If already authenticated, continue
        if (Auth::check()) {
            return $next($request);
        }

        // Try to get token from multiple sources
        $token = null;
        $source = null;

        // 1. Try Authorization header (Bearer token)
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            $source = 'bearer';
        }
        // 2. Try X-Auth-Token header
        elseif ($request->header('X-Auth-Token')) {
            $token = $request->header('X-Auth-Token');
            $source = 'header';
        }
        // 3. Try cookie
        elseif ($request->cookie('crm_auth_token')) {
            $token = $request->cookie('crm_auth_token');
            $source = 'cookie';
        }

        // If we have a token, try to authenticate
        if ($token) {
            try {
                $accessToken = PersonalAccessToken::findToken($token);
                
                if (!$accessToken) {
                    Log::debug('Token not found in database', [
                        'source' => $source, 
                        'token_start' => substr($token, 0, 10),
                        'path' => $request->path(),
                        'method' => $request->method()
                    ]);
                    if ($source === 'cookie') {
                        Cookie::queue(Cookie::forget('crm_auth_token'));
                    }
                    return $next($request);
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
                        Log::debug('User authenticated from token', [
                            'user_id' => $user->id, 
                            'source' => $source,
                            'path' => $request->path(),
                            'method' => $request->method()
                        ]);
                    } else {
                        Log::debug('User not active', [
                            'email' => $user->email,
                            'path' => $request->path()
                        ]);
                        if ($source === 'cookie') {
                            Cookie::queue(Cookie::forget('crm_auth_token'));
                        }
                    }
                } elseif ($user instanceof \App\Models\Customer) {
                    $resolvedCustomer = $user->withAccessToken($accessToken);
                    Auth::guard('customer')->setUser($resolvedCustomer);
                    $request->setUserResolver(static fn () => $resolvedCustomer);
                    Log::debug('Customer authenticated from token', [
                        'customer_id' => $user->id,
                        'source' => $source,
                        'path' => $request->path(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('Token validation error', [
                    'error' => $e->getMessage(),
                    'path' => $request->path()
                ]);
                if ($source === 'cookie') {
                    Cookie::queue(Cookie::forget('crm_auth_token'));
                }
            }
        } else {
            Log::debug('No token found', [
                'path' => $request->path(),
                'method' => $request->method(),
                'has_bearer' => $request->bearerToken() ? 'yes' : 'no',
                'has_cookie' => $request->cookie('crm_auth_token') ? 'yes' : 'no'
            ]);
        }

        return $next($request);
    }
}
