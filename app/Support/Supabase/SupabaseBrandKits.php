<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesBrandKits;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads and writes brand kits through PostgREST using the server-side
 * service-role key. Reached only from the owner-gated platform routes.
 */
final class SupabaseBrandKits implements WritesBrandKits
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function findPublishedDefaultKitId(string $organizationId): ?string
    {
        $this->assertConfigured();

        $rows = $this->get('/rest/v1/brand_kits', [
            'select' => 'id',
            'organization_id' => 'eq.'.$organizationId,
            'is_default' => 'is.true',
            'status' => 'eq.published',
            'limit' => '1',
        ]);

        $id = $rows[0]['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function ensurePublishedDefaultKitId(string $organizationId, string $organizationName): string
    {
        $existing = $this->findPublishedDefaultKitId($organizationId);
        if ($existing !== null) {
            return $existing;
        }

        try {
            $response = $this->request()
                ->withHeaders(['Prefer' => 'return=representation'])
                ->post('/rest/v1/brand_kits', [
                    'organization_id' => $organizationId,
                    'name' => trim($organizationName).' Brand Kit',
                    'status' => 'published',
                    'is_default' => true,
                ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to create the brand kit.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Creating the brand kit failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];
        $id = $rows[0]['id'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new SupabaseAuthException('Supabase did not return the new brand kit id.');
        }

        return $id;
    }

    public function overrides(string $brandKitId): array
    {
        $this->assertConfigured();

        $rows = $this->get('/rest/v1/brand_kit_tokens', [
            'select' => 'token_key,value',
            'brand_kit_id' => 'eq.'.$brandKitId,
        ]);

        $out = [];
        foreach ($rows as $row) {
            $key = $row['token_key'] ?? null;
            $value = $row['value'] ?? null;
            if (is_string($key) && $key !== '' && is_string($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public function save(string $brandKitId, array $upserts, array $deletes): void
    {
        $this->assertConfigured();

        // Delete cleared tokens first (single request via the in.() filter).
        $deletes = array_values(array_filter($deletes, static fn ($k): bool => is_string($k) && $k !== ''));
        if ($deletes !== []) {
            try {
                $response = $this->request()->delete('/rest/v1/brand_kit_tokens', [
                    'brand_kit_id' => 'eq.'.$brandKitId,
                    'token_key' => 'in.('.implode(',', $deletes).')',
                ]);
            } catch (ConnectionException $e) {
                throw new SupabaseAuthException('Could not reach Supabase to clear brand tokens.', 0, $e);
            }
            if (! $response->successful()) {
                throw new SupabaseAuthException("Clearing brand tokens failed (HTTP {$response->status()}).", $response->status());
            }
        }

        // Upsert the set values (merge on the (brand_kit_id, token_key) PK).
        $payload = [];
        foreach ($upserts as $key => $value) {
            if (is_string($key) && $key !== '' && is_string($value) && $value !== '') {
                $payload[] = ['brand_kit_id' => $brandKitId, 'token_key' => $key, 'value' => $value];
            }
        }

        if ($payload !== []) {
            try {
                $response = $this->request()
                    ->withHeaders(['Prefer' => 'resolution=merge-duplicates'])
                    ->post('/rest/v1/brand_kit_tokens', $payload);
            } catch (ConnectionException $e) {
                throw new SupabaseAuthException('Could not reach Supabase to save brand tokens.', 0, $e);
            }
            if (! $response->successful()) {
                throw new SupabaseAuthException("Saving brand tokens failed (HTTP {$response->status()}).", $response->status());
            }
        }
    }

    /**
     * @param  array<string,string>  $query
     * @return array<int,array<string,mixed>>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->request()->get($path, $query);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service for brand kits.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Supabase brand-kit lookup failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    private function assertConfigured(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so brand kits cannot be read or written.');
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
