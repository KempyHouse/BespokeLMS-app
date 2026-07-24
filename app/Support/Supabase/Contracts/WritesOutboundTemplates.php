<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes the platform-owner outbound communication templates through the
 * service-role key. Reached only from the owner-gated platform routes.
 */
interface WritesOutboundTemplates
{
    /**
     * Patch a single template row. Only the whitelisted keys in $attrs are sent
     * (name, subject, body_html, variables, is_active, updated_at).
     *
     * @param  array<string,mixed>  $attrs
     *
     * @throws SupabaseAuthException
     */
    public function update(string $id, array $attrs): void;
}
