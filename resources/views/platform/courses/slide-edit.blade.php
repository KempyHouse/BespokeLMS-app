@extends('layouts.app')

@section('title', 'Slide · Content builder')

@php
    $c = $course;
    $s = $slide;
    $type = $s['type'] ?? 'image_text';
    $p = is_array($s['payload'] ?? null) ? $s['payload'] : [];
    $cr = is_array($s['completion_rule'] ?? null) ? $s['completion_rule'] : [];
    $pv = fn ($k, $d = '') => old($k, $p[$k] ?? $d);
    $typeLabel = ['image_text' => 'Image & text', 'video' => 'Video', 'document' => 'Document'][$type] ?? $type;
    $fieldCls = 'mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary';
    $labelCls = 'block text-sm font-semibold text-slatecard';
@endphp

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-20 lg:self-start">
        <x-workspace-switcher active="platform" />
        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.courses.content', $c['id']) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Back to content
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Slide editor</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">{{ $typeLabel }}</h1>
            <p class="mt-2 text-caption text-ink-soft">Edit this slide's content and how a learner completes it. The slide type is fixed once created.</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Content builder</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">Edit slide — {{ $typeLabel }}</h2>
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

        <form method="POST" action="{{ route('platform.courses.slides.update', ['course' => $c['id'], 'slide' => $s['id']]) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="slide_type" value="{{ $type }}">

            {{-- Common --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="{{ $labelCls }}">Slide title</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $s['title'] ?? '') }}" class="{{ $fieldCls }}" placeholder="Optional">
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-2.5">
                        <input id="is_required" name="is_required" type="checkbox" value="1" @checked((bool) old('is_required', $s['is_required'] ?? true)) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                        <label for="is_required" class="text-sm font-medium text-slatecard">Required — this slide counts toward course completion</label>
                    </div>
                </div>
            </section>

            {{-- Type-specific content --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Content</h3>

                @if ($type === 'image_text')
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="image_url" class="{{ $labelCls }}">Image URL</label>
                            <input id="image_url" name="image_url" type="url" value="{{ $pv('image_url') }}" class="{{ $fieldCls }}" placeholder="https://…">
                            <p class="mt-1 text-micro text-ink-faint">Media upload to Storage arrives later; paste a URL for now.</p>
                        </div>
                        <div>
                            <label for="image_alt" class="{{ $labelCls }}">Image alt text</label>
                            <input id="image_alt" name="image_alt" type="text" value="{{ $pv('image_alt') }}" class="{{ $fieldCls }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="body" class="{{ $labelCls }}">Body text</label>
                            <textarea id="body" name="body" rows="8" class="{{ $fieldCls }}" placeholder="Supports HTML.">{{ $pv('body') }}</textarea>
                        </div>
                    </div>
                @elseif ($type === 'video')
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="video_url" class="{{ $labelCls }}">Video URL</label>
                            <input id="video_url" name="video_url" type="url" value="{{ $pv('video_url') }}" class="{{ $fieldCls }}" placeholder="https://… (MP4 / Vimeo / YouTube)">
                        </div>
                        <div>
                            <label for="poster_url" class="{{ $labelCls }}">Poster image URL</label>
                            <input id="poster_url" name="poster_url" type="url" value="{{ $pv('poster_url') }}" class="{{ $fieldCls }}" placeholder="Optional thumbnail">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="transcript" class="{{ $labelCls }}">Transcript</label>
                            <textarea id="transcript" name="transcript" rows="8" class="{{ $fieldCls }}" placeholder="Accessibility transcript / captions text.">{{ $pv('transcript') }}</textarea>
                        </div>
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="document_url" class="{{ $labelCls }}">Document URL</label>
                            <input id="document_url" name="document_url" type="url" value="{{ $pv('document_url') }}" class="{{ $fieldCls }}" placeholder="https://… (PDF etc.)">
                        </div>
                        <div class="flex items-center gap-2.5 sm:pt-7">
                            <input id="allow_download" name="allow_download" type="checkbox" value="1" @checked((bool) old('allow_download', $p['allow_download'] ?? false)) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                            <label for="allow_download" class="text-sm font-medium text-slatecard">Allow the learner to download it</label>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="body" class="{{ $labelCls }}">Notes / instructions</label>
                            <textarea id="body" name="body" rows="6" class="{{ $fieldCls }}" placeholder="Optional context shown with the document.">{{ $pv('body') }}</textarea>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Completion rule --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Completion</h3>
                <p class="mt-1 text-caption text-ink-soft">How much of this slide a learner must engage with before it's marked done.</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($type === 'video')
                        <div>
                            <label for="video_watch_pct" class="{{ $labelCls }}">Minimum watched (%)</label>
                            <input id="video_watch_pct" name="video_watch_pct" type="number" min="0" max="100" value="{{ old('video_watch_pct', $cr['video_watch_pct'] ?? '') }}" class="{{ $fieldCls }}" placeholder="e.g. 90">
                            <p class="mt-1 text-micro text-ink-faint">Blank = no watch requirement.</p>
                        </div>
                    @else
                        <div>
                            <label for="min_view_seconds" class="{{ $labelCls }}">Minimum time on slide (seconds)</label>
                            <input id="min_view_seconds" name="min_view_seconds" type="number" min="0" max="36000" value="{{ old('min_view_seconds', $cr['min_view_seconds'] ?? '') }}" class="{{ $fieldCls }}" placeholder="e.g. 10">
                            <p class="mt-1 text-micro text-ink-faint">Blank = no dwell requirement.</p>
                        </div>
                    @endif
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('platform.courses.content', $c['id']) }}" class="rounded-control px-4 py-2 text-sm font-semibold text-ink-soft transition hover:text-slatecard">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-5 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">Save slide</button>
            </div>
        </form>
    </main>
</div>
@endsection
