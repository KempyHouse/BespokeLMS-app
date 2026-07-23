@extends('layouts.app')

@section('title', $course['title'].' · Course Library')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <x-my-nav active="course-library" />

    <main class="min-w-0 flex-1">
        {{-- Breadcrumb --}}
        <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-mini text-ink-soft" aria-label="Breadcrumb">
            <a href="{{ route('my.home') }}" class="transition hover:text-teachhq">My workspace</a>
            <svg class="h-3.5 w-3.5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('my.courses') }}" class="transition hover:text-teachhq">Course Library</a>
            <svg class="h-3.5 w-3.5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span class="min-w-0 truncate font-semibold text-slatecard">{{ $course['title'] }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-3">
            {{-- Main column --}}
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-panel border border-line bg-surface shadow-panel">
                    <div class="relative aspect-video overflow-hidden bg-teachhq-soft">
                        @if (! empty($course['cover_url']))
                            <img src="{{ $course['cover_url'] }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="absolute inset-0 flex items-center justify-center bg-teachhq-soft">
                                <svg class="h-14 w-14 text-teachhq" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
                            </span>
                        @endif
                    </div>

                    <div class="p-6 sm:p-8">
                        <div class="mb-3 flex flex-wrap items-center gap-1.5">
                            @if (! empty($course['category']))
                                <span class="inline-flex items-center rounded-full bg-teachhq-soft px-2.5 py-0.5 text-micro font-semibold text-teachhq-dark">{{ $course['category'] }}</span>
                            @endif
                            @if (! empty($course['level']))
                                <span class="inline-flex items-center rounded-full bg-line-soft px-2.5 py-0.5 text-micro font-semibold text-ink-muted">{{ $course['level'] }}</span>
                            @endif
                            @if ($course['state'] === 'coming_soon')
                                <span class="inline-flex items-center rounded-full bg-line-soft px-2.5 py-0.5 text-micro font-semibold text-ink-soft">Coming soon</span>
                            @endif
                        </div>

                        <h1 class="text-2xl font-black text-slatecard sm:text-3xl">{{ $course['title'] }}</h1>

                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-mini text-ink-soft">
                            @if (! empty($course['duration_label']))
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    {{ $course['duration_label'] }}
                                </span>
                            @endif
                            @if (! empty($course['cpd']))
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-teachhq" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                                    CPD certified{{ ! empty($course['accreditation']) ? ' · '.$course['accreditation'] : '' }}
                                </span>
                            @endif
                        </div>

                        @if (! empty($course['description']))
                            <div class="mt-6">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-teachhq">About this course</h2>
                                <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $course['description'] }}</p>
                            </div>
                        @endif

                        {{-- Content outline — honest empty state until the content module is live. --}}
                        <div class="mt-6 rounded-control border border-dashed border-line bg-paper p-6 text-center">
                            <svg class="mx-auto h-8 w-8 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
                            <p class="mt-2 text-sm font-semibold text-slatecard">Course lessons appear here</p>
                            <p class="mt-1 text-caption text-ink-soft">The lesson outline and player load once the course content module is live.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                    <p class="text-xs font-bold uppercase tracking-wider text-teachhq">Your progress</p>

                    @switch($course['state'])
                        @case('completed')
                            <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-rag-green-soft px-3 py-1 text-mini font-semibold text-rag-green">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                Completed
                            </div>
                            @break
                        @case('in_progress')
                            <div class="mt-3">
                                <div class="mb-1 flex items-center justify-between text-mini font-semibold text-ink-soft">
                                    <span>In progress</span>
                                    <span class="tabular-nums text-teachhq-dark">{{ $course['progress'] }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-line-soft" role="progressbar" aria-valuenow="{{ $course['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <span class="block h-full rounded-full bg-teachhq" style="width: {{ $course['progress'] }}%"></span>
                                </div>
                            </div>
                            @break
                        @case('assigned')
                            <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-rag-amber-soft px-3 py-1 text-mini font-semibold text-rag-amber">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                Assigned{{ ! empty($course['due_label']) ? ' · '.$course['due_label'] : '' }}
                            </div>
                            @break
                        @case('coming_soon')
                            <p class="mt-3 text-sm text-ink-soft">This course is not released yet.</p>
                            @break
                        @default
                            <p class="mt-3 text-sm text-ink-soft">Not started</p>
                    @endswitch

                    <div class="mt-4 flex items-center justify-between border-y border-line-soft py-3">
                        <span class="text-caption text-ink-soft">Enrolment</span>
                        <span class="text-caption font-semibold text-slatecard">{{ $course['price_label'] }}</span>
                    </div>

                    @if ($course['state'] === 'coming_soon')
                        <span class="mt-4 inline-flex w-full items-center justify-center rounded-control bg-line-soft px-4 py-2.5 text-sm font-semibold text-ink-soft">Coming soon</span>
                    @else
                        <button type="button" disabled
                                class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-control bg-button-primary px-4 py-2.5 text-sm font-semibold text-button-primary-text opacity-60"
                                title="The course player arrives with the content module">
                            <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                            {{ $course['state'] === 'in_progress' ? 'Continue' : ($course['state'] === 'completed' ? 'Review' : 'Start course') }}
                        </button>
                        <p class="mt-2 text-center text-micro text-ink-faint">The player unlocks when the content module goes live.</p>
                    @endif

                    <a href="{{ route('my.courses') }}" class="mt-4 inline-flex items-center gap-1.5 text-mini font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Back to Course Library
                    </a>
                </div>
            </aside>
        </div>
    </main>
</div>
@endsection
