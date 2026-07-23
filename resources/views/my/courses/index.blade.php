@extends('layouts.app')

@section('title', 'Course Library · My')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <x-my-nav active="course-library" />

    <main class="min-w-0 flex-1">
        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-mini text-ink-soft" aria-label="Breadcrumb">
            <a href="{{ route('my.home') }}" class="transition hover:text-teachhq">My workspace</a>
            <svg class="h-3.5 w-3.5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span class="font-semibold text-slatecard">Course Library</span>
        </nav>

        @if (! empty($libraryError))
            <div class="rounded-panel border border-line bg-rag-red-soft p-6 text-sm text-rag-red" role="alert">
                {{ $libraryError }}
            </div>
        @else
            {{-- Hero --}}
            <section class="overflow-hidden rounded-panel border border-line bg-surface shadow-panel">
                <div class="flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0 max-w-2xl">
                        <h1 class="text-2xl font-black text-slatecard sm:text-3xl">Browse the Course Library</h1>
                        <p class="mt-2 text-sm text-ink-soft">Explore training across catering, hospitality, safety and wellbeing. Enrol in your own time and pick up exactly where you left off.</p>

                        <div class="relative mt-5 max-w-md">
                            <label for="library-search" class="sr-only">Search courses</label>
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input id="library-search" type="search" data-library-search autocomplete="off"
                                   placeholder="Search courses, topics or categories"
                                   class="w-full rounded-control border border-line bg-surface py-2.5 pl-11 pr-4 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-teachhq">
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid flex-none grid-cols-2 gap-3 lg:w-72">
                        @foreach ([
                            ['label' => 'Courses', 'value' => $summary['total'], 'tone' => 'plain'],
                            ['label' => 'Categories', 'value' => $summary['categories'], 'tone' => 'plain'],
                            ['label' => 'Available now', 'value' => $summary['available_now'], 'tone' => 'brand'],
                            ['label' => "You've completed", 'value' => $summary['completed'], 'tone' => 'green'],
                        ] as $stat)
                            <div @class([
                                    'rounded-control border p-3',
                                    'border-line bg-paper' => $stat['tone'] === 'plain',
                                    'border-line bg-teachhq-soft' => $stat['tone'] === 'brand',
                                    'border-line bg-rag-green-soft' => $stat['tone'] === 'green',
                                ])>
                                <div @class([
                                    'text-2xl font-black tabular-nums',
                                    'text-slatecard' => $stat['tone'] === 'plain',
                                    'text-teachhq-dark' => $stat['tone'] === 'brand',
                                    'text-rag-green' => $stat['tone'] === 'green',
                                ])>{{ $stat['value'] }}</div>
                                <div class="text-mini font-semibold text-ink-soft">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Assigned banner --}}
            @if (! empty($assigned))
                <div class="mt-5 flex flex-wrap items-center gap-4 rounded-panel border border-line bg-rag-amber-soft p-4 sm:p-5">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-surface text-rag-amber shadow-quiet">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slatecard">{{ count($assigned) }} {{ \Illuminate\Support\Str::plural('course', count($assigned)) }} assigned to you · due soon</p>
                        <p class="text-caption text-ink-soft">Required training set for your role or organisation — get these done first.</p>
                    </div>
                    <button type="button" onclick="window.__libraryFilterStatus &amp;&amp; window.__libraryFilterStatus('assigned')"
                            class="inline-flex flex-none items-center gap-1 rounded-control bg-surface px-3.5 py-2 text-mini font-semibold text-rag-amber shadow-quiet transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-rag-amber">
                        View assigned
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            @endif

            {{-- Jump back in --}}
            @if (! empty($inProgress))
                <section class="mt-8" aria-labelledby="jump-back-in-h">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 id="jump-back-in-h" class="text-xs font-bold uppercase tracking-wider text-teachhq">Jump back in</h2>
                        <span class="text-mini text-ink-faint">In progress</span>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($inProgress as $course)
                            <x-course-card :course="$course" />
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- All courses --}}
            <section class="mt-10" id="all-courses" aria-labelledby="all-courses-h">
                <h2 id="all-courses-h" class="mb-3 text-xs font-bold uppercase tracking-wider text-teachhq">All courses</h2>

                <div class="flex flex-col gap-3 rounded-panel border border-line bg-surface p-4 shadow-quiet lg:flex-row lg:items-center lg:justify-between">
                    {{-- Category chips --}}
                    <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter by category">
                        <button type="button" data-cat-chip="" aria-pressed="true"
                                class="cat-chip rounded-full bg-teachhq px-3 py-1.5 text-mini font-semibold text-on-brand transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            All <span class="ml-1 tabular-nums">{{ $summary['total'] }}</span>
                        </button>
                        @foreach ($categoryOptions as $cat)
                            <button type="button" data-cat-chip="{{ $cat['value'] }}" aria-pressed="false"
                                    class="cat-chip rounded-full border border-line px-3 py-1.5 text-mini font-semibold text-ink-muted transition hover:border-teachhq hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                                {{ $cat['label'] }} <span class="ml-1 tabular-nums text-ink-faint">{{ $cat['count'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Status + sort --}}
                    <div class="flex flex-none items-center gap-2">
                        <label for="library-status" class="sr-only">Filter by status</label>
                        <select id="library-status" data-library-status
                                class="rounded-control border border-line bg-surface py-2 pl-3 pr-8 text-mini font-semibold text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                        <label for="library-sort" class="sr-only">Sort courses</label>
                        <select id="library-sort" data-library-sort
                                class="rounded-control border border-line bg-surface py-2 pl-3 pr-8 text-mini font-semibold text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">
                            <option value="recommended">Sort: Recommended</option>
                            <option value="az">Title A–Z</option>
                            <option value="shortest">Shortest first</option>
                            <option value="newest">Newest first</option>
                        </select>
                    </div>
                </div>

                <p class="mt-4 text-mini text-ink-soft" aria-live="polite">
                    Showing <span data-library-shown class="font-semibold text-slatecard">{{ count($courses) }}</span> of {{ count($courses) }} {{ \Illuminate\Support\Str::plural('course', count($courses)) }}
                </p>

                @if (empty($courses))
                    <div class="mt-4 rounded-panel border border-dashed border-line bg-paper p-10 text-center">
                        <p class="text-sm font-semibold text-slatecard">No courses in the catalogue yet</p>
                        <p class="mt-1 text-caption text-ink-soft">Courses published to your organisation will appear here.</p>
                    </div>
                @else
                    <div data-library-grid class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($courses as $course)
                            <x-course-card :course="$course" />
                        @endforeach
                    </div>
                    <div data-library-empty class="mt-4 hidden rounded-panel border border-dashed border-line bg-paper p-10 text-center">
                        <p class="text-sm font-semibold text-slatecard">No courses match your filters</p>
                        <p class="mt-1 text-caption text-ink-soft">Try clearing the search or choosing a different category.</p>
                    </div>
                @endif
            </section>
        @endif
    </main>
