<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads and writes an organisation's brand kit — the per-tenant design-token
 * overrides that reskin its white-label instance.
 *
 * Writes use the server-side service-role key and are only ever reached on the
 * owner-gated platform routes; the route middleware is the authorisation
 * boundary (RLS still protects any direct client access).
 */
interface WritesBrandKits
{
    /**
     * The id of an organisation's published default brand kit, or null if it
     * has none yet (read-only — does not create one).
     *
     * @throws SupabaseAuthException
     */
    public function findPublishedDefaultKitId(string $organizationId): ?string;

    /**
     * The id of an organisation's published default brand kit, creating one if
     * it does not exist.
     *
     * @throws SupabaseAuthException
     */
    public function ensurePublishedDefaultKitId(string $organizationId, string $organizationName): string;

    /**
     * Current override values for a kit, keyed by token_key.
     *
     * @return array<string,string>
     *
     * @throws SupabaseAuthException
     */
    public function overrides(string $brandKitId): array;

    /**
     * Persist overrides: upsert the given token_key => value pairs and delete
     * the given token keys (cleared back to the platform default).
     *
     * @param  array<string,string>  $upserts
     * @param  array<int,string>  $deletes
     *
     * @throws SupabaseAuthException
     */
    public function save(string $brandKitId, array $upserts, array $deletes): void;
}
