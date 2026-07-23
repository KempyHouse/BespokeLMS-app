<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

/**
 * Reads the raw rows the dashboard widgets are computed from.
 *
 * Personal rows (enrolments, certificates, learning attempts) are scoped to a
 * single profile id in the query, so this reader never returns another user's
 * data even though it holds the service-role key. The platform overview is read
 * only for the platform owner (guarded by the platform.owner middleware).
 *
 * Every read degrades to an empty collection on failure, so a widget shows its
 * honest empty state rather than an error if a table is briefly unreachable.
 */
interface ReadsWidgetData
{
    /**
     * Raw personal learning rows for one profile.
     *
     * @return array{
     *     enrollments:array<int,array<string,mixed>>,
     *     certificates:array<int,array<string,mixed>>,
     *     attempts:array<int,array<string,mixed>>,
     *     course_titles:array<string,string>
     * }
     */
    public function personalFor(?string $profileId): array;

    /**
     * Raw platform-wide rows for the owner's platform widgets.
     *
     * @return array{
     *     organizations:array<int,array<string,mixed>>,
     *     profiles:array<int,array<string,mixed>>,
     *     ai_integrations:array<int,array<string,mixed>>,
     *     email_integrations:array<int,array<string,mixed>>
     * }
     */
    public function platformOverview(): array;
}
