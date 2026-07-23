<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Http\Requests\UpdateCoursePricingRequest;
use App\Support\Supabase\Contracts\WritesCoursePricing;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * The course pricing & retake/retry editor (migration 007: `course_pricing`,
 * `pricing_defaults`, `v_course_effective_pricing`). Owner-gated by the
 * `platform.owner` middleware; the write goes through {@see WritesCoursePricing}
 * (service-role), with Supabase RLS (`can_manage_course`) as defence-in-depth.
 *
 * Course catalogue/marketing/certification details live on
 * {@see CourseController::update()}; this slice owns only the commercial policy.
 */
final class CourseEditorController extends Controller
{
    /**
     * The pricing & retake/retry editor. Shows the course's own pricing row
     * (or the free default when it has none yet), the platform's per-mechanism
     * defaults, and the resolved "effective" policy from the view so the owner
     * can see what a learner actually gets before they override anything.
     */
    public function editPricing(Request $request, string $course): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $row = $this->loadCourse($course);
        if ($row === null) {
            abort(404);
        }

        return view('platform.courses.pricing', [
            'user' => $user,
            'course' => $row,
            'pricing' => $this->loadPricing($course),
            'defaults' => $this->pricingDefaults(),
            'effective' => $this->effectivePricing($course),
            'typeOptions' => [
                ['value' => 'free', 'label' => 'Free'],
                ['value' => 'one_off', 'label' => 'One-off payment'],
                ['value' => 'credits', 'label' => 'Credits'],
                ['value' => 'included_in_subscription', 'label' => 'Included in subscription'],
                ['value' => 'pay_as_you_go', 'label' => 'Pay as you go'],
            ],
        ]);
    }

    public function updatePricing(UpdateCoursePricingRequest $request, WritesCoursePricing $writer, string $course): RedirectResponse
    {
        try {
            $writer->upsertPricing($course, $request->pricingFields());
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.courses.pricing', $course)
                ->withInput()
                ->with('editorError', 'Pricing could not be saved right now. Please try again shortly.');
        }

        return redirect()->route('platform.courses.pricing', $course)
            ->with('status', 'Pricing saved.');
    }

    /**
     * Course identity row (title etc.) so the pricing page can show which course
     * it is editing. Returns null on a genuinely missing course (→ 404).
     *
     * @return array<string,mixed>|null
     */
    private function loadCourse(string $courseId): ?array
    {
        try {
            $response = $this->req()->get('/rest/v1/courses', [
                'select' => 'id,title,slug',
                'id' => 'eq.'.$courseId,
            ]);
        } catch (\Throwable) {
            abort(503, 'The course could not be loaded right now. Please try again shortly.');
        }

        if (! $response->successful()) {
            abort(503, 'The course could not be loaded right now. Please try again shortly.');
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows[0] ?? null;
    }

    /**
     * The course's own pricing row, or null if it has never been set (in which
     * case the form falls back to the free default and inherit-everything).
     *
     * @return array<string,mixed>|null
     */
    private function loadPricing(string $courseId): ?array
    {
        try {
            $response = $this->req()->get('/rest/v1/course_pricing', [
                'select' => '*',
                'course_id' => 'eq.'.$courseId,
            ]);
            if (! $response->successful()) {
                return null;
            }
            $rows = $response->json() ?? [];
        } catch (\Throwable) {
            return null;
        }

        return $rows[0] ?? null;
    }

    /**
     * Platform default retake/retry policy per pricing mechanism, keyed by
     * pricing_type so the form can show "inherited: …" next to each control.
     *
     * @return array<string,array<string,mixed>>
     */
    private function pricingDefaults(): array
    {
        try {
            $response = $this->req()->get('/rest/v1/pricing_defaults', ['select' => '*']);
            if (! $response->successful()) {
                return [];
            }
            $rows = $response->json() ?? [];
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $type = (string) ($r['pricing_type'] ?? '');
            if ($type !== '') {
                $out[$type] = $r;
            }
        }

        return $out;
    }

    /**
     * The resolved (course-override-else-default) policy from the view, so the
     * owner sees exactly what a learner gets. Null until pricing exists.
     *
     * @return array<string,mixed>|null
     */
    private function effectivePricing(string $courseId): ?array
    {
        try {
            $response = $this->req()->get('/rest/v1/v_course_effective_pricing', [
                'select' => '*',
                'course_id' => 'eq.'.$courseId,
            ]);
            if (! $response->successful()) {
                return null;
            }
            $rows = $response->json() ?? [];
        } catch (\Throwable) {
            return null;
        }

        return $rows[0] ?? null;
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
