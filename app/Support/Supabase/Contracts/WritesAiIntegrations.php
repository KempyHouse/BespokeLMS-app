<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes the platform-owner AI integrations through the service-role key.
 * Reached only from the owner-gated platform routes.
 */
interface WritesAiIntegrations
{
    /**
     * Patch a single integration row. Only the whitelisted, non-null keys in
     * $attrs are sent (is_enabled, status, default_model, base_url, options,
     * api_key_cipher, last_tested_at). The caller is responsible for encrypting
     * any API key before it reaches this method.
     *
     * @param  array<string,mixed>  $attrs
     *
     * @throws SupabaseAuthException
     */
    public function update(string $id, array $attrs): void;
}
