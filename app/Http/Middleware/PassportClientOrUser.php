<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PassportClientOrUser
{
    /**
     * Allow requests authenticated either by a user (Bearer user token)
     * or by a client (client_credentials token).
     */
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('api');

        // Try to get user
        try {
            $user = $guard->user();
        } catch (\Throwable) {
            $user = null;
        }

        if ($user) {
            return $next($request);
        }

        // If no user, allow client-only tokens if client exists on the guard
        try {
            if (method_exists($guard, 'client') && $guard->client()) {
                return $next($request);
            }
        } catch (\Throwable) {
            // ignore
        }

        return response()->json(['message' => 'Unauthenticated'], 401);
    }
}
