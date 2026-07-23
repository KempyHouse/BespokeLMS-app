<?php

declare(strict_types=1);

namespace App\Support\Widgets;

use Carbon\CarbonImmutable;

/**
 * Computes the dashboard widget metrics from raw Supabase rows.
 *
 * Pure, side-effect-free calculation kept out of the controllers and readers so
 * it is trivially testable. Every figure is derived from real rows — enrolments,
 * certificates, learning attempts, organisations, profiles and integrations.
 * When a profile has no learning rows the personal metrics come back empty
 * (has_data = false), which the widgets render as an honest "nothing yet" state
 * rather than fabricated zeros with invented trends.
 *
 * Comparison deltas are only produced where they can be derived truthfully from
 * timestamps (completed_at, due_at, assigned_at): point-in-time metrics with no
 * historical basis (e.g. "in progress") deliberately carry no delta.
 */
final class WidgetMetrics
{
    /**
     * @param  array{enrollments:array<int,array<string,mixed>>,certificates:array<int,array<string,mixed>>,attempts:array<int,array<string,mixed>>,course_titles:array<string,string>}  $raw
     * @return array<string,mixed>
     */
    public static function personal(array $raw, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $titles = $raw['course_titles'] ?? [];

        // The learning universe: real enrolments (exclude the "not assigned" state).
        $universe = [];
        foreach ($raw['enrollments'] ?? [] as $e) {
            if ((string) ($e['status'] ?? '') === 'notassigned') {
                continue;
            }
            $universe[] = $e;
        }

        $total = count($universe);
        $hasData = $total > 0;

        $isCompleted = static function (array $e): bool {
            return (string) ($e['status'] ?? '') === 'completed'
                || ($e['completed_at'] ?? null) !== null
                || (int) ($e['progress_pct'] ?? 0) >= 100;
        };

        $completedCount = 0;
        foreach ($universe as $e) {
            if ($isCompleted($e)) {
                $completedCount++;
            }
        }

        // --- Overdue ---------------------------------------------------------
        $overdueNow = 0;
        $dueWithin7 = 0;
        $oldestDaysLate = null;
        $weekAgo = $now->subDays(7);
        $overdueWeekAgo = 0;
        $in7 = $now->addDays(7);

        foreach ($universe as $e) {
            $due = self::parse($e['due_at'] ?? null);
            $completedAt = self::parse($e['completed_at'] ?? null);
            $done = $isCompleted($e);

            if (! $done && $due !== null && $due->lessThan($now)) {
                $overdueNow++;
                $late = (int) $due->startOfDay()->diffInDays($now->startOfDay());
                $oldestDaysLate = $oldestDaysLate === null ? $late : max($oldestDaysLate, $late);
            }
            if (! $done && $due !== null && $due->greaterThanOrEqualTo($now) && $due->lessThanOrEqualTo($in7)) {
                $dueWithin7++;
            }
            // Overdue as of a week ago: due before then, and not yet completed then.
            if ($due !== null && $due->lessThan($weekAgo)
                && ($completedAt === null || $completedAt->greaterThan($weekAgo))) {
                $overdueWeekAgo++;
            }
        }

        // --- To complete: new assignments in the last 7 days -----------------
        $assignedThisWeek = 0;
        foreach ($universe as $e) {
            $assigned = self::parse($e['assigned_at'] ?? null);
            if ($assigned !== null && $assigned->greaterThanOrEqualTo($weekAgo)) {
                $assignedThisWeek++;
            }
        }

        // --- In progress -----------------------------------------------------
        $inProgress = 0;
        $resume = null;
        $bestProgress = -1;
        foreach ($universe as $e) {
            $progress = (int) ($e['progress_pct'] ?? 0);
            $status = (string) ($e['status'] ?? '');
            $started = ! $isCompleted($e) && ($status === 'inprogress' || ($progress > 0 && $progress < 100));
            if ($started) {
                $inProgress++;
                if ($progress > $bestProgress) {
                    $bestProgress = $progress;
                    $courseId = (string) ($e['course_id'] ?? '');
                    $resume = [
                        'title' => $titles[$courseId] ?? 'Your course',
                        'progress' => $progress,
                        'course_id' => $courseId,
                    ];
                }
            }
        }

        // --- Monthly completion + learning-time buckets (last 12 months) -----
        $months = [];
        $bucketIndex = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $now->startOfMonth()->subMonths($i);
            $bucketIndex[$m->format('Y-m')] = count($months);
            $months[] = ['label' => $m->format('M'), 'count' => 0, 'seconds' => 0];
        }

