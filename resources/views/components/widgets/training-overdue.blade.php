{{-- Overdue training — assigned courses past their due date. Empty until the
     user has assignments; "all clear" when none are overdue; red when some are. --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'Overdue';
    $icon = $widget['icon'] ?? '';
    $opts = $widget['comparison_options'] ?? [];
    $sel = $comparison ?? ($widget['comparison_default'] ?? ($opts[0]['key'] ?? null));
    $count = (int) ($metric['count'] ?? 0);
    $within7 = (int) ($metric['due_within_7'] ?? 0);
    $oldest = $metric['oldest_days_late'] ?? null;
    $clear = $count === 0;
    $accent = ! $hasData ? null : ($clear ? 'green' : 'red');
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size" :accent="$accent">
    @if ($hasData && ! $clear && ! empty($opts) && $size !== 's')
        <x-slot:header>
            <x-widgets.parts.delta :deltas="$metric['delta'] ?? []" :options="$opts" :selected="$sel" good-when="down" />
        </x-slot:header>
    @endif

    @unless ($hasData)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="No training assigned yet"
            hint="Overdue courses appear here once training is assigned to you." />
    @elseif ($clear)
        <div class="flex flex-1 flex-col items-start justify-center gap-1">
            <span class="inline-flex items-center gap-1.5 text-rag-green">
                <svg class="h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <span class="text-lg font-black">All clear</span>
            </span>
            @if ($size !== 's')
                <p class="text-mini text-ink-soft">No overdue courses — nice work.</p>
            @endif
        </div>
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-rag-red">{{ $count }}</p>
        @if ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">
                {{ $within7 }} due within 7 days
                @if ($oldest !== null) &middot; oldest {{ $oldest }} {{ \Illuminate\Support\Str::plural('day', (int) $oldest) }} late @endif
            </p>
        @endif
        <div class="mt-auto pt-2">
            <a href="{{ route('my.courses') }}" class="inline-flex items-center gap-1 text-micro font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:underline">
                View overdue courses
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    @endunless
</x-widgets.frame>
