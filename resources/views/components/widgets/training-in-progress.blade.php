{{-- In progress — courses started but not finished, with a "jump back in"
     resume card on M/L. No comparison (progress has no honest history). --}}
@props(['widget' => [], 'metric' => [], 'size' => 'm', 'comparison' => null, 'hasData' => true])

@php
    $name = $widget['name'] ?? 'In progress';
    $icon = $widget['icon'] ?? '';
    $count = (int) ($metric['count'] ?? 0);
    $resume = $metric['resume'] ?? null;
    $resumeProgress = (int) ($resume['progress'] ?? 0);
    $resumeHref = ! empty($resume['course_id']) ? route('my.courses.show', $resume['course_id']) : route('my.courses');
@endphp

<x-widgets.frame :title="$name" :icon="$icon" :size="$size">
    @unless ($hasData)
        <x-widgets.parts.empty :size="$size" :icon="$icon"
            message="Nothing in progress"
            hint="Courses you start will show here so you can pick up where you left off." />
    @else
        <p class="text-3xl font-black leading-none tabular-nums text-slatecard">{{ $count }}</p>
        @if ($size !== 's' && $resume !== null)
            <div class="mt-2 rounded-control bg-paper p-2.5">
                <p class="truncate text-mini font-semibold text-slatecard">{{ $resume['title'] }}</p>
                <div class="mt-1.5 flex items-center gap-2">
                    <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-line-soft" role="progressbar" aria-valuenow="{{ $resumeProgress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress">
                        <span class="block h-full rounded-full bg-teachhq" style="width: {{ $resumeProgress }}%"></span>
                    </span>
                    <a href="{{ $resumeHref }}" class="flex-none rounded-full bg-button-primary px-2.5 py-1 text-nano font-bold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">Continue</a>
                </div>
            </div>
        @elseif ($size !== 's')
            <p class="mt-1 text-mini text-ink-soft">courses on the go</p>
        @endif
    @endunless
</x-widgets.frame>
