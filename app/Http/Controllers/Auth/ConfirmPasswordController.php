<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\SupabaseUser;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireRecentReauth;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Exceptions\InvalidCredentialsException;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The step-up ("sudo mode") password confirmation for the platform-owner area.
 *
 * Verifies the owner's password against Supabase Auth without disturbing the
 * existing session, then stamps the session so {@see RequireRecentReauth} lets
 * sensitive writes through for a short window.
 */
final class ConfirmPasswordController extends Controller
{
    public function create(): View
    {
        return view('platform.confirm');
    }

    public function store(Request $request, AuthenticatesWithSupabase $supabase): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            // Verify the password only; we deliberately discard the returned
            // token payload so the live session is left untouched.
            $supabase->signInWithPassword($user->email, (string) $request->input('password'));
        } catch (InvalidCredentialsException) {
            return back()->withErrors(['password' => 'That password was incorrect.']);
        } catch (SupabaseAuthException $e) {
            report($e);

            return back()->withErrors(['password' => 'Your password could not be verified right now. Please try again shortly.']);
        }

        $request->session()->put(RequireRecentReauth::SESSION_KEY, time());

        return redirect()->intended(route('platform.home'));
    }
}
