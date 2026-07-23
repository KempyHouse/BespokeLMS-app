{{--
    Design-system dropdown. A native <select> re-skinned with DS tokens: the OS
    (Microsoft/native) arrow is removed with appearance-none and replaced by a
    token-coloured chevron. Use everywhere instead of a bare <select> so every
    dropdown looks identical. Pass name/id/aria-label as normal attributes; the
    <option> markup goes in the slot.
--}}
<div class="relative">
    <select {{ $attributes->merge(['class' => 'w-full appearance-none rounded-control border border-line bg-surface py-2 pl-3 pr-9 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq']) }}>
        {{ $slot }}
    </select>
    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</div>
