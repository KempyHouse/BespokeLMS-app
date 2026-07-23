{{--
 | Widget empty state — shown when the signed-in user has no data for a widget
 | yet (e.g. no training assigned). Honest, calm, and scaled to the widget size.
 |
 | @param string       $size
 | @param string       $message
 | @param string|null  $hint
 | @param string       $icon
--}}
@props([
    'size' => 'm',
    'message' => 'Nothing to show yet.',
    'hint' => null,
    'icon' => '',
])

<div class="flex h-full flex-col items-center justify-center gap-1.5 py-2 text-center">
    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-line-soft text-ink-faint" aria-hidden="true">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
    </span>
    <p class="text-caption font-semibold text-ink-soft">{{ $message }}</p>
    @if ($hint && $size !== 's')
        <p class="max-w-xs text-mini leading-snug text-ink-faint">{{ $hint }}</p>
    @endif
</div>
