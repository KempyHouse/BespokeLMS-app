<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\SupabaseUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to the BespokeLMS platform owner (role `bespokelms_owner`).
 *
 * Returns 404 rather than 403 so the existence of the owner-only platform area
 * is not disclosed to tenant users. This is the application-layer counterpart to
 * the database's `is_platform_owner()` predicate: Row Level Security remains the
 * authoritative boundary, and this middleware keeps unauthorised users out of the
 * platform routes entirely.
 */
final class EnsurePlatformOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof SupabaseUser && $user->isPlatformOwner(), 404);

        return $next($request);
    }
}
