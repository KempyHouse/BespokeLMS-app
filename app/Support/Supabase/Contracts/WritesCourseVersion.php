<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Version lifecycle transitions for a course (migrations 001/003:
 * `course_versions.status`, `course_versions.published_at`,
 * `courses.current_published_version_id`).
 *
 * Publishing a draft archives whichever version is currently published and
 * repoints the course to the new one. Learners already enrolled stay pinned to
 * the version they started on (enrolments carry `course_version_id`), so a
 * publish never disturbs an in-flight learner.
 *
 * Owner-gated route; service-role write with RLS as defence-in-depth.
 */
interface WritesCourseVersion
{
    /**
     * Promote a draft version to the course's published version.
     *
     * @throws SupabaseAuthException
     */
    public function publish(string $courseId, string $versionId): void;
}
