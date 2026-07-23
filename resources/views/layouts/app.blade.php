@php($themePref = ($user ?? auth()->user())?->themePreference ?? 'system')
<!DOCTYPE html>
<html lang="en" class="antialiased" data-theme-pref="{{ $themePref }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    {{-- Resolve light/dark before paint (from the saved preference, or the OS
         setting when 'system') so there is no flash of the wrong theme. --}}
    <script>(function(){var el=document.documentElement,p=el.getAttribute('data-theme-pref')||'system',d=p==='dark'||(p==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);el.setAttribute('data-theme',d?'dark':'light');})();</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Supabase-driven design tokens: platform defaults merged with the tenant
         brand kit, emitted as CSS variables (light in :root, dark overrides in
         [data-theme='dark']) after the compiled stylesheet so they win. Absent
         when the database is unreachable (compiled @theme then holds). Values
         are sanitised in App\Support\Theme\ThemeResolver. --}}
    @php($brandLight = $brandTokensCss ?? '')
    @php($brandDark = $brandTokensDarkCss ?? '')
    @if ($brandLight !== '' || $brandDark !== '')
        <style id="brand-tokens">@if ($brandLight !== ''):root{ {!! $brandLight !!} }@endif @if ($brandDark !== '')[data-theme='dark']{ {!! $brandDark !!} }@endif</style>
    @endif
