<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsLearnerCatalogue;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * The learner Course Library — the browsable catalogue on the "My" workspace.
 *
 * Every card, stat and banner is driven by real data from the live schema
 * (courses + the signed-in user's enrolments, requirements and certificates).
 * A user with no enrolments simply sees the catalogue with empty personal
 * sections; nothing is fabricated. Because the LMS is dogfooded, a BespokeLMS
 * staff member browses and completes their own statutory / mandatory training
 * through exactly this page.
 */
final class CourseLibraryController extends Controller
{
    /** How many days ahead still counts as "due soon" for the assigned banner. */
    private const DUE_SOON_DAYS = 30;

    /**
     * The Course Library index — hero stats, an assigned-work banner, a
     * "jump back in" row for in-progress courses, and the full filterable grid.
     */
    public function index(Request $request, ReadsLearnerCatalogue $catalogue): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $data = $catalogue->forLearner($user->profileId, $user->organizationId, $user->role);
        } catch (SupabaseAuthException $e) {
            report($e);

            return view('my.courses.index', [
                'user' => $user,
                'libraryError' => 'The course library could not be loaded right now. Please try again shortly.',
                'courses' => [],
                'summary' => null,
                'assigned' => [],
                'inProgress' => [],
                'categoryOptions' => [],
                'statusOptions' => [],
            ]);
        }

        return view('my.courses.index', array_merge(
            ['user' => $user, 'libraryError' => null],
            $this->build($data, $user),
        ));
    }

    /**
     * A single course overview — the read-only learner view of one course:
     * its real metadata plus this user's own enrolment / completion state. The
     * lesson player itself arrives with the content-delivery slice (migration
     * 010); until then the content area shows an honest "coming" state rather
     * than fabricated lessons.
     */
    public function show(Request $request, ReadsLearnerCatalogue $catalogue, string $course): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $data = $catalogue->forLearner($user->profileId, $user->organizationId, $user->role);
        } catch (SupabaseAuthException $e) {
            report($e);
            abort(503, 'The course could not be loaded right now. Please try again shortly.');
        }

        $model = $this->build($data, $user);

        $found = null;
        foreach ($model['courses'] as $card) {
            if ($card['id'] === $course) {
                $found = $card;
                break;
            }
        }

        if ($found === null) {
            abort(404);
        }

        return view('my.courses.show', [
            'user' => $user,
            'course' => $found,
        ]);
    }

    /**
     * Assemble the whole view model from the raw catalogue payload.
     *
     * @param  array{courses:array<int,array<string,mixed>>,categories:array<string,string>,enrollments:array<int,array<string,mixed>>,requirements:array<int,array<string,mixed>>,certificates:array<int,array<string,mixed>>}  $data
     * @return array<string,mixed>
     */
    private function build(array $data, SupabaseUser $user): array
    {
        $categories = $data['categories'];

        // Personal rows, keyed by course for O(1) lookup.
        $enrollments = [];
        foreach ($data['enrollments'] as $row) {
            $courseId = (string) ($row['course_id'] ?? '');
            if ($courseId !== '') {
                $enrollments[$courseId] = $row;
            }
        }

        $certificates = [];
        foreach ($data['certificates'] as $row) {
            $courseId = (string) ($row['course_id'] ?? '');
            if ($courseId !== '') {
                $certificates[$courseId] = $row;
            }
        }

        // Mandatory course ids that apply to THIS user (org- or role-scoped).
        // Team-scoped requirements are resolved once the session carries a team.
        $requiredCourseIds = [];
        foreach ($data['requirements'] as $row) {
            $scope = (string) ($row['scope'] ?? '');
            $ref = (string) ($row['scope_ref'] ?? '');
            $applies = ($scope === 'org' && $ref === (string) $user->organizationId)
                || ($scope === 'role' && $ref === $user->role);
            if ($applies) {
                $requiredCourseIds[(string) ($row['course_id'] ?? '')] = true;
            }
        }

        $courses = [];
        $assigned = [];
        $inProgress = [];
        $categoryCounts = [];
        $total = 0;
        $availableNow = 0;
        $completedCount = 0;

        foreach ($data['courses'] as $c) {
            $status = (string) ($c['catalog_status'] ?? 'published');
            // The library shows the live catalogue only (never drafts / retired).
            if (! in_array($status, ['published', 'coming_soon'], true)) {
                continue;
            }

            $id = (string) ($c['id'] ?? '');
            $categoryId = (string) ($c['category_id'] ?? '');
            $categoryName = $categoryId !== '' ? ($categories[$categoryId] ?? null) : null;
            $enrollment = $enrollments[$id] ?? null;
            $hasCertificate = isset($certificates[$id]);

            $progress = (int) ($enrollment['progress_pct'] ?? 0);
            $enrolStatus = (string) ($enrollment['status'] ?? '');
            $dueAt = $enrollment['due_at'] ?? null;
            $isRequired = isset($requiredCourseIds[$id]);

            $comingSoon = $status === 'coming_soon';
            $completed = ! $comingSoon
                && ($enrolStatus === 'completed' || $progress >= 100 || $hasCertificate || ($enrollment['completed_at'] ?? null) !== null);
            $started = ! $completed && ($enrolStatus === 'inprogress' || ($progress > 0 && $progress < 100));
            $isAssigned = ! $completed && ! $comingSoon
                && (in_array($enrolStatus, ['assigned', 'overdue', 'duesoon'], true) || $isRequired || ($enrolStatus === 'notstarted' && $dueAt !== null));

            $state = match (true) {
                $comingSoon => 'coming_soon',
                $completed => 'completed',
                $started => 'in_progress',
                $isAssigned => 'assigned',
                default => 'available',
            };

            [$dueLabel, $dueTone, $daysLeft] = $this->dueMeta($dueAt);

            $card = [
                'id' => $id,
                'title' => (string) ($c['title'] ?? ''),
                'description' => ($c['description'] ?? null) !== null ? (string) $c['description'] : null,
                'category' => $categoryName,
                'level' => ($c['level'] ?? null) !== null ? (string) $c['level'] : null,
                'duration_label' => $this->durationLabel($c['duration_min'] ?? null),
                'duration_min' => (int) ($c['duration_min'] ?? 0),
                'cpd' => (($c['accreditation'] ?? null) !== null && $c['accreditation'] !== ''),
                'accreditation' => ($c['accreditation'] ?? null) !== null ? (string) $c['accreditation'] : null,
                'price_label' => $this->priceLabel($c['price_pennies'] ?? null, (int) ($c['credits'] ?? 0), $comingSoon),
                'status' => $status,
                'state' => $state,
                'progress' => $progress,
                'due_label' => $dueLabel,
                'due_tone' => $dueTone,
                'days_left' => $daysLeft,
                'cover_url' => $this->coverUrl($c['thumbnail_path'] ?? null),
                'href' => route('my.courses.show', $id),
                'created_sort' => is_string($c['created_at'] ?? null) ? (string) $c['created_at'] : '',
            ];

            $courses[] = $card;
            $total++;
            if (! $comingSoon) {
                $availableNow++;
            }
            if ($completed) {
                $completedCount++;
            }
            if ($categoryName !== null && $categoryName !== '') {
                $categoryCounts[$categoryName] = ($categoryCounts[$categoryName] ?? 0) + 1;
            }

            if ($state === 'assigned' && $daysLeft !== null && $daysLeft <= self::DUE_SOON_DAYS) {
                $assigned[] = $card;
            }
            if ($state === 'in_progress') {
                $inProgress[] = $card;
            }
        }

        // Assigned: soonest due first. In-progress: furthest-along first.
        usort($assigned, static fn (array $a, array $b): int => ($a['days_left'] ?? PHP_INT_MAX) <=> ($b['days_left'] ?? PHP_INT_MAX));
        usort($inProgress, static fn (array $a, array $b): int => $b['progress'] <=> $a['progress']);

        ksort($categoryCounts);
        $categoryOptions = [];
        foreach ($categoryCounts as $name => $count) {
            $categoryOptions[] = ['value' => (string) $name, 'label' => (string) $name, 'count' => $count];
        }

        return [
            'courses' => $courses,
            'summary' => [
                'total' => $total,
                'categories' => count($categoryCounts),
                'available_now' => $availableNow,
                'completed' => $completedCount,
            ],
            'assigned' => $assigned,
            'inProgress' => $inProgress,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => [
                ['value' => 'available', 'label' => 'Not started'],
                ['value' => 'in_progress', 'label' => 'In progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'assigned', 'label' => 'Assigned'],
                ['value' => 'coming_soon', 'label' => 'Coming soon'],
            ],
        ];
    }

    /**
     * Human duration from minutes, e.g. 30 -> "30 min", 90 -> "1h 30m".
     */
    private function durationLabel(mixed $minutes): ?string
    {
        $min = (int) ($minutes ?? 0);
        if ($min <= 0) {
            return null;
        }
        if ($min < 60) {
            return $min.' min';
        }
        $hours = intdiv($min, 60);
        $rem = $min % 60;

        return $rem === 0 ? $hours.'h' : $hours.'h '.$rem.'m';
    }

    /**
     * Price label from the real pricing columns. Coming-soon courses read
     * "Free on release" only when they carry no price.
     */
    private function priceLabel(mixed $pricePennies, int $credits, bool $comingSoon): string
    {
        $pennies = $pricePennies === null ? null : (int) $pricePennies;

        if ($pennies !== null && $pennies > 0) {
            $price = '£'.number_format($pennies / 100, 2);

            return $credits >= 1 ? $price.' or '.$credits.' credit'.($credits === 1 ? '' : 's') : $price;
        }

        return $comingSoon ? 'Free on release' : 'Free';
    }

    /**
     * Public cover-image URL from the stored path, or null when none is set yet.
     * Images live in the public `course-covers` Storage bucket; the card shows
     * a token-driven placeholder until a real cover is uploaded.
     */
    private function coverUrl(mixed $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            return null;
        }

        // Allow an absolute URL to pass through unchanged.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = rtrim((string) config('services.supabase.url'), '/');
        if ($base === '') {
            return null;
        }

        return $base.'/storage/v1/object/public/course-covers/'.ltrim($path, '/');
    }

    /**
     * Due-date badge label, tone and days remaining for an enrolment.
     *
     * @return array{0:?string,1:string,2:?int}
     */
    private function dueMeta(mixed $dueAt): array
    {
        if (! is_string($dueAt) || $dueAt === '') {
            return [null, 'neutral', null];
        }

        try {
            $due = Carbon::parse($dueAt)->startOfDay();
        } catch (\Throwable) {
            return [null, 'neutral', null];
        }

        $days = (int) Carbon::now()->startOfDay()->diffInDays($due, false);

        if ($days < 0) {
            return ['Overdue', 'red', $days];
        }
        if ($days === 0) {
            return ['Due today', 'red', 0];
        }
        if ($days <= 7) {
            return ['Due in '.$days.' day'.($days === 1 ? '' : 's'), 'amber', $days];
        }

        return ['Due '.$due->format('j M'), 'neutral', $days];
    }
}
