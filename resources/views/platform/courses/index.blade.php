@extends('layouts.app')

@section('title', 'Global Courses · Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-20 lg:self-start">
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.home') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Platform overview
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Global catalogue</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Global Courses</h1>
            <p class="mt-2 text-caption text-ink-soft">Every course across the ecosystem — platform courses that cascade to tenants, plus operator-authored courses. Versioning, language variants, workflow and visibility are managed per course.</p>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Platform owner</p>
                <h2 class="mt-1 text-2xl font-black text-slatecard">Global Course Catalogue</h2>
                <p class="mt-2 max-w-2xl text-sm text-ink-soft">Publish system courses that cascade to every tenant, and manage operator-authored courses. Open a course to edit its versions, languages, workflow and visibility.</p>
            </div>
            <button type="button" disabled
                    class="inline-flex flex-none items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text opacity-60"
                    title="Course authoring arrives in a later slice">
                <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                New course (coming soon)
            </button>
        </div>

        @unless (empty($summary))
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Courses', 'value' => $summary['total']],
                    ['label' => 'Published', 'value' => $summary['published']],
                    ['label' => 'Coming soon', 'value' => $summary['coming_soon']],
                    ['label' => 'Native', 'value' => $summary['native']],
                    ['label' => 'Imported', 'value' => $summary['imported']],
                ] as $chip)
                    <div class="rounded-control border border-line bg-surface p-3">
                        <div class="text-2xl font-black tabular-nums text-slatecard">{{ $chip['value'] }}</div>
                        <div class="text-mini font-semibold uppercase tracking-wide text-ink-soft">{{ $chip['label'] }}</div>
                    </div>
                @endforeach
            </div>
        @endunless

        @php
            $courseColumns = [
                ['key' => 'title', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'owner', 'label' => 'Owner', 'type' => 'text', 'hide' => 'sm'],
                ['key' => 'type', 'label' => 'Type', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'category', 'label' => 'Category', 'type' => 'text', 'hide' => 'md'],
                ['key' => 'version', 'label' => 'Version', 'type' => 'text', 'hide' => 'lg'],
                ['key' => 'visibility', 'label' => 'Visibility', 'type' => 'text', 'hide' => 'sm'],
                ['key' => 'updated', 'label' => 'Updated', 'type' => 'date', 'align' => 'end', 'hide' => 'lg'],
            ];

            $courseFilters = [
                ['key' => 'type', 'label' => 'Type', 'options' => [
                    ['value' => 'native', 'label' => 'Native'],
                    ['value' => 'scorm', 'label' => 'SCORM'],
                    ['value' => 'mixed', 'label' => 'Mixed'],
                ]],
                ['key' => 'status', 'label' => 'Status', 'options' => [
                    ['value' => 'published', 'label' => 'Published'],
                    ['value' => 'coming_soon', 'label' => 'Coming soon'],
                    ['value' => 'retired', 'label' => 'Retired'],
                ]],
                ['key' => 'visibility', 'label' => 'Visibility', 'options' => [
                    ['value' => 'global', 'label' => 'All tenants'],
                    ['value' => 'allowlist', 'label' => 'Selected tenants'],
                    ['value' => 'private', 'label' => 'Private'],
                ]],
            ];

            // Only surface the Owner filter once more than one owner exists
            // (today every global course is platform-owned, so it would be a
            // single-option, no-op filter — it appears when operators author).
            if (count($ownerOptions ?? []) > 1) {
                $courseFilters[] = ['key' => 'owner', 'label' => 'Owner', 'options' => $ownerOptions];
            }

            $courseFilters[] = ['key' => 'category', 'label' => 'Category', 'options' => $categoryOptions ?? []];

            $courseRows = [];
            foreach ($courses ?? [] as $c) {
                $courseRows[] = [
                    'id' => $c['id'],
                    'href' => route('platform.courses.show', $c['id']),
                    'search' => implode(' ', array_filter([
                        $c['title'], $c['owner_name'], $c['category'], $c['type_label'], $c['status_label'],
                    ])),
                    'filters' => [
                        'type' => $c['type'],
                        'status' => $c['status'],
                        'visibility' => $c['scope'],
                        'owner' => $c['owner_key'],
                        'category' => $c['category'] ?? '',
                    ],
                    'cells' => [
                        'title' => ['type' => 'strong', 'value' => $c['title'], 'sort' => $c['title']],
                        'owner' => ['type' => 'text', 'value' => $c['owner_name'], 'sort' => $c['owner_name']],
                        'type' => ['type' => 'badge', 'value' => $c['type_label'], 'tone' => $c['type'] === 'native' ? 'neutral' : 'brand', 'sort' => $c['type_label']],
                        'status' => ['type' => 'badge', 'value' => $c['status_label'], 'tone' => $c['status_tone'], 'sort' => $c['status_label']],
                        'category' => ['type' => 'muted', 'value' => $c['category'] ?? '—', 'sort' => $c['category'] ?? ''],
                        'version' => ['type' => 'text', 'value' => $c['version'] ?? '—', 'sub' => $c['version_status'] ? ucfirst($c['version_status']) : null, 'sort' => $c['version'] ?? ''],
                        'visibility' => ['type' => 'badge', 'value' => $c['scope_label'], 'tone' => $c['scope_tone'], 'sort' => $c['scope_label']],
                        'updated' => ['type' => 'muted', 'value' => $c['updated_label'], 'sort' => $c['updated_sort']],
                    ],
                    'actions' => [
                        ['label' => 'Open course', 'href' => route('platform.courses.show', $c['id'])],
                        ['label' => 'Manage visibility', 'disabled' => true, 'note' => 'soon'],
                    ],
                ];
            }
        @endphp

        <x-data-table
            id="courses"
            :columns="$courseColumns"
            :rows="$courseRows"
            :filters="$courseFilters"
            :search="$q ?? ''"
            search-placeholder="Search courses by title, owner or category"
            count-noun="course"
            :row-actions="true"
            :per-page="25"
            :error="$catalogueError"
            empty="No courses in the catalogue yet." />

        <p class="mt-4 text-mini text-ink-faint">The course workspace (versions, languages, workflow &amp; visibility) and the create flow arrive in the next slice. This page reads the course model from migrations 003&ndash;006 &mdash; apply those in Supabase to populate it.</p>
    </main>
</div>
@endsection
