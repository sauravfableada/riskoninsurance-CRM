<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SanctumLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ResolveAuthToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            $plainTextToken = $request->bearerToken();

            if ($plainTextToken) {
                $accessToken = PersonalAccessToken::findToken($plainTextToken);
                $user = $accessToken?->tokenable;

                if ($accessToken && $user instanceof User && SanctumLogin::findActiveUserByEmail($user->email)?->is($user)) {
                    $resolvedUser = $user->withAccessToken($accessToken);

                    Auth::setUser($resolvedUser);
                    $request->setUserResolver(static fn () => $resolvedUser);
                    
                    \Illuminate\Support\Facades\Log::debug('User authenticated from Bearer token', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'is_admin' => $user->isAdmin(),
                        'path' => $request->path()
                    ]);
                }
            }
        }

        return $next($request);
    }
}
