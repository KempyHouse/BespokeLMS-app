{{--
 | My-workspace left rail — the workspace switcher plus the grouped navigation
 | (My workspace + Browse) for the learner experience.
 |
 | Styled to match the Platform rail exactly (borderless bg-paper container,
 | brand-coloured section headers, rail-item rows with bottom dividers) using
 | design-system tokens only. Live destinations link; areas that arrive in a
 | later slice render but are inert (flagged "Soon"). "Help & support" opens the
 | existing help-chat drawer.
 |
 | @param  string  $active  Current item key, e.g. 'my-learning' | 'course-library'.
--}}
@props(['active' => ''])

@php
    $groups = [
        'My workspace' => [
            ['key' => 'my-learning', 'label' => 'My Learning', 'href' => route('my.home'),
             'icon' => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1 2 3 6 3s6-2 6-3v-5"/>'],
            ['key' => 'my-certificates', 'label' => 'My Certificates', 'soon' => true,
             'icon' => '<circle cx="12" cy="8" r="6"/><path d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5"/>'],
            ['key' => 'my-account', 'label' => 'My Account', 'href' => route('profile.edit'),
             'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
            ['key' => 'my-business', 'label' => 'My Business', 'soon' => true,
             'icon' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M16 10h.01M8 10h.01M12 14h.01M16 14h.01M8 14h.01"/>'],
        ],
        'Browse' => [
            ['key' => 'course-library', 'label' => 'Course Library', 'href' => route('my.courses'),
             'icon' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>'],
            ['key' => 'achievements', 'label' => 'Achievements', 'soon' => true,
             'icon' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2Z"/>'],
            ['key' => 'resources', 'label' => 'Resources', 'soon' => true,
             'icon' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>'],
            ['key' => 'help-support', 'label' => 'Help & support', 'action' => 'openChat',
             'icon' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>'],
        ],
    ];
@endphp

<aside class="w-full lg:w-rail lg:flex-none rail-sticky" aria-label="My workspace navigation">
    <x-workspace-switcher active="my" />

    <div class="rounded-control bg-paper p-6">
        @foreach ($groups as $groupLabel => $items)
            <nav @class(['mb-6' => $loop->first]) aria-label="{{ $groupLabel }}">
                <div @class([
                    'mb-2.5 text-xs font-bold uppercase tracking-wider text-teachhq',
                    'border-t border-line pt-5' => ! $loop->first,
                ])>{{ $groupLabel }}</div>
                <ul>
                    @foreach ($items as $item)
                        @php
                            $isActive = $item['key'] === $active;
                            $rowBase = 'rail-item flex items-center gap-2.5 py-2.5 text-sm font-medium transition focus:outline-none';
                            $rowBorder = $loop->last ? '' : ' border-b border-line';
                            $tone = $isActive ? ' font-semibold text-teachhq-dark' : ' text-slatecard hover:text-teachhq';
                        @endphp
                        <li>
                            @if (isset($item['soon']))
                                <span class="flex items-center gap-2.5 py-2.5 text-sm font-medium text-ink-faint cursor-not-allowed{{ $rowBorder }}" aria-disabled="true">
                                    <svg class="h-icon w-icon flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                    <span class="flex-none rounded-full bg-line-soft px-1.5 py-0.5 text-nano font-semibold text-ink-soft">Soon</span>
                                </span>
                            @elseif (isset($item['action']))
                                <button type="button" onclick="{{ $item['action'] }}()" class="w-full text-left {{ $rowBase }}{{ $rowBorder }}{{ $tone }}">
                                    <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                </button>
                            @else
                                <a href="{{ $item['href'] }}" @if ($isActive) aria-current="page" @endif class="{{ $rowBase }}{{ $rowBorder }}{{ $tone }}">
                                    <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>
</aside>
