<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCourseAvailability;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Writes a course's territory availability and author credits through
 * PostgREST with the server-side service-role key. Each collection is edited
 * as a set: the course's existing rows are deleted, then the submitted set is
 * inserted. Only invoked behind the `platform.owner` middleware; Supabase RLS
 * (`can_manage_course`) is the defence-in-depth layer.
 *
 * The delete-then-insert is not wrapped in a transaction (PostgREST has no
 * multi-statement transaction here); on the rare insert failure after a
 * successful delete the caller surfaces an error and the owner can re-save.
 */
final class SupabaseCourseAvailabilityWriter implements WritesCourseAvailability
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function replaceTerritories(string $courseId, array $territoryIds): void
    {
        $this->guardKey();

        $this->deleteFor('course_territories', $courseId);

        $ids = array_values(array_unique(array_filter($territoryIds, static fn ($v) => $v !== null && $v !== '')));
        if ($ids === []) {
            return;
        }

        $rows = array_map(
            static fn (string $territoryId): array => ['course_id' => $courseId, 'territory_id' => $territoryId],
            $ids,
        );

        $this->insertInto('course_territories', $rows);
    }

    public function replaceAuthors(string $courseId, array $authors): void
    {
        $this->guardKey();

        $this->deleteFor('course_authors', $courseId);

        $rows = [];
        foreach ($authors as $a) {
            $profileId = ($a['profile_id'] ?? null) !== null && $a['profile_id'] !== '' ? (string) $a['profile_id'] : null;
            $displayName = ($a['display_name'] ?? null) !== null && trim((string) $a['display_name']) !== ''
                ? trim((string) $a['display_name'])
                : null;

            // Mirror the DB check constraint: at least one identity is required.
            if ($profileId === null && $displayName === null) {
                continue;
            }

            $credit = ($a['credit_label'] ?? null) !== null && trim((string) $a['credit_label']) !== ''
                ? trim((string) $a['credit_label'])
                : null;

            $rows[] = [
                'course_id' => $courseId,
                'profile_id' => $profileId,
                'display_name' => $displayName,
                'credit_label' => $credit,
                'sort' => (int) ($a['sort'] ?? 0),
            ];
        }

        if ($rows === []) {
            return;
        }

        $this->insertInto('course_authors', $rows);
    }

    private function guardKey(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so availability cannot be saved.',
            );
        }
    }

    private function deleteFor(string $table, string $courseId): void
    {
        try {
            $response = $this->request()->delete("/rest/v1/{$table}?course_id=eq.".$courseId);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase delete on {$table} failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     */
    private function insertInto(string $table, array $rows): void
    {
        try {
            $response = $this->request()->post("/rest/v1/{$table}", $rows);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase insert into {$table} failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['apikey' => $this->serviceRoleKey, 'Prefer' => 'return=minimal'])
            ->withToken($this->serviceRoleKey);
    }
}
