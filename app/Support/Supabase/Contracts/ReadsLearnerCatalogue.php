<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the learner-facing course catalogue for one signed-in user.
 *
 * This is the counterpart to {@see ReadsCourses} (the platform-owner console):
 * where that reader lists every course for the owner, this one assembles the
 * catalogue a single learner sees on their "My" workspace — the browsable
 * courses plus that learner's own enrolments, mandatory requirements and
 * certificates.
 *
 * The concrete implementation reads with the server-side service-role key, so
 * personal rows (enrolments, certificates) MUST be scoped to the given
 * profile id in the query itself — never returned unscoped. The course list is
 * the global catalogue, which migration 001 RLS already makes readable to every
 * authenticated user; once course visibility (migration 004, can_see_course) is
 * applied, the course read should be tightened to the learner's entitlements.
 */
interface ReadsLearnerCatalogue
{
    /**
     * Assemble the catalogue payload for one learner.
     *
     * @param  string|null  $profileId       The learner's profiles.id (personal rows are scoped to it).
     * @param  string|null  $organizationId  The learner's organisation (for org-scoped requirements).
     * @param  string|null  $role            The learner's role (for role-scoped requirements).
     * @return array{
     *     courses:array<int,array<string,mixed>>,
     *     categories:array<string,string>,
     *     enrollments:array<int,array<string,mixed>>,
     *     requirements:array<int,array<string,mixed>>,
     *     certificates:array<int,array<string,mixed>>
     * }
     *
     * @throws SupabaseAuthException
     */
    public function forLearner(?string $profileId, ?string $organizationId = null, ?string $role = null): array;
}
