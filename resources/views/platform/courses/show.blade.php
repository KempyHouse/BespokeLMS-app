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
                    ['visibility', 'Visibility'],
                ] as [$anchor, $label])
                    <a href="#{{ $anchor }}" class="rounded-control px-2 py-1.5 font-medium text-slatecard transition hover:bg-surface hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">{{ $label }}</a>
                @endforeach
                <a href="{{ route('platform.courses.pricing', $course['id']) }}" class="rounded-control px-2 py-1.5 font-medium text-slatecard transition hover:bg-surface hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Pricing &amp; retakes</a>
                <a href="{{ route('platform.courses.availability', $course['id']) }}" class="rounded-control px-2 py-1.5 font-medium text-slatecard transition hover:bg-surface hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Availability &amp; authors</a>
                <a href="{{ route('platform.courses.content', $course['id']) }}" class="rounded-control px-2 py-1.5 font-medium text-slatecard transition hover:bg-surface hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Content builder</a>
                <a href="{{ route('platform.courses.workflow', $course['id']) }}" class="rounded-control px-2 py-1.5 font-medium text-slatecard transition hover:bg-surface hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Workflow &amp; approval</a>
            </nav>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <x-breadcrumb :items="[
            ['label' => 'Platform', 'href' => route('platform.home')],
            ['label' => 'Global Courses', 'href' => route('platform.courses')],
            ['label' => $course['title']],
        ]" />

        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
                <h2 class="mt-1 text-2xl font-black text-slatecard">{{ $course['title'] }}</h2>
                <p class="mt-1 text-sm text-ink-soft">Owned by {{ $course['owner_name'] }}@if ($course['category']) &middot; {{ $course['category'] }}@endif</p>
            </div>
            <a href="#overview"
               class="inline-flex flex-none items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
                <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Edit details
            </a>
        </div>

        {{-- Course details (editable) --}}
        @php
            $inCls = 'w-full rounded-control border border-line bg-surface px-3 py-2 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq';
            $lblCls = 'mb-1 block text-micro font-semibold uppercase tracking-wide text-ink-faint';
        @endphp
        <section id="overview" class="mb-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-black text-slatecard">Course details</h3>
                <span class="text-mini text-ink-faint">Catalogue &amp; certification</span>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-control border border-line bg-rag-green-soft px-4 py-2.5 text-sm font-medium text-rag-green" role="status">{{ session('status') }}</div>
            @endif
            @if (session('courseError'))
                <div class="mt-4 rounded-control border border-line bg-rag-red-soft px-4 py-2.5 text-sm font-medium text-rag-red" role="alert">{{ session('courseError') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-4 rounded-control border border-line bg-rag-red-soft px-4 py-2.5 text-sm text-rag-red" role="alert">Please correct the highlighted fields and save again.</div>
            @endif

            <form method="POST" action="{{ route('platform.courses.update', $course['id']) }}" class="mt-5 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="f-title" class="{{ $lblCls }}">Title</label>
                    <input id="f-title" name="title" type="text" required value="{{ old('title', $course['title']) }}" class="{{ $inCls }} @error('title') border-rag-red @enderror">
                    @error('title') <p class="mt-1 text-mini text-rag-red">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="f-category" class="{{ $lblCls }}">Category</label>
                        <x-ds-select id="f-category" name="category_id">
                            <option value="">&mdash; None &mdash;</option>
                            @foreach ($categoryOptions as $opt)
                                <option value="{{ $opt['value'] }}" @selected(old('category_id', $course['category_id']) === $opt['value'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </x-ds-select>
                    </div>
                    <div>
                        <label for="f-status" class="{{ $lblCls }}">Status</label>
                        <x-ds-select id="f-status" name="catalog_status">
                            @foreach (['published' => 'Published', 'coming_soon' => 'Coming soon', 'retired' => 'Retired'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('catalog_status', $course['status']) === $val)>{{ $label }}</option>
                            @endforeach
                        </x-ds-select>
                    </div>
                    <div>
                        <label for="f-type" class="{{ $lblCls }}">Content type</label>
                        <x-ds-select id="f-type" name="content_type">
                            @foreach (['native' => 'Native', 'scorm' => 'SCORM', 'mixed' => 'Mixed'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('content_type', $course['type']) === $val)>{{ $label }}</option>
                            @endforeach
                        </x-ds-select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="f-level" class="{{ $lblCls }}">Level</label>
                        <input id="f-level" name="level" type="text" value="{{ old('level', $course['level']) }}" placeholder="e.g. Level 2" class="{{ $inCls }}">
                    </div>
                    <div>
                        <label for="f-duration" class="{{ $lblCls }}">Duration (minutes)</label>
                        <input id="f-duration" name="duration_min" type="number" min="0" value="{{ old('duration_min', $course['duration_min']) }}" class="{{ $inCls }}">
                    </div>
                    <div>
                        <label for="f-cpd" class="{{ $lblCls }}">CPD points</label>
                        <input id="f-cpd" name="cpd_points" type="number" min="0" step="0.5" value="{{ old('cpd_points', $course['cpd_points']) }}" class="{{ $inCls }}">
                    </div>
                </div>

                <div>
                    <label for="f-description" class="{{ $lblCls }}">Description</label>
                    <textarea id="f-description" name="description" rows="3" class="{{ $inCls }}">{{ old('description', $course['description']) }}</textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-accred" class="{{ $lblCls }}">Accreditation</label>
                        <input id="f-accred" name="accreditation" type="text" value="{{ old('accreditation', $course['accreditation']) }}" placeholder="e.g. RSPH &amp; CIEH mapped" class="{{ $inCls }}">
                    </div>
                    <div>
                        <label for="f-cpdbody" class="{{ $lblCls }}">CPD body</label>
                        <input id="f-cpdbody" name="cpd_body" type="text" value="{{ old('cpd_body', $course['cpd_body']) }}" placeholder="e.g. The CPD Certification Service" class="{{ $inCls }}">
                    </div>
                </div>

                <div class="grid gap-4 rounded-control border border-line bg-paper p-4 sm:grid-cols-3 sm:items-center">
                    <label class="flex items-center gap-2.5 text-sm font-medium text-slatecard">
                        <input name="issues_certificate" type="checkbox" value="1" @checked(old('issues_certificate', $course['issues_certificate'])) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                        Issues a certificate
                    </label>
                    <div class="sm:col-span-2">
                        <label for="f-validity" class="{{ $lblCls }}">Certificate validity (months, 0 = never expires)</label>
                        <input id="f-validity" name="certificate_validity_months" type="number" min="0" value="{{ old('certificate_validity_months', $course['certificate_validity_months']) }}" class="{{ $inCls }}">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-hero" class="{{ $lblCls }}">Hero image path</label>
                        <input id="f-hero" name="hero_image_path" type="text" value="{{ old('hero_image_path', $course['hero_image_path']) }}" placeholder="course-covers/&hellip; or full URL" class="{{ $inCls }}">
                    </div>
                    <div>
                        <label for="f-heroalt" class="{{ $lblCls }}">Hero image alt text</label>
                        <input id="f-heroalt" name="hero_image_alt" type="text" value="{{ old('hero_image_alt', $course['hero_image_alt']) }}" class="{{ $inCls }}">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-metatitle" class="{{ $lblCls }}">Meta title (SEO)</label>
                        <input id="f-metatitle" name="meta_title" type="text" value="{{ old('meta_title', $course['meta_title']) }}" class="{{ $inCls }}">
                    </div>
                    <div>
                        <label for="f-metadesc" class="{{ $lblCls }}">Meta description (SEO)</label>
                        <textarea id="f-metadesc" name="meta_description" rows="2" class="{{ $inCls }}">{{ old('meta_description', $course['meta_description']) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t border-line-soft pt-4">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
                        <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        Save details
                    </button>
                    <a href="{{ route('platform.courses') }}" class="text-sm font-semibold text-ink-soft transition hover:text-slatecard">Cancel</a>
                </div>
            </form>
        </section>

        {{-- At a glance (read-only) --}}
        <section class="mb-6 rounded-panel border border-line bg-surface p-6 shadow-panel">
            <h3 class="text-lg font-black text-slatecard">At a glance</h3>
            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                @foreach ([
                    ['Owner', $course['owner_name']],
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

        <p class="mt-2 text-mini text-ink-faint">Course details are editable above. The version content builder, language editor, workflow actions and visibility/entitlement management arrive in the next editor slices.</p>
    </main>
</div>
@endsection
