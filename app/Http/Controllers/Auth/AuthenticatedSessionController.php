<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles the server-side sign-in session for BespokeLMS.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Show the sign-in screen.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Verify credentials against Supabase and start a Laravel session.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Prevent session fixation: rotate the id now that the user is known.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Sign out: revoke the Supabase session (best effort) and destroy the local one.
     */
    public function destroy(Request $request, AuthenticatesWithSupabase $supabase): RedirectResponse
    {
        $tokens = $request->session()->get('supabase.tokens');

        if (is_array($tokens) && ! empty($tokens['access_token'])) {
            $supabase->signOut((string) $tokens['access_token']);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
