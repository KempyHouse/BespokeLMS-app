{{-- Platform users (platform owner) — people across every tenant, read live
     from profiles. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Platform users';
    $icon = $widget['icon'] ?? '';
    $total = (int) ($metric['total'] ?? 0);
    $active = (int) ($metric['active'] ?? 0);
    $recent = (int) ($metric['recently_active'] ?? 0);
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size" accent="brand">
    @if ($total === 0)
        <x-widgets.parts.empty :size="$size" :icon="$icon" message="No users yet" />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">{{ $total }}</p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">{{ $active }} active {{ \Illuminate\Support\Str::plural('account', $active) }}</p>
        @endif
        @if ($size === 'l')
            <div class="mt-2 flex items-center gap-1.5 text-mini text-ink-soft">
                <span class="h-2 w-2 flex-none rounded-full bg-rag-green" aria-hidden="true"></span>
                {{ $recent }} active in the last 30 days
            </div>
        @endif
        <div class="mt-auto pt-2">
            <a href="{{ route('platform.home') }}" class="inline-flex items-center gap-1 text-micro font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">
                View platform users
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    @endif
</x-widgets.frame>
