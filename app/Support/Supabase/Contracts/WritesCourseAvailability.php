<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Replaces a course's territory availability (`course_territories`) and its
 * author credits (`course_authors`) from migration 007. Both are edited as a
 * whole set — the writer deletes the course's existing rows and inserts the
 * submitted set. Owner-gated route; service-role write with RLS
 * (`can_manage_course`) as defence-in-depth.
 */
interface WritesCourseAvailability
{
    /**
     * @param  array<int,string>  $territoryIds  territory UUIDs the course is available in
     *
     * @throws SupabaseAuthException
     */
    public function replaceTerritories(string $courseId, array $territoryIds): void;

    /**
     * @param  array<int,array{profile_id:?string,display_name:?string,credit_label:?string,sort:int}>  $authors
     *
     * @throws SupabaseAuthException
     */
    public function replaceAuthors(string $courseId, array $authors): void;
}
