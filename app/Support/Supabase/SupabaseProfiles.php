<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsProfiles;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * Reads BespokeLMS application profiles through PostgREST, scoped by the
 * signed-in user's own access token so Row Level Security applies.
 */
final class SupabaseProfiles implements ReadsProfiles
{
    /**
     * PostgREST select: the profile plus its embedded organisation (to-one).
     */
    private const SELECT = 'id,role,full_name,job_title,avatar_path,theme_preference,organization_id,organizations(name,slug,type)';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $anonKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function findByAuthUserId(string $accessToken, string $authUserId): ?array
    {
        try {
            $response = $this->http
                ->baseUrl($this->url)
                ->timeout($this->timeout)
                ->acceptJson()
                ->withHeaders(['apikey' => $this->anonKey])
                ->withToken($accessToken)
                ->get('/rest/v1/profiles', [
                    'select' => self::SELECT,
                    'auth_user_id' => 'eq.'.$authUserId,
                    'limit' => 1,
                ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase profile lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows[0] ?? null;
    }
}
