<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCourses;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Writes course catalogue/commercial fields through PostgREST with the
 * server-side service-role key. Only invoked behind the `platform.owner`
 * middleware; Supabase RLS (`can_manage_course`) is the defence-in-depth layer.
 */
final class SupabaseCourseWriter implements WritesCourses
{
    /** Columns the details editor is allowed to write (allow-list, defensive). */
    private const WRITABLE = [
        'title', 'slug', 'catalog_status', 'content_type', 'category_id',
        'hero_image_path', 'hero_image_alt', 'trailer_video_path', 'trailer_url',
        'duration_min', 'cpd_points', 'cpd_body',
        'meta_title', 'meta_description', 'meta_keywords',
        'issues_certificate', 'certificate_validity', 'auto_reassign_on_expiry',
    ];

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
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so the course cannot be saved.',
            );
        }

        // Only ever send known columns; drop anything else.
        $payload = array_intersect_key($fields, array_flip(self::WRITABLE));
        $payload['updated_at'] = now()->toIso8601String();

        if ($payload === ['updated_at' => $payload['updated_at']]) {
            return; // nothing to change
        }

        try {
            $response = $this->request()->patch('/rest/v1/courses?id=eq.'.$courseId, $payload);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase course update failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['apikey' => $this->serviceRoleKey, 'Prefer' => 'return=minimal'])
            ->withToken($this->serviceRoleKey);
    }
}
