<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsDesignTokens;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads the design-token contract and brand-kit overrides through PostgREST
 * using the server-side service-role key.
 *
 * These tables carry no secrets (only style values), and the layout needs to
 * theme both authenticated and — in future — public pages, so a service-role
 * read is appropriate. All results are cached by {@see \App\Support\Theme\ThemeResolver}.
 */
final class SupabaseDesignTokens implements ReadsDesignTokens
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function tokens(): array
    {
        $response = $this->safeGet('/rest/v1/design_tokens', [
            'select' => 'key,css_var,default_value,themeable',
            'order' => 'sort_order.asc',
        ]);

        /** @var array<int,array{key:string,css_var:string,default_value:string,themeable:bool}> $rows */
        $rows = $response;

        return $rows;
    }

    public function overrideRowsForOrg(string $organizationId): array
    {
        if ($organizationId === '') {
            return [];
        }

        $kits = $this->safeGet('/rest/v1/brand_kits', [
            'select' => 'id',
            'organization_id' => 'eq.'.$organizationId,
            'is_default' => 'is.true',
            'status' => 'eq.published',
            'limit' => '1',
        ]);

        $kitId = $kits[0]['id'] ?? null;

        if (! is_string($kitId) || $kitId === '') {
            return [];
        }

        $rows = $this->safeGet('/rest/v1/brand_kit_tokens', [
            'select' => 'token_key,value',
            'brand_kit_id' => 'eq.'.$kitId,
        ]);

        /** @var array<int,array{token_key:string,value:string}> $rows */
        return $rows;
    }

    /**
     * Perform a GET and decode the JSON body, mapping transport/HTTP failures
     * onto {@see SupabaseAuthException}.
     *
     * @param  array<string,string>  $query
     * @return array<int,array<string,mixed>>
     */
    private function safeGet(string $path, array $query): array
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so design tokens cannot be loaded.');
        }

        try {
            $response = $this->request()->get($path, $query);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service for design tokens.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Supabase design-token lookup failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
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
