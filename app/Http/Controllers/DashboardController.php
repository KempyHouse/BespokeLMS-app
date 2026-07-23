<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Entry point behind the auth gate ("/", route name "dashboard").
 *
 * The frozen prototype dashboard has been retired from the live flow. This route
 * now sends the signed-in user straight to their workspace home instead of
 * rendering the prototype, so the logo, the profile menu, the profile page and
 * the post-login landing all resolve to a real page:
 *
 *   - the platform owner lands on the owner-only Platform console;
 *   - everyone else lands on their My workspace, from where the workspace
 *     switcher moves them between My / Team / Platform.
 *
 * The route name is kept as "dashboard" so existing route('dashboard') links keep
 * resolving. Data access is independently enforced by Supabase RLS.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return redirect()->route($user->isPlatformOwner() ? 'platform.home' : 'my.home');
    }
}
