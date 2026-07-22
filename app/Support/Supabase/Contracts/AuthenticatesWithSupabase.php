<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\InvalidCredentialsException;
use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Thin contract over the Supabase Auth (GoTrue) REST API.
 *
 * Supabase Auth is the single identity provider for BespokeLMS (web + Flutter).
 * The web app verifies credentials here, then establishes its own server-side
 * Laravel session — Supabase never stores a duplicate password anywhere else.
 */
interface AuthenticatesWithSupabase
{
    /**
     * Exchange an email + password for a Supabase session.
     *
     * @return array<string,mixed> The decoded GoTrue token payload
     *                             (access_token, refresh_token, expires_at, user, ...).
     *
     * @throws InvalidCredentialsException When the email/password pair is rejected.
     * @throws SupabaseAuthException       When the auth service cannot be reached or errors.
     */
    public function signInWithPassword(string $email, string $password): array;

    /**
     * Fetch the authenticated Supabase user for a given access token.
     *
     * @return array<string,mixed>
     *
     * @throws SupabaseAuthException
     */
    public function getUser(string $accessToken): array;

    /**
     * Trigger a Supabase password-recovery (reset / magic-link) email.
     *
     * Always resolves without revealing whether the address exists
     * (GoTrue does not disclose account existence).
     *
     * @throws SupabaseAuthException
     */
    public function sendPasswordResetEmail(string $email, ?string $redirectTo = null): void;

    /**
     * Best-effort revocation of a Supabase session. Never throws.
     */
    public function signOut(string $accessToken): void;
}
