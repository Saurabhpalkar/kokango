<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated account must stop working immediately, even with a still-valid token.
 * Runs on every API request (also on routes with optional auth); answers 401 so the SPA drops the token.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $user = $request->user('sanctum');

            if ($user && ! $user->is_active) {
                $user->tokens()->delete();

                return response()->json(['message' => 'This account has been deactivated. Please contact support.'], 401);
            }
        }

        return $next($request);
    }
}
