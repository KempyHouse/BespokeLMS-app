<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Creates or updates a tenant's email sender identity ("alias").
 *
 * One row per organisation (unique on organization_id); the write is an upsert.
 * Reached only from the owner-gated platform routes with the service-role key.
 */
interface WritesTenantEmailAliases
{
    /**
     * Upsert the alias for an organisation. Only the whitelisted keys in $attrs
     * are sent (from_name, from_address, reply_to, sending_domain, is_active,
     * is_verified, updated_at).
     *
     * @param  array<string,mixed>  $attrs
     *
     * @throws SupabaseAuthException
     */
    public function upsert(string $organizationId, array $attrs): void;
}