</head>
<body class="flex min-h-screen flex-col bg-paper text-slatecard">
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

            <!-- Header actions -->
            <div class="ml-auto flex items-center gap-2.5 lg:ml-0">
            <!-- Help chat -->
            <button type="button" id="chatBtn" onclick="toggleChat()" aria-haspopup="dialog" aria-expanded="false" aria-controls="chatPanel" aria-label="Help chat"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-control bg-paper text-ink-muted transition hover:bg-line hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </button>

            <!-- Notifications -->
            <button type="button" id="notifBell" onclick="toggleNotifications()" aria-haspopup="dialog" aria-expanded="false" aria-controls="notifPanel" aria-label="Notifications"
                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-control bg-paper text-ink-muted transition hover:bg-line hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <span id="notifBadge" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rag-red px-1 text-nano font-bold leading-none text-on-brand ring-2 ring-surface">4</span>
            </button>

            <!-- Account menu -->
            <div class="relative" data-account-menu>
                <button type="button" aria-haspopup="menu" aria-expanded="false" data-account-toggle
                        class="flex items-center gap-2 rounded-full py-0.5 pl-0.5 pr-2 transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">
                    @if ($hdrUser?->avatarUrl())
                        <img src="{{ $hdrUser->avatarUrl() }}" alt="" class="h-9 w-9 flex-none rounded-full object-cover">
                    @else
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-teachhq text-sm font-bold text-on-brand" aria-hidden="true">{{ $hdrUser?->initials() ?? 'U' }}</span>
                    @endif
                    <span class="hidden min-w-0 text-left sm:block">
                        <span class="block truncate text-sm font-semibold text-slatecard">{{ $hdrUser?->displayName() ?? 'Account' }}</span>
                        <span class="block truncate text-mini text-ink-soft">{{ $hdrUser?->roleLabel() }}</span>
                    </span>
                    <svg class="select-chevron h-4 w-4 flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div role="menu" aria-label="Account menu" data-account-panel
                     class="absolute right-0 top-full z-50 mt-2 hidden w-menu rounded-panel border border-line bg-surface p-2 shadow-panel">
                    {{-- Identity --}}
                    <div class="flex items-center gap-3 px-2.5 py-2">
                        @if ($hdrUser?->avatarUrl())
                            <img src="{{ $hdrUser->avatarUrl() }}" alt="" class="h-10 w-10 flex-none rounded-full object-cover">
                        @else
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-teachhq text-sm font-bold text-on-brand" aria-hidden="true">{{ $hdrUser?->initials() ?? 'U' }}</span>
                        @endif
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slatecard">{{ $hdrUser?->displayName() ?? 'Account' }}</div>
                            <div class="truncate text-mini text-ink-soft">{{ $hdrUser?->email }}</div>
                            @if ($hdrUser?->roleLabel())
                                <span class="mt-1 inline-flex items-center rounded-full bg-teachhq-soft px-2 py-0.5 text-micro font-bold text-teachhq-dark">{{ $hdrUser?->roleLabel() }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="my-1.5 h-px bg-line-soft"></div>

                    {{-- Preferences --}}
                    <div class="px-2.5 pb-1 pt-1 text-nano font-bold uppercase tracking-wider text-ink-faint">Preferences</div>
                    <a href="{{ route('profile.edit') }}#preferences" role="menuitem"
                       class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                        <svg class="h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                        Personal preferences
                    </a>
                    <span class="flex w-full cursor-not-allowed items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-ink-faint" aria-disabled="true">
                        <svg class="h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        Notification settings
                        <span class="ml-auto rounded-full bg-line-soft px-1.5 py-0.5 text-nano font-semibold text-ink-soft">Soon</span>
                    </span>

                    {{-- Theme (live) --}}
                    <div class="px-2.5 py-2" data-theme-endpoint="{{ route('preferences.theme') }}">
                        <div class="mb-1.5 flex items-center gap-2 text-nano font-bold uppercase tracking-wider text-ink-faint">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                            Theme
                        </div>
                        <div class="grid grid-cols-3 gap-1 rounded-control border border-line bg-paper p-1" role="group" aria-label="Theme preference">
                            <button type="button" data-theme-set="light" class="theme-opt rounded-lg py-1.5 text-mini font-semibold text-ink-soft transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Light</button>
                            <button type="button" data-theme-set="dark" class="theme-opt rounded-lg py-1.5 text-mini font-semibold text-ink-soft transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Dark</button>
                            <button type="button" data-theme-set="system" class="theme-opt rounded-lg py-1.5 text-mini font-semibold text-ink-soft transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">System</button>
                        </div>
                    </div>

                    <div class="my-1.5 h-px bg-line-soft"></div>

                    {{-- Account --}}
                    <div class="px-2.5 pb-1 pt-1 text-nano font-bold uppercase tracking-wider text-ink-faint">Account</div>
                    <a href="{{ route('profile.edit') }}" role="menuitem"
                       class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                        <svg class="h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My profile
                    </a>
                    <span class="flex w-full cursor-not-allowed items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-ink-faint" aria-disabled="true">
                        <svg class="h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                        Security &amp; password
                        <span class="ml-auto rounded-full bg-line-soft px-1.5 py-0.5 text-nano font-semibold text-ink-soft">Soon</span>
                    </span>
                    <a href="{{ route('dashboard') }}" role="menuitem"
                       class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                        <svg class="h-icon w-icon flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                        Back to dashboard
                    </a>

                    <div class="mt-1 flex items-center gap-2 px-2.5 py-1.5 text-mini text-ink-soft">
                        <span class="h-2 w-2 flex-none rounded-full bg-rag-green" aria-hidden="true"></span>
                        Signed in on this device
                    </div>

                    <div class="my-1.5 h-px bg-line-soft"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-slatecard transition hover:bg-rag-red-soft hover:text-rag-red focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            <svg class="h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1 px-4 pb-14 pt-8 sm:px-6 lg:px-12">
        @yield('content')
    </main>

    {{-- Temporary prototype reference links (MY-old / TEAM-old). They point at the
         frozen prototype served at "/" and are removed once the My and Team
         content has been migrated into the rebuilt Blade pages. --}}
    <footer class="border-t border-line bg-surface px-4 py-4 sm:px-6 lg:px-12">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-mini text-ink-soft">
            <span class="font-bold uppercase tracking-wider text-ink-faint">Prototype reference</span>
            <span class="text-ink-faint">Temporary &mdash; remove after content is migrated.</span>
            <nav class="flex items-center gap-3" aria-label="Prototype reference pages">
                <a href="{{ route('dashboard', ['ws' => 'my']) }}"
                   class="font-semibold text-teachhq underline-offset-2 transition hover:text-teachhq-dark hover:underline focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">MY-old</a>
                <a href="{{ route('dashboard', ['ws' => 'team']) }}"
                   class="font-semibold text-teachhq underline-offset-2 transition hover:text-teachhq-dark hover:underline focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">TEAM-old</a>
            </nav>
        </div>
    </footer>

    @include('partials.notifications-drawer')
    @include('partials.help-chat-drawer')

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

    <script>
    /* ===== Slide-in drawers: notifications + help chat (ported from prototype) ===== */
    (function () {
        var html = document.documentElement;
        var notifPanel = document.getElementById('notifPanel');
        var notifOverlay = document.getElementById('notifOverlay');
        var notifBell = document.getElementById('notifBell');
        var chatPanel = document.getElementById('chatPanel');
        var chatOverlay = document.getElementById('chatOverlay');
        var chatBtn = document.getElementById('chatBtn');

        function anyOpen() {
            return (notifPanel && notifPanel.classList.contains('is-open')) ||
                   (chatPanel && chatPanel.classList.contains('is-open'));
        }
        function syncScrollLock() {
            html.classList.toggle('overflow-hidden', anyOpen());
        }
        function setState(panel, overlay, trigger, open) {
            if (!panel) return;
            panel.classList.toggle('is-open', open);
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (overlay) overlay.classList.toggle('is-open', open);
            if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        /* Notifications */
        window.openNotifications = function () {
            window.closeChat();
            setState(notifPanel, notifOverlay, notifBell, true);
            syncScrollLock();
        };
        window.closeNotifications = function () {
            setState(notifPanel, notifOverlay, notifBell, false);
            syncScrollLock();
        };
        window.toggleNotifications = function () {
            notifPanel.classList.contains('is-open') ? window.closeNotifications() : window.openNotifications();
        };
        window.markAllNotifsRead = function () {
            document.querySelectorAll('#notifList [data-unread]').forEach(function (el) {
                el.removeAttribute('data-unread');
                el.classList.remove('bg-teachhq-soft/60');
                var d = el.querySelector('.notif-dot');
                if (d) d.classList.add('hidden');
            });
            var b = document.getElementById('notifBadge');
            if (b) b.classList.add('hidden');
            var c = document.getElementById('notifUnreadCount');
            if (c) c.textContent = '0';
        };
        var NTAB_ON = 'flex flex-col items-center justify-center gap-1 bg-teachhq-soft py-2.5 text-micro font-bold text-teachhq transition focus:outline-none';
        var NTAB_OFF = 'flex flex-col items-center justify-center gap-1 py-2.5 text-micro font-bold text-ink-faint transition hover:bg-paper hover:text-slatecard focus:outline-none';
        window.switchNotifTab = function (name) {
            ['notifications', 'ideas', 'roadmap'].forEach(function (t) {
                var sec = document.getElementById('tab-' + t);
                if (sec) sec.classList.toggle('hidden', t !== name);
                var btn = document.getElementById('ntab-' + t);
                if (btn) btn.className = (t === name) ? NTAB_ON : NTAB_OFF;
            });
            var titles = { notifications: 'Notifications', ideas: 'Ideas', roadmap: 'Roadmap' };
            var h = document.getElementById('notifTitle');
            if (h) h.textContent = titles[name];
            var sub = document.getElementById('notifSub');
            if (sub) sub.classList.toggle('hidden', name !== 'notifications');
            var ctl = document.getElementById('notifReadCtl');
            if (ctl) ctl.classList.toggle('hidden', name !== 'notifications');
        };
        window.voteIdea = function (btn) {
            var sp = btn.querySelector('span');
            var n = parseInt(sp.textContent, 10) || 0;
            if (btn.getAttribute('data-voted')) {
                btn.removeAttribute('data-voted');
                sp.textContent = n - 1;
                btn.classList.remove('border-teachhq', 'text-teachhq', 'bg-teachhq-soft');
            } else {
                btn.setAttribute('data-voted', '1');
                sp.textContent = n + 1;
                btn.classList.add('border-teachhq', 'text-teachhq', 'bg-teachhq-soft');
            }
        };

        /* Help chat */
        window.openChat = function () {
            window.closeNotifications();
            setState(chatPanel, chatOverlay, chatBtn, true);
            syncScrollLock();
            setTimeout(function () {
                var i = document.getElementById('chatInput');
                if (i) i.focus();
            }, 480);
        };
        window.closeChat = function () {
            setState(chatPanel, chatOverlay, chatBtn, false);
            syncScrollLock();
        };
        window.toggleChat = function () {
            chatPanel.classList.contains('is-open') ? window.closeChat() : window.openChat();
        };
        function appendChat(text, who) {
            var body = document.getElementById('chatBody');
            var wrap = document.createElement('div');
            if (who === 'user') {
                wrap.className = 'flex justify-end';
                var bu = document.createElement('div');
                bu.className = 'max-w-bubble rounded-panel rounded-tr-sm bg-teachhq px-3.5 py-2.5 text-caption leading-snug text-on-brand shadow-quiet';
                bu.textContent = text;
                wrap.appendChild(bu);
            } else {
                wrap.className = 'flex gap-2';
                wrap.innerHTML = '<span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-teachhq text-nano font-black text-on-brand" aria-hidden="true">B</span>';
                var bb = document.createElement('div');
                bb.className = 'max-w-bubble rounded-panel rounded-tl-sm bg-surface px-3.5 py-2.5 text-caption leading-snug text-slatecard shadow-quiet';
                bb.textContent = text;
                wrap.appendChild(bb);
            }
            body.appendChild(wrap);
            body.scrollTop = body.scrollHeight;
        }
        function chatTyping(on) {
            var body = document.getElementById('chatBody');
            var ex = document.getElementById('chatTyping');
            if (on) {
                if (ex) return;
                var w = document.createElement('div');
                w.id = 'chatTyping';
                w.className = 'flex gap-2';
                w.innerHTML = '<span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-teachhq text-nano font-black text-on-brand" aria-hidden="true">B</span><div class="rounded-panel rounded-tl-sm bg-surface px-3.5 py-3 shadow-quiet"><span class="chat-typing"><i></i><i></i><i></i></span></div>';
                body.appendChild(w);
                body.scrollTop = body.scrollHeight;
            } else if (ex) {
                ex.remove();
            }
        }
        function chatBotReply(text) {
            var t = (text || '').toLowerCase(), r;
            if (t.indexOf('certif') >= 0) r = 'You can download your certificates from “My Certificates” in the left menu. Want me to take you there?';
            else if (t.indexOf('password') >= 0 || t.indexOf('reset') >= 0) r = 'No problem — I can send a password reset link to your registered email. Shall I do that?';
            else if (t.indexOf('course') >= 0 || t.indexOf('enrol') >= 0 || t.indexOf('training') >= 0) r = 'Happy to help with courses. Are you looking to enrol, resume, or find an overdue course?';
            else if (t.indexOf('problem') >= 0 || t.indexOf('issue') >= 0 || t.indexOf('bug') >= 0 || t.indexOf('report') >= 0) r = 'Sorry to hear that. Please describe what happened and I’ll raise a ticket with our support team.';
            else r = 'Thanks, Emma. A support specialist will pick this up shortly. Is there anything else I can help with?';
            chatTyping(true);
            setTimeout(function () { chatTyping(false); appendChat(r, 'bot'); }, 1000);
        }
        window.sendChat = function (e) {
            e.preventDefault();
            var i = document.getElementById('chatInput');
            var v = (i.value || '').trim();
            if (!v) return;
            appendChat(v, 'user');
            i.value = '';
            var q = document.getElementById('chatQuick');
            if (q) q.classList.add('hidden');
            chatBotReply(v);
        };
        window.chatQuickReply = function (text) {
            appendChat(text, 'user');
            var q = document.getElementById('chatQuick');
            if (q) q.classList.add('hidden');
            chatBotReply(text);
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { window.closeNotifications(); window.closeChat(); }
        });
    })();
    </script>

    <script>
    /* Theme toggle (Light / Dark / System) in the account menu. Applies instantly
       to <html data-theme>, reflects the active choice, and persists via the
       preferences endpoint. The pre-paint script in <head> handles first render. */
    document.addEventListener('DOMContentLoaded', function () {
        var opts = Array.prototype.slice.call(document.querySelectorAll('[data-theme-set]'));
        if (!opts.length) return;
        var el = document.documentElement;
        var wrap = document.querySelector('[data-theme-endpoint]');
        var endpoint = wrap ? wrap.getAttribute('data-theme-endpoint') : null;
        var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

        function pref() { return el.getAttribute('data-theme-pref') || 'system'; }
        function applyTheme() {
            var p = pref();
            var dark = p === 'dark' || (p === 'system' && mq && mq.matches);
            el.setAttribute('data-theme', dark ? 'dark' : 'light');
        }
        function paint() {
            var p = pref();
            opts.forEach(function (b) {
                var on = b.getAttribute('data-theme-set') === p;
                b.classList.toggle('bg-surface', on);
                b.classList.toggle('text-slatecard', on);
                b.classList.toggle('shadow-quiet', on);
                b.classList.toggle('text-ink-soft', !on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }
        opts.forEach(function (b) {
            b.addEventListener('click', function () {
                var choice = b.getAttribute('data-theme-set');
                el.setAttribute('data-theme-pref', choice);
                applyTheme();
                paint();
                if (endpoint) {
                    var m = document.querySelector('meta[name=csrf-token]');
                    fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': m ? m.getAttribute('content') : '' },
                        body: JSON.stringify({ theme: choice })
                    }).catch(function () {});
                }
            });
        });
        if (mq && mq.addEventListener) {
            mq.addEventListener('change', function () { if (pref() === 'system') applyTheme(); });
        }
        paint();
    });
    </script>

    {{-- Page/component-pushed scripts (e.g. the reusable data-table behaviour). --}}
    @stack('scripts')
</body>
</html>
