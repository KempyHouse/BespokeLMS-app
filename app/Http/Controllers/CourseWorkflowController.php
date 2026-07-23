<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Http\Requests\WorkflowTransitionRequest;
use App\Support\Supabase\Contracts\WritesCourseVersion;
use App\Support\Supabase\Contracts\WritesCourseWorkflow;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * The editorial workflow / approval panel (migration 005) — a data-driven state
 * machine (draft → in review → approved → published, plus review-due / retired)
 * with separation of duties: the person who submits a version cannot be the one
 * who approves it. Managing the latest version of a course.
 *
 * Owner-gated by `platform.owner`. Transitions are recorded through
 * {@see WritesCourseWorkflow}; a transition into the published state also runs
 * the real version publish via {@see WritesCourseVersion}. Assignment and
 * checklist editing are a later slice; those are shown read-only here.
 */
final class CourseWorkflowController extends Controller
{
    /** Actions that record an approval decision, and the decision they map to. */
    private const APPROVAL_DECISIONS = [
        'approve' => 'approved',
        'reapprove' => 'approved',
        'request_changes' => 'changes_requested',
        'reject' => 'rejected',
    ];

    public function index(Request $request, string $course): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $courseRow = $this->loadCourse($course);
        if ($courseRow === null) {
            abort(404);
        }

        $version = $this->latestVersion($course);
        $states = $this->states();
        [$statesById, $statesByKey] = $this->stateMaps($states);

        $current = $version !== null ? $this->currentState((string) $version['id'], $statesByKey) : null;
        $transitions = $current !== null ? $this->transitionsFrom((string) $current['state_id'], $statesById) : [];

