<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes the platform-owner email transport integrations through the
 * service-role key. Reached only from the owner-gated platform routes.
 */
interface WritesEmailIntegrations
{
    /**
     * Patch a single transport row. Only the whitelisted keys in $attrs are sent
     * (is_enabled, status, from_address, from_name, reply_to, base_url, options,
     * api_key_cipher, last_tested_at, updated_at). The caller is responsible for
     * encrypting any secret before it reaches this method.
     *
     * @param  array<string,mixed>  $attrs
     *
     * @throws SupabaseAuthException
     */
    public function update(string $id, array $attrs): void;
}
