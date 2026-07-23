{{--
 | Workspace switcher — the left-rail selector that moves the signed-in user
 | between the My, Team and Platform workspaces.
 |
 | Extracted from platform/home.blade.php so a single component is shared by the
 | Platform console and the rebuilt My / Team pages. The Platform tab is only
 | rendered for the platform owner (the /platform route 404s everyone else).
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
@endphp

<div class="mb-4 grid {{ $gridClass }} gap-1 rounded-full border border-line bg-surface p-1 text-sm font-semibold shadow-panel"
     role="tablist" aria-label="Workspace">
    @foreach ($tabs as $tab)
        @if ($tab['key'] === $active)
            <span role="tab" aria-selected="true" aria-current="page"
                  class="ws-bounce rounded-full bg-teachhq py-2 text-center text-on-brand shadow-panel">{{ $tab['label'] }}</span>
        @else
            <a href="{{ $tab['href'] }}" role="tab" aria-selected="false"
               class="rounded-full py-2 text-center text-ink-soft transition hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">{{ $tab['label'] }}</a>
        @endif
    @endforeach
</div>
