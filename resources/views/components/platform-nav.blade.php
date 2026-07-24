{{--
 | Platform-owner left rail — the workspace switcher plus the platform section
 | navigation. Mirrors the My-workspace rail (x-my-nav) so all workspaces share
 | the same structure and styling. Design-system tokens only.
 |
 | @param  string  $active  Current section key: overview|courses|widgets|ai|email.
--}}
@props(['active' => ''])

@php
    $groups = [
        'Platform' => [
            ['key' => 'overview', 'label' => 'Overview', 'href' => route('platform.home'),
             'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>'],
            ['key' => 'courses', 'label' => 'Global Courses', 'href' => route('platform.courses'),
             'icon' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>'],
            ['key' => 'widgets', 'label' => 'Widget Library', 'href' => route('platform.widgets'),
             'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'],
            ['key' => 'outbound', 'label' => 'Outbound', 'href' => route('platform.outbound'),
             'icon' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>'],
        ],
        'Integrations' => [
            ['key' => 'ai', 'label' => 'AI &amp; Voice', 'href' => route('platform.ai'),
             'icon' => '<path d="m12 3 2.1 5.8L20 10l-5.9 1.2L12 17l-2.1-5.8L4 10l5.9-1.2z"/>'],
            ['key' => 'email', 'label' => 'Email', 'href' => route('platform.email'),
             'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'],
        ],
    ];
@endphp

<aside class="w-full lg:w-rail lg:flex-none rail-sticky" aria-label="Platform navigation">
    <x-workspace-switcher active="platform" />

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
                                    <span class="min-w-0 flex-1 truncate">{!! $item['label'] !!}</span>
                                    <span class="flex-none rounded-full bg-line-soft px-1.5 py-0.5 text-nano font-semibold text-ink-soft">Soon</span>
                                </span>
                            @else
                                <a href="{{ $item['href'] }}" @if ($isActive) aria-current="page" @endif class="{{ $rowBase }}{{ $rowBorder }}{{ $tone }}">
                                    <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                    <span class="min-w-0 flex-1 truncate">{!! $item['label'] !!}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>
</aside>
