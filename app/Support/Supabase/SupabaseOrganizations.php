<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads the full tenant estate through PostgREST using the server-side
 * service-role key.
 *
 * This intentionally bypasses Row Level Security: the platform-owner console
 * needs to see every tenant, and the query is only invoked from routes behind
 * the `platform.owner` middleware (which 404s non-owners). The organisations
 * table holds no secrets — API keys and ciphers live elsewhere — so a
 * read-only estate select is safe to serve here.
 */
final class SupabaseOrganizations implements ReadsOrganizations
{
    /**
     * The organisation columns the console needs. Deliberately avoids a
     * `profiles(count)` aggregate embed: Supabase disables aggregate functions
     * in the data API by default, so per-tenant user totals are derived in PHP
     * from a lightweight second read instead (see {@see userCountsByOrg()}).
     */
    private const ORG_SELECT = 'id,parent_id,type,operator_subtype,has_client_layer,subtype,name,slug,location,created_at';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function all(): array
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the platform tenant list cannot be loaded.',
            );
        }

        $organisations = $this->fetchOrganisations();
        $userCounts = $this->userCountsByOrg();

        foreach ($organisations as $index => $organisation) {
            $id = (string) ($organisation['id'] ?? '');
            $organisations[$index]['user_count'] = $userCounts[$id] ?? 0;
        }

        return $organisations;
    }

    /**
     * Fetch every organisation row. A failure here is fatal to the estate view.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchOrganisations(): array
    {
        try {
            $response = $this->request()->get('/rest/v1/organizations', [
                'select' => self::ORG_SELECT,
                'order' => 'created_at.asc',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase organisations lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    /**
     * Count profiles per organisation. Best-effort: a failure here degrades the
     * counts to zero but never hides the tenant list itself.
     *
     * @return array<string,int>
     */
    private function userCountsByOrg(): array
    {
        try {
            $response = $this->request()->get('/rest/v1/profiles', [
                'select' => 'organization_id',
            ]);

            if (! $response->successful()) {
                return [];
            }

            /** @var array<int,array<string,mixed>> $rows */
            $rows = $response->json() ?? [];
        } catch (ConnectionException) {
            return [];
        }

        $counts = [];

        foreach ($rows as $row) {
            $organizationId = $row['organization_id'] ?? null;

            if (is_string($organizationId) && $organizationId !== '') {
                $counts[$organizationId] = ($counts[$organizationId] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * A PostgREST request pre-bound to the project and the service-role key.
     */
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
