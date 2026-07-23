{{--
 | Course card — one course in the learner library grid (and the "jump back in"
 | row). Every value is real catalogue / enrolment data passed in as $course.
 |
 | The cover slot shows the uploaded image from the `course-covers` bucket the
 | moment `cover_url` is set; until then it falls back to a restrained,
 | token-driven placeholder (no fabricated imagery). Design-system tokens only.
 |
 | @param  array  $course  Shaped course card (see CourseLibraryController::build()).
--}}
@props(['course'])

@php
    $state = $course['state'] ?? 'available';
    $dueToneClass = match ($course['due_tone'] ?? 'neutral') {
        'red' => 'bg-rag-red-soft text-rag-red',
        'amber' => 'bg-rag-amber-soft text-rag-amber',
        default => 'bg-line-soft text-ink-soft',
    };
    $search = strtolower(trim(($course['title'] ?? '').' '.($course['category'] ?? '').' '.($course['description'] ?? '')));
@endphp

<article data-course-card
         data-state="{{ $state }}"
         data-category="{{ $course['category'] ?? '' }}"
         data-title="{{ strtolower($course['title'] ?? '') }}"
         data-duration="{{ $course['duration_min'] ?? 0 }}"
         data-created="{{ $course['created_sort'] ?? '' }}"
         data-search="{{ $search }}"
         class="group flex flex-col overflow-hidden rounded-panel border border-line bg-surface shadow-quiet transition hover:-translate-y-0.5 hover:shadow-panel">

    {{-- Cover --}}
    <a href="{{ $course['href'] }}" tabindex="-1" aria-hidden="true"
       class="relative block aspect-video overflow-hidden bg-teachhq-soft">
        @if (! empty($course['cover_url']))
            <img src="{{ $course['cover_url'] }}" alt="" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            {{-- Token-driven placeholder until a real cover image is uploaded. --}}
            <span class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-teachhq-soft to-surface">
                <svg class="h-10 w-10 text-teachhq opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
            </span>
        @endif

        {{-- State ribbon --}}
        @if ($state === 'completed')
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-rag-green-soft px-2 py-0.5 text-mini font-semibold text-rag-green shadow-quiet">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                Completed
            </span>
        @elseif ($state === 'in_progress')
            <span class="absolute left-3 top-3 inline-flex items-center rounded-full bg-teachhq px-2 py-0.5 text-mini font-semibold text-on-brand shadow-quiet">In progress</span>
        @elseif ($state === 'coming_soon')
            <span class="absolute left-3 top-3 inline-flex items-center rounded-full bg-surface/90 px-2 py-0.5 text-mini font-semibold text-ink-muted shadow-quiet ring-1 ring-line">Coming soon</span>
        @elseif ($state === 'assigned' && ! empty($course['due_label']))
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-mini font-semibold shadow-quiet {{ $dueToneClass }}">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                {{ $course['due_label'] }}
            </span>
        @endif
    </a>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-4">
        <div class="mb-2 flex flex-wrap items-center gap-1.5">
            @if (! empty($course['category']))
                <span class="inline-flex items-center rounded-full bg-teachhq-soft px-2 py-0.5 text-micro font-semibold text-teachhq-dark">{{ $course['category'] }}</span>
            @endif
            @if (! empty($course['level']))
                <span class="inline-flex items-center rounded-full bg-line-soft px-2 py-0.5 text-micro font-semibold text-ink-muted">{{ $course['level'] }}</span>
            @endif
        </div>

        <h3 class="text-base font-bold leading-snug text-slatecard">
            <a href="{{ $course['href'] }}" class="transition hover:text-teachhq focus:outline-none focus-visible:underline">
                <span class="line-clamp-2">{{ $course['title'] }}</span>
            </a>
        </h3>

        @if (! empty($course['description']))
            <p class="mt-1.5 line-clamp-2 text-caption text-ink-soft">{{ $course['description'] }}</p>
        @endif

        @if ($state === 'in_progress')
            <div class="mt-3">
                <div class="mb-1 flex items-center justify-between text-mini font-semibold text-ink-soft">
                    <span>Resume where you left off</span>
                    <span class="tabular-nums text-teachhq-dark">{{ $course['progress'] }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-line-soft" role="progressbar" aria-valuenow="{{ $course['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Course progress">
                    <span class="block h-full rounded-full bg-teachhq" style="width: {{ $course['progress'] }}%"></span>
                </div>
            </div>
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-mini text-ink-soft">
            @if (! empty($course['duration_label']))
                <span class="inline-flex items-center gap-1">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    {{ $course['duration_label'] }}
                </span>
            @endif
            @if (! empty($course['cpd']))
                <span class="inline-flex items-center gap-1">
                    <svg class="h-3.5 w-3.5 text-teachhq" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                    CPD certified
                </span>
            @endif
        </div>

        <div class="mt-4 flex items-center justify-between gap-3 border-t border-line-soft pt-3">
            <span class="min-w-0 truncate text-caption font-semibold text-slatecard">{{ $course['price_label'] }}</span>

            @if ($state === 'coming_soon')
                <span class="inline-flex flex-none items-center rounded-control bg-line-soft px-3 py-1.5 text-mini font-semibold text-ink-soft">Coming soon</span>
            @else
                @php
                    $cta = match ($state) {
                        'in_progress' => 'Continue',
                        'completed' => 'Review',
                        'assigned' => 'Start now',
                        default => 'Start',
                    };
                @endphp
                <a href="{{ $course['href'] }}"
                   class="inline-flex flex-none items-center gap-1 rounded-control bg-button-primary px-3.5 py-1.5 text-mini font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
                    {{ $cta }}
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @endif
        </div>
    </div>
</article>
