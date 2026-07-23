<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads tenant organisations for the platform-owner console.
 *
 * Unlike {@see ReadsProfiles} (which is scoped by the signed-in user's own
 * access token so RLS applies), the platform estate view spans every tenant
 * and is only ever reached on the owner-gated `/platform` routes. The concrete
 * implementation therefore reads with the server-side service-role key; the
 * authorisation boundary is the `platform.owner` route middleware, not RLS.
 */
interface ReadsOrganizations
{
    /**
     * Return every organisation row across the estate, each with an embedded
     * `profiles(count)` so per-tenant user totals can be derived.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws SupabaseAuthException
     */
    public function all(): array;
}
