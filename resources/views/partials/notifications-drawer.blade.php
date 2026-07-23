{{-- Notifications drawer (Beamer-style UX) — ported from the working prototype, tokenised.
     Interim placeholder content: wire to the notifications / ideas / roadmap tables in the DB-driven pass. --}}
<div id="notifOverlay" onclick="closeNotifications()" class="app-drawer-overlay" aria-hidden="true"></div>
<aside id="notifPanel" class="app-drawer" role="dialog" aria-modal="true" aria-label="Notifications" aria-hidden="true">
    <div class="flex h-15 items-center justify-between border-b border-line-soft px-5">
        <div class="min-w-0">
            <h2 id="notifTitle" class="text-base font-black leading-tight text-slatecard">Notifications</h2>
            <p id="notifSub" class="text-mini leading-tight text-ink-soft"><span id="notifUnreadCount">4</span> unread</p>
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button" id="notifReadCtl" onclick="markAllNotifsRead()" class="rounded-lg px-2.5 py-1.5 text-mini font-bold text-teachhq transition hover:bg-teachhq-soft focus:outline-none focus:ring-2 focus:ring-teachhq">Mark all read</button>
            <button type="button" onclick="closeNotifications()" aria-label="Close notifications" class="inline-flex h-9 w-9 items-center justify-center rounded-control bg-paper text-ink-soft transition hover:bg-line hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        {{-- Notifications tab --}}
        <div id="tab-notifications" data-tab>
            <div id="notifList">
                <button data-unread class="group flex w-full gap-3 border-b border-line-soft bg-teachhq-soft/60 px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-rag-red-soft text-rag-red"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 20h20z"/><path d="M12 10v4"/><path d="M12 17h.01"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-rag-red">Action needed</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">Food Allergy Awareness is overdue</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">Your refresher was due 14 days ago. Complete it to stay compliant.</span>
                        <span class="mt-1 block text-micro text-ink-faint">2 hours ago</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq"></span>
                </button>
                <button data-unread class="group flex w-full gap-3 border-b border-line-soft bg-teachhq-soft/60 px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-rag-amber-soft text-rag-amber"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-rag-amber">Reminder</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">Level 2 Food Safety expires in 7 days</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">Renew before 2 July to keep your certification active.</span>
                        <span class="mt-1 block text-micro text-ink-faint">Yesterday</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq"></span>
                </button>
                <button data-unread class="group flex w-full gap-3 border-b border-line-soft bg-teachhq-soft/60 px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-teachhq-soft text-teachhq-dark"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-teachhq-dark">New course</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">New course available: HACCP Principles</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">Just added to your Course Library. Enrol to get ahead.</span>
                        <span class="mt-1 block text-micro text-ink-faint">2 days ago</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq"></span>
                </button>
                <button data-unread class="group flex w-full gap-3 border-b border-line-soft bg-teachhq-soft/60 px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-rag-green-soft text-rag-green"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M15.5 13.5 17 22l-5-3-5 3 1.5-8.5"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-rag-green">Achievement</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">Certificate earned: Allergen Awareness</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">Well done! Your Natasha&rsquo;s Law certificate is ready to download.</span>
                        <span class="mt-1 block text-micro text-ink-faint">3 days ago</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq"></span>
                </button>
                <button class="group flex w-full gap-3 border-b border-line-soft px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-teachhq-soft text-teachhq-dark"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-teachhq-dark">Assignment</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">Your manager assigned 2 new courses</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">COSHH Essentials and Manual Handling were added to your plan.</span>
                        <span class="mt-1 block text-micro text-ink-faint">4 days ago</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq hidden"></span>
                </button>
                <button class="group flex w-full gap-3 border-b border-line-soft px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-rag-amber-soft text-rag-amber"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-rag-amber">Reminder</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">Scheduled training tomorrow</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">Fire Safety Awareness at 10:00. Add it to your calendar.</span>
                        <span class="mt-1 block text-micro text-ink-faint">5 days ago</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq hidden"></span>
                </button>
                <button class="group flex w-full gap-3 border-b border-line-soft px-5 py-4 text-left transition hover:bg-paper">
                    <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-paper text-ink-soft"><svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg></span>
                    <span class="min-w-0 flex-1">
                        <span class="text-nano font-black uppercase tracking-wide text-ink-soft">Announcement</span>
                        <span class="mt-0.5 block text-sm font-bold text-slatecard">All courses are now free of charge</span>
                        <span class="mt-0.5 block text-caption leading-snug text-ink-soft">Browse the full Course Library and enrol at no cost.</span>
                        <span class="mt-1 block text-micro text-ink-faint">1 week ago</span>
                    </span>
                    <span class="notif-dot mt-1.5 h-2 w-2 flex-none rounded-full bg-teachhq hidden"></span>
                </button>
            </div>
        </div>

        {{-- Ideas tab --}}
        <div id="tab-ideas" data-tab class="hidden">
            <div class="flex items-center justify-between gap-3 border-b border-line-soft bg-paper px-5 py-3">
                <p class="text-mini text-ink-soft">Vote on what we build next.</p>
                <button type="button" class="rounded-lg bg-teachhq px-3 py-1.5 text-mini font-bold text-on-brand transition hover:bg-teachhq-dark focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-1">Suggest an idea</button>
            </div>
            <div class="flex gap-3 border-b border-line-soft px-5 py-4">
                <button type="button" onclick="voteIdea(this)" class="flex h-14 w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-lg border border-line text-ink-soft transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg><span class="text-sm font-black tabular-nums">124</span></button>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slatecard">Offline mode for mobile learning</div>
                    <p class="mt-0.5 text-caption leading-snug text-ink-soft">Let staff download courses and complete training without a connection.</p>
                    <div class="mt-1.5 flex items-center gap-2 text-micro"><span class="rounded-full bg-rag-amber-soft px-2 py-0.5 font-bold text-rag-amber">Planned</span><span class="text-ink-faint">&middot; 18 comments</span></div>
                </div>
            </div>
            <div class="flex gap-3 border-b border-line-soft px-5 py-4">
                <button type="button" onclick="voteIdea(this)" class="flex h-14 w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-lg border border-line text-ink-soft transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg><span class="text-sm font-black tabular-nums">98</span></button>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slatecard">Bulk-assign courses to a whole site</div>
                    <p class="mt-0.5 text-caption leading-snug text-ink-soft">Assign training to every learner at a site or group in one action.</p>
                    <div class="mt-1.5 flex items-center gap-2 text-micro"><span class="rounded-full bg-teachhq-soft px-2 py-0.5 font-bold text-teachhq-dark">Under review</span><span class="text-ink-faint">&middot; 11 comments</span></div>
                </div>
            </div>
            <div class="flex gap-3 border-b border-line-soft px-5 py-4">
                <button type="button" onclick="voteIdea(this)" class="flex h-14 w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-lg border border-line text-ink-soft transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg><span class="text-sm font-black tabular-nums">76</span></button>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slatecard">Downloadable wallet certificates</div>
                    <p class="mt-0.5 text-caption leading-snug text-ink-soft">A printable, pocket-sized proof of certification for inspections.</p>
                    <div class="mt-1.5 flex items-center gap-2 text-micro"><span class="rounded-full bg-rag-amber-soft px-2 py-0.5 font-bold text-rag-amber">Planned</span><span class="text-ink-faint">&middot; 7 comments</span></div>
                </div>
            </div>
            <div class="flex gap-3 border-b border-line-soft px-5 py-4">
                <button type="button" onclick="voteIdea(this)" class="flex h-14 w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-lg border border-line text-ink-soft transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg><span class="text-sm font-black tabular-nums">54</span></button>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slatecard">Dark mode for the dashboard</div>
                    <p class="mt-0.5 text-caption leading-snug text-ink-soft">An easier-on-the-eyes theme for evening admin work.</p>
                    <div class="mt-1.5 flex items-center gap-2 text-micro"><span class="rounded-full bg-teachhq-soft px-2 py-0.5 font-bold text-teachhq-dark">Under review</span><span class="text-ink-faint">&middot; 5 comments</span></div>
                </div>
            </div>
            <div class="flex gap-3 border-b border-line-soft px-5 py-4">
                <button type="button" onclick="voteIdea(this)" class="flex h-14 w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-lg border border-line text-ink-soft transition hover:border-teachhq hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-teachhq"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg><span class="text-sm font-black tabular-nums">41</span></button>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slatecard">Reminders via WhatsApp</div>
                    <p class="mt-0.5 text-caption leading-snug text-ink-soft">Send overdue and due-soon nudges through WhatsApp.</p>
                    <div class="mt-1.5 flex items-center gap-2 text-micro"><span class="rounded-full bg-line-soft px-2 py-0.5 font-bold text-ink-soft">New</span><span class="text-ink-faint">&middot; 3 comments</span></div>
                </div>
            </div>
        </div>

        {{-- Roadmap tab --}}
        <div id="tab-roadmap" data-tab class="hidden">
            <div class="sticky top-0 z-10 bg-surface px-5 pb-2 pt-4">
                <span class="inline-flex items-center gap-1.5 text-micro font-black uppercase tracking-wide text-teachhq"><span class="h-2 w-2 rounded-full bg-teachhq"></span>In progress</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">Bulk course assignments</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">Assign training to an entire site or group in one click.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Manager tools</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">HACCP course series</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">A new multi-part HACCP pathway for catering teams.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Content</span>
            </div>
            <div class="sticky top-0 z-10 bg-surface px-5 pb-2 pt-4">
                <span class="inline-flex items-center gap-1.5 text-micro font-black uppercase tracking-wide text-rag-amber"><span class="h-2 w-2 rounded-full bg-rag-amber"></span>Planned</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">Offline mobile learning</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">Download and complete courses without a connection.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Mobile</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">Wallet certificates</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">Pocket-sized, printable proof of certification.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Certificates</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">HR system integrations</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">Sync learners and records with your HR platform via API.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Integrations</span>
            </div>
            <div class="sticky top-0 z-10 bg-surface px-5 pb-2 pt-4">
                <span class="inline-flex items-center gap-1.5 text-micro font-black uppercase tracking-wide text-rag-green"><span class="h-2 w-2 rounded-full bg-rag-green"></span>Live</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">Natasha&rsquo;s Law allergen module</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">Updated allergen training aligned to current regulations.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Compliance</span>
            </div>
            <div class="mx-5 mb-2.5 rounded-control border border-line bg-surface p-3.5 shadow-quiet">
                <div class="text-sm font-bold text-slatecard">Site compliance dashboard</div>
                <p class="mt-0.5 text-caption leading-snug text-ink-soft">At-a-glance overdue, due-soon and risk by site.</p>
                <span class="mt-2 inline-block rounded-full bg-teachhq-soft px-2 py-0.5 text-nano font-bold text-teachhq-dark">Analytics</span>
            </div>
            <div class="h-3"></div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="grid grid-cols-3 border-t border-line-soft">
        <button type="button" id="ntab-notifications" onclick="switchNotifTab('notifications')" class="flex flex-col items-center justify-center gap-1 bg-teachhq-soft py-2.5 text-micro font-bold text-teachhq transition focus:outline-none"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>Notifications</button>
        <button type="button" id="ntab-ideas" onclick="switchNotifTab('ideas')" class="flex flex-col items-center justify-center gap-1 py-2.5 text-micro font-bold text-ink-faint transition hover:bg-paper hover:text-slatecard focus:outline-none"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.6 4.6 0 0 1 8.91 14"/></svg>Ideas</button>
        <button type="button" id="ntab-roadmap" onclick="switchNotifTab('roadmap')" class="flex flex-col items-center justify-center gap-1 py-2.5 text-micro font-bold text-ink-faint transition hover:bg-paper hover:text-slatecard focus:outline-none"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/></svg>Roadmap</button>
    </div>
</aside>
