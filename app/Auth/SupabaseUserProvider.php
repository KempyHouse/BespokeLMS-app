<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;

/**
 * Bridges Laravel's session guard to Supabase-backed identity.
 *
 * Credential verification happens against Supabase Auth in the login request
 * (see {@see \App\Http\Requests\Auth\LoginRequest}); this provider is only
 * responsible for rehydrating the current user from the server-side session
 * snapshot on subsequent requests. That keeps the app gate (a normal Laravel
 * session) resilient even after the short-lived Supabase access token expires.
 */
final class SupabaseUserProvider implements UserProvider
{
    /**
     * Session key holding the identity snapshot written at login.
     */
    public const SESSION_KEY = 'supabase.user';

    public function __construct(private readonly Session $session)
    {
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $snapshot = $this->session->get(self::SESSION_KEY);

        if (! is_array($snapshot) || ($snapshot['id'] ?? null) !== $identifier) {
            return null;
        }

        return SupabaseUser::fromSession($snapshot);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        // Remember-me tokens are not supported by the Supabase provider.
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // No-op.
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        // Credentials are validated directly against Supabase Auth in the
        // login request, not here, so there is nothing to retrieve.
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // No-op: password hashing is owned entirely by Supabase Auth.
    }
}
