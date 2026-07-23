<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-slatecard">
    @php($hdrUser = $user ?? null)

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-control focus:bg-teachhq focus:px-4 focus:py-2 focus:font-semibold focus:text-on-brand">
        Skip to content
    </a>

    <!-- Utility bar -->
    <header class="sticky top-0 z-50 border-b border-line bg-surface/95 backdrop-blur">
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 sm:gap-5 sm:px-6 lg:flex-nowrap lg:gap-6 lg:px-12">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 whitespace-nowrap text-xl font-black text-slatecard" aria-label="{{ config('app.name') }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teachhq text-base font-black text-on-brand" aria-hidden="true">B</span>
                <span>Bespoke<b class="text-teachhq">LMS</b></span>
            </a>

            <!-- Search -->
            <form role="search" action="{{ route('platform.home') }}" method="GET"
                  class="relative order-last w-full lg:order-none lg:mx-auto lg:w-search">
                <label for="platform-search" class="sr-only">Search the platform</label>
                <input id="platform-search" type="search" name="q" placeholder="Search tenants, courses or users"
                       class="w-full rounded-full border border-line bg-surface py-2 pl-5 pr-11 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-teachhq">
                <button type="submit" aria-label="Search"
                        class="absolute right-1 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-teachhq text-on-brand transition hover:bg-teachhq-dark focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">
                    <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </form>

            <!-- Account menu -->
            <div class="relative ml-auto lg:ml-0" data-account-menu>
                <button type="button" aria-haspopup="menu" aria-expanded="false" data-account-toggle
                        class="flex items-center gap-2 rounded-full py-0.5 pl-0.5 pr-2 transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-teachhq text-sm font-bold text-on-brand" aria-hidden="true">{{ $hdrUser?->initials() ?? 'U' }}</span>
                    <span class="hidden min-w-0 text-left sm:block">
                        <span class="block truncate text-sm font-semibold text-slatecard">{{ $hdrUser?->displayName() ?? 'Account' }}</span>
                        <span class="block truncate text-mini text-ink-soft">{{ $hdrUser?->roleLabel() }}</span>
                    </span>
                    <svg class="h-4 w-4 flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div role="menu" aria-label="Account menu" data-account-panel
                     class="absolute right-0 top-full z-50 mt-2 hidden w-menu rounded-panel border border-line bg-surface p-2 shadow-panel">
                    <div class="flex items-center gap-3 px-2.5 py-2">
                        <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-teachhq text-sm font-bold text-on-brand" aria-hidden="true">{{ $hdrUser?->initials() ?? 'U' }}</span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slatecard">{{ $hdrUser?->displayName() ?? 'Account' }}</div>
                            <div class="truncate text-mini text-ink-soft">{{ $hdrUser?->email }}</div>
                            <span class="mt-1 inline-flex items-center rounded-full bg-teachhq-soft px-2 py-0.5 text-micro font-bold text-teachhq-dark">{{ $hdrUser?->roleLabel() }}</span>
                        </div>
                    </div>
                    <div class="my-1.5 h-px bg-line-soft"></div>
                    <a href="{{ route('dashboard') }}" role="menuitem"
                       class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                        <svg class="h-icon w-icon flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                        Back to dashboard
                    </a>
                    <div class="my-1.5 h-px bg-line-soft"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-slatecard transition hover:bg-rag-red-soft hover:text-rag-red focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            <svg class="h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="px-4 pb-14 pt-8 sm:px-6 lg:px-12">
        @yield('content')
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const menu = document.querySelector('[data-account-menu]');
        if (!menu) return;
        const toggle = menu.querySelector('[data-account-toggle]');
        const panel = menu.querySelector('[data-account-panel]');

        function close() {
            panel.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        }
        function open() {
            panel.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.contains('hidden') ? open() : close();
        });
        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target)) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });
    });
    </script>
</body>
</html>
