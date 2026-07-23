<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsEmailIntegrations;
use App\Support\Supabase\Contracts\WritesEmailIntegrations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads and writes the platform-owner email transport integrations through
 * PostgREST using the server-side service-role key. Reached only from the
 * owner-gated platform routes; the encrypted secret never leaves this layer.
 *
 * This is the direct email counterpart to {@see SupabaseAiIntegrations}: the
 * enabled owner-level row is the platform transport, and swapping providers is
 * a matter of enabling a different row.
 */
final class SupabaseEmailIntegrations implements ReadsEmailIntegrations, WritesEmailIntegrations
{
    /** Columns the update() whitelist may set. */
    private const WRITABLE = [
        'is_enabled', 'status', 'from_address', 'from_name', 'reply_to',
        'base_url', 'options', 'api_key_cipher', 'last_tested_at', 'updated_at',
    ];

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

        $rows = $this->get('/rest/v1/email_integrations', [
            'select' => 'id,provider,display_name,is_enabled,api_key_cipher,from_address,from_name,reply_to,base_url,options,status,last_tested_at',
            'organization_id' => 'is.null',
            'order' => 'is_enabled.desc,display_name.asc',
        ]);

        // Strip the ciphertext; expose only whether a secret is present.
        $out = [];
        foreach ($rows as $row) {
            $row['has_key'] = isset($row['api_key_cipher']) && is_string($row['api_key_cipher']) && $row['api_key_cipher'] !== '';
            unset($row['api_key_cipher']);
            $out[] = $row;
        }

        return $out;
    }

    public function update(string $id, array $attrs): void
    {
        $this->assertConfigured();

        if ($id === '') {
            throw new SupabaseAuthException('An email integration id is required to save.');
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
                ->patch('/rest/v1/email_integrations?id=eq.'.rawurlencode($id), $payload);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to save the email integration.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Saving the email integration failed (HTTP {$response->status()}).", $response->status());
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
            throw new SupabaseAuthException('Could not reach the Supabase data service for email integrations.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Supabase email-integration lookup failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    private function assertConfigured(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so email integrations cannot be read or written.');
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
