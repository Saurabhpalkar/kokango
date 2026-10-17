<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor panel routes (after auth:sanctum): the signed-in user must own a vendor store.
 * The store is put on the request as the "vendor" attribute for the controllers.
 */
class EnsureVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $vendor = $user ? Vendor::where('user_id', $user->id)->first() : null;

        if (! $vendor) {
            return response()->json(['message' => 'Please sign in with your seller account.'], 403);
        }

        $request->attributes->set('vendor', $vendor);

        return $next($request);
    }
}
