<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesProfiles;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * Writes profile fields through PostgREST with the service-role key. Only ever
 * called with the signed-in user's own profile id (see PreferencesController /
 * ProfileController), so the caller is the authorisation boundary.
 */
final class SupabaseProfilesWriter implements WritesProfiles
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function updateThemePreference(string $profileId, string $theme): void
    {
        $this->patch($profileId, ['theme_preference' => $theme]);
    }

    public function updateAvatarPath(string $profileId, ?string $avatarPath): void
    {
        $this->patch($profileId, ['avatar_path' => $avatarPath]);
    }

    public function updateDetails(string $profileId, string $firstName, string $lastName, ?string $jobTitle): void
    {
        $this->patch($profileId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'job_title' => $jobTitle,
        ]);
    }

    public function uploadAvatar(string $objectPath, string $contents, string $contentType): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so the avatar cannot be uploaded.');
        }

        $path = ltrim($objectPath, '/');

        try {
            $response = $this->http
                ->baseUrl($this->url)
                ->timeout($this->timeout)
                ->withHeaders([
                    'apikey' => $this->serviceRoleKey,
                    'x-upsert' => 'true',
                    'Content-Type' => $contentType,
                    'Cache-Control' => 'max-age=3600',
                ])
                ->withToken($this->serviceRoleKey)
                ->withBody($contents, $contentType)
                ->post('/storage/v1/object/avatars/'.$path);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase Storage to upload the avatar.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Avatar upload failed (HTTP {$response->status()}).", $response->status());
        }
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function patch(string $profileId, array $data): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so the profile cannot be updated.');
        }
        if ($profileId === '') {
            throw new SupabaseAuthException('No profile id to update.');
        }

        try {
            $response = $this->http
                ->baseUrl($this->url)
                ->timeout($this->timeout)
                ->acceptJson()
                ->withHeaders(['apikey' => $this->serviceRoleKey, 'Prefer' => 'return=minimal'])
                ->withToken($this->serviceRoleKey)
                ->patch('/rest/v1/profiles?id=eq.'.$profileId, $data);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to update the profile.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Profile update failed (HTTP {$response->status()}).", $response->status());
        }
    }
}
