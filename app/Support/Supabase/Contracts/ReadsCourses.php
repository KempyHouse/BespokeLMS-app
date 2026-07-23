<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the ecosystem-wide course catalogue for the platform-owner console.
 *
 * Like {@see ReadsOrganizations}, the catalogue spans every tenant and is only
 * reached on the owner-gated `/platform` routes, so the concrete implementation
 * reads with the server-side service-role key; the authorisation boundary is
 * the `platform.owner` route middleware. Token-scoped reads elsewhere remain
 * governed by Supabase RLS (can_see_course / can_manage_course).
 */
interface ReadsCourses
{
    /**
     * Return every course, each enriched with its category name, visibility
     * scope, and current published version (semver + status). The enrichment
     * joins are best-effort so a not-yet-applied migration degrades a column
     * rather than blanking the whole catalogue.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws SupabaseAuthException
     */
    public function all(): array;

    /**
     * Return one course by id, enriched with its category, visibility scope +
     * entitlement count, versions, language variants, current-version workflow
     * state, and content-review date. Null if the id is unknown. Enrichment
     * joins are best-effort (degrade before migrations 003–006 are applied).
     *
     * @return array<string,mixed>|null
     *
     * @throws SupabaseAuthException
     */
    public function find(string $courseId): ?array;

    /**
     * All course categories as id => name (for the editor's category picker).
     *
     * @return array<string,string>
     *
     * @throws SupabaseAuthException
     */
    public function categories(): array;
}
