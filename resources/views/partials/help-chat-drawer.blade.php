{{-- Help chat drawer (Freshchat-style UX) — ported from the working prototype, tokenised.
     Interim placeholder assistant: wire to real support / AI chat in a later pass. --}}
<div id="chatOverlay" onclick="closeChat()" class="app-drawer-overlay" aria-hidden="true"></div>
<aside id="chatPanel" class="app-drawer" role="dialog" aria-modal="true" aria-label="Help chat" aria-hidden="true">
    <div class="flex items-start justify-between gap-3 bg-teachhq px-5 py-4 text-on-brand">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-on-brand/20 text-sm font-black" aria-hidden="true">B</span>
            <div>
                <h2 class="text-base font-black leading-tight">BespokeLMS Support</h2>
                <p class="mt-0.5 text-mini text-on-brand/85"><span class="mr-1 inline-block h-2 w-2 rounded-full bg-rag-green align-middle"></span>We typically reply in a few minutes</p>
            </div>
        </div>
        <button type="button" onclick="closeChat()" aria-label="Close chat" class="inline-flex h-9 w-9 flex-none items-center justify-center rounded-control bg-on-brand/15 text-on-brand transition hover:bg-on-brand/25 focus:outline-none focus:ring-2 focus:ring-on-brand"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>

    <div id="chatBody" class="flex-1 space-y-3 overflow-y-auto bg-paper px-4 py-4">
        <div class="flex gap-2">
            <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-teachhq text-nano font-black text-on-brand" aria-hidden="true">B</span>
            <div class="max-w-bubble rounded-panel rounded-tl-sm bg-surface px-3.5 py-2.5 text-caption leading-snug text-slatecard shadow-quiet">Hi Emma, I&rsquo;m the BespokeLMS assistant. How can I help with your training today?</div>
        </div>
        <div id="chatQuick" class="flex flex-wrap gap-2 pl-9">
            <button type="button" onclick="chatQuickReply('Course help')" class="rounded-full border border-line bg-surface px-3 py-1.5 text-mini font-semibold text-slatecard transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq">Course help</button>
            <button type="button" onclick="chatQuickReply('My certificates')" class="rounded-full border border-line bg-surface px-3 py-1.5 text-mini font-semibold text-slatecard transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq">My certificates</button>
            <button type="button" onclick="chatQuickReply('Reset a password')" class="rounded-full border border-line bg-surface px-3 py-1.5 text-mini font-semibold text-slatecard transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq">Reset a password</button>
            <button type="button" onclick="chatQuickReply('Report a problem')" class="rounded-full border border-line bg-surface px-3 py-1.5 text-mini font-semibold text-slatecard transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq">Report a problem</button>
        </div>
    </div>

    <div class="border-t border-line-soft bg-surface p-3">
        <form onsubmit="sendChat(event)" class="flex items-center gap-2">
            <label for="chatInput" class="sr-only">Type your message</label>
            <input id="chatInput" type="text" autocomplete="off" placeholder="Type your message&hellip;" class="min-w-0 flex-1 rounded-full border border-line bg-surface px-4 py-2.5 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-teachhq">
            <button type="submit" aria-label="Send message" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-teachhq text-on-brand transition hover:bg-teachhq-dark focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></button>
        </form>
    </div>
</aside>
