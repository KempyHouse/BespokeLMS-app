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
        } catch (SupabaseAuthException) {
            // Deliberately swallowed: the response must never reveal whether an
            // address is registered (no account enumeration).
        }

        return back()->with('status', 'If that email address is registered, a password reset link is on its way.');
    }
}
