<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Auth\SupabaseUser;
use App\Auth\SupabaseUserProvider;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Contracts\ReadsProfiles;
use App\Support\Supabase\Exceptions\InvalidCredentialsException;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Verify the credentials against Supabase Auth and establish the session.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $auth = app(AuthenticatesWithSupabase::class);
        $profiles = app(ReadsProfiles::class);

        try {
            $session = $auth->signInWithPassword(
                (string) $this->input('email'),
                (string) $this->input('password'),
            );
        } catch (InvalidCredentialsException) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        } catch (SupabaseAuthException) {
            throw ValidationException::withMessages([
                'email' => 'We could not reach the sign-in service. Please try again in a moment.',
            ]);
        }

        $accessToken = (string) ($session['access_token'] ?? '');
        /** @var array<string,mixed> $authUser */
        $authUser = is_array($session['user'] ?? null) ? $session['user'] : [];
        $authUserId = (string) ($authUser['id'] ?? '');

        $profile = $authUserId !== ''
            ? $profiles->findByAuthUserId($accessToken, $authUserId)
            : null;

        if ($profile === null) {
            throw ValidationException::withMessages([
                'email' => 'Your account is not fully provisioned yet. Please contact your administrator.',
            ]);
        }

        $user = SupabaseUser::fromSupabase($authUser, $profile);

        // Persist the Supabase session + identity snapshot server-side only.
        $this->session()->put('supabase.tokens', [
            'access_token' => $accessToken,
            'refresh_token' => (string) ($session['refresh_token'] ?? ''),
            'expires_at' => (int) ($session['expires_at'] ?? 0),
        ]);
        $this->session()->put(SupabaseUserProvider::SESSION_KEY, $user->toSession());

        Auth::guard('web')->login($user);

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    /**
     * The throttle key for the request (per email + IP).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
