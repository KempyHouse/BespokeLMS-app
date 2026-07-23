<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Upserts a course's pricing + retake/retry policy (migration 007,
 * `course_pricing`). Owner-gated route; service-role write with RLS
 * (`can_manage_course`) as defence-in-depth. Per-course values may be null to
 * inherit the platform default for that pricing_type (see the -1 = unlimited
 * convention on the retry columns).
 */
interface WritesCoursePricing
{
    /**
     * @param  array<string,mixed>  $fields
     *
     * @throws SupabaseAuthException
     */
    public function upsertPricing(string $courseId, array $fields): void;
}
