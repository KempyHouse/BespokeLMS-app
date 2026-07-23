<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsCourses;
use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The Global Courses console — the platform-owner catalogue of every course in
 * the ecosystem (platform-owned courses that cascade to tenants, plus
 * operator-authored courses).
 *
 * This is the read-only catalogue slice: a searchable, filterable, sortable
 * table over the course model (migrations 003–006). Data is read with the
 * service-role key ({@see ReadsCourses}) behind the `platform.owner`
 * middleware; the course workspace and create/authoring flows are later slices.
 */
final class CourseController extends Controller
{
    /**
     * Global Courses index — the full catalogue as one sortable/filterable table.
     */
    public function index(Request $request, ReadsCourses $courses, ReadsOrganizations $organizations): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $q = trim((string) $request->query('q', ''));

        try {
            $rows = $courses->all();
        } catch (SupabaseAuthException $e) {
            report($e);

            return view('platform.courses.index', [
                'user' => $user,
                'courses' => null,
                'summary' => null,
                'categoryOptions' => [],
                'ownerOptions' => [],
                'catalogueError' => 'The course catalogue could not be loaded right now. Please try again shortly.',
                'q' => $q,
            ]);
        }

        $ownerNames = $this->ownerNames($organizations);
        [$catalogue, $summary, $categoryOptions, $ownerOptions] = $this->buildCatalogue($rows, $ownerNames);

        return view('platform.courses.index', [
            'user' => $user,
            'courses' => $catalogue,
            'summary' => $summary,
            'categoryOptions' => $categoryOptions,
            'ownerOptions' => $ownerOptions,
            'catalogueError' => null,
            'q' => $q,
        ]);
    }

    /**
     * Organisation id => name, for labelling each course's owner. Best-effort:
     * a failure degrades owner labels to "Operator" but never hides the list.
     *
     * @return array<string,string>
     */
    private function ownerNames(ReadsOrganizations $organizations): array
    {
        try {
            $orgs = $organizations->all();
        } catch (SupabaseAuthException) {
            return [];
        }

        $names = [];
        foreach ($orgs as $org) {
            $id = (string) ($org['id'] ?? '');
            if ($id !== '') {
                $names[$id] = (string) ($org['name'] ?? '');
            }
        }

        return $names;
    }

    /**
     * Shape the enriched course rows into the table model, a summary, and the
     * distinct category/owner options used by the table's filters.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @param  array<string,string>  $ownerNames
     * @return array{
     *     0:array<int,array<string,mixed>>,
     *     1:array<string,int>,
     *     2:array<int,array{value:string,label:string}>,
     *     3:array<int,array{value:string,label:string}>
     * }
     */
    private function buildCatalogue(array $rows, array $ownerNames): array
    {
        $catalogue = [];
        $categories = [];
        $owners = [];
        $total = 0;
        $published = 0;
        $comingSoon = 0;
        $native = 0;
        $imported = 0;

        foreach ($rows as $course) {
            $ownerOrgId = $course['owner_org_id'] ?? null;
            $ownerIsPlatform = $ownerOrgId === null;
            $ownerKey = $ownerIsPlatform ? 'platform' : (string) $ownerOrgId;
            $ownerName = $ownerIsPlatform ? 'Platform' : ($ownerNames[(string) $ownerOrgId] ?? 'Operator');

            $type = (string) ($course['content_type'] ?? 'native');
            $status = (string) ($course['catalog_status'] ?? 'published');
            $categoryName = $course['category_name'] !== null ? (string) $course['category_name'] : null;
            $scope = (string) ($course['visibility_scope'] ?? 'global');

            if ($categoryName !== null && $categoryName !== '') {
                $categories[$categoryName] = $categoryName;
            }
            $owners[$ownerKey] = $ownerName;

            $total++;
            if ($status === 'published') {
                $published++;
            } elseif ($status === 'coming_soon') {
                $comingSoon++;
            }
            if ($type === 'native') {
                $native++;
            } else {
                $imported++;
            }

            $updatedRaw = $course['updated_at'] ?? $course['created_at'] ?? null;

            $catalogue[] = [
                'id' => (string) ($course['id'] ?? ''),
                'title' => (string) ($course['title'] ?? ''),
                'owner_key' => $ownerKey,
                'owner_name' => $ownerName,
                'type' => $type,
                'type_label' => $this->typeLabel($type),
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'status_tone' => $this->statusTone($status),
                'category' => $categoryName,
                'version' => $course['version_semver'] !== null ? (string) $course['version_semver'] : null,
                'version_status' => $course['version_status'] !== null ? (string) $course['version_status'] : null,
                'scope' => $scope,
                'scope_label' => $this->scopeLabel($scope),
                'scope_tone' => $this->scopeTone($scope),
                'updated_sort' => is_string($updatedRaw) ? $updatedRaw : '',
                'updated_label' => $this->formatDate($updatedRaw),
            ];
        }

        usort($catalogue, static fn (array $a, array $b): int => strcasecmp($a['title'], $b['title']));

        asort($categories);
        $categoryOptions = [];
        foreach ($categories as $name) {
            $categoryOptions[] = ['value' => (string) $name, 'label' => (string) $name];
        }

        asort($owners);
        $ownerOptions = [];
        foreach ($owners as $key => $name) {
            $ownerOptions[] = ['value' => (string) $key, 'label' => (string) $name];
        }

        return [
            $catalogue,
            [
                'total' => $total,
                'published' => $published,
                'coming_soon' => $comingSoon,
                'native' => $native,
                'imported' => $imported,
            ],
            $categoryOptions,
            $ownerOptions,
        ];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'native' => 'Native',
            'scorm' => 'SCORM',
            'mixed' => 'Mixed',
            default => Str::title($type),
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'published' => 'Published',
            'coming_soon' => 'Coming soon',
            'retired' => 'Retired',
            default => Str::of($status)->replace('_', ' ')->title()->toString(),
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'published' => 'green',
            'coming_soon' => 'amber',
            'retired' => 'soft',
            default => 'neutral',
        };
    }

    private function scopeLabel(string $scope): string
    {
        return match ($scope) {
            'global' => 'All tenants',
            'allowlist' => 'Selected tenants',
            'private' => 'Private',
            'denylist' => 'All except',
            default => Str::title($scope),
        };
    }

    private function scopeTone(string $scope): string
    {
        return match ($scope) {
            'global' => 'brand',
            'allowlist' => 'amber',
            'private' => 'soft',
            'denylist' => 'amber',
            default => 'neutral',
        };
    }

    private function formatDate(mixed $raw): string
    {
        if (! is_string($raw) || $raw === '') {
            return '—';
        }

        try {
            return Carbon::parse($raw)->format('j M Y');
        } catch (\Throwable) {
            return substr($raw, 0, 10);
        }
    }
}
