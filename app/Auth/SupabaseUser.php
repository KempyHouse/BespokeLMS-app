<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

/**
 * An authenticated BespokeLMS user.
 *
 * This is a lightweight, immutable value object (not an Eloquent model): the
 * source of truth for identity is Supabase Auth, and the source of truth for
 * role/organisation is the `profiles` table. A snapshot is held in the
 * server-side session and rehydrated per request by {@see SupabaseUserProvider}.
 */
final class SupabaseUser implements Authenticatable
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly ?string $name,
        public readonly string $role,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $profileId = null,
        public readonly ?string $organizationId = null,
        public readonly ?string $organizationName = null,
        public readonly ?string $organizationSlug = null,
        public readonly ?string $organizationType = null,
        public readonly string $themePreference = 'light',
        public readonly ?string $jobTitle = null,
        public readonly ?string $avatarPath = null,
    ) {
    }

    /**
     * Build a user from a Supabase auth payload + its application profile.
     *
     * @param  array<string,mixed>  $authUser  GoTrue "user" object.
     * @param  array<string,mixed>  $profile   PostgREST profile row (with embedded organisation).
     */
    public static function fromSupabase(array $authUser, array $profile): self
    {
        $organisation = $profile['organizations'] ?? null;

        // A to-one PostgREST embed is an object; guard against a list form too.
        if (is_array($organisation) && array_is_list($organisation)) {
            $organisation = $organisation[0] ?? null;
        }

        return new self(
            id: (string) ($authUser['id'] ?? ''),
            email: (string) ($authUser['email'] ?? ''),
            name: isset($profile['full_name']) ? (string) $profile['full_name'] : null,
            role: (string) ($profile['role'] ?? ''),
            firstName: isset($profile['first_name']) ? (string) $profile['first_name'] : null,
            lastName: isset($profile['last_name']) ? (string) $profile['last_name'] : null,
            profileId: isset($profile['id']) ? (string) $profile['id'] : null,
            organizationId: isset($profile['organization_id']) ? (string) $profile['organization_id'] : null,
            organizationName: is_array($organisation) && isset($organisation['name']) ? (string) $organisation['name'] : null,
            organizationSlug: is_array($organisation) && isset($organisation['slug']) ? (string) $organisation['slug'] : null,
            organizationType: is_array($organisation) && isset($organisation['type']) ? (string) $organisation['type'] : null,
            themePreference: isset($profile['theme_preference']) ? (string) $profile['theme_preference'] : 'light',
            jobTitle: isset($profile['job_title']) ? (string) $profile['job_title'] : null,
            avatarPath: isset($profile['avatar_path']) ? (string) $profile['avatar_path'] : null,
        );
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromSession(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            name: $data['name'] ?? null,
            role: (string) ($data['role'] ?? ''),
            firstName: $data['firstName'] ?? null,
            lastName: $data['lastName'] ?? null,
            profileId: $data['profileId'] ?? null,
            organizationId: $data['organizationId'] ?? null,
            organizationName: $data['organizationName'] ?? null,
            organizationSlug: $data['organizationSlug'] ?? null,
            organizationType: $data['organizationType'] ?? null,
            themePreference: (string) ($data['themePreference'] ?? 'light'),
            jobTitle: $data['jobTitle'] ?? null,
            avatarPath: $data['avatarPath'] ?? null,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toSession(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'profileId' => $this->profileId,
            'organizationId' => $this->organizationId,
            'organizationName' => $this->organizationName,
            'organizationSlug' => $this->organizationSlug,
            'organizationType' => $this->organizationType,
            'themePreference' => $this->themePreference,
            'jobTitle' => $this->jobTitle,
            'avatarPath' => $this->avatarPath,
        ];
    }

    /**
     * Public URL of the uploaded avatar, or null when the user has no image
     * (the UI then falls back to {@see initials()}). Built from the public
     * `avatars` Storage bucket.
     */
    public function avatarUrl(): ?string
    {
        if ($this->avatarPath === null || $this->avatarPath === '') {
            return null;
        }

        $base = rtrim((string) config('services.supabase.url'), '/');
        if ($base === '') {
            return null;
        }

        return $base.'/storage/v1/object/public/avatars/'.ltrim($this->avatarPath, '/');
    }

    public function isPlatformOwner(): bool
    {
        return $this->role === 'bespokelms_owner';
    }

    /**
     * Human-readable role label for the UI.
     */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'bespokelms_owner' => 'Platform Owner',
            'lms_operator_admin' => 'Operator Admin',
            'client_admin' => 'Client Admin',
            'team_manager' => 'Team Manager',
            'learner' => 'Learner',
            default => Str::of($this->role)->replace('_', ' ')->title()->toString(),
        };
    }

    public function displayName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Up to two initials for a text avatar.
     */
    public function initials(): string
    {
        $source = $this->name ?? $this->email;

        $initials = Str::of($source)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    // --- Illuminate\Contracts\Auth\Authenticatable -----------------------

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        // Passwords live in Supabase Auth, never here.
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void
    {
        // No-op: remember-me tokens are not used with the Supabase provider.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
