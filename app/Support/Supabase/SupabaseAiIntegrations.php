<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsAiIntegrations;
use App\Support\Supabase\Contracts\WritesAiIntegrations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads and writes the platform-owner AI integrations through PostgREST using
 * the server-side service-role key. Reached only from the owner-gated platform
 * routes; the encrypted API key never leaves this layer.
 */
final class SupabaseAiIntegrations implements ReadsAiIntegrations, WritesAiIntegrations
{
    /** Columns the update() whitelist may set. */
    private const WRITABLE = ['is_enabled', 'status', 'default_model', 'base_url', 'options', 'api_key_cipher', 'last_tested_at', 'updated_at'];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function all(): array
    {
        $this->assertConfigured();

        $rows = $this->get('/rest/v1/ai_integrations', [
            'select' => 'id,provider,display_name,is_enabled,api_key_cipher,default_model,base_url,options,status,last_tested_at',
            'organization_id' => 'is.null',
            'order' => 'is_enabled.desc,display_name.asc',
        ]);

        // Strip the ciphertext; expose only whether a key is present.
        $out = [];
        foreach ($rows as $row) {
            $row['has_key'] = isset($row['api_key_cipher']) && is_string($row['api_key_cipher']) && $row['api_key_cipher'] !== '';
            unset($row['api_key_cipher']);
            $out[] = $row;
        }

        return $out;
    }

    public function usageSince(string $sinceIso): array
    {
        $this->assertConfigured();

        // Fetch the month's rows and aggregate in PHP. Volume is low at this
        // tier; if it grows this should move to a Postgres aggregate view.
        $rows = $this->get('/rest/v1/ai_usage_logs', [
            'select' => 'integration_id,tokens_in,tokens_out',
            'created_at' => 'gte.'.$sinceIso,
            'limit' => '10000',
        ]);

        $out = [];
        foreach ($rows as $row) {
            $id = $row['integration_id'] ?? null;
            if (! is_string($id) || $id === '') {
                continue;
            }
            if (! isset($out[$id])) {
                $out[$id] = ['calls' => 0, 'tokens_in' => 0, 'tokens_out' => 0];
            }
            $out[$id]['calls']++;
            $out[$id]['tokens_in'] += (int) ($row['tokens_in'] ?? 0);
            $out[$id]['tokens_out'] += (int) ($row['tokens_out'] ?? 0);
        }

        return $out;
    }

    public function update(string $id, array $attrs): void
    {
        $this->assertConfigured();

        if ($id === '') {
            throw new SupabaseAuthException('An AI integration id is required to save.');
        }

        $payload = [];
        foreach (self::WRITABLE as $key) {
            if (array_key_exists($key, $attrs)) {
                $payload[$key] = $attrs[$key];
            }
        }

        if ($payload === []) {
            return;
        }

        try {
            $response = $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->patch('/rest/v1/ai_integrations?id=eq.'.rawurlencode($id), $payload);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to save the AI integration.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Saving the AI integration failed (HTTP {$response->status()}).", $response->status());
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
            throw new SupabaseAuthException('Could not reach the Supabase data service for AI integrations.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Supabase AI-integration lookup failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    private function assertConfigured(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so AI integrations cannot be read or written.');
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