        foreach ($universe as $e) {
            $completedAt = self::parse($e['completed_at'] ?? null);
            if ($completedAt === null) {
                continue;
            }
            $key = $completedAt->format('Y-m');
            if (isset($bucketIndex[$key])) {
                $months[$bucketIndex[$key]]['count']++;
            }
        }
        foreach ($raw['attempts'] ?? [] as $a) {
            $seconds = self::attemptSeconds($a);
            if ($seconds <= 0) {
                continue;
            }
            $end = self::parse($a['completed_at'] ?? null);
            if ($end === null) {
                continue;
            }
            $key = $end->format('Y-m');
            if (isset($bucketIndex[$key])) {
                $months[$bucketIndex[$key]]['seconds'] += $seconds;
            }
        }

        $completedTrend = array_map(static fn (array $m): int => (int) $m['count'], $months);
        $secondsTrend = array_map(static fn (array $m): int => (int) $m['seconds'], $months);
        $completed12m = array_sum($completedTrend);
        $seconds12m = array_sum($secondsTrend);

        return [
            'has_data' => $hasData,
            'overdue' => [
                'count' => $overdueNow,
                'due_within_7' => $dueWithin7,
                'oldest_days_late' => $oldestDaysLate,
                'delta' => ['wow' => $overdueNow - $overdueWeekAgo],
            ],
            'to_complete' => [
                'count' => max(0, $total - $completedCount),
                'total' => $total,
                'completed' => $completedCount,
                'delta' => ['wow' => $assignedThisWeek],
            ],
            'completion_rate' => [
                'pct' => $total > 0 ? (int) round($completedCount / $total * 100) : 0,
                'delta' => [
                    'wow' => self::rateDelta($universe, $now, $weekAgo),
                    'mom' => self::rateDelta($universe, $now, $now->subMonth()),
                    'yoy' => self::rateDelta($universe, $now, $now->subYear()),
                ],
                'trend' => array_slice($completedTrend, 3), // last 9 months
            ],
            'in_progress' => [
                'count' => $inProgress,
                'resume' => $resume,
            ],
            'completed' => [
                'count' => $completed12m,
                'delta' => [
                    'mom' => self::countCompleted($universe, $now->subMonth(), $now)
                        - self::countCompleted($universe, $now->subMonths(2), $now->subMonth()),
                    'yoy' => $completed12m
                        - self::countCompleted($universe, $now->subYears(2), $now->subYear()),
                ],
                'trend' => $completedTrend,
            ],
            'training_time' => [
                'seconds' => $seconds12m,
                'delta' => [
                    'mom' => self::sumSeconds($raw['attempts'] ?? [], $now->subMonth(), $now)
                        - self::sumSeconds($raw['attempts'] ?? [], $now->subMonths(2), $now->subMonth()),
                    'yoy' => $seconds12m
                        - self::sumSeconds($raw['attempts'] ?? [], $now->subYears(2), $now->subYear()),
                ],
                'trend' => $secondsTrend,
                'months' => $months,
            ],
        ];
    }

    /**
     * @param  array{organizations:array<int,array<string,mixed>>,profiles:array<int,array<string,mixed>>,ai_integrations:array<int,array<string,mixed>>,email_integrations:array<int,array<string,mixed>>}  $raw
     * @return array<string,mixed>
     */
    public static function platform(array $raw, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();

        $operators = 0;
        $clients = 0;
        $subtypes = [];
        foreach ($raw['organizations'] ?? [] as $org) {
            $type = (string) ($org['type'] ?? '');
            if ($type === 'operator') {
                $operators++;
                $sub = (string) ($org['operator_subtype'] ?? '');
                if ($sub !== '') {
                    $subtypes[$sub] = ($subtypes[$sub] ?? 0) + 1;
                }
            } elseif ($type === 'client') {
                $clients++;
            }
        }

        $usersTotal = 0;
        $usersActive = 0;
        $recentlyActive = 0;
        $recentCutoff = $now->subDays(30);
        foreach ($raw['profiles'] ?? [] as $p) {
            $usersTotal++;
            if ((string) ($p['employment_status'] ?? '') === 'active') {
                $usersActive++;
            }
            $last = self::parse($p['last_active_at'] ?? null);
            if ($last !== null && $last->greaterThanOrEqualTo($recentCutoff)) {
                $recentlyActive++;
            }
        }

        $providers = [];
        $connected = 0;
        $issues = 0;
        foreach ([['ai_integrations', 'ai'], ['email_integrations', 'email']] as [$bucket, $kind]) {
            foreach ($raw[$bucket] ?? [] as $row) {
                $status = (string) ($row['status'] ?? 'unconfigured');
                if ($status === 'connected') {
                    $connected++;
                } elseif ($status === 'error') {
                    $issues++;
                }
                $providers[] = [
                    'name' => (string) ($row['display_name'] ?? '') !== ''
                        ? (string) $row['display_name']
                        : self::titleise((string) ($row['provider'] ?? 'Provider')),
                    'status' => $status,
                    'enabled' => (bool) ($row['is_enabled'] ?? false),
                    'kind' => $kind,
                ];
            }
        }

        return [
            'tenant_estate' => [
                'tenants' => $operators + $clients,
                'operators' => $operators,
                'clients' => $clients,
                'subtypes' => $subtypes,
            ],
            'platform_users' => [
                'total' => $usersTotal,
                'active' => $usersActive,
                'recently_active' => $recentlyActive,
            ],
            'integration_health' => [
                'connected' => $connected,
                'issues' => $issues,
                'total' => count($providers),
                'providers' => $providers,
            ],
        ];
    }

    /**
     * The completion-rate change (percentage points) between "now" and an
     * earlier instant, reconstructed from assigned_at/completed_at timestamps.
     *
     * @param  array<int,array<string,mixed>>  $universe
     */
    private static function rateDelta(array $universe, CarbonImmutable $now, CarbonImmutable $then): int
    {
        return self::rateAsOf($universe, $now) - self::rateAsOf($universe, $then);
    }

    /**
     * @param  array<int,array<string,mixed>>  $universe
     */
    private static function rateAsOf(array $universe, CarbonImmutable $at): int
    {
        $assigned = 0;
        $completed = 0;
        foreach ($universe as $e) {
            $assignedAt = self::parse($e['assigned_at'] ?? null) ?? self::parse($e['created_at'] ?? null);
            if ($assignedAt !== null && $assignedAt->lessThanOrEqualTo($at)) {
                $assigned++;
                $completedAt = self::parse($e['completed_at'] ?? null);
                if ($completedAt !== null && $completedAt->lessThanOrEqualTo($at)) {
                    $completed++;
                }
            }
        }

        return $assigned > 0 ? (int) round($completed / $assigned * 100) : 0;
    }

    /**
     * @param  array<int,array<string,mixed>>  $universe
     */
    private static function countCompleted(array $universe, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $n = 0;
        foreach ($universe as $e) {
            $completedAt = self::parse($e['completed_at'] ?? null);
            if ($completedAt !== null && $completedAt->greaterThan($from) && $completedAt->lessThanOrEqualTo($to)) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param  array<int,array<string,mixed>>  $attempts
     */
    private static function sumSeconds(array $attempts, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $total = 0;
        foreach ($attempts as $a) {
            $end = self::parse($a['completed_at'] ?? null);
            if ($end !== null && $end->greaterThan($from) && $end->lessThanOrEqualTo($to)) {
                $total += self::attemptSeconds($a);
            }
        }

        return $total;
    }

    /**
     * Elapsed learning seconds for one attempt (completed_at − started_at),
     * clamped to a sane range so a bad row cannot dominate a total.
     *
     * @param  array<string,mixed>  $a
     */
    private static function attemptSeconds(array $a): int
    {
        $start = self::parse($a['started_at'] ?? null);
        $end = self::parse($a['completed_at'] ?? null);
        if ($start === null || $end === null || $end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $seconds = (int) $start->diffInSeconds($end);

        // Ignore implausible spans (> 24h) — treat as a data glitch, not time spent.
        return $seconds > 0 && $seconds <= 86_400 ? $seconds : 0;
    }

    private static function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function titleise(string $value): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $value));
    }
}
