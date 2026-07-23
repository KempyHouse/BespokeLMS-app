@extends('layouts.app')

@section('title', 'My Learning')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    {{-- Left rail: workspace switcher + the My workspace / Browse menu. --}}
    <x-my-nav active="my-learning" />

    {{-- My Learning dashboard content is migrated in here in a later slice. For
         now the workspace is navigable via the rail — the Course Library is
         live under Browse. --}}
    <main class="min-w-0 flex-1">
        <p class="text-xs font-bold uppercase tracking-wider text-teachhq">My workspace</p>
        <h1 class="mt-1 text-2xl font-black text-slatecard">My Learning</h1>
        <p class="mt-2 max-w-xl text-sm text-ink-soft">Your learning dashboard is on its way. In the meantime, browse and enrol on courses in the
            <a href="{{ route('my.courses') }}" class="font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">Course Library</a>.
        </p>

        <a href="{{ route('my.courses') }}"
           class="mt-6 inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2.5 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
            <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
            Browse the Course Library
        </a>
    </main>
</div>
@endsection
