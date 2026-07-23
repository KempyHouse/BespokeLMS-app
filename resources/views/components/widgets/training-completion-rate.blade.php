{{-- Completion rate — share of assigned courses completed, with a comparison
     selector (vs last week / month / year) and a trend sparkline on M/L. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Completion rate';
    $icon = $widget['icon'] ?? '';
    $opts = $widget['comparison_options'] ?? [];
    $sel = $comparison ?? ($widget['comparison_default'] ?? ($opts[0]['key'] ?? null));
    $pct = (int) ($metric['pct'] ?? 0);
    $trend = $metric['trend'] ?? [];
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size">
    @if ($hasData && ! empty($opts) && $size !== 's')
        <x-slot:header>
            <x-widgets.parts.delta :deltas="$metric['delta'] ?? []" :options="$opts" :selected="$sel" good-when="up" suffix="pt" />
        </x-slot:header>
    @endif

    @unless ($hasData)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="No completion rate yet"
            hint="Your completion rate builds as you finish assigned courses." />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">{{ $pct }}<span class="text-lg">%</span></p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">of your assigned courses completed</p>
            @if (! empty($trend))
                <div class="mt-2">
                    <x-widgets.parts.spark :values="$trend" height="h-7" />
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
