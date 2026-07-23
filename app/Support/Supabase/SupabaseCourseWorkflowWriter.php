<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCourseWorkflow;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Writes editorial workflow records through PostgREST with the server-side
 * service-role key. Only invoked behind the `platform.owner` middleware;
 * Supabase RLS (`can_manage_course`) is the defence-in-depth layer.
 */
final class SupabaseCourseWorkflowWriter implements WritesCourseWorkflow
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function transition(
        string $versionId,
        ?string $fromStateId,
        string $toStateId,
        string $action,
        ?string $actorId,
        ?string $comment,
    ): void {
        $this->guardKey();
        $now = now()->toIso8601String();

        // 1) append an immutable history row
        $this->post('/rest/v1/course_workflow_history', [
            'course_version_id' => $versionId,
            'from_state_id' => $fromStateId,
            'to_state_id' => $toStateId,
            'action' => $action,
            'actor_id' => $actorId,
            'comment' => $comment,
        ], 'return=minimal');

        // 2) upsert the current-state row (PK = course_version_id)
        $this->post('/rest/v1/course_workflow_state', [
            'course_version_id' => $versionId,
            'state_id' => $toStateId,
            'entered_at' => $now,
            'entered_by' => $actorId,
        ], 'resolution=merge-duplicates,return=minimal');
    }

    public function recordApproval(string $versionId, ?string $actorId, string $decision, ?string $comment): void
    {
        $this->guardKey();

        $this->post('/rest/v1/course_approvals', [
            'course_version_id' => $versionId,
            'actor_id' => $actorId,
            'decision' => $decision,
            'comment' => $comment,
        ], 'return=minimal');
    }

    private function guardKey(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the transition cannot be saved.',
            );
        }
    }

    /**
     * @param  array<string,mixed>  $body
     */
    private function post(string $path, array $body, string $prefer): void
    {
        try {
            $response = $this->request($prefer)->post($path, $body);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase workflow write failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    private function request(string $prefer): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['apikey' => $this->serviceRoleKey, 'Prefer' => $prefer])
            ->withToken($this->serviceRoleKey);
    }
}
