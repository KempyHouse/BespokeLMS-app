<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

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
     * The organisation columns needed by the console, plus an embedded count of
     * the profiles attached to each organisation (per-tenant user totals).
     */
    private const SELECT = 'id,parent_id,type,operator_subtype,has_client_layer,subtype,name,slug,location,created_at,profiles(count)';

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

        try {
            $response = $this->http
                ->baseUrl($this->url)
                ->timeout($this->timeout)
                ->acceptJson()
                ->withHeaders(['apikey' => $this->serviceRoleKey])
                ->withToken($this->serviceRoleKey)
                ->get('/rest/v1/organizations', [
                    'select' => self::SELECT,
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
}
