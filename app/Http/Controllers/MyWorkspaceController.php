<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves the rebuilt learner "My" workspace shell.
 *
 * A blank Blade scaffold — the layout header and the left-rail workspace
 * switcher only — that the frozen prototype's "My" content is being migrated
 * into. Reachable by any authenticated user; data access stays enforced by
 * Supabase RLS, so the shell never discloses tenant data on its own.
 */
final class MyWorkspaceController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return view('my.home', ['user' => $user]);
    }
}
