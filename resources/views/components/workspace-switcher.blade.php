{{--
 | Workspace switcher — the left-rail selector that moves the signed-in user
 | between the My, Team and Platform workspaces.
 |
 | Extracted from platform/home.blade.php so a single component is shared by the
 | Platform console and the rebuilt My / Team pages. The Platform tab is only
 | rendered for the platform owner (the /platform route 404s everyone else).
 |
 | The active pill slides between tabs: a single decorative indicator is
 | absolutely positioned in the track and translated to the active column via
 | the --ws-index custom property (see .ws-switch in resources/css/app.css).
 | Each tab is a real link, so the control works without JavaScript; the script
 | below is progressive enhancement that lets the pill finish sliding before it
 | follows the link, and steps aside for prefers-reduced-motion.
 |
 | @param  string  $active  Which tab is current: 'my' | 'team' | 'platform'.
--}}
@props(['active' => 'my'])

@php
    $currentUser = $user ?? request()->user();
    $showPlatform = $currentUser?->isPlatformOwner() ?? false;

    $tabs = [
        ['key' => 'my', 'label' => 'My', 'href' => route('my.home')],
        ['key' => 'team', 'label' => 'Team', 'href' => route('team.home')],
    ];

    if ($showPlatform) {
        $tabs[] = ['key' => 'platform', 'label' => 'Platform', 'href' => route('platform.home')];
    }

    $gridClass = count($tabs) === 3 ? 'grid-cols-3' : 'grid-cols-2';

    $activeIndex = 0;
    foreach ($tabs as $i => $tab) {
        if ($tab['key'] === $active) {
            $activeIndex = $i;
            break;
        }
    }
@endphp

<div class="ws-switch mb-4 grid {{ $gridClass }} rounded-full border border-line bg-surface p-1 text-sm font-semibold shadow-panel"
     role="tablist" aria-label="Workspace"
     data-ws-switch
     style="--ws-count: {{ count($tabs) }}; --ws-index: {{ $activeIndex }};">
    {{-- Sliding indicator — decorative; sits behind the tab labels. --}}
    <span class="ws-switch__pill rounded-full bg-teachhq shadow-panel" aria-hidden="true"></span>

    @foreach ($tabs as $i => $tab)
        <a href="{{ $tab['href'] }}"
           role="tab"
           data-ws-tab="{{ $i }}"
           aria-selected="{{ $tab['key'] === $active ? 'true' : 'false' }}"
           @if ($tab['key'] === $active) aria-current="page" @endif
           class="ws-switch__tab rounded-full py-2 text-center transition focus:outline-none focus:ring-2 focus:ring-teachhq {{ $tab['key'] === $active ? 'text-on-brand' : 'text-ink-soft hover:text-slatecard' }}">{{ $tab['label'] }}</a>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            (function () {
                'use strict';

                function onActivate(event) {
                    var tab = event.target.closest('[data-ws-switch] [data-ws-tab]');
                    if (!tab) {
                        return;
                    }

                    var track = tab.closest('[data-ws-switch]');
                    var index = tab.getAttribute('data-ws-tab');

                    // Already the current tab — let the browser follow the link.
                    if (track.style.getPropertyValue('--ws-index').trim() === index) {
                        return;
                    }

                    // Respect reduced-motion: no slide, navigate immediately.
                    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }

                    event.preventDefault();
                    var href = tab.getAttribute('href');

                    // Slide the pill to the clicked column and move the on-brand
                    // text emphasis with it so the label stays legible in transit.
                    track.style.setProperty('--ws-index', index);
                    var tabs = track.querySelectorAll('[data-ws-tab]');
                    for (var i = 0; i < tabs.length; i++) {
                        var isTarget = tabs[i] === tab;
                        tabs[i].classList.toggle('text-on-brand', isTarget);
                        tabs[i].classList.toggle('text-ink-soft', !isTarget);
                        tabs[i].setAttribute('aria-selected', isTarget ? 'true' : 'false');
                    }

                    // Navigate once the pill has finished sliding, with a timeout
                    // fallback in case transitionend does not fire.
                    var pill = track.querySelector('.ws-switch__pill');
                    var navigated = false;
                    function go() {
                        if (navigated) {
                            return;
                        }
                        navigated = true;
                        window.location.assign(href);
                    }

                    if (pill) {
                        pill.addEventListener('transitionend', function handler(e) {
                            if (e.propertyName === 'transform') {
                                pill.removeEventListener('transitionend', handler);
                                go();
                            }
                        });
                    }

                    var slide = parseFloat(getComputedStyle(track).getPropertyValue('--duration-ws-slide')) || 360;
                    window.setTimeout(go, slide + 80);
                }

                document.addEventListener('click', onActivate);
            })();
        </script>
    @endpush
@endonce
