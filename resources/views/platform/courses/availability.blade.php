@extends('layouts.app')

@section('title', 'Availability & authors · Global Courses')

@php
    $c = $course;
    $selected = collect($selectedTerritoryIds ?? [])->flip();

    // Group territories by parent for display (roots first, children indented).
    $byParent = [];
    foreach (($territories ?? []) as $t) {
        $byParent[$t['parent_id'] ?? '_root'][] = $t;
    }
    $roots = $byParent['_root'] ?? [];

    $fieldCls = 'mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary';
    $labelCls = 'block text-sm font-semibold text-slatecard';
@endphp

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-20 lg:self-start">
        <x-workspace-switcher active="platform" />
        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.courses.show', $c['id']) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Back to course
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Course editor</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Availability &amp; authors</h1>
            <p class="mt-2 text-caption text-ink-soft">Which territories the course is offered in, and who is credited as an author or subject-matter expert.</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">{{ $c['title'] ?? 'Course' }} — availability &amp; authors</h2>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">{{ session('status') }}</div>
        @endif
        @if (session('editorError'))
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">{{ session('editorError') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">Some values were not valid — check the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('platform.courses.availability.update', $c['id']) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Territories --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Territories</h3>
                <p class="mt-1 text-caption text-ink-soft">Tick the territories where this course is offered. Leaving all unticked means it is not territory-restricted.</p>

                @if (empty($territories))
                    <p class="mt-4 rounded-control border border-dashed border-line bg-paper p-4 text-sm text-ink-soft">No territories are defined yet. Seed the <code>territories</code> table (migration 007a) and they will appear here to choose from.</p>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2">
                        @foreach ($roots as $root)
                            <div class="break-inside-avoid">
                                <label class="flex items-center gap-2.5 py-1">
                                    <input type="checkbox" name="territory_ids[]" value="{{ $root['id'] }}" @checked($selected->has($root['id'])) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                                    <span class="text-sm font-semibold text-slatecard">{{ $root['name'] }}</span>
                                    <span class="text-micro uppercase tracking-wide text-ink-faint">{{ $root['code'] }}</span>
                                </label>
                                @foreach (($byParent[$root['id']] ?? []) as $child)
                                    <label class="ml-6 flex items-center gap-2.5 py-1">
                                        <input type="checkbox" name="territory_ids[]" value="{{ $child['id'] }}" @checked($selected->has($child['id'])) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                                        <span class="text-sm text-slatecard">{{ $child['name'] }}</span>
                                        <span class="text-micro uppercase tracking-wide text-ink-faint">{{ $child['code'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Authors --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slatecard">Authors &amp; credits</h3>
                        <p class="mt-1 text-caption text-ink-soft">Credit internal staff or external subject-matter experts. Drag order is set by the sequence below.</p>
                    </div>
                    <button type="button" id="add-author" class="inline-flex flex-none items-center gap-1.5 rounded-control border border-line bg-paper px-3 py-2 text-sm font-semibold text-slatecard transition hover:bg-surface focus:outline-none focus:ring-2 focus:ring-button-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Add author
                    </button>
                </div>

                <div id="authors-list" class="mt-4 space-y-3">
                    @forelse ($authors as $i => $a)
                        <div class="author-row grid grid-cols-1 gap-3 rounded-control border border-line bg-paper p-3 sm:grid-cols-[1fr_1fr_auto]">
                            <div>
                                <label class="{{ $labelCls }} text-xs">Person</label>
                                <select name="authors[{{ $i }}][profile_id]" class="{{ $fieldCls }}">
                                    <option value="">— External / named —</option>
                                    @foreach ($profileOptions as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(($a['profile_id'] ?? null) === $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="authors[{{ $i }}][display_name]" value="{{ $a['display_name'] ?? '' }}" class="{{ $fieldCls }}" placeholder="External name (if not internal)">
                            </div>
                            <div>
                                <label class="{{ $labelCls }} text-xs">Credit label</label>
                                <input type="text" name="authors[{{ $i }}][credit_label]" value="{{ $a['credit_label'] ?? '' }}" class="{{ $fieldCls }}" placeholder="Author / SME / Reviewer">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="remove-author rounded-control border border-line px-3 py-2 text-sm font-semibold text-rag-red transition hover:bg-rag-red-soft focus:outline-none focus:ring-2 focus:ring-rag-red" aria-label="Remove author">Remove</button>
                            </div>
                        </div>
                    @empty
                    @endforelse
                </div>
                <p id="authors-empty" class="mt-1 text-mini text-ink-faint @unless(empty($authors)) hidden @endunless">No authors yet — use “Add author”.</p>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('platform.courses.show', $c['id']) }}" class="rounded-control px-4 py-2 text-sm font-semibold text-ink-soft transition hover:text-slatecard">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-5 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">Save availability</button>
            </div>
        </form>
    </main>
</div>

<template id="author-row-tpl">
    <div class="author-row grid grid-cols-1 gap-3 rounded-control border border-line bg-paper p-3 sm:grid-cols-[1fr_1fr_auto]">
        <div>
            <label class="block text-xs font-semibold text-slatecard">Person</label>
            <select name="authors[__IDX__][profile_id]" class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary">
                <option value="">— External / named —</option>
                @foreach ($profileOptions as $opt)
                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
            <input type="text" name="authors[__IDX__][display_name]" class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary" placeholder="External name (if not internal)">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slatecard">Credit label</label>
            <input type="text" name="authors[__IDX__][credit_label]" class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary" placeholder="Author / SME / Reviewer">
        </div>
        <div class="flex items-end">
            <button type="button" class="remove-author rounded-control border border-line px-3 py-2 text-sm font-semibold text-rag-red transition hover:bg-rag-red-soft focus:outline-none focus:ring-2 focus:ring-rag-red" aria-label="Remove author">Remove</button>
        </div>
    </div>
</template>

<script>
    (function () {
        var list = document.getElementById('authors-list');
        var tpl = document.getElementById('author-row-tpl');
        var addBtn = document.getElementById('add-author');
        var emptyMsg = document.getElementById('authors-empty');
        if (!list || !tpl || !addBtn) { return; }

        var counter = {{ count($authors) }};

        function refreshEmpty() {
            if (!emptyMsg) { return; }
            emptyMsg.classList.toggle('hidden', list.querySelectorAll('.author-row').length > 0);
        }

        addBtn.addEventListener('click', function () {
            var html = tpl.innerHTML.replace(/__IDX__/g, String(counter++));
            var frag = document.createElement('div');
            frag.innerHTML = html.trim();
            list.appendChild(frag.firstChild);
            refreshEmpty();
        });

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-author');
            if (!btn) { return; }
            var row = btn.closest('.author-row');
            if (row) { row.remove(); refreshEmpty(); }
        });

        refreshEmpty();
    })();
</script>
@endsection
