<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Records editorial workflow transitions for a course version (migration 005:
 * `course_workflow_state`, `course_workflow_history`, `course_approvals`).
 *
 * The transition guards (allowed action from the current state, and the
 * separation-of-duties `requires_distinct_actor` rule) are enforced in the
 * controller; this writer is the record and the state upsert. Owner-gated
 * route; service-role write with RLS as defence-in-depth.
 */
interface WritesCourseWorkflow
{
    /**
     * Move a version into a new state: append a history row and upsert the
     * current-state row.
     *
     * @throws SupabaseAuthException
     */
    public function transition(
        string $versionId,
        ?string $fromStateId,
        string $toStateId,
        string $action,
        ?string $actorId,
        ?string $comment,
    ): void;

    /**
     * Record an immutable approval decision (approved / changes_requested /
     * rejected) against the version.
     *
     * @throws SupabaseAuthException
     */
    public function recordApproval(string $versionId, ?string $actorId, string $decision, ?string $comment): void;
}
