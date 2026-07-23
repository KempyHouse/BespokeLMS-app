<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the platform-owner email transport integrations.
 *
 * Email transport is configured once at the platform-owner level (rows with a
 * NULL organization_id — Resend / Postmark / SES / SMTP / custom) and inherited
 * by every tenant. Reads use the server-side service-role key; the owner-gated
 * routes are the only callers. The encrypted secret (`api_key_cipher`) is never
 * returned to the presentation layer — only a boolean "a key is set" is exposed.
 */
interface ReadsEmailIntegrations
{
    /**
     * The owner-level transport rows (organization_id IS NULL), ordered with the
     * enabled/connected providers first. `api_key_cipher` is replaced by a
     * `has_key` boolean so the ciphertext never leaves the server.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws SupabaseAuthException
     */
    public function all(): array;
}
