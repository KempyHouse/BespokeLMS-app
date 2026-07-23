{{--
 | Breadcrumb — the shared "where am I / go back a tier" trail shown at the top
 | of a page's content column. Replaces the per-page hand-rolled single
 | back-links so every screen exposes the full path and lets the user jump to
 | any ancestor tier in one click. Design-system tokens / existing utilities
 | only; mirrors the trail already used on the My Course Library pages.
 |
 | @param  array  $items  Ordered trail, root first. Each entry:
 |                        ['label' => string, 'href' => ?string].
 |                        Ancestors provide an href and render as links; the
 |                        final item is the current page and renders as plain
 |                        bold text with aria-current="page".
 |
 | Example:
 |   <x-breadcrumb :items="[
 |       ['label' => 'Platform', 'href' => route('platform.home')],
 |       ['label' => 'Global Courses', 'href' => route('platform.courses')],
 |       ['label' => $course['title']],
 |   ]" />
--}}
@props(['items' => []])

@if (! empty($items))
    <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-mini text-ink-soft" aria-label="Breadcrumb">
        @foreach ($items as $item)
            @if (! $loop->last && ! empty($item['href']))
                <a href="{{ $item['href'] }}"
                   class="transition hover:text-teachhq focus:outline-none focus-visible:underline">{{ $item['label'] }}</a>
            @else
                <span @class(['min-w-0 truncate font-semibold text-slatecard' => $loop->last])
                      @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
            @endif

            @unless ($loop->last)
                <svg class="h-3.5 w-3.5 flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            @endunless
        @endforeach
    </nav>
@endif
