<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\WritesCourseContent;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Writes course authoring content through PostgREST with the server-side
 * service-role key. Only invoked behind the `platform.owner` middleware;
 * Supabase RLS (`can_manage_course`) is the defence-in-depth layer.
 *
 * Every write is constrained to a per-table column allow-list, so a caller can
 * only ever set the columns the content builder is meant to manage.
 */
final class SupabaseCourseContentWriter implements WritesCourseContent
{
    /** table => columns the builder may write. */
    private const WRITABLE = [
        'course_versions' => ['course_id', 'version_no', 'semver', 'status', 'title', 'summary'],
        'modules'         => ['course_version_id', 'title', 'position'],
        'lessons'         => ['module_id', 'title', 'position'],
        'slides'          => ['lesson_id', 'type', 'title', 'position', 'payload', 'completion_rule', 'is_required'],
    ];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function createRow(string $table, array $fields): string
    {
        $payload = $this->clean($table, $fields);

        $response = $this->send(
            fn (PendingRequest $r) => $r
                ->withHeaders(['Prefer' => 'return=representation'])
                ->post("/rest/v1/{$table}", $payload),
        );

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];
        $id = (string) ($rows[0]['id'] ?? '');
        if ($id === '') {
            throw new SupabaseAuthException("Supabase insert into {$table} returned no id.");
        }

        return $id;
    }

    public function updateRow(string $table, string $id, array $fields): void
    {
        $payload = $this->clean($table, $fields);
        if ($payload === []) {
            return;
        }

        $this->send(
            fn (PendingRequest $r) => $r
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->patch("/rest/v1/{$table}?id=eq.".$id, $payload),
        );
    }

    public function deleteRow(string $table, string $id): void
    {
        $this->guardTable($table);

        $this->send(
            fn (PendingRequest $r) => $r
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->delete("/rest/v1/{$table}?id=eq.".$id),
        );
    }

    /**
     * @param  array<string,mixed>  $fields
     * @return array<string,mixed>
     */
    private function clean(string $table, array $fields): array
    {
        $allowed = $this->guardTable($table);

        return array_intersect_key($fields, array_flip($allowed));
    }

    /**
     * @return array<int,string>
     */
    private function guardTable(string $table): array
    {
        if (! isset(self::WRITABLE[$table])) {
            throw new SupabaseAuthException("Table {$table} is not writable by the content builder.");
        }

        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so content cannot be saved.',
            );
        }

        return self::WRITABLE[$table];
    }

    /**
     * @param  callable(PendingRequest):\Illuminate\Http\Client\Response  $call
     */
    private function send(callable $call): \Illuminate\Http\Client\Response
    {
        try {
            $response = $call($this->request());
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase content write failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        return $response;
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
