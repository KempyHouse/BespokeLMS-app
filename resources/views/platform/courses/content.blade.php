@extends('layouts.app')

@section('title', 'Content · Global Courses')

@php
    $c = $course;
    $act = route('platform.courses.content.action', $c['id']);
    $fieldCls = 'rounded-control border border-line bg-paper px-2.5 py-1.5 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary';
    $iconBtn = 'inline-flex h-7 w-7 items-center justify-center rounded-control border border-line text-ink-soft transition hover:bg-surface hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary disabled:opacity-40';
    $typeLabel = ['image_text' => 'Image & text', 'video' => 'Video', 'document' => 'Document'];
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
            <h1 class="mt-1 text-xl font-black text-slatecard">Content</h1>
            <p class="mt-2 text-caption text-ink-soft">Build the course outline — modules, lessons and slides — on a draft version. Learners on the published version don't see draft changes.</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">{{ $c['title'] ?? 'Course' }} — content</h2>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">{{ session('status') }}</div>
        @endif
        @if (session('editorError'))
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">{{ session('editorError') }}</div>
        @endif

        @if ($draft === null)
            {{-- No draft yet --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">No draft version</h3>
                <p class="mt-1 text-caption text-ink-soft">Create a draft to start (or continue) authoring. Editing happens on the draft so published content stays untouched until you promote it through the workflow.</p>
                @if (!empty($versions))
                    <p class="mt-3 text-mini text-ink-faint">Existing versions:
                        @foreach ($versions as $v)<span class="mr-1 inline-flex items-center gap-1 rounded-control border border-line px-2 py-0.5">{{ $v['semver'] ?? '—' }} · {{ $v['status'] ?? '' }}</span>@endforeach
                    </p>
                @endif
                <form method="POST" action="{{ route('platform.courses.content.draft', $c['id']) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">Create draft version</button>
                </form>
            </section>
        @else
            {{-- Draft banner --}}
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-panel border border-teachhq/30 bg-paper p-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-teachhq">Editing draft</p>
                    <p class="text-sm font-semibold text-slatecard">Version {{ $draft['semver'] ?? '' }} · draft (v{{ $draft['version_no'] ?? '' }})</p>
                </div>
                <p class="text-mini text-ink-faint">Promote to published via the Workflow tab when ready.</p>
            </div>

            {{-- Module tree --}}
            @forelse ($tree as $mi => $module)
                <section class="mb-4 rounded-panel border border-line bg-surface shadow-panel">
                    <header class="flex flex-wrap items-center gap-2 border-b border-line p-4">
                        <div class="flex flex-none flex-col">
                            <form method="POST" action="{{ $act }}"><input type="hidden" name="action" value="move_module"><input type="hidden" name="id" value="{{ $module['id'] }}"><input type="hidden" name="direction" value="up">@csrf<button type="submit" class="{{ $iconBtn }} h-5 w-7" @disabled($mi === 0) aria-label="Move module up">▲</button></form>
                            <form method="POST" action="{{ $act }}"><input type="hidden" name="action" value="move_module"><input type="hidden" name="id" value="{{ $module['id'] }}"><input type="hidden" name="direction" value="down">@csrf<button type="submit" class="{{ $iconBtn }} h-5 w-7" @disabled($mi === count($tree) - 1) aria-label="Move module down">▼</button></form>
                        </div>
                        <span class="text-micro font-bold uppercase tracking-wide text-ink-faint">Module {{ $mi + 1 }}</span>
                        <form method="POST" action="{{ $act }}" class="flex min-w-0 flex-1 items-center gap-2">
                            <input type="hidden" name="action" value="rename_module"><input type="hidden" name="id" value="{{ $module['id'] }}">@csrf
                            <input type="text" name="title" value="{{ $module['title'] ?? '' }}" class="{{ $fieldCls }} min-w-0 flex-1 font-semibold" aria-label="Module title">
                            <button type="submit" class="rounded-control border border-line px-3 py-1.5 text-sm font-semibold text-slatecard transition hover:bg-paper">Rename</button>
                        </form>
                        <form method="POST" action="{{ $act }}" onsubmit="return confirm('Delete this module and everything in it?');"><input type="hidden" name="action" value="delete_module"><input type="hidden" name="id" value="{{ $module['id'] }}">@csrf<button type="submit" class="rounded-control border border-line px-3 py-1.5 text-sm font-semibold text-rag-red transition hover:bg-rag-red-soft">Delete</button></form>
                    </header>

                    <div class="space-y-3 p-4">
                        @foreach (($module['lessons'] ?? []) as $li => $lesson)
                            <div class="rounded-control border border-line bg-paper p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="flex flex-none flex-col">
                                        <form method="POST" action="{{ $act }}"><input type="hidden" name="action" value="move_lesson"><input type="hidden" name="id" value="{{ $lesson['id'] }}"><input type="hidden" name="parent_id" value="{{ $module['id'] }}"><input type="hidden" name="direction" value="up">@csrf<button type="submit" class="{{ $iconBtn }} h-5 w-6" @disabled($li === 0) aria-label="Move lesson up">▲</button></form>
                                        <form method="POST" action="{{ $act }}"><input type="hidden" name="action" value="move_lesson"><input type="hidden" name="id" value="{{ $lesson['id'] }}"><input type="hidden" name="parent_id" value="{{ $module['id'] }}"><input type="hidden" name="direction" value="down">@csrf<button type="submit" class="{{ $iconBtn }} h-5 w-6" @disabled($li === count($module['lessons']) - 1) aria-label="Move lesson down">▼</button></form>
                                    </div>
                                    <span class="text-micro font-semibold uppercase tracking-wide text-ink-faint">Lesson {{ $mi + 1 }}.{{ $li + 1 }}</span>
                                    <form method="POST" action="{{ $act }}" class="flex min-w-0 flex-1 items-center gap-2">
                                        <input type="hidden" name="action" value="rename_lesson"><input type="hidden" name="id" value="{{ $lesson['id'] }}">@csrf
                                        <input type="text" name="title" value="{{ $lesson['title'] ?? '' }}" class="{{ $fieldCls }} min-w-0 flex-1" aria-label="Lesson title">
                                        <button type="submit" class="rounded-control border border-line px-2.5 py-1 text-xs font-semibold text-slatecard transition hover:bg-surface">Rename</button>
                                    </form>
                                    <form method="POST" action="{{ $act }}" onsubmit="return confirm('Delete this lesson and its slides?');"><input type="hidden" name="action" value="delete_lesson"><input type="hidden" name="id" value="{{ $lesson['id'] }}">@csrf<button type="submit" class="rounded-control border border-line px-2.5 py-1 text-xs font-semibold text-rag-red transition hover:bg-rag-red-soft">Delete</button></form>
                                </div>

                                {{-- Slides --}}
                                <ul class="mt-3 space-y-1.5 pl-4">
                                    @foreach (($lesson['slides'] ?? []) as $si => $slide)
                                        <li class="flex flex-wrap items-center gap-2 rounded-control bg-surface px-2.5 py-1.5">
                                            <div class="flex flex-none items-center gap-1">
                                                <form method="POST" action="{{ $act }}"><input type="hidden" name="action" value="move_slide"><input type="hidden" name="id" value="{{ $slide['id'] }}"><input type="hidden" name="parent_id" value="{{ $lesson['id'] }}"><input type="hidden" name="direction" value="up">@csrf<button type="submit" class="{{ $iconBtn }} h-6 w-6" @disabled($si === 0) aria-label="Move slide up">▲</button></form>
                                                <form method="POST" action="{{ $act }}"><input type="hidden" name="action" value="move_slide"><input type="hidden" name="id" value="{{ $slide['id'] }}"><input type="hidden" name="parent_id" value="{{ $lesson['id'] }}"><input type="hidden" name="direction" value="down">@csrf<button type="submit" class="{{ $iconBtn }} h-6 w-6" @disabled($si === count($lesson['slides']) - 1) aria-label="Move slide down">▼</button></form>
                                            </div>
                                            <span class="rounded-control bg-teachhq/10 px-2 py-0.5 text-micro font-bold uppercase tracking-wide text-teachhq">{{ $typeLabel[$slide['type']] ?? $slide['type'] }}</span>
                                            <a href="{{ route('platform.courses.slides.edit', ['course' => $c['id'], 'slide' => $slide['id']]) }}" class="rounded-control border border-line px-2 py-1 text-micro font-semibold text-teachhq transition hover:bg-paper">Edit content</a>
                                            <form method="POST" action="{{ $act }}" class="flex min-w-0 flex-1 items-center gap-2">
                                                <input type="hidden" name="action" value="rename_slide"><input type="hidden" name="id" value="{{ $slide['id'] }}">@csrf
                                                <input type="text" name="title" value="{{ $slide['title'] ?? '' }}" placeholder="Untitled slide" class="{{ $fieldCls }} min-w-0 flex-1 py-1 text-xs" aria-label="Slide title">
                                                <button type="submit" class="rounded-control border border-line px-2 py-1 text-micro font-semibold text-slatecard transition hover:bg-paper">Save</button>
                                            </form>
                                            <form method="POST" action="{{ $act }}" onsubmit="return confirm('Delete this slide?');"><input type="hidden" name="action" value="delete_slide"><input type="hidden" name="id" value="{{ $slide['id'] }}">@csrf<button type="submit" class="rounded-control border border-line px-2 py-1 text-micro font-semibold text-rag-red transition hover:bg-rag-red-soft">Delete</button></form>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Add slide --}}
                                <form method="POST" action="{{ $act }}" class="mt-2 flex flex-wrap items-center gap-2 pl-4">
                                    <input type="hidden" name="action" value="add_slide"><input type="hidden" name="parent_id" value="{{ $lesson['id'] }}">@csrf
                                    <select name="slide_type" class="{{ $fieldCls }} py-1 text-xs" aria-label="Slide type">
                                        <option value="image_text">Image &amp; text</option>
                                        <option value="video">Video</option>
                                        <option value="document">Document</option>
                                    </select>
                                    <input type="text" name="title" placeholder="Slide title (optional)" class="{{ $fieldCls }} py-1 text-xs">
                                    <button type="submit" class="rounded-control bg-teachhq px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-teachhq-dark">+ Slide</button>
                                </form>
                            </div>
                        @endforeach

                        {{-- Add lesson --}}
                        <form method="POST" action="{{ $act }}" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="action" value="add_lesson"><input type="hidden" name="parent_id" value="{{ $module['id'] }}">@csrf
                            <input type="text" name="title" placeholder="New lesson title" class="{{ $fieldCls }}">
                            <button type="submit" class="rounded-control border border-line px-3 py-1.5 text-sm font-semibold text-slatecard transition hover:bg-paper">+ Lesson</button>
                        </form>
                    </div>
                </section>
            @empty
                <p class="mb-4 rounded-panel border border-dashed border-line bg-paper p-4 text-sm text-ink-soft">No modules yet. Add the first one below.</p>
            @endforelse

            {{-- Add module --}}
            <form method="POST" action="{{ $act }}" class="flex flex-wrap items-center gap-2 rounded-panel border border-line bg-surface p-4 shadow-panel">
                <input type="hidden" name="action" value="add_module">@csrf
                <input type="text" name="title" placeholder="New module title" class="{{ $fieldCls }} min-w-0 flex-1">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">+ Module</button>
            </form>
        @endif
    </main>
</div>
@endsection
