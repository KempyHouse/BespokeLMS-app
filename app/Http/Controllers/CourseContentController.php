<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Http\Requests\ContentActionRequest;
use App\Http\Requests\UpdateSlideRequest;
use App\Support\Supabase\Contracts\WritesCourseContent;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * The course content builder — the module / lesson / slide outline for a course
 * (migration 003). All editing happens on a DRAFT version, so learners sitting
 * on the published version never see changes mid-flight; the author promotes a
 * draft through the workflow (migration 005) when it is ready.
 *
 * This slice owns the outline (structure + ordering + slide type). The rich
 * per-type slide payload editors (image+text, video, document) are the next
 * slice. Owner-gated by `platform.owner`; writes go through
 * {@see WritesCourseContent} (service-role) with RLS as defence-in-depth.
 */
final class CourseContentController extends Controller
{
    public function index(Request $request, string $course): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $row = $this->loadCourse($course);
        if ($row === null) {
            abort(404);
        }

        $draft = $this->workingDraft($course);
        $tree = $draft !== null ? $this->loadTree((string) $draft['id']) : [];

        return view('platform.courses.content', [
            'user' => $user,
            'course' => $row,
            'draft' => $draft,
            'versions' => $this->versions($course),
            'tree' => $tree,
        ]);
    }

    /**
     * Create a new draft version so the author can build/edit offline.
     */
    public function createDraft(Request $request, WritesCourseContent $writer, string $course): RedirectResponse
    {
        try {
            $versions = $this->versions($course);
            $nextNo = 1;
            foreach ($versions as $v) {
                $nextNo = max($nextNo, ((int) ($v['version_no'] ?? 0)) + 1);
            }
            $semver = $this->nextSemver($versions);

            $writer->createRow('course_versions', [
                'course_id' => $course,
                'version_no' => $nextNo,
                'semver' => $semver,
                'status' => 'draft',
            ]);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.courses.content', $course)
                ->with('editorError', 'The draft could not be created right now. Please try again shortly.');
        }

        return redirect()->route('platform.courses.content', $course)
            ->with('status', 'Draft version created — start adding modules below.');
    }

    /**
     * Dispatch a single builder action against the working draft.
     */
    public function handle(ContentActionRequest $request, WritesCourseContent $writer, string $course): RedirectResponse
    {
        $draft = $this->workingDraft($course);
        if ($draft === null) {
            return redirect()->route('platform.courses.content', $course)
                ->with('editorError', 'Create a draft version before editing content.');
        }
        $versionId = (string) $draft['id'];

        try {
            $this->dispatch($request, $writer, $versionId);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.courses.content', $course)
                ->with('editorError', 'That change could not be saved right now. Please try again shortly.');
        }

        return redirect()->route('platform.courses.content', $course)
            ->with('status', 'Saved.');
    }

    /**
     * The per-type payload editor for a single slide (image+text / video /
     * document) plus its completion rule.
     */
    public function editSlide(Request $request, string $course, string $slide): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $courseRow = $this->loadCourse($course);
        $slideRow = $this->loadSlide($slide);

        if ($courseRow === null || $slideRow === null || (string) ($slideRow['_course_id'] ?? '') !== $course) {
            abort(404);
        }

        return view('platform.courses.slide-edit', [
            'user' => $user,
            'course' => $courseRow,
            'slide' => $slideRow,
        ]);
    }

    public function updateSlide(UpdateSlideRequest $request, WritesCourseContent $writer, string $course, string $slide): RedirectResponse
    {
        $slideRow = $this->loadSlide($slide);
        if ($slideRow === null || (string) ($slideRow['_course_id'] ?? '') !== $course) {
            abort(404);
        }

        try {
            $writer->updateRow('slides', $slide, $request->slideFields());
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.courses.slides.edit', ['course' => $course, 'slide' => $slide])
                ->withInput()
                ->with('editorError', 'The slide could not be saved right now. Please try again shortly.');
        }

        return redirect()->route('platform.courses.slides.edit', ['course' => $course, 'slide' => $slide])
            ->with('status', 'Slide saved.');
    }

    private function dispatch(ContentActionRequest $request, WritesCourseContent $writer, string $versionId): void
    {
        $action = $request->action();
        $id = $request->id();
        $parent = $request->parentId();
        $title = $request->title();

        switch ($action) {
            case 'add_module':
                $writer->createRow('modules', [
                    'course_version_id' => $versionId,
                    'title' => $title !== '' ? $title : 'Untitled module',
                    'position' => $this->nextPosition('modules', 'course_version_id', $versionId),
                ]);
                break;

            case 'rename_module':
                if ($id !== null && $title !== '') {
                    $writer->updateRow('modules', $id, ['title' => $title]);
                }
                break;

            case 'delete_module':
                if ($id !== null) {
                    $writer->deleteRow('modules', $id);
                }
                break;

            case 'move_module':
                if ($id !== null) {
                    $this->move($writer, 'modules', 'course_version_id', $versionId, $id, $request->direction());
                }
                break;

            case 'add_lesson':
                if ($parent !== null) {
                    $writer->createRow('lessons', [
                        'module_id' => $parent,
                        'title' => $title !== '' ? $title : 'Untitled lesson',
                        'position' => $this->nextPosition('lessons', 'module_id', $parent),
                    ]);
                }
                break;

            case 'rename_lesson':
                if ($id !== null && $title !== '') {
                    $writer->updateRow('lessons', $id, ['title' => $title]);
                }
                break;

            case 'delete_lesson':
                if ($id !== null) {
                    $writer->deleteRow('lessons', $id);
                }
                break;

            case 'move_lesson':
                if ($id !== null && $parent !== null) {
                    $this->move($writer, 'lessons', 'module_id', $parent, $id, $request->direction());
                }
                break;

            case 'add_slide':
                if ($parent !== null) {
                    $writer->createRow('slides', [
                        'lesson_id' => $parent,
                        'type' => $request->slideType(),
                        'title' => $title !== '' ? $title : null,
                        'position' => $this->nextPosition('slides', 'lesson_id', $parent),
                    ]);
                }
                break;

            case 'rename_slide':
                if ($id !== null && $title !== '') {
                    $writer->updateRow('slides', $id, ['title' => $title]);
                }
                break;

            case 'delete_slide':
                if ($id !== null) {
                    $writer->deleteRow('slides', $id);
                }
                break;

            case 'move_slide':
                if ($id !== null && $parent !== null) {
                    $this->move($writer, 'slides', 'lesson_id', $parent, $id, $request->direction());
                }
                break;
        }
    }

    /**
     * Swap a row's position with its neighbour in the given direction.
     */
    private function move(WritesCourseContent $writer, string $table, string $parentCol, string $parentId, string $id, string $direction): void
    {
        $siblings = $this->siblings($table, $parentCol, $parentId);
        $idx = null;
        foreach ($siblings as $i => $s) {
            if ((string) ($s['id'] ?? '') === $id) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            return;
        }

        $swapWith = $direction === 'up' ? $idx - 1 : $idx + 1;
        if ($swapWith < 0 || $swapWith >= count($siblings)) {
            return; // already at the end
        }

        $a = $siblings[$idx];
        $b = $siblings[$swapWith];
        $writer->updateRow($table, (string) $a['id'], ['position' => (int) ($b['position'] ?? 0)]);
        $writer->updateRow($table, (string) $b['id'], ['position' => (int) ($a['position'] ?? 0)]);
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
     * A single slide with its resolved owning course id (via the
     * lesson→module→version chain) for the ownership check. Adds a synthetic
     * `_course_id` key. Returns null if the slide does not exist.
     *
     * @return array<string,mixed>|null
     */
    private function loadSlide(string $slideId): ?array
    {
        $rows = $this->get('/rest/v1/slides', [
            'select' => 'id,lesson_id,type,title,payload,is_required,completion_rule,'
                .'lessons(module_id,modules(course_version_id,course_versions(course_id)))',
            'id' => 'eq.'.$slideId,
        ]);

        $slide = $rows[0] ?? null;
        if ($slide === null) {
            return null;
        }

        $slide['_course_id'] = $slide['lessons']['modules']['course_versions']['course_id'] ?? '';

        return $slide;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function versions(string $courseId): array
    {
        return $this->get('/rest/v1/course_versions', [
            'select' => 'id,version_no,semver,status,created_at',
            'course_id' => 'eq.'.$courseId,
            'order' => 'version_no.desc',
        ]);
    }

    /**
     * The latest draft version to edit, or null if the course has none yet.
     *
     * @return array<string,mixed>|null
     */
    private function workingDraft(string $courseId): ?array
    {
        foreach ($this->versions($courseId) as $v) {
            if (($v['status'] ?? null) === 'draft') {
                return $v;
            }
        }

        return null;
    }

    /**
     * The full module→lesson→slide tree for a version, sorted by position.
     *
     * @return array<int,array<string,mixed>>
     */
    private function loadTree(string $versionId): array
    {
        $modules = $this->get('/rest/v1/modules', [
            'select' => 'id,title,position,lessons(id,title,position,slides(id,title,type,position,is_required))',
            'course_version_id' => 'eq.'.$versionId,
        ]);

        usort($modules, fn ($a, $b) => ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0)));
        foreach ($modules as &$m) {
            $lessons = $m['lessons'] ?? [];
            usort($lessons, fn ($a, $b) => ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0)));
            foreach ($lessons as &$l) {
                $slides = $l['slides'] ?? [];
                usort($slides, fn ($a, $b) => ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0)));
                $l['slides'] = $slides;
            }
            unset($l);
            $m['lessons'] = $lessons;
        }
        unset($m);

        return $modules;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function siblings(string $table, string $parentCol, string $parentId): array
    {
        $rows = $this->get("/rest/v1/{$table}", [
            'select' => 'id,position',
            $parentCol => 'eq.'.$parentId,
            'order' => 'position.asc',
        ]);

        return $rows;
    }

    private function nextPosition(string $table, string $parentCol, string $parentId): int
    {
        return count($this->siblings($table, $parentCol, $parentId));
    }

    /**
     * @param  array<int,array<string,mixed>>  $versions
     */
    private function nextSemver(array $versions): string
    {
        // Bump the minor of the highest semver we can parse; else start at 0.1.0.
        $bestMajor = 0;
        $bestMinor = 0;
        $found = false;
        foreach ($versions as $v) {
            if (preg_match('/^(\d+)\.(\d+)/', (string) ($v['semver'] ?? ''), $m)) {
                $found = true;
                $maj = (int) $m[1];
                $min = (int) $m[2];
                if ($maj > $bestMajor || ($maj === $bestMajor && $min > $bestMinor)) {
                    $bestMajor = $maj;
                    $bestMinor = $min;
                }
            }
        }

        return $found ? $bestMajor.'.'.($bestMinor + 1).'.0' : '0.1.0';
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
