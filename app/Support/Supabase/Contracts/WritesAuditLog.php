<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

/**
 * Appends entries to the platform audit trail (`audit_log`).
 *
 * Writes run with the server-side service-role key (the table has no INSERT
 * policy for the anon/authenticated key — it is a read-only surface for admins
 * and append-only for the server). Logging is best-effort: a failure to record
 * an entry must never break the user action it describes.
 */
interface WritesAuditLog
{
    /**
     * Record one audit entry. Never throws — logging failures are reported and
     * swallowed so the originating action still succeeds.
     *
     * @param  array<string,mixed>  $meta
     */
    public function record(
        string $action,
        ?string $actorId = null,
        ?string $organizationId = null,
        ?string $entity = null,
        ?string $entityId = null,
        array $meta = [],
    ): void;
}
