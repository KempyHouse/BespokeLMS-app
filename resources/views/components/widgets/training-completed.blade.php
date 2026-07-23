{{-- Completed — courses finished in the last 12 months, with vs last month/year
     comparison and a 12-month trend (spark on M, monthly bars on L). --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Completed';
    $icon = $widget['icon'] ?? '';
    $opts = $widget['comparison_options'] ?? [];
    $sel = $comparison ?? ($widget['comparison_default'] ?? ($opts[0]['key'] ?? null));
    $count = (int) ($metric['count'] ?? 0);
    $trend = $metric['trend'] ?? [];
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size">
    @if ($hasData && ! empty($opts) && $size !== 's')
        <x-slot:header>
            <x-widgets.parts.delta :deltas="$metric['delta'] ?? []" :options="$opts" :selected="$sel" good-when="up" />
        </x-slot:header>
    @endif

    @unless ($hasData)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="Nothing completed yet"
            hint="Completed courses from the last 12 months are counted here." />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">{{ $count }}</p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">completed in the last 12 months</p>
            @if (! empty($trend))
                <div class="mt-2">
                    <x-widgets.parts.spark :values="$trend" :height="$size === 'l' ? 'h-16' : 'h-7'" />
                </div>
            @endif
        @endif

        @if (count($opts) > 1 && $size !== 's')
            <x-slot:footer>
                <div class="relative inline-flex items-center">
                    <select data-widget-compare aria-label="Comparison period for {{ $name }}"
                            class="appearance-none rounded-control border border-line bg-surface py-1 pl-2.5 pr-7 text-mini font-medium text-ink-soft focus:outline-none focus:ring-2 focus:ring-teachhq">
                        @foreach ($opts as $o)
                            <option value="{{ $o['key'] }}" @selected($o['key'] === $sel)>{{ $o['label'] }}</option>
                        @endforeach
                    </select>
                    <svg class="select-chevron pointer-events-none absolute right-2 h-3.5 w-3.5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </x-slot:footer>
        @endif
    @endunless
</x-widgets.frame>
