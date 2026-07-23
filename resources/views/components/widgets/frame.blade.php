{{--
 | Widget frame — the shared card chrome for every dashboard widget.
 |
 | Provides the token-driven surface card, the header row (icon + title, plus an
 | optional top-right {{ $header }} slot for a delta chip), the body ({{ $slot }})
 | and an optional {{ $footer }} slot (links / comparison selector). Size only
 | affects padding here; each widget decides how much detail its S/M/L body shows.
 |
 | @param string       $title
 | @param string       $icon    Inline SVG path markup (from the registry).
 | @param string       $size    's' | 'm' | 'l'
 | @param string|null  $accent  null | 'red' | 'green' | 'amber' | 'brand' — top border cue.
--}}
@props([
    'title' => '',
    'icon' => '',
    'size' => 'm',
    'accent' => null,
])

@php
    $accentClass = match ($accent) {
        'red' => 'border-t-2 border-t-rag-red',
        'green' => 'border-t-2 border-t-rag-green',
        'amber' => 'border-t-2 border-t-rag-amber',
        'brand' => 'border-t-2 border-t-teachhq',
        default => '',
    };
    $pad = $size === 's' ? 'p-3' : 'p-4';
@endphp

<div class="flex h-full min-h-0 flex-col rounded-panel border border-line bg-surface {{ $accentClass }} {{ $pad }} shadow-quiet">
    <div class="flex items-start justify-between gap-2">
        <div class="flex min-w-0 items-center gap-1.5">
            <svg class="h-4 w-4 flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
            <h3 class="truncate text-micro font-bold uppercase tracking-wide text-ink-soft">{{ $title }}</h3>
        </div>
        @isset($header)
            <div class="flex-none">{{ $header }}</div>
        @endisset
    </div>

    <div class="mt-1.5 flex min-h-0 flex-1 flex-col">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="mt-2">{{ $footer }}</div>
    @endisset
</div>
