{{-- Tenant estate (platform owner) — operators and client organisations across
     the platform, read live from organizations. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Tenant estate';
    $icon = $widget['icon'] ?? '';
    $tenants = (int) ($metric['tenants'] ?? 0);
    $operators = (int) ($metric['operators'] ?? 0);
    $clients = (int) ($metric['clients'] ?? 0);
    $subtypes = $metric['subtypes'] ?? [];
    $subtypeLabels = ['reseller' => 'Reseller', 'inhouse' => 'In-house', 'own_brand' => 'Own brand'];
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size" accent="brand">
    @if ($tenants === 0)
        <x-widgets.parts.empty :size="$size" :icon="$icon" message="No tenants yet" />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">{{ $tenants }}</p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">{{ $operators }} {{ \Illuminate\Support\Str::plural('operator', $operators) }} &middot; {{ $clients }} client {{ \Illuminate\Support\Str::plural('org', $clients) }}</p>
        @endif
        @if ($size === 'l' && ! empty($subtypes))
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ($subtypes as $key => $n)
                    <span class="inline-flex items-center gap-1 rounded-full bg-line-soft px-2 py-0.5 text-nano font-semibold text-ink-soft">
                        {{ $subtypeLabels[$key] ?? \Illuminate\Support\Str::headline($key) }} <span class="font-black tabular-nums text-slatecard">{{ $n }}</span>
                    </span>
                @endforeach
            </div>
        @endif
        <div class="mt-auto pt-2">
            <a href="{{ route('platform.home') }}" class="inline-flex items-center gap-1 text-micro font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">
                Manage tenants
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    @endif
</x-widgets.frame>
