<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves the rebuilt manager "Team" workspace shell.
 *
 * A blank Blade scaffold — the layout header and the left-rail workspace
 * switcher only — that the frozen prototype's "Team" content is being migrated
 * into. Reachable by any authenticated user; tenant-scoped data access stays
 * enforced by Supabase RLS, so the shell never discloses data on its own.
 */
final class TeamWorkspaceController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return view('team.home', ['user' => $user]);
    }
}
