@extends('layouts.app')

@section('title', 'Platform')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-teachhq">BespokeLMS · Platform</p>
        <h1 class="mt-1 text-2xl font-black text-slatecard">Platform administration</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-500">
            Signed in as {{ $user->displayName() }} ({{ $user->email }}) · {{ $user->roleLabel() }}.
            This area is exclusive to BespokeLMS platform owners and is enforced both here and by
            row-level security in the database.
        </p>
    </div>

    <section aria-labelledby="scope-heading" class="rounded-control border border-slate-200 bg-white p-6">
        <h2 id="scope-heading" class="text-lg font-bold text-slatecard">What you manage here</h2>
        <p class="mt-1 text-sm text-slate-500">The platform tier spans every tenant in the estate.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <article class="rounded-control border border-slate-200 bg-paper p-4">
                <h3 class="font-semibold text-slatecard">Tenants</h3>
                <p class="mt-1 text-sm text-slate-500">Create, brand and configure operator and client organisations across the estate.</p>
            </article>
            <article class="rounded-control border border-slate-200 bg-paper p-4">
                <h3 class="font-semibold text-slatecard">Global course catalogue</h3>
                <p class="mt-1 text-sm text-slate-500">Publish system courses that cascade to every tenant.</p>
            </article>
            <article class="rounded-control border border-slate-200 bg-paper p-4">
                <h3 class="font-semibold text-slatecard">Platform settings</h3>
                <p class="mt-1 text-sm text-slate-500">Platform-wide branding and configuration, held at the owner level.</p>
            </article>
            <article class="rounded-control border border-slate-200 bg-paper p-4">
                <h3 class="font-semibold text-slatecard">AI integration</h3>
                <p class="mt-1 text-sm text-slate-500">The Claude (Anthropic) integration is configured once here, at the platform-owner level.</p>
            </article>
        </div>

        <p class="mt-5 text-sm text-slate-500">
            The live tenant list and the “view as tenant” switch are wired in the next build step.
        </p>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}"
               class="inline-flex rounded-control border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2">
                ← Back to dashboard
            </a>
        </div>
    </section>
@endsection
