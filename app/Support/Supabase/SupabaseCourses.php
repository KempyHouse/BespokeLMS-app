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

    /**
     * The richer column set the course workspace + editor need (migration 003
     * + 007 fields). Only used by find(); the list view keeps the lean select.
     */
    private const COURSE_SELECT_DETAIL = 'id,title,content_type,catalog_status,owner_org_id,category_id,current_published_version_id,created_at,updated_at,description,level,duration_min,accreditation,cpd_points,cpd_body,issues_certificate,certificate_validity,hero_image_path,hero_image_alt,meta_title,meta_description';

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

    public function find(string $courseId): ?array
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the course cannot be loaded.',
            );
        }

        $course = $this->fetchCourse($courseId);
        if ($course === null) {
            return null;
        }

        $ownerIsPlatform = ($course['owner_org_id'] ?? null) === null;

        // category
        $categoryId = (string) ($course['category_id'] ?? '');
        $course['category_name'] = $categoryId !== '' ? ($this->categoryNames()[$categoryId] ?? null) : null;

        // visibility + entitlement count
        $vis = $this->bestEffort('/rest/v1/course_visibility', [
            'select' => 'scope', 'course_id' => 'eq.'.$courseId,
        ]);
        $course['visibility_scope'] = isset($vis[0]['scope'])
            ? (string) $vis[0]['scope']
            : ($ownerIsPlatform ? 'global' : 'private');
        $granted = 0;
        foreach ($this->bestEffort('/rest/v1/course_entitlements', ['select' => 'state', 'course_id' => 'eq.'.$courseId]) as $e) {
            if (($e['state'] ?? '') === 'granted') {
                $granted++;
            }
        }
        $course['entitlement_count'] = $granted;

        // versions (newest first)
        $versions = $this->bestEffort('/rest/v1/course_versions', [
            'select' => 'id,version_no,semver,status,published_at,review_due_at,changelog',
            'course_id' => 'eq.'.$courseId,
            'order' => 'version_no.desc',
        ]);
        $course['versions'] = $versions;

        $currentId = (string) ($course['current_published_version_id'] ?? '');
        $course['current_version'] = null;
        foreach ($versions as $version) {
            if ((string) ($version['id'] ?? '') === $currentId) {
                $course['current_version'] = $version;
                break;
            }
        }

        // language variants across this course's versions
        $versionIds = array_values(array_filter(array_map(
            static fn (array $v): string => (string) ($v['id'] ?? ''),
            $versions,
        )));
        $locales = [];
        if ($versionIds !== []) {
            $translations = $this->bestEffort('/rest/v1/content_translations', [
                'select' => 'locale',
                'entity_type' => 'eq.course_version',
                'entity_id' => 'in.('.implode(',', $versionIds).')',
            ]);
            foreach ($translations as $t) {
                $locale = (string) ($t['locale'] ?? '');
                if ($locale !== '') {
                    $locales[$locale] = true;
                }
            }
        }
        $course['locales'] = array_keys($locales);

        // workflow state of the current (or latest) version
        $wfVersionId = $currentId !== '' ? $currentId : (string) ($versions[0]['id'] ?? '');
        $course['workflow_state'] = null;
        if ($wfVersionId !== '') {
            $wf = $this->bestEffort('/rest/v1/course_workflow_state', [
                'select' => 'workflow_states(key,label)',
                'course_version_id' => 'eq.'.$wfVersionId,
            ]);
            $course['workflow_state'] = $wf[0]['workflow_states']['label']
                ?? $wf[0]['workflow_states']['key']
                ?? null;
        }

        // content review clock
        $rs = $this->bestEffort('/rest/v1/review_schedule', [
            'select' => 'review_due_at', 'course_id' => 'eq.'.$courseId,
        ]);
        $course['review_due_at'] = $rs[0]['review_due_at'] ?? null;

        return $course;
    }

    /**
     * Fetch a single course row (with the full model, falling back to the
     * legacy columns), including its description. Null if the id is unknown.
     *
     * @return array<string,mixed>|null
     */
    private function fetchCourse(string $courseId): ?array
    {
        try {
            $response = $this->request()->get('/rest/v1/courses', [
                'select' => self::COURSE_SELECT_DETAIL,
                'id' => 'eq.'.$courseId,
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            try {
                $legacy = $this->request()->get('/rest/v1/courses', [
                    'select' => self::COURSE_SELECT_LEGACY.',description',
                    'id' => 'eq.'.$courseId,
                ]);
            } catch (ConnectionException $e) {
                throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
            }

            if (! $legacy->successful()) {
                throw new SupabaseAuthException(
                    "Supabase course lookup failed (HTTP {$response->status()}).",
                    $response->status(),
                );
            }

            /** @var array<int,array<string,mixed>> $rows */
            $rows = $legacy->json() ?? [];

            return $rows[0] ?? null;
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows[0] ?? null;
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
     * All course categories as id => name, for the editor's category picker.
     * Best-effort: an unreachable table yields an empty list.
     *
     * @return array<string,string>
     */
    public function categories(): array
    {
        return $this->categoryNames();
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
