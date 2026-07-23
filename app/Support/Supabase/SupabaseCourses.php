<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsCourses;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads the ecosystem-wide course catalogue through PostgREST using the
 * server-side service-role key.
 *
 * This intentionally bypasses Row Level Security: the platform-owner console
 * needs to see every course (platform-owned and operator-authored), and the
 * query is only invoked from routes behind the `platform.owner` middleware. The
 * `courses` table holds no secrets. Enrichment joins (category, visibility,
 * current version) are best-effort so the catalogue still renders if a later
 * migration has not yet been applied.
 */
final class SupabaseCourses implements ReadsCourses
{
    /**
     * Columns the catalogue list needs from `courses`. `content_type`,
     * `current_published_version_id` and `updated_at` are added by migration
     * 003; if it has not been applied the primary read fails and the console
     * shows its error state (rather than a partial catalogue).
     */
    private const COURSE_SELECT = 'id,title,content_type,catalog_status,owner_org_id,category_id,current_published_version_id,created_at,updated_at';

    /**
     * Columns guaranteed by migration 001. Used as a fallback so the console
     * still lists the existing catalogue if migration 003 has not been applied
     * yet (Type/Version default, Visibility falls back by ownership).
     */
    private const COURSE_SELECT_LEGACY = 'id,title,catalog_status,owner_org_id,category_id,created_at';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function all(): array
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the course catalogue cannot be loaded.',
            );
        }

        $courses = $this->fetchCourses();
        $categories = $this->categoryNames();      // best-effort (id => name)
        $scopes = $this->visibilityByCourse();     // best-effort (course_id => scope)
        $versions = $this->versionsById();         // best-effort (id => [semver,status])

        foreach ($courses as $index => $course) {
            $categoryId = (string) ($course['category_id'] ?? '');
            $courseId = (string) ($course['id'] ?? '');
            $versionId = (string) ($course['current_published_version_id'] ?? '');
            $ownerIsPlatform = ($course['owner_org_id'] ?? null) === null;

            $courses[$index]['category_name'] = $categories[$categoryId] ?? null;
            // Mirror can_see_course's fallback: no visibility row → platform
            // courses are global, operator courses are private.
            $courses[$index]['visibility_scope'] = $scopes[$courseId]
                ?? ($ownerIsPlatform ? 'global' : 'private');

            $version = $versions[$versionId] ?? null;
            $courses[$index]['version_semver'] = $version['semver'] ?? null;
            $courses[$index]['version_status'] = $version['status'] ?? null;
        }

        return $courses;
    }

    /**
     * Fetch every course row. A failure here is fatal to the catalogue view.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchCourses(): array
    {
        try {
            $response = $this->request()->get('/rest/v1/courses', [
                'select' => self::COURSE_SELECT,
                'order' => 'title.asc',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if ($response->successful()) {
            /** @var array<int,array<string,mixed>> $rows */
            $rows = $response->json() ?? [];

            return $rows;
        }

        // Fallback: the full course model (migration 003) may not be applied yet.
        // Retry with only the columns migration 001 guarantees, so the console
        // still lists the existing catalogue rather than showing an error.
        try {
            $legacy = $this->request()->get('/rest/v1/courses', [
                'select' => self::COURSE_SELECT_LEGACY,
                'order' => 'title.asc',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $legacy->successful()) {
            throw new SupabaseAuthException(
                "Supabase courses lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $legacy->json() ?? [];

        return $rows;
    }

    /**
     * Category id => display name.
     *
     * @return array<string,string>
     */
    private function categoryNames(): array
    {
        $out = [];
        foreach ($this->bestEffort('/rest/v1/course_categories', ['select' => 'id,name']) as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id !== '') {
                $out[$id] = (string) ($row['name'] ?? '');
            }
        }

        return $out;
    }

    /**
     * Course id => visibility scope (global|allowlist|private|denylist).
     *
     * @return array<string,string>
     */
    private function visibilityByCourse(): array
    {
        $out = [];
        foreach ($this->bestEffort('/rest/v1/course_visibility', ['select' => 'course_id,scope']) as $row) {
            $courseId = (string) ($row['course_id'] ?? '');
            if ($courseId !== '') {
                $out[$courseId] = (string) ($row['scope'] ?? '');
            }
        }

        return $out;
    }

    /**
     * Version id => [semver, status] for the current-published-version lookup.
     *
     * @return array<string,array{semver:string,status:string}>
     */
    private function versionsById(): array
    {
        $out = [];
        foreach ($this->bestEffort('/rest/v1/course_versions', ['select' => 'id,semver,status']) as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id !== '') {
                $out[$id] = [
                    'semver' => (string) ($row['semver'] ?? ''),
                    'status' => (string) ($row['status'] ?? ''),
                ];
            }
        }

        return $out;
    }

    /**
     * A read that degrades to an empty array on any failure — used for the
     * enrichment joins so a not-yet-applied migration never blanks the whole
     * catalogue (the column simply shows as unset).
     *
     * @param  array<string,string>  $query
     * @return array<int,array<string,mixed>>
     */
    private function bestEffort(string $path, array $query): array
    {
        try {
            $response = $this->request()->get($path, $query);
        } catch (ConnectionException) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    /**
     * A PostgREST request pre-bound to the project and the service-role key.
     */
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
