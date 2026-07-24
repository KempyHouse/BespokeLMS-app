<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the platform-owner outbound communication templates.
 *
 * Templates are authored once at the platform-owner level (rows with a NULL
 * organization_id) and inherited by every tenant; tenants may later reword,
 * reset or clone them (their own rows carry their organization_id). Reads use
 * the server-side service-role key; the owner-gated platform routes are the
 * only callers.
 */
interface ReadsOutboundTemplates
{
    /**
     * All platform-default templates (organization_id IS NULL), ordered by
     * category then name.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws SupabaseAuthException
     */
    public function platformAll(): array;

    /**
     * A single platform-default template by channel + key, or null if none.
     *
     * @return array<string,mixed>|null
     *
     * @throws SupabaseAuthException
     */
    public function platformFind(string $channel, string $key): ?array;
}
