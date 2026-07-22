<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the tenant-aware application profile that sits alongside a Supabase
 * auth user (role, organisation, display name). RLS ensures a signed-in user
 * can only ever read their own profile with their own access token.
 */
interface ReadsProfiles
{
    /**
     * Return the profile row linked to a Supabase auth user id, or null.
     *
     * @param  string  $accessToken  The signed-in user's own access token (RLS scope).
     * @return array<string,mixed>|null
     *
     * @throws SupabaseAuthException
     */
    public function findByAuthUserId(string $accessToken, string $authUserId): ?array;
}
