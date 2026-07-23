{{-- To complete — assigned courses not yet finished, against the total assigned. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'To complete';
    $icon = $widget['icon'] ?? '';
    $opts = $widget['comparison_options'] ?? [];
    $sel = $comparison ?? ($widget['comparison_default'] ?? ($opts[0]['key'] ?? null));
    $count = (int) ($metric['count'] ?? 0);
    $total = (int) ($metric['total'] ?? 0);
    $completed = (int) ($metric['completed'] ?? 0);
    $pct = $total > 0 ? (int) round($completed / $total * 100) : 0;
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size">
    @if ($hasData && ! empty($opts) && $size !== 's')
        <x-slot:header>
            <x-widgets.parts.delta :deltas="$metric['delta'] ?? []" :options="$opts" :selected="$sel" good-when="down" />
        </x-slot:header>
    @endif

    @unless ($hasData)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="Nothing to complete yet"
            hint="Courses assigned to you will be tracked here." />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">
            {{ $count }}<span class="text-base font-bold text-ink-faint"> / {{ $total }}</span>
        </p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">{{ $completed }} of {{ $total }} completed</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line-soft" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Completed">
                <span class="block h-full rounded-full bg-teachhq" style="width: {{ $pct }}%"></span>
            </div>
        @endif
        @if ($size === 'l')
            <div class="mt-auto pt-2">
                <a href="{{ route('my.courses') }}" class="inline-flex items-center gap-1 text-micro font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">
                    Go to my courses
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>
        @endif
    @endunless
</x-widgets.frame>
