<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The BespokeLMS platform-owner console.
 *
 * Access is gated twice: the "platform.owner" route middleware (404s non-owners)
 * and Supabase RLS on every table the console will read. This first slice renders
 * the owner-only landing; the live tenant list and the "view as tenant" switch are
 * layered on in the database-driven build.
 */
final class PlatformController extends Controller
{
    public function index(Request $request): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return view('platform.home', [
            'user' => $user,
        ]);
    }
}
