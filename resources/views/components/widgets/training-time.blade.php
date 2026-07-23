{{-- Training time — learning time in the last 12 months. Time comes from real
     course attempts; empty until the course player logs any. L shows a monthly
     breakdown. Comparison deltas are shown in whole hours. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Training time';
    $icon = $widget['icon'] ?? '';
    $opts = $widget['comparison_options'] ?? [];
    $sel = $comparison ?? ($widget['comparison_default'] ?? ($opts[0]['key'] ?? null));
    $seconds = (int) ($metric['seconds'] ?? 0);
    $hours = intdiv($seconds, 3600);
    $mins = intdiv($seconds % 3600, 60);
    $months = $metric['months'] ?? [];
    $trend = $metric['trend'] ?? [];
    $anyTime = $seconds > 0;

    // Comparison deltas are stored in seconds; show them in whole hours.
    $rawDelta = $metric['delta'] ?? [];
    $deltaHours = [];
    foreach ($rawDelta as $k => $v) {
        $deltaHours[$k] = (int) round(((int) $v) / 3600);
    }
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size">
    @if ($hasData && $anyTime && ! empty($opts) && $size !== 's')
        <x-slot:header>
            <x-widgets.parts.delta :deltas="$deltaHours" :options="$opts" :selected="$sel" good-when="up" suffix="h" />
        </x-slot:header>
    @endif

    @if (! $hasData || ! $anyTime)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="No learning time yet"
            hint="Time is recorded automatically as you work through course content." />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">
            {{ $hours }}<span class="text-lg">h</span> {{ $mins }}<span class="text-lg">m</span>
        </p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">in the last 12 months</p>
        @endif

        @if ($size === 'l' && ! empty($months))
            <div class="mt-3 grid grid-cols-3 gap-1.5">
                @foreach (array_slice($months, -6) as $m)
                    @php $h = intdiv((int) ($m['seconds'] ?? 0), 3600); $mm = intdiv(((int) ($m['seconds'] ?? 0)) % 3600, 60); @endphp
                    <div class="rounded-control bg-paper px-2 py-1.5">
                        <div class="text-nano font-bold uppercase tracking-wide text-ink-faint">{{ $m['label'] ?? '' }}</div>
                        <div class="text-mini font-black tabular-nums text-slatecard">{{ $h }}h {{ $mm }}m</div>
                    </div>
                @endforeach
            </div>
        @elseif ($size === 'm' && ! empty($trend))
            <div class="mt-2">
                <x-widgets.parts.spark :values="$trend" height="h-7" />
            </div>
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
    @endif
</x-widgets.frame>
