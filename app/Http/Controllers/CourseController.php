<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsCourses;
use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Http\Requests\UpdateCourseRequest;
use App\Support\Supabase\Contracts\WritesAuditLog;
use App\Support\Supabase\Contracts\WritesCourses;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
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
    use \App\Http\Controllers\Mixins\CourseStatusLogic;
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
     * Course workspace — the read-only drill-in for one course: overview,
     * versions, language variants, workflow state and visibility. Editing and
     * the create flow arrive in later slices.
     */
    public function show(Request $request, ReadsCourses $courses, ReadsOrganizations $organizations, string $course): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $row = $courses->find($course);
        } catch (SupabaseAuthException $e) {
            report($e);
            abort(503, 'The course could not be loaded right now. Please try again shortly.');
        }

        if ($row === null) {
            abort(404);
        }

        $categoryOptions = [];
        try {
            $categories = $courses->categories();
            asort($categories);
            foreach ($categories as $catId => $catName) {
                $categoryOptions[] = ['value' => (string) $catId, 'label' => (string) $catName];
            }
        } catch (SupabaseAuthException) {
            $categoryOptions = [];
        }

        return view('platform.courses.show', [
            'user' => $user,
            'course' => $this->buildCourseDetail($row, $this->ownerNames($organizations)),
            'categoryOptions' => $categoryOptions,
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
     * Save the course-details editor form (catalogue / marketing / commercial
     * fields on `courses`). Owner-gated at the route; validated by
     * {@see UpdateCourseRequest}; written with the service-role key.
     */
    public function update(UpdateCourseRequest $request, WritesCourses $courses, WritesAuditLog $audit, string $course): RedirectResponse
    {
        $v = $request->validated();

        $fields = [
            'title' => (string) $v['title'],
            'category_id' => ($v['category_id'] ?? null) !== null && $v['category_id'] !== '' ? (string) $v['category_id'] : null,
            'content_type' => (string) $v['content_type'],
            'catalog_status' => (string) $v['catalog_status'],
            'level' => $this->nullableString($v['level'] ?? null),
            'duration_min' => ($v['duration_min'] ?? null) !== null && $v['duration_min'] !== '' ? (int) $v['duration_min'] : null,
            'description' => $this->nullableString($v['description'] ?? null),
            'accreditation' => $this->nullableString($v['accreditation'] ?? null),
            'cpd_points' => ($v['cpd_points'] ?? null) !== null && $v['cpd_points'] !== '' ? (float) $v['cpd_points'] : null,
            'cpd_body' => $this->nullableString($v['cpd_body'] ?? null),
            'issues_certificate' => $request->boolean('issues_certificate'),
            'certificate_validity' => $this->monthsToInterval($v['certificate_validity_months'] ?? null),
            'hero_image_path' => $this->nullableString($v['hero_image_path'] ?? null),
            'hero_image_alt' => $this->nullableString($v['hero_image_alt'] ?? null),
            'meta_title' => $this->nullableString($v['meta_title'] ?? null),
            'meta_description' => $this->nullableString($v['meta_description'] ?? null),
        ];

        try {
            $courses->updateDetails($course, $fields);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.courses.show', $course)
                ->withFragment('overview')
                ->with('courseError', 'The course could not be saved right now. Please try again shortly.');
        }

        /** @var SupabaseUser $user */
        $user = $request->user();
        $audit->record(
            action: 'course.updated',
            actorId: $user->profileId,
            organizationId: $user->organizationId,
            entity: 'course',
            entityId: $course,
            meta: ['fields_changed' => count($fields)],
        );

        return redirect()->route('platform.courses.show', $course)
            ->withFragment('overview')
            ->with('status', 'Course details saved.');
    }

    /** Trim a string input to null when empty. */
    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === null || $value === '') ? null : (string) $value;
    }


    /**
     * Handle Slice 9 course update with action-based status workflow.
     * Validates fields, then routes to save_draft or publish handler.
     */
    public function updateCourseEditor(
        UpdateCourseRequest $request,
        string $course,
    ): RedirectResponse {
        /** @var \App\Auth\SupabaseUser $user */
        $user = $request->user();
        $actor = $user->profileId ?? null;

        $validated = $request->validated();
        $action = $request->input('action', 'save_draft');

        try {
            if ($action === 'publish') {
                $this->actionPublish($course, $validated, $actor, null, null, null);
                $message = 'Course published successfully.';
            } else {
                // save_draft (default)
                $this->actionSaveDraft($course, $validated, $actor, null);
                $message = 'Course saved as draft.';
            }
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('platform.courses.edit', $course)
                ->with('editorError', 'Your changes could not be saved. Please try again.');
        }

        return redirect()->route('platform.courses.edit', $course)
            ->with('status', $message);
    }
    /**
     * Show course edit form with Slice 9 action buttons.
     */
    public function edit(Request $request, string $course): View
    {
        $courseRow = $this->loadCourse($course);
        if ($courseRow === null) {
            abort(404);
        }

        $version = $this->latestVersion($course);
        $hasApprovalWorkflow = $this->courseHasApprovalWorkflow($course);
        $workflowCurrent = $version !== null ? $this->currentWorkflowState((string) $version['id']) : null;

        $readiness = $this->readinessChecks($courseRow, $version, $hasApprovalWorkflow);
        $publishGate = $this->publishReadiness($courseRow, $version, $hasApprovalWorkflow, $workflowCurrent);

        return view('platform.courses.edit', [
            'course' => $courseRow,
            'version' => $version,
            'hasApprovalWorkflow' => $hasApprovalWorkflow,
            'readinessChecks' => $readiness,
            'publishDisabled' => $publishGate['disabled'],
            'publishDisabledReason' => $publishGate['reason'],
            'statusLabel' => $this->statusLabel($courseRow, $version, $workflowCurrent, $hasApprovalWorkflow),
            'statusBadgeClass' => $this->statusBadgeClass($courseRow, $version, $workflowCurrent, $hasApprovalWorkflow),
        ]);
    }

    /** Months (int) -> a Postgres interval string, or null (0 = never expires). */
    private function monthsToInterval(mixed $months): ?string
    {
        if ($months === null || $months === '') {
            return null;
        }
        $m = (int) $months;

        return $m > 0 ? $m.' months' : null;
    }

    /** Best-effort parse a Postgres interval text (e.g. "1 year 6 mons") to whole months. */
    private function intervalToMonths(mixed $raw): ?int
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $months = 0;
        $matched = false;
        if (preg_match('/(\\d+)\\s*year/i', $raw, $mm)) {
            $months += ((int) $mm[1]) * 12;
            $matched = true;
        }
        if (preg_match('/(\\d+)\\s*mon/i', $raw, $mm)) {
            $months += (int) $mm[1];
            $matched = true;
        }
        if (! $matched && preg_match('/(\\d+)\\s*day/i', $raw, $mm)) {
            $months = (int) round(((int) $mm[1]) / 30);
            $matched = true;
        }

        return $matched ? $months : null;
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

    /**
     * Shape one enriched course row into the workspace detail model.
     *
     * @param  array<string,mixed>  $c
     * @param  array<string,string>  $ownerNames
     * @return array<string,mixed>
     */
    private function buildCourseDetail(array $c, array $ownerNames): array
    {
        $ownerOrgId = $c['owner_org_id'] ?? null;
        $ownerIsPlatform = $ownerOrgId === null;
        $type = (string) ($c['content_type'] ?? 'native');
        $status = (string) ($c['catalog_status'] ?? 'published');
        $scope = (string) ($c['visibility_scope'] ?? 'global');

        $versions = [];
        foreach (($c['versions'] ?? []) as $v) {
            $vStatus = (string) ($v['status'] ?? '');
            $versions[] = [
                'version_no' => (int) ($v['version_no'] ?? 0),
                'semver' => (string) ($v['semver'] ?? ''),
                'status' => $vStatus,
                'status_label' => $this->versionStatusLabel($vStatus),
                'status_tone' => $this->versionStatusTone($vStatus),
                'published_label' => $this->formatDate($v['published_at'] ?? null),
                'review_due_label' => $this->formatDate($v['review_due_at'] ?? null),
                'changelog' => ($v['changelog'] ?? null) !== null ? (string) $v['changelog'] : null,
            ];
        }

        return [
            'id' => (string) ($c['id'] ?? ''),
            'title' => (string) ($c['title'] ?? ''),
            'description' => ($c['description'] ?? null) !== null ? (string) $c['description'] : null,
            'category_id' => ($c['category_id'] ?? null) !== null ? (string) $c['category_id'] : null,
            'level' => ($c['level'] ?? null) !== null ? (string) $c['level'] : null,
            'duration_min' => ($c['duration_min'] ?? null) !== null ? (int) $c['duration_min'] : null,
            'accreditation' => ($c['accreditation'] ?? null) !== null ? (string) $c['accreditation'] : null,
            'cpd_points' => ($c['cpd_points'] ?? null) !== null ? (string) $c['cpd_points'] : null,
            'cpd_body' => ($c['cpd_body'] ?? null) !== null ? (string) $c['cpd_body'] : null,
            'issues_certificate' => (bool) ($c['issues_certificate'] ?? true),
            'certificate_validity_months' => $this->intervalToMonths($c['certificate_validity'] ?? null),
            'hero_image_path' => ($c['hero_image_path'] ?? null) !== null ? (string) $c['hero_image_path'] : null,
            'hero_image_alt' => ($c['hero_image_alt'] ?? null) !== null ? (string) $c['hero_image_alt'] : null,
            'meta_title' => ($c['meta_title'] ?? null) !== null ? (string) $c['meta_title'] : null,
            'meta_description' => ($c['meta_description'] ?? null) !== null ? (string) $c['meta_description'] : null,
            'slug' => (string) ($c['slug'] ?? ''),
            'owner_name' => $ownerIsPlatform ? 'Platform' : ($ownerNames[(string) $ownerOrgId] ?? 'Operator'),
            'owner_is_platform' => $ownerIsPlatform,
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_tone' => $this->statusTone($status),
            'category' => ($c['category_name'] ?? null) !== null ? (string) $c['category_name'] : null,
            'scope' => $scope,
            'scope_label' => $this->scopeLabel($scope),
            'scope_tone' => $this->scopeTone($scope),
            'entitlement_count' => (int) ($c['entitlement_count'] ?? 0),
            'versions' => $versions,
            'current_version' => isset($c['current_version']['semver']) ? (string) $c['current_version']['semver'] : null,
            'locales' => array_values($c['locales'] ?? []),
            'workflow_state' => ($c['workflow_state'] ?? null) !== null ? (string) $c['workflow_state'] : null,
            'review_due_label' => $this->formatDate($c['review_due_at'] ?? null),
            'created_label' => $this->formatDate($c['created_at'] ?? null),
            'updated_label' => $this->formatDate($c['updated_at'] ?? ($c['created_at'] ?? null)),
        ];
    }

    private function versionStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
            default => Str::title($status),
        };
    }

    private function versionStatusTone(string $status): string
    {
        return match ($status) {
            'published' => 'green',
            'archived' => 'soft',
            'draft' => 'neutral',
            default => 'neutral',
        };
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

    /**
     * Load a course by ID.
     *
     * @return array<string,mixed>|null
     */
    private function loadCourse(string $courseId): ?array
    {
        $rows = $this->get('/rest/v1/courses', [
            'select' => 'id,title,description,aims,aims_short,objectives_short,slug,status',
            'id' => 'eq.'.$courseId,
        ]);

        return $rows[0] ?? null;
    }

    /**
     * Get latest version of a course.
     *
     * @return array<string,mixed>|null
     */
    private function latestVersion(string $courseId): ?array
    {
        $rows = $this->get('/rest/v1/course_versions', [
            'select' => 'id,version_no,semver,status',
            'course_id' => 'eq.'.$courseId,
            'order' => 'version_no.desc',
            'limit' => '1',
        ]);

        return $rows[0] ?? null;
    }

    /**
     * Make a GET request to Supabase PostgREST API.
     *
     * @param  array<string,mixed>  $query
     * @return array<int,array<string,mixed>>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->req()->get($path, $query);
            if (! $response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get a configured HTTP client for Supabase API calls.
     */
    private function req(): \Illuminate\Http\Client\PendingRequest
    {
        /** @var array<string,mixed> $config */
        $config = config('services.supabase', []);
        $key = (string) ($config['service_role_key'] ?? '');

        return \Illuminate\Support\Facades\Http::baseUrl((string) ($config['url'] ?? ''))
            ->timeout((int) ($config['timeout'] ?? 10))
            ->acceptJson()
            ->withHeaders(['apikey' => $key])
            ->withToken($key);
    }

    /**
     * Load a course by ID.
     *
     * @return array<string,mixed>|null
     */
    private function loadCourse(string $courseId): ?array
    {
        $rows = $this->get('/rest/v1/courses', [
            'select' => 'id,title,description,aims,aims_short,objectives_short,slug,status',
            'id' => 'eq.'.$courseId,
        ]);

        return $rows[0] ?? null;
    }

    /**
     * Get latest version of a course.
     *
     * @return array<string,mixed>|null
     */
    private function latestVersion(string $courseId): ?array
    {
        $rows = $this->get('/rest/v1/course_versions', [
            'select' => 'id,version_no,semver,status',
            'course_id' => 'eq.'.$courseId,
            'order' => 'version_no.desc',
            'limit' => '1',
        ]);

        return $rows[0] ?? null;
    }

    /**
     * Make a GET request to Supabase PostgREST API.
     *
     * @param  array<string,mixed>  $query
     * @return array<int,array<string,mixed>>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->req()->get($path, $query);
            if (! $response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get a configured HTTP client for Supabase API calls.
     */
    private function req(): \Illuminate\Http\Client\PendingRequest
    {
        /** @var array<string,mixed> $config */
        $config = config('services.supabase', []);
        $key = (string) ($config['service_role_key'] ?? '');

        return \Illuminate\Support\Facades\Http::baseUrl((string) ($config['url'] ?? ''))
            ->timeout((int) ($config['timeout'] ?? 10))
            ->acceptJson()
            ->withHeaders(['apikey' => $key])
            ->withToken($key);
    }
}
