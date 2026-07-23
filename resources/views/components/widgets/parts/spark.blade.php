{{--
 | Sparkline — a compact bar chart of a real numeric series (e.g. monthly
 | completions). Bar heights are data-driven percentages of the series max, so
 | they are computed, not tokenised. Colour uses the brand token; the newest bar
 | is emphasised. Decorative (aria-hidden) — the figure it supports is in text.
 |
 | @param array   $values   int[]
 | @param string  $height   Tailwind height utility for the track.
--}}
@props([
    'values' => [],
    'height' => 'h-8',
])

@php
    $ints = array_map(static fn ($v): int => (int) $v, $values);
    $max = count($ints) > 0 ? max($ints) : 0;
    $max = $max > 0 ? $max : 1;
    $last = count($ints) - 1;
@endphp

<div class="flex items-end gap-0.5 {{ $height }}" aria-hidden="true">
    @foreach ($ints as $i => $v)
        @php $pct = max(6, (int) round($v / $max * 100)); @endphp
        <span class="flex-1 rounded-t-sm {{ $i === $last ? 'bg-teachhq' : 'bg-teachhq/50' }}" style="height: {{ $pct }}%"></span>
    @endforeach
</div>