        return view('platform.courses.workflow', [
            'user' => $user,
            'course' => $courseRow,
            'version' => $version,
            'states' => $states,
            'current' => $current,
            'transitions' => $transitions,
            'history' => $version !== null ? $this->history((string) $version['id'], $statesById) : [],
            'assignments' => $this->assignments($course),
        ]);
    }

    public function transition(
        WorkflowTransitionRequest $request,
        WritesCourseWorkflow $writer,
        WritesCourseVersion $versionWriter,
        string $course,
    ): RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();
        $actor = $user->profileId ?? null;

        $version = $this->latestVersion($course);
        if ($version === null) {
            return $this->back($course, 'error', 'This course has no version to move through the workflow.');
        }
        $versionId = (string) $version['id'];

        $states = $this->states();
        [$statesById, $statesByKey] = $this->stateMaps($states);
        $current = $this->currentState($versionId, $statesByKey);
        if ($current === null) {
            return $this->back($course, 'error', 'The workflow could not be read right now. Please try again shortly.');
        }

        $action = $request->action();
        $transition = $this->findTransition((string) $current['state_id'], $action);
        if ($transition === null) {
            return $this->back($course, 'error', 'That action is not available from the current state.');
        }

        // Separation of duties: the actor who entered the current state cannot
        // also perform a transition that requires a distinct actor.
        $enteredBy = $current['entered_by'] ?? null;
        if (! empty($transition['requires_distinct_actor'])
            && $enteredBy !== null && $actor !== null && (string) $enteredBy === (string) $actor) {
            return $this->back($course, 'error', 'Separation of duties: a different person must perform this step from the one who submitted it.');
        }

        $toStateId = (string) $transition['to_state_id'];

        try {
            $writer->transition($versionId, (string) $current['state_id'], $toStateId, $action, $actor, $request->comment());

            if (isset(self::APPROVAL_DECISIONS[$action])) {
                $writer->recordApproval($versionId, $actor, self::APPROVAL_DECISIONS[$action], $request->comment());
            }

            // Entering the published state performs the real version publish.
            if (! empty(($statesById[$toStateId] ?? [])['is_published'])) {
                $versionWriter->publish($course, $versionId);
            }
        } catch (SupabaseAuthException $e) {
            report($e);

            return $this->back($course, 'error', 'That step could not be saved right now. Please try again shortly.');
        }

        $label = ($statesById[$toStateId] ?? [])['label'] ?? 'the next state';

        return $this->back($course, 'ok', 'Moved to '.$label.'.');
    }

    // ----------------------------------------------------------------- reads

    /**
     * @return array<string,mixed>|null
     */
    private function loadCourse(string $courseId): ?array
    {
        $rows = $this->get('/rest/v1/courses', ['select' => 'id,title,slug', 'id' => 'eq.'.$courseId]);

        return $rows[0] ?? null;
    }

    /**
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
     * @return array<int,array<string,mixed>>
     */
    private function states(): array
    {
        return $this->get('/rest/v1/workflow_states', [
            'select' => 'id,key,label,is_initial,is_published,is_terminal,sort',
            'organization_id' => 'is.null',
            'order' => 'sort.asc',
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $states
     * @return array{0:array<string,array<string,mixed>>,1:array<string,array<string,mixed>>}
     */
    private function stateMaps(array $states): array
    {
        $byId = [];
        $byKey = [];
        foreach ($states as $s) {
            $byId[(string) ($s['id'] ?? '')] = $s;
            $byKey[(string) ($s['key'] ?? '')] = $s;
        }

        return [$byId, $byKey];
    }

    /**
     * The version's current state row, falling back to the initial state when
     * a version has not been moved through the workflow yet.
     *
     * @param  array<string,array<string,mixed>>  $statesByKey
     * @return array<string,mixed>|null
     */
    private function currentState(string $versionId, array $statesByKey): ?array
    {
        $rows = $this->get('/rest/v1/course_workflow_state', [
            'select' => 'state_id,entered_by,entered_at',
            'course_version_id' => 'eq.'.$versionId,
        ]);

        if (! empty($rows[0]['state_id'])) {
            return $rows[0];
        }

        foreach ($statesByKey as $s) {
            if (! empty($s['is_initial'])) {
                return ['state_id' => (string) $s['id'], 'entered_by' => null, 'entered_at' => null];
            }
        }

        return null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function transitionsRaw(string $fromStateId): array
    {
        return $this->get('/rest/v1/workflow_transitions', [
            'select' => 'id,to_state_id,action,requires_distinct_actor,required_capability,sort',
            'from_state_id' => 'eq.'.$fromStateId,
            'organization_id' => 'is.null',
            'order' => 'sort.asc',
        ]);
    }

    /**
     * Transitions from a state, enriched with the destination label.
     *
     * @param  array<string,array<string,mixed>>  $statesById
     * @return array<int,array<string,mixed>>
     */
    private function transitionsFrom(string $fromStateId, array $statesById): array
    {
        $out = [];
        foreach ($this->transitionsRaw($fromStateId) as $t) {
            $t['to_label'] = ($statesById[(string) ($t['to_state_id'] ?? '')] ?? [])['label'] ?? '';
            $out[] = $t;
        }

        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findTransition(string $fromStateId, string $action): ?array
    {
        foreach ($this->transitionsRaw($fromStateId) as $t) {
            if ((string) ($t['action'] ?? '') === $action) {
                return $t;
            }
        }

        return null;
    }

    /**
     * @param  array<string,array<string,mixed>>  $statesById
     * @return array<int,array<string,mixed>>
     */
    private function history(string $versionId, array $statesById): array
    {
        $rows = $this->get('/rest/v1/course_workflow_history', [
            'select' => 'action,comment,at,from_state_id,to_state_id,actor_id,profiles:actor_id(full_name)',
            'course_version_id' => 'eq.'.$versionId,
            'order' => 'at.desc',
            'limit' => '25',
        ]);

        foreach ($rows as &$r) {
            $r['to_label'] = ($statesById[(string) ($r['to_state_id'] ?? '')] ?? [])['label'] ?? '';
            $r['from_label'] = ($statesById[(string) ($r['from_state_id'] ?? '')] ?? [])['label'] ?? '';
        }
        unset($r);

        return $rows;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function assignments(string $courseId): array
    {
        return $this->get('/rest/v1/course_assignments', [
            'select' => 'role,user_id,profiles:user_id(full_name,first_name,last_name)',
            'course_id' => 'eq.'.$courseId,
            'order' => 'role.asc',
        ]);
    }

    /**
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

    private function back(string $course, string $kind, string $message): RedirectResponse
    {
        $key = $kind === 'ok' ? 'status' : 'editorError';

        return redirect()->route('platform.courses.workflow', $course)->with($key, $message);
    }

    private function req(): \Illuminate\Http\Client\PendingRequest
    {
        /** @var array<string,mixed> $config */
        $config = config('services.supabase', []);
        $key = (string) ($config['service_role_key'] ?? '');

        return Http::baseUrl((string) ($config['url'] ?? ''))
            ->timeout((int) ($config['timeout'] ?? 10))
            ->acceptJson()
            ->withHeaders(['apikey' => $key])
            ->withToken($key);
    }
}