</div>
@endsection

@push('scripts')
<script>
/* Course Library — client-side search / category / status filtering + sort.
   Operates only on the already-rendered, server-provided cards (no data is
   invented on the client); it just shows, hides and reorders them. */
(function () {
    var grid = document.querySelector('[data-library-grid]');
    if (!grid) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-course-card]'));
    var searchEl = document.querySelector('[data-library-search]');
    var statusEl = document.querySelector('[data-library-status]');
    var sortEl = document.querySelector('[data-library-sort]');
    var chips = Array.prototype.slice.call(document.querySelectorAll('[data-cat-chip]'));
    var shownEl = document.querySelector('[data-library-shown]');
    var emptyEl = document.querySelector('[data-library-empty]');

    var state = { q: '', category: '', status: '' };

    var CHIP_ON = 'cat-chip rounded-full bg-teachhq px-3 py-1.5 text-mini font-semibold text-on-brand transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq';
    var CHIP_OFF = 'cat-chip rounded-full border border-line px-3 py-1.5 text-mini font-semibold text-ink-muted transition hover:border-teachhq hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq';

    function apply() {
        var shown = 0;
        cards.forEach(function (card) {
            var matchQ = !state.q || (card.getAttribute('data-search') || '').indexOf(state.q) >= 0;
            var matchCat = !state.category || card.getAttribute('data-category') === state.category;
            var matchStatus = !state.status || card.getAttribute('data-state') === state.status;
            var visible = matchQ && matchCat && matchStatus;
            card.classList.toggle('hidden', !visible);
            if (visible) shown++;
        });
        if (shownEl) shownEl.textContent = shown;
        if (emptyEl) emptyEl.classList.toggle('hidden', shown !== 0);
    }

    function sortCards(mode) {
        var sorted = cards.slice().sort(function (a, b) {
            if (mode === 'az') return a.getAttribute('data-title').localeCompare(b.getAttribute('data-title'));
            if (mode === 'shortest') return (parseInt(a.getAttribute('data-duration'), 10) || 0) - (parseInt(b.getAttribute('data-duration'), 10) || 0);
            if (mode === 'newest') return (b.getAttribute('data-created') || '').localeCompare(a.getAttribute('data-created') || '');
            return 0; /* recommended = server order */
        });
        sorted.forEach(function (c) { grid.appendChild(c); });
    }

    if (searchEl) searchEl.addEventListener('input', function () { state.q = (this.value || '').trim().toLowerCase(); apply(); });
    if (statusEl) statusEl.addEventListener('change', function () { state.status = this.value; apply(); });
    if (sortEl) sortEl.addEventListener('change', function () { sortCards(this.value); });

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            state.category = chip.getAttribute('data-cat-chip');
            chips.forEach(function (c) {
                var on = c === chip;
                c.className = on ? CHIP_ON : CHIP_OFF;
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            apply();
        });
    });

    /* Called from the "View assigned" banner button. */
    window.__libraryFilterStatus = function (status) {
        if (statusEl) { statusEl.value = status; }
        state.status = status;
        apply();
        var section = document.getElementById('all-courses');
        if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
})();
</script>
@endpush
