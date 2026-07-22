<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * "Forgot password" — asks Supabase Auth to send a recovery / magic-link email.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request, AuthenticatesWithSupabase $supabase): RedirectResponse
    {
        $redirectTo = config('services.supabase.redirect_url') ?: route('password.reset');

        try {
            $supabase->sendPasswordResetEmail((string) $request->validated('email'), $redirectTo);
        } catch (SupabaseAuthException $e) {
            // GoTrue returns HTTP 200 even for unknown addresses, so a failure here
            // is never an account-enumeration signal — it is a genuine transport or
            // service error (the host cannot reach Supabase, or the project URL /
            // anon key is misconfigured). Log it so the failure is diagnosable
            // server-side, while still showing the user the neutral message below.
            report($e);
        }

        return back()->with('status', 'If that email address is registered, a password reset link is on its way.');
    }
}
