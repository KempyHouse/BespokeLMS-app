<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Step-up ("sudo mode") re-authentication for sensitive platform-owner writes.
 *
 * The platform-owner area is already owner-only (see {@see EnsurePlatformOwner}),
 * but the genuinely sensitive actions — adding or rotating a provider secret,
 * switching the active AI/email provider, changing a tenant's branding — also
 * require the owner to have re-entered their password recently. This guards an
 * unattended or hijacked owner session from being used to change what leaves the
 * platform (spend, outbound email) without a fresh proof of identity.
 *
 * A successful confirmation (see {@see \App\Http\Controllers\Auth\ConfirmPasswordController})
 * stamps the session; this middleware lets the request through while that stamp
 * is within the TTL, and otherwise bounces to the confirm screen, returning here
 * once the password is re-entered.
 */
final class RequireRecentReauth
{
    /** How long a confirmation stays valid, in seconds. */
    private const TTL = 900; // 15 minutes

    /** Session key holding the unix timestamp of the last confirmation. */
    public const SESSION_KEY = 'platform.sudo_at';

    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get(self::SESSION_KEY, 0);

        if ($confirmedAt > 0 && (time() - $confirmedAt) < self::TTL) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            // 423 Locked — the client must re-authenticate before retrying.
            abort(423, 'Re-authentication required.');
        }

        // Send the owner to the confirm screen and return to the page they were
        // on (the form) once they have re-entered their password.
        $request->session()->put('url.intended', url()->previous());

        return redirect()->route('platform.confirm');
    }
}
