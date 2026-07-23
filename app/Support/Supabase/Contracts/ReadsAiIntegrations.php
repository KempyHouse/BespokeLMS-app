<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the platform-owner AI integrations and their usage.
 *
 * AI is configured once at the platform-owner level (rows with a NULL
 * organization_id) and inherited by every tenant. Reads use the server-side
 * service-role key; the owner-gated routes are the only callers. The encrypted
 * API key (`api_key_cipher`) is never returned to the presentation layer — only
 * a boolean "a key is set" is exposed.
 */
interface ReadsAiIntegrations
{
    /**
     * The owner-level integration rows (organization_id IS NULL), ordered with
     * the enabled/connected providers first. `api_key_cipher` is replaced by a
     * `has_key` boolean so the ciphertext never leaves the server.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws SupabaseAuthException
     */
    public function all(): array;

    /**
     * Month-to-date usage per integration, keyed by integration id.
     *
     * @return array<string,array{calls:int,tokens_in:int,tokens_out:int}>
     *
     * @throws SupabaseAuthException
     */
    public function usageSince(string $sinceIso): array;
}
