<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsWidgetData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads the raw rows the dashboard widgets are computed from, through PostgREST
 * using the server-side service-role key.
 *
 * Personal rows are scoped to the given profile id IN THE QUERY (enrolments and
 * certificates by user_id; learning attempts by an inner embed on the owning
 * enrolment), so another user's progress is never returned. The platform
 * overview is read whole and is only requested for the platform owner. Every
 * read degrades to an empty array on failure — a widget then shows its honest
 * empty state instead of an error.
 */
final class SupabaseWidgetData implements ReadsWidgetData
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function personalFor(?string $profileId): array
    {
        if ($profileId === null || $profileId === '' || $this->serviceRoleKey === '') {
            return ['enrollments' => [], 'certificates' => [], 'attempts' => [], 'course_titles' => []];
        }

        return [
            'enrollments' => $this->bestEffort('/rest/v1/enrollments', [
                'select' => 'course_id,status,progress_pct,assigned_at,due_at,completed_at,created_at',
                'user_id' => 'eq.'.$profileId,
            ]),
            'certificates' => $this->bestEffort('/rest/v1/certificates', [
                'select' => 'course_id,issued_at,expires_at',
                'user_id' => 'eq.'.$profileId,
            ]),
            // Learning time: attempts whose owning enrolment belongs to this user.
            'attempts' => $this->bestEffort('/rest/v1/course_attempts', [
                'select' => 'started_at,completed_at,status,enrollments!inner(user_id)',
                'enrollments.user_id' => 'eq.'.$profileId,
            ]),
            'course_titles' => $this->courseTitles(),
        ];
    }

    public function platformOverview(): array
    {
        if ($this->serviceRoleKey === '') {
            return ['organizations' => [], 'profiles' => [], 'ai_integrations' => [], 'email_integrations' => []];
        }

        return [
            'organizations' => $this->bestEffort('/rest/v1/organizations', [
                'select' => 'id,type,operator_subtype,parent_id',
            ]),
            'profiles' => $this->bestEffort('/rest/v1/profiles', [
                'select' => 'id,employment_status,last_active_at',
            ]),
            'ai_integrations' => $this->bestEffort('/rest/v1/ai_integrations', [
                'select' => 'provider,display_name,is_enabled,status',
            ]),
            'email_integrations' => $this->bestEffort('/rest/v1/email_integrations', [
                'select' => 'provider,display_name,is_enabled,status',
            ]),
        ];
    }

    /**
     * Course id => title, for the "resume" line on the in-progress widget.
     *
     * @return array<string,string>
     */
    private function courseTitles(): array
    {
        $out = [];
        foreach ($this->bestEffort('/rest/v1/courses', ['select' => 'id,title']) as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id !== '') {
                $out[$id] = (string) ($row['title'] ?? '');
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
