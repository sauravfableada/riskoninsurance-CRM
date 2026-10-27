<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMatrixPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        if (!$user->hasMatrixPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
