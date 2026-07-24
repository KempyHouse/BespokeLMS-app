@extends('layouts.app')

@section('title', 'System Emails · Outbound · Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <x-platform-nav active="outbound" />
    <x-outbound-nav active="system-emails" />

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">Outbound &middot; System emails</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">System emails</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">The automatic emails the platform sends — password resets, account notices and the like. Set the wording here; every tenant inherits it unless they reword their own copy, and each email is brand-styled automatically.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if ($error)
            <div class="rounded-panel border border-dashed border-line bg-surface p-8 text-center text-sm text-ink-soft">{{ $error }}</div>
        @elseif (empty($templates))
            <div class="rounded-panel border border-dashed border-line bg-surface p-8 text-center text-sm text-ink-soft">No system email templates yet.</div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach ($templates as $t)
                    <a href="{{ route('platform.outbound.system-emails.edit', ['key' => $t['key']]) }}"
                       class="flex items-center justify-between gap-4 rounded-panel border border-line bg-surface p-5 shadow-panel transition hover:border-teachhq/40 focus:outline-none focus:ring-2 focus:ring-button-primary">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slatecard">{{ $t['name'] }}</h3>
                                @if (! empty($t['is_protected']))
                                    <span class="flex-none rounded-full bg-line-soft px-2 py-0.5 text-nano font-semibold text-ink-soft">Platform original</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-mini text-ink-soft">{{ $t['subject'] }}</p>
                        </div>
                        <span class="inline-flex flex-none items-center gap-1 text-sm font-semibold text-teachhq">
                            Edit
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</div>
@endsection
