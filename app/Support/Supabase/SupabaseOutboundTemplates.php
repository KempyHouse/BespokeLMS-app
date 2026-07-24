<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsOutboundTemplates;
use App\Support\Supabase\Contracts\WritesOutboundTemplates;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads and writes the outbound communication templates through PostgREST using
 * the server-side service-role key. Mirrors {@see SupabaseEmailIntegrations}:
 * one client implements both the read and write contracts, and only the
 * owner-gated platform routes reach it.
 */
final class SupabaseOutboundTemplates implements ReadsOutboundTemplates, WritesOutboundTemplates
{
    private const SELECT = 'id,organization_id,channel,category,key,name,subject,body_html,variables,is_protected,is_active,updated_at';

    /** Columns the update() whitelist may set. */
    private const WRITABLE = ['name', 'subject', 'body_html', 'variables', 'is_active', 'updated_at'];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function platformAll(): array
    {
        $this->assertConfigured();

        return $this->get('/rest/v1/outbound_templates', [
            'select' => self::SELECT,
            'organization_id' => 'is.null',
            'order' => 'category.asc,name.asc',
        ]);
    }

    public function platformFind(string $channel, string $key): ?array
    {
        $this->assertConfigured();

        $rows = $this->get('/rest/v1/outbound_templates', [
            'select' => self::SELECT,
            'organization_id' => 'is.null',
            'channel' => 'eq.'.$channel,
            'key' => 'eq.'.$key,
            'limit' => '1',
        ]);

        return $rows[0] ?? null;
    }

    public function update(string $id, array $attrs): void
    {
        $this->assertConfigured();

        if ($id === '') {
            throw new SupabaseAuthException('An outbound template id is required to save.');
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
                ->patch('/rest/v1/outbound_templates?id=eq.'.rawurlencode($id), $payload);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to save the template.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Saving the template failed (HTTP {$response->status()}).", $response->status());
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
            throw new SupabaseAuthException('Could not reach the Supabase data service for outbound templates.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Supabase outbound-template lookup failed (HTTP {$response->status()}).", $response->status());
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    private function assertConfigured(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so outbound templates cannot be read or written.');
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
