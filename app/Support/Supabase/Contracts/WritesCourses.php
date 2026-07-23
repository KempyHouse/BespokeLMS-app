<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes course-level catalogue / commercial fields for the course editor.
 *
 * Reached only on the owner-gated `/platform` routes; the concrete implementation
 * writes with the service-role key (the authorisation boundary is the
 * `platform.owner` middleware, with Supabase RLS `can_manage_course` as
 * defence-in-depth). Version content/copy editing is a separate concern (it
 * goes through the draft-version workflow) and is not part of this contract.
 */
interface WritesCourses
{
    /**
     * Patch the given columns onto one `courses` row.
     *
     * @param  array<string,mixed>  $fields
     *
     * @throws SupabaseAuthException
     */
    public function updateCourse(string $courseId, array $fields): void;
}
