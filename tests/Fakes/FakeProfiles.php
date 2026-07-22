<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Support\Supabase\Contracts\ReadsProfiles;

/**
 * Returns a fixed profile (or null) regardless of the token / id.
 */
final class FakeProfiles implements ReadsProfiles
{
    /**
     * @param  array<string,mixed>|null  $profile
     */
    public function __construct(private ?array $profile = null)
    {
    }

    public function findByAuthUserId(string $accessToken, string $authUserId): ?array
    {
        return $this->profile;
    }
}
