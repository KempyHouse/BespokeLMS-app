<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCoursePricing;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Upserts a course's pricing + retake/retry policy (migration 007,
 * `course_pricing`). The primary key is `course_id`, so a POST with
 * `Prefer: resolution=merge-duplicates` inserts on first save and updates
 * thereafter. Only invoked behind the `platform.owner` middleware; Supabase
 * RLS (`can_manage_course` on `course_pricing`) is the defence-in-depth layer.
 *
 * The retry/retake columns follow the migration's nullability convention:
 *   null = inherit the pricing_defaults value for this pricing_type
 *   -1   = override to unlimited
 *   N>=0 = an explicit limit
 */
final class SupabaseCoursePricingWriter implements WritesCoursePricing
{
    /** Columns the pricing editor is allowed to write (allow-list, defensive). */
    private const WRITABLE = [
        'pricing_type', 'price_pennies', 'currency', 'credit_cost',
        'included_in_subscription',
        'assessment_retry_limit', 'retake_after_pass', 'retake_limit',
        'access_revoked_on_pass',
    ];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function upsertPricing(string $courseId, array $fields): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so pricing cannot be saved.',
            );
        }

        // Only ever send known columns; drop anything else.
        $payload = array_intersect_key($fields, array_flip(self::WRITABLE));
        $payload['course_id'] = $courseId;
        $payload['updated_at'] = now()->toIso8601String();

        try {
            $response = $this->request()->post('/rest/v1/course_pricing', $payload);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase pricing upsert failed (HTTP {$response->status()}).",
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
            ->withHeaders([
                'apikey' => $this->serviceRoleKey,
                // merge-duplicates => upsert on the course_id primary key.
                'Prefer' => 'resolution=merge-duplicates,return=minimal',
            ])
            ->withToken($this->serviceRoleKey);
    }
}
