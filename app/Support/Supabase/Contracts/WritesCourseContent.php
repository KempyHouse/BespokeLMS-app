<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes course authoring content — draft versions and their module / lesson /
 * slide outline (migration 003: `course_versions`, `modules`, `lessons`,
 * `slides`). Generic create/update/delete over a fixed allow-list of tables and
 * columns so the content builder can manage the whole tree through one contract.
 *
 * Owner-gated route; service-role write with RLS (`can_manage_course`, resolved
 * through the version/module/lesson chain) as defence-in-depth. Edits only ever
 * touch a DRAFT version, so published content learners are sitting on is never
 * mutated mid-flight.
 */
interface WritesCourseContent
{
    /**
     * Insert a row and return its new id.
     *
     * @param  array<string,mixed>  $fields
     *
     * @throws SupabaseAuthException
     */
    public function createRow(string $table, array $fields): string;

    /**
     * @param  array<string,mixed>  $fields
     *
     * @throws SupabaseAuthException
     */
    public function updateRow(string $table, string $id, array $fields): void;

    /**
     * @throws SupabaseAuthException
     */
    public function deleteRow(string $table, string $id): void;
}
