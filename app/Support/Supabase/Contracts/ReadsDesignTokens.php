<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the design-system token contract and per-tenant brand-kit overrides.
 *
 * The token contract (`design_tokens`) and brand kits (`brand_kits` /
 * `brand_kit_tokens`) are the single source of truth for styling: the app
 * resolves them into CSS custom properties at request time. Reads use the
 * server-side service-role key so the layout can theme every page without a
 * user token; RLS still protects direct client access.
 */
interface ReadsDesignTokens
{
    /**
     * The full token contract, ordered for display. Alongside the raw contract
     * (key, css_var, type, values, themeable) each row carries the editor
     * metadata used by the brand-kit editor: a human `label`, one-line `helper`
     * text, and the `editor_group` a themeable token belongs to (null when the
     * token is not surfaced to tenants).
     *
     * @return array<int,array{key:string,css_var:string,type:string,default_value:string,dark_value:?string,themeable:bool,category:?string,description:?string,label:?string,helper:?string,editor_group:?string,inherits_from:?string}>
     *
     * @throws SupabaseAuthException
     */
    public function tokens(): array;

    /**
     * The override rows (token_key => value) from an organisation's published
     * default brand kit. Empty when the org has no published default kit.
     *
     * @return array<int,array{token_key:string,value:string}>
     *
     * @throws SupabaseAuthException
     */
    public function overrideRowsForOrg(string $organizationId): array;
}
