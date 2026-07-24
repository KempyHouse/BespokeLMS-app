<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCourses;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;

/**
 * Writes course fields through PostgREST using the server-side service-role
 * key. Reached only from the owner-gated Global Courses console routes.
 */
final class SupabaseCoursesWriter implements WritesCourses
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function updateCourse(string $courseId, array $fields): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException('The Supabase service-role key is not configured, so the course cannot be saved.');
        }

        if ($fields === []) {
            return;
        }

        // Always stamp the edit time (003 added updated_at with no auto-trigger).
        $fields['updated_at'] = Carbon::now()->toIso8601String();

        // PostgREST filters MUST travel in the query string.
        $url = '/rest/v1/courses?'.http_build_query(['id' => 'eq.'.$courseId]);

        try {
            $response = $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->patch($url, $fields);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach Supabase to save the course.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException("Saving the course failed (HTTP {$response->status()}).", $response->status());
        }
    }

    /**
     * Transitional alias kept so the currently-deployed course-details
     * controller (which calls updateDetails()) keeps working while the contract
     * standardises on updateCourse(). Remove once every caller uses updateCourse().
     *
     * @param  array<string,mixed>  $fields
     *
     * @throws SupabaseAuthException
     *
     * @deprecated Use {@see updateCourse()} instead.
     */
    public function updateDetails(string $courseId, array $fields): void
    {
        $this->updateCourse($courseId, $fields);
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
