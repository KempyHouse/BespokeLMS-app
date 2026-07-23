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

    /**
     * Update a profile's editable identity fields. full_name is a generated
     * concatenation in the database, so only the parts are written here.
     *
     * @throws SupabaseAuthException
     */
    public function updateDetails(string $profileId, string $firstName, string $lastName, ?string $jobTitle): void;

    /**
     * Upload raw image bytes into the public `avatars` Storage bucket at the
     * given object path (overwriting any existing object at that path).
     *
     * @throws SupabaseAuthException
     */
    public function uploadAvatar(string $objectPath, string $contents, string $contentType): void;
}
