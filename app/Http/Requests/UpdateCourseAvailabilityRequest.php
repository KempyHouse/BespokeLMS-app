<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the course availability & authors editor (migration 007:
 * `course_territories`, `course_authors`). Authorisation is handled by the
 * `platform.owner` route middleware, so this request authorises any caller that
 * reaches it.
 */
final class UpdateCourseAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'territory_ids'            => ['nullable', 'array'],
            'territory_ids.*'          => ['uuid'],

            'authors'                  => ['nullable', 'array'],
            'authors.*.profile_id'     => ['nullable', 'uuid'],
            'authors.*.display_name'   => ['nullable', 'string', 'max:200'],
            'authors.*.credit_label'   => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * The selected territory UUIDs.
     *
     * @return array<int,string>
     */
    public function territoryIds(): array
    {
        /** @var array<int,string> $ids */
        $ids = $this->validated()['territory_ids'] ?? [];

        return array_values(array_filter($ids, static fn ($v) => is_string($v) && $v !== ''));
    }

    /**
     * The author rows, dropping any with no identity and assigning display sort
     * from the submitted order.
     *
     * @return array<int,array{profile_id:?string,display_name:?string,credit_label:?string,sort:int}>
     */
    public function authors(): array
    {
        /** @var array<int,array<string,mixed>> $rows */
        $rows = $this->validated()['authors'] ?? [];

        $out = [];
        $sort = 0;
        foreach ($rows as $r) {
            $profileId = ($r['profile_id'] ?? null) !== null && $r['profile_id'] !== '' ? (string) $r['profile_id'] : null;
            $displayName = isset($r['display_name']) && trim((string) $r['display_name']) !== ''
                ? trim((string) $r['display_name'])
                : null;

            if ($profileId === null && $displayName === null) {
                continue; // empty row
            }

            $out[] = [
                'profile_id'   => $profileId,
                'display_name' => $displayName,
                'credit_label' => isset($r['credit_label']) && trim((string) $r['credit_label']) !== ''
                    ? trim((string) $r['credit_label'])
                    : null,
                'sort'         => $sort++,
            ];
        }

        return $out;
    }
}
