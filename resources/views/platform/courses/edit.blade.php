@extends('layouts.app')

@section('title', 'Edit course · Global Courses')

@php
    $c = $course;
    $val = fn (string $k, $d = '') => old($k, $c[$k] ?? $d);
    // certificate_validity comes back as an interval string (e.g. "12 mons"); pull the month count for the form.
    $certMonths = old('certificate_validity_months');
    if ($certMonths === null && !empty($c['certificate_validity'])) {
        if (preg_match('/(\d+)\s*mon/i', (string) $c['certificate_validity'], $m)) { $certMonths = (int) $m[1]; }
    }
    $fieldCls = 'mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary';
    $labelCls = 'block text-sm font-semibold text-slatecard';
@endphp

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <aside class="w-full lg:w-rail lg:flex-none rail-sticky">
        <x-workspace-switcher active="platform" />
        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.courses.show', $c['id']) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Back to course
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Course editor</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Details</h1>
            <p class="mt-2 text-caption text-ink-soft">Catalogue, media, SEO and certification settings for this course. Content, versions, pricing and voiceover are edited in their own tabs.</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">Edit course details</h2>
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

        <form method="POST" action="{{ route('platform.courses.update', $c['id']) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Basics --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Basics</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="{{ $labelCls }}">Course title</label>
                        <input id="title" name="title" type="text" value="{{ $val('title') }}" class="{{ $fieldCls }}" required>
                    </div>
                    <div>
                        <label for="slug" class="{{ $labelCls }}">Slug</label>
                        <input id="slug" name="slug" type="text" value="{{ $val('slug') }}" class="{{ $fieldCls }}" placeholder="auto from title">
                        <p class="mt-1 text-micro text-ink-faint">Lowercase, hyphenated. Leave blank to regenerate from the title.</p>
                    </div>
                    <div>
                        <label for="category_id" class="{{ $labelCls }}">Category</label>
                        <select id="category_id" name="category_id" class="{{ $fieldCls }}">
                            <option value="">— None —</option>
                            @foreach ($categoryOptions as $opt)
                                <option value="{{ $opt['value'] }}" @selected($val('category_id') === $opt['value'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="catalog_status" class="{{ $labelCls }}">Catalogue status</label>
                        <select id="catalog_status" name="catalog_status" class="{{ $fieldCls }}">
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt['value'] }}" @selected($val('catalog_status','published') === $opt['value'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="content_type" class="{{ $labelCls }}">Type</label>
                        <select id="content_type" name="content_type" class="{{ $fieldCls }}">
                            @foreach ($typeOptions as $opt)
                                <option value="{{ $opt['value'] }}" @selected($val('content_type','native') === $opt['value'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="duration_min" class="{{ $labelCls }}">Time to complete (minutes)</label>
                        <input id="duration_min" name="duration_min" type="number" min="0" value="{{ $val('duration_min') }}" class="{{ $fieldCls }}" placeholder="e.g. 90">
                    </div>
                </div>
            </section>

            {{-- Marketing & SEO --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Marketing &amp; search</h3>
                <p class="mt-1 text-caption text-ink-soft">Hero image and trailer sell the course in the library; meta fields help Google and AI search find it. (Descriptive copy is edited per version.)</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="hero_image_alt" class="{{ $labelCls }}">Hero image alt text</label>
                        <input id="hero_image_alt" name="hero_image_alt" type="text" value="{{ $val('hero_image_alt') }}" class="{{ $fieldCls }}">
                    </div>
                    <div>
                        <label for="trailer_url" class="{{ $labelCls }}">Trailer URL</label>
                        <input id="trailer_url" name="trailer_url" type="url" value="{{ $val('trailer_url') }}" class="{{ $fieldCls }}" placeholder="https://…">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="meta_title" class="{{ $labelCls }}">Meta title</label>
                        <input id="meta_title" name="meta_title" type="text" value="{{ $val('meta_title') }}" class="{{ $fieldCls }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="meta_description" class="{{ $labelCls }}">Meta description</label>
                        <textarea id="meta_description" name="meta_description" rows="2" class="{{ $fieldCls }}">{{ $val('meta_description') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="meta_keywords" class="{{ $labelCls }}">Meta keywords</label>
                        <input id="meta_keywords" name="meta_keywords" type="text" value="{{ $val('meta_keywords') }}" class="{{ $fieldCls }}" placeholder="comma, separated, terms">
                    </div>
                </div>
            </section>

            {{-- CPD & certification --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">CPD &amp; certification</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cpd_points" class="{{ $labelCls }}">CPD / CE points</label>
                        <input id="cpd_points" name="cpd_points" type="number" step="0.5" min="0" value="{{ $val('cpd_points') }}" class="{{ $fieldCls }}">
                    </div>
                    <div>
                        <label for="cpd_body" class="{{ $labelCls }}">CPD body / scheme</label>
                        <input id="cpd_body" name="cpd_body" type="text" value="{{ $val('cpd_body') }}" class="{{ $fieldCls }}">
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-2.5">
                        <input id="issues_certificate" name="issues_certificate" type="checkbox" value="1" @checked((bool) $val('issues_certificate', true)) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                        <label for="issues_certificate" class="text-sm font-medium text-slatecard">Issues a certificate on successful completion</label>
                    </div>
                    <div>
                        <label for="certificate_validity_months" class="{{ $labelCls }}">Certificate validity (months)</label>
                        <input id="certificate_validity_months" name="certificate_validity_months" type="number" min="0" value="{{ $certMonths }}" class="{{ $fieldCls }}" placeholder="blank = never expires">
                    </div>
                    <div class="flex items-center gap-2.5 sm:pt-7">
                        <input id="auto_reassign_on_expiry" name="auto_reassign_on_expiry" type="checkbox" value="1" @checked((bool) $val('auto_reassign_on_expiry', false)) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                        <label for="auto_reassign_on_expiry" class="text-sm font-medium text-slatecard">Auto re-assign to the learner when it expires</label>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('platform.courses.show', $c['id']) }}" class="rounded-control px-4 py-2 text-sm font-semibold text-ink-soft transition hover:text-slatecard">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-5 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">Save details</button>
            </div>
        </form>
    </main>
</div>
@endsection
