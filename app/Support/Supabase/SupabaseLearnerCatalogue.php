<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsLearnerCatalogue;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads the learner-facing course catalogue through PostgREST using the
 * server-side service-role key.
 *
 * The course list is the global catalogue (migration 001 already makes it
 * readable to every authenticated user). The personal rows — the learner's
 * enrolments and certificates — are scoped to the given profile id IN THE
 * QUERY, so this reader never returns another user's progress even though it
 * holds the service-role key. Requirements are read as the mandatory set and
 * matched to the learner (org / role) in the controller.
 *
 * All reads but the course list degrade to an empty array on failure, so the
 * catalogue still renders (with an empty personal state) if a table is briefly
 * unreachable. Every column selected here is guaranteed by migration 001.
 */
final class SupabaseLearnerCatalogue implements ReadsLearnerCatalogue
{
    /** Columns the catalogue cards need from `courses` (all from migration 001). */
    private const COURSE_SELECT = 'id,title,category_id,level,duration_min,price_pennies,credits,accreditation,description,thumbnail_path,catalog_status,owner_org_id,created_at';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function forLearner(?string $profileId, ?string $organizationId = null, ?string $role = null): array
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the course library cannot be loaded.',
            );
        }

        return [
            'courses' => $this->fetchCourses(),
            'categories' => $this->categoryNames(),
            'enrollments' => $profileId !== null && $profileId !== ''
                ? $this->bestEffort('/rest/v1/enrollments', [
                    'select' => 'course_id,status,progress_pct,assigned_at,due_at,completed_at',
                    'user_id' => 'eq.'.$profileId,
                ])
                : [],
            'requirements' => $this->bestEffort('/rest/v1/course_requirements', [
                'select' => 'course_id,scope,scope_ref,is_mandatory',
                'is_mandatory' => 'eq.true',
            ]),
            'certificates' => $profileId !== null && $profileId !== ''
                ? $this->bestEffort('/rest/v1/certificates', [
                    'select' => 'course_id,issued_at,expires_at',
                    'user_id' => 'eq.'.$profileId,
                ])
                : [],
        ];
    }

    /**
     * Fetch the course catalogue. A failure here is fatal to the library view.
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

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase courses lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

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
     * A read that degrades to an empty array on any failure.
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
