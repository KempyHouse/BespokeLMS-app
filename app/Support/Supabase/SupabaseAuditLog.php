<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesAuditLog;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

/**
 * Writes audit entries to Supabase (`audit_log`) via PostgREST using the
 * server-side service-role key. Best-effort: every failure is reported and
 * swallowed so audit logging can never break the action it records.
 */
final class SupabaseAuditLog implements WritesAuditLog
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function record(
        string $action,
        ?string $actorId = null,
        ?string $organizationId = null,
        ?string $entity = null,
        ?string $entityId = null,
        array $meta = [],
    ): void {
        if ($this->serviceRoleKey === '') {
            return;
        }

        $payload = [
            'action' => $action,
            'actor_id' => $actorId,
            'organization_id' => $organizationId,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => $meta === [] ? (object) [] : $meta,
        ];

        try {
            $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->post('/rest/v1/audit_log', $payload);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['apikey' => $this->serviceRoleKey])
            ->withToken($this->serviceRoleKey);
    }
}
