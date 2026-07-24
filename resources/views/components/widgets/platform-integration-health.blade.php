{{-- Integration health (platform owner) — status of connected AI, voice and
     email providers, read live from ai_integrations + email_integrations. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Integration health';
    $icon = $widget['icon'] ?? '';
    $connected = (int) ($metric['connected'] ?? 0);
    $issues = (int) ($metric['issues'] ?? 0);
    $total = (int) ($metric['total'] ?? 0);
    $providers = $metric['providers'] ?? [];
    $accent = $issues > 0 ? 'amber' : 'brand';

    $dot = static fn (string $status): string => match ($status) {
        'connected' => 'bg-rag-green',
        'error' => 'bg-rag-red',
        'disabled' => 'bg-ink-faint',
        default => 'bg-rag-amber',
    };
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size" :accent="$accent">
    @if ($total === 0)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="No integrations configured"
            hint="Connect AI, voice and email providers from the Platform workspace." />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">
            {{ $connected }}<span class="text-base font-bold text-ink-faint"> / {{ $total }}</span>
        </p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">
                connected
                @if ($issues > 0) &middot; <span class="font-semibold text-rag-red">{{ $issues }} {{ \Illuminate\Support\Str::plural('issue', $issues) }}</span>@endif
            </p>
        @endif
        @if ($size === 'l' && ! empty($providers))
            <ul class="mt-2.5 space-y-1.5">
                @foreach (array_slice($providers, 0, 5) as $p)
                    <li class="flex items-center gap-2 text-mini">
                        <span class="h-2 w-2 flex-none rounded-full {{ $dot((string) ($p['status'] ?? '')) }}" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1 truncate text-slatecard">{{ $p['name'] ?? 'Provider' }}</span>
                        <span class="flex-none text-nano font-semibold uppercase tracking-wide text-ink-faint">{{ $p['kind'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="mt-auto pt-2">
            <a href="{{ route('platform.ai') }}" class="inline-flex items-center gap-1 text-micro font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">
                Manage integrations
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    @endif
</x-widgets.frame>
