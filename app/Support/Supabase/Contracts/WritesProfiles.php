<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes a user's own profile fields (preferences, avatar) with the server-side
 * service-role key. Callers must scope the write to the signed-in user's own
 * profile id — there is no cross-user authorisation here.
 */
interface WritesProfiles
{
    /**
     * @throws SupabaseAuthException
     */
    public function updateThemePreference(string $profileId, string $theme): void;

    /**
     * Set (or clear, with null) a profile's uploaded avatar path.
     *
     * @throws SupabaseAuthException
     */
    public function updateAvatarPath(string $profileId, ?string $avatarPath): void;
}
