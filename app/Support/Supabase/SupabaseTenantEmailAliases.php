<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsTenantEmailAliases;
use App\Support\Supabase\Contracts\WritesTenantEmailAliases;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads and upserts per-tenant email aliases (`tenant_email_aliases`) through
 * PostgREST using the server-side service-role key. Reached from the owner-gated
 * tenant console; RLS also permits a tenant admin to manage its own row.
 */
final class SupabaseTenantEmailAliases implements ReadsTenantEmailAliases, WritesTenantEmailAliases
{
    /** Columns the upsert() whitelist may set. */
    private const WRITABLE = [
        'from_name', 'from_address', 'reply_to', 'sending_domain',
        'is_active', 'is_verified', 'updated_at',
    ];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function forOrganization(string $organizationId): ?array
    {
        $this->assertConfigured();

        if ($organizationId === '') {
            return null;
        }

        try {
            $response = $this->request()->get('/rest/v1/tenant_email_aliases', [
                'select' => 'id,organization_id,from_name,from_address,reply_to,sending_domain,is_active,is_verified',
                'organization_id' => 'eq.'.$organizationId,
                'limit' => '1',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service for tenant email aliases.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Supabase tenant-alias lookup failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows[0] ?? null;
    }

    public function upsert(string $organizationId, array $attrs): void
    {
        $this->assertConfigured();

        if ($organizationId === '') {
            throw new SupabaseAuthException('An organisation id is required to save an email alias.');
        }

        $payload = ['organization_id' => $organizationId];
        foreach (self::WRITABLE as $key) {
            if (array_key_exists($key, $attrs)) {
                $payload[$key] = $attrs[$key];
            }
        }

        try {
            // Upsert on the unique organization_id, merging into any existing row.
            $response = $this->request()
                ->withHeaders([
                    'Prefer' => 'resolution=merge-duplicates,return=minimal',
                ])
                ->post('/rest/v1/tenant_email_aliases?on_conflict=organization_id', $payload);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to save the email alias.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Saving the email alias failed (HTTP {$response->status()}).", $response->status());
        }
    }

    private function assertConfigured(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so tenant email aliases cannot be read or written.');
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
