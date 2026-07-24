{{--
 | Team-workspace left rail — the workspace switcher plus the grouped navigation
 | for the team experience.
 |
 | Styled to match the My workspace rail exactly (borderless bg-paper container,
 | brand-coloured section headers, rail-item rows with bottom dividers) using
 | design-system tokens only.
 |
 | @param  string  $active  Current item key, e.g. 'team-dashboard'.
--}}
@props(['active' => ''])

@php
    $groups = [
        'Team workspace' => [
            ['key' => 'team-dashboard', 'label' => 'Team Dashboard', 'href' => route('team.home'),
             'icon' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>'],
            ['key' => 'team-members', 'label' => 'Team Members', 'soon' => true,
             'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
        ],
    ];
@endphp

<aside class="w-full lg:w-rail lg:flex-none rail-sticky" aria-label="Team workspace navigation">
    <x-workspace-switcher active="team" />

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
