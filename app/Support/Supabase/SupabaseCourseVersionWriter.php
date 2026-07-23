<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCourseVersion;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Publishes a course version through PostgREST with the server-side
 * service-role key. Only invoked behind the `platform.owner` middleware;
 * Supabase RLS (`can_manage_course`) is the defence-in-depth layer.
 *
 * publish() runs three ordered writes (archive the current published version,
 * flip the draft to published, repoint the course). PostgREST has no
 * multi-statement transaction here, so a mid-sequence failure surfaces an error
 * and the owner can re-publish — the operation is idempotent.
 */
final class SupabaseCourseVersionWriter implements WritesCourseVersion
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function publish(string $courseId, string $versionId): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the version cannot be published.',
            );
        }

        $now = now()->toIso8601String();

        // 1) Archive any version of this course that is currently published
        //    (but not the one we are about to publish).
        $this->patch(
            '/rest/v1/course_versions?course_id=eq.'.$courseId
                .'&status=eq.published&id=neq.'.$versionId,
            ['status' => 'archived'],
        );

        // 2) Promote the draft to published.
        $this->patch(
            '/rest/v1/course_versions?id=eq.'.$versionId,
            ['status' => 'published', 'published_at' => $now],
        );

        // 3) Repoint the course at its new published version.
        $this->patch(
            '/rest/v1/courses?id=eq.'.$courseId,
            ['current_published_version_id' => $versionId],
        );
    }

    /**
     * @param  array<string,mixed>  $body
     */
    private function patch(string $path, array $body): void
    {
        try {
            $response = $this->request()->patch($path, $body);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase version publish step failed (HTTP {$response->status()}).",
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
