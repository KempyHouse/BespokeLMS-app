<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads a tenant's email sender identity ("alias").
 *
 * A tenant sends on the platform transport but as its own from-name / address /
 * reply-to / verified domain. There is at most one alias row per organisation.
 * Reads run with the server-side service-role key from the owner-gated console.
 */
interface ReadsTenantEmailAliases
{
    /**
     * The alias row for one organisation, or null if it has none yet.
     *
     * @return array<string,mixed>|null
     *
     * @throws SupabaseAuthException
     */
    public function forOrganization(string $organizationId): ?array;
}
