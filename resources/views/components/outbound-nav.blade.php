{{--
 | Outbound secondary sub-rail — sits beside x-platform-nav on Outbound pages
 | (chosen layout: secondary sub-rail). Lists the Outbound sub-areas; only the
 | active ones link, the rest carry a "Soon" tag. Design-system tokens only.
 |
 | @param  string  $active  Current sub-area key: system-emails|transactional|…
--}}
@props(['active' => ''])

@php
    $groups = [
        'Messages' => [
            ['key' => 'system-emails', 'label' => 'System Emails', 'href' => route('platform.outbound.system-emails')],
            ['key' => 'transactional', 'label' => 'Transactional', 'soon' => true],
            ['key' => 'marketing', 'label' => 'Marketing', 'soon' => true],
        ],
        'Channels' => [
            ['key' => 'sms', 'label' => 'SMS', 'soon' => true],
            ['key' => 'whatsapp', 'label' => 'WhatsApp', 'soon' => true],
            ['key' => 'social', 'label' => 'Social Media', 'soon' => true],
        ],
        'More' => [
            ['key' => 'notifications', 'label' => 'Notifications', 'soon' => true],
            ['key' => 'score', 'label' => 'Score Messages', 'soon' => true],
            ['key' => 'logs', 'label' => 'Logs', 'soon' => true],
        ],
    ];
@endphp

<aside class="w-full lg:w-52 lg:flex-none" aria-label="Outbound sections">
    <div class="rounded-control bg-paper p-5">
        @foreach ($groups as $groupLabel => $items)
            <nav @class(['mb-5' => ! $loop->last]) aria-label="{{ $groupLabel }}">
                <div @class([
                    'mb-2 text-nano font-bold uppercase tracking-wider text-teachhq',
                    'border-t border-line pt-4' => ! $loop->first,
                ])>{{ $groupLabel }}</div>
                <ul>
                    @foreach ($items as $item)
                        @php $isActive = ($item['key'] ?? '') === $active; @endphp
                        <li>
                            @if (! empty($item['soon']))
                                <span class="flex items-center justify-between gap-2 py-2 text-sm font-medium text-ink-faint cursor-not-allowed" aria-disabled="true">
                                    <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                                    <span class="flex-none rounded-full bg-line-soft px-1.5 py-0.5 text-nano font-semibold text-ink-soft">Soon</span>
                                </span>
                            @else
                                <a href="{{ $item['href'] }}" @if ($isActive) aria-current="page" @endif
                                   class="flex items-center py-2 text-sm font-medium transition focus:outline-none {{ $isActive ? 'font-semibold text-teachhq-dark' : 'text-slatecard hover:text-teachhq' }}">
                                    <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>
</aside>
