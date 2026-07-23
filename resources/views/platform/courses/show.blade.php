@extends('layouts.app')

@section('title', $course['title'].' · Global Courses')

@php
    $toneClass = [
        'brand' => 'bg-teachhq-soft text-teachhq-dark',
        'neutral' => 'border border-line bg-surface text-ink-muted',
        'green' => 'bg-rag-green-soft text-rag-green',
        'amber' => 'bg-rag-amber-soft text-rag-amber',
        'red' => 'bg-rag-red-soft text-rag-red',
        'soft' => 'bg-line-soft text-ink-soft',
    ];
    $badge = fn (string $value, string $tone) => '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-micro font-bold '.($toneClass[$tone] ?? $toneClass['neutral']).'">'.e($value).'</span>';
@endphp

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none rail-sticky">
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.courses') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Global Courses
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Course</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">{{ $course['title'] }}</h1>
            <div class="mt-3 flex flex-wrap gap-1.5">
                {!! $badge($course['type_label'], $course['type'] === 'native' ? 'neutral' : 'brand') !!}
                {!! $badge($course['status_label'], $course['status_tone']) !!}
                {!! $badge($course['scope_label'], $course['scope_tone']) !!}
            </div>
            <nav class="mt-5 flex flex-col gap-1 border-t border-line pt-4 text-sm" aria-label="Course sections">
                @foreach ([
                    ['overview', 'Overview'],
                    ['versions', 'Versions'],
                    ['languages', 'Languages'],
                    ['workflow', 'Workflow & approval'],
                    ['visibility', 'Visibility'],
                ] as [$anchor, $label])
                    <a href="#{{ $anchor }}" class="rounded-control px-2 py-1.5 font-medium text-slatecard transition hover:bg-surface hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
                <h2 class="mt-1 text-2xl font-black text-slatecard">{{ $course['title'] }}</h2>
                <p class="mt-1 text-sm text-ink-soft">Owned by {{ $course['owner_name'] }}@if ($course['category']) &middot; {{ $course['category'] }}@endif</p>
            </div>
            <button type="button" disabled
                    class="inline-flex flex-none items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text opacity-60"
                    title="Course editing arrives in a later slice">
                Edit course (coming soon)
            </button>
        </div>

        {{-- Overview --}}
        <section id="overview" class="mb-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <h3 class="text-lg font-black text-slatecard">Overview</h3>
            @if ($course['description'])
                <p class="mt-2 max-w-2xl text-sm text-ink-soft">{{ $course['description'] }}</p>
            @endif
            <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                @foreach ([
                    ['Owner', $course['owner_name']],
                    ['Category', $course['category'] ?? '—'],
                    ['Type', $course['type_label']],
                    ['Status', $course['status_label']],
                    ['Current version', $course['current_version'] ?? '—'],
                    ['Workflow', $course['workflow_state'] ?? '—'],
                    ['Review due', $course['review_due_label']],
                    ['Created', $course['created_label']],
                    ['Updated', $course['updated_label']],
                ] as [$label, $value])
                    <div>
                        <dt class="text-micro font-semibold uppercase tracking-wide text-ink-faint">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slatecard">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Versions --}}
        <section id="versions" class="mb-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-black text-slatecard">Versions</h3>
                <span class="text-mini text-ink-faint">{{ count($course['versions']) }} {{ \Illuminate\Support\Str::plural('version', count($course['versions'])) }}</span>
            </div>
            @if (empty($course['versions']))
                <p class="mt-3 rounded-control border border-dashed border-line bg-paper p-4 text-sm text-ink-soft">No versions yet. Once migrations 003–006 are applied, each course has an immutable published version and editable drafts here.</p>
            @else
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($course['versions'] as $v)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-3">
                            <span class="font-black tabular-nums text-slatecard">v{{ $v['semver'] }}</span>
                            {!! $badge($v['status_label'], $v['status_tone']) !!}
                            <span class="text-mini text-ink-soft">Published {{ $v['published_label'] }}</span>
                            <span class="text-mini text-ink-soft">Review due {{ $v['review_due_label'] }}</span>
                            @if ($v['changelog'])
                                <span class="w-full text-mini text-ink-muted">{{ $v['changelog'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Languages --}}
        <section id="languages" class="mb-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <h3 class="text-lg font-black text-slatecard">Languages</h3>
            @if (empty($course['locales']))
                <p class="mt-3 rounded-control border border-dashed border-line bg-paper p-4 text-sm text-ink-soft">English (base) only. Translated language variants — AI-drafted then human-reviewed — will appear here per version.</p>
            @else
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($course['locales'] as $loc)
                        <span class="inline-flex items-center rounded-full bg-line-soft px-3 py-1 text-mini font-semibold uppercase text-ink-soft">{{ $loc }}</span>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Workflow & approval --}}
        <section id="workflow" class="mb-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <h3 class="text-lg font-black text-slatecard">Workflow &amp; approval</h3>
            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-micro font-semibold uppercase tracking-wide text-ink-faint">Current state</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-slatecard">{{ $course['workflow_state'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-micro font-semibold uppercase tracking-wide text-ink-faint">Review due</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-slatecard">{{ $course['review_due_label'] }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-mini text-ink-faint">The full planning tool — author/reviewer/approver assignment, separation-of-duties approval, the sign-off checklist and the state-transition history — arrives with the course editor.</p>
        </section>

        {{-- Visibility --}}
        <section id="visibility" class="mb-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <h3 class="text-lg font-black text-slatecard">Visibility</h3>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                {!! $badge($course['scope_label'], $course['scope_tone']) !!}
                @if ($course['scope'] === 'allowlist' || $course['scope'] === 'denylist')
                    <span class="text-sm text-ink-soft">{{ $course['entitlement_count'] }} {{ \Illuminate\Support\Str::plural('tenant', $course['entitlement_count']) }} entitled</span>
                @endif
            </div>
            <p class="mt-4 text-mini text-ink-faint">
                @switch($course['scope'])
                    @case('global') Visible to every tenant — a system course that cascades across the platform. @break
                    @case('allowlist') Visible only to the entitled tenants (and their sub-organisations). @break
                    @case('private') Visible only to the owning organisation and its sub-organisations. @break
                    @default Visibility is governed by the course_visibility scope and org-tree entitlements.
                @endswitch
                Managing entitlements arrives with the course editor.
            </p>
        </section>

        <p class="mt-2 text-mini text-ink-faint">This workspace is read-only for now — it reads the course model from migrations 003–006. Editing, the content builder, voiceover and taxonomy management arrive in later slices.</p>
    </main>
</div>
@endsection
