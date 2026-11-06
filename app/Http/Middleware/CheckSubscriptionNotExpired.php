<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionStaffLimitService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionNotExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? Auth::user();

        // Customer portal users or guests do not check staff CRM subscription
        if (!$user || !($user instanceof \App\Models\User)) {
            return $next($request);
        }

        // Allow logging out
        if ($request->routeIs('logout') || $request->is('logout')) {
            return $next($request);
        }

        $expired = app(SubscriptionStaffLimitService::class)->isSubscriptionExpired($user);
        if ($expired) {
            // Log out user session
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'subscription_expired',
                    'message' => $expired['message'],
                    'plan_name' => $expired['plan_name'],
                    'end_date' => $expired['end_date'],
                ], 403);
            }

            return redirect()->route('login')->with('subscription_expired_msg', $expired['message']);
        }

        return $next($request);
    }
}
