@extends('layouts.app')

@section('title', $widget['name'].' · Widget Library')

@section('content')
@php
    $sizeName = ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'];
@endphp
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none rail-sticky">
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.widgets') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Widget Library
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">{{ $widget['category'] }}</p>
            <h1 class="mt-1 flex items-center gap-2 text-xl font-black text-slatecard">
                <svg class="h-5 w-5 flex-none text-teachhq" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $widget['icon'] !!}</svg>
                {{ $widget['name'] }}
            </h1>
            @if ($widget['description'] !== '')
                <p class="mt-2 text-caption text-ink-soft">{{ $widget['description'] }}</p>
            @endif
            <p class="mt-3 text-mini text-ink-faint">Key: <code class="rounded bg-surface px-1 py-0.5 text-slatecard">{{ $widget['key'] }}</code></p>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <h2 class="text-2xl font-black text-slatecard">Edit widget</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">Set who can add <strong class="font-semibold text-slatecard">{{ $widget['name'] }}</strong> to their dashboard, whether it is active, and the size it starts at.</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <p class="font-semibold">Please check the form:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('platform.widgets.update', $widget['key']) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Role visibility --}}
            <fieldset class="rounded-panel border border-line bg-surface p-5 shadow-quiet">
                <legend class="px-1 text-sm font-bold text-slatecard">Visible to roles</legend>
                <p class="mb-3 text-mini text-ink-soft">Only the selected roles can see and add this widget. Clearing every role hides it from all dashboards.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($roleLabels as $roleKey => $roleLabel)
                        <label class="flex items-center gap-3 rounded-control border border-line bg-paper px-3 py-2.5 text-sm font-medium text-slatecard transition hover:border-ink-faint has-[:checked]:border-teachhq has-[:checked]:bg-teachhq-soft">
                            <input type="checkbox" name="roles[]" value="{{ $roleKey }}"
                                   @checked(in_array($roleKey, old('roles', $widget['roles']), true))
                                   class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                            <span>{{ $roleLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-6 sm:grid-cols-2">
                {{-- Status --}}
                <fieldset class="rounded-panel border border-line bg-surface p-5 shadow-quiet">
                    <legend class="px-1 text-sm font-bold text-slatecard">Status</legend>
                    <p class="mb-3 text-mini text-ink-soft">Only active widgets appear in the picker. Draft and retired widgets stay hidden.</p>
                    <div class="relative">
                        <label for="status" class="sr-only">Status</label>
                        <select id="status" name="status"
                                class="w-full appearance-none rounded-control border border-line bg-surface py-2 pl-3 pr-9 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">
                            @foreach (['active' => 'Active', 'draft' => 'Draft', 'retired' => 'Retired'] as $val => $lbl)
                                <option value="{{ $val }}" @selected(old('status', $widget['status']) === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <svg class="select-chevron pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                {{-- Default size --}}
                <fieldset class="rounded-panel border border-line bg-surface p-5 shadow-quiet">
                    <legend class="px-1 text-sm font-bold text-slatecard">Default size</legend>
                    <p class="mb-3 text-mini text-ink-soft">The size the widget starts at when added. Users can resize it afterwards.</p>
                    <div class="flex gap-2" role="radiogroup" aria-label="Default size">
                        @foreach ($widget['sizes'] as $sz)
                            <label class="flex flex-1 cursor-pointer items-center justify-center rounded-control border border-line bg-paper px-3 py-2 text-sm font-semibold text-slatecard transition hover:border-ink-faint has-[:checked]:border-teachhq has-[:checked]:bg-teachhq-soft has-[:checked]:text-teachhq-dark">
                                <input type="radio" name="default_size" value="{{ $sz }}" class="sr-only"
                                       @checked(old('default_size', $widget['default_size']) === $sz)>
                                {{ $sizeName[$sz] ?? strtoupper($sz) }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-5 py-2.5 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                    <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    Save changes
                </button>
                <a href="{{ route('platform.widgets') }}" class="text-sm font-semibold text-ink-soft transition hover:text-slatecard focus:outline-none focus-visible:underline">Cancel</a>
                <p class="text-mini text-ink-faint">You may be asked to re-enter your password to confirm.</p>
            </div>
        </form>
    </main>
</div>
@endsection
