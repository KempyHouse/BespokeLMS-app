@extends('layouts.app')

@section('title', 'Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-stretch lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none">
        <!-- Workspace switcher -->
        <div class="mb-4 grid grid-cols-3 gap-1 rounded-full border border-line bg-surface p-1 text-sm font-semibold shadow-panel" role="tablist" aria-label="Workspace">
            <a href="{{ route('dashboard') }}" role="tab" class="rounded-full py-2 text-center text-ink-soft transition hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">My</a>
            <a href="{{ route('dashboard') }}" role="tab" class="rounded-full py-2 text-center text-ink-soft transition hover:text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">Team</a>
            <span role="tab" aria-selected="true" aria-current="page" class="rounded-full bg-teachhq py-2 text-center text-on-brand shadow-panel">Platform</span>
        </div>

        <div class="rounded-control bg-paper p-6">
            <!-- Owner identity -->
            <div class="mb-6 border-b border-line pb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Platform</p>
                <h1 class="mt-1 text-lg font-black text-slatecard">Platform administration</h1>
                <p class="mt-2 text-caption text-ink-soft">
                    {{ $user->displayName() }} ({{ $user->roleLabel() }})
                </p>
            </div>

            <!-- Platform Workspace -->
            <nav class="mb-6">
                <div class="mb-2.5 text-xs font-bold uppercase tracking-wider text-teachhq">Platform Workspace</div>
                <ul>
                    <li>
                        <a href="#tenants" data-section="tenants"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">🏢</span>
                            <span class="min-w-0 flex-1 truncate">Tenants</span>
                        </a>
                    </li>
                    <li>
                        <a href="#courses" data-section="courses"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">📖</span>
                            <span class="min-w-0 flex-1 truncate">Global Courses</span>
                        </a>
                    </li>
                    <li>
                        <a href="#settings" data-section="settings"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">⚙️</span>
                            <span class="min-w-0 flex-1 truncate">Platform Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="#ai" data-section="ai"
                           class="rail-item flex items-center gap-2.5 py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">⚡</span>
                            <span class="min-w-0 flex-1 truncate">AI Integration</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Monitor -->
            <nav>
                <div class="mb-2.5 border-t border-line pt-5 text-xs font-bold uppercase tracking-wider text-teachhq">Monitor</div>
                <ul>
                    <li>
                        <a href="#users" data-section="users"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">👤</span>
                            <span class="min-w-0 flex-1 truncate">Platform Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="#analytics" data-section="analytics"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">📈</span>
                            <span class="min-w-0 flex-1 truncate">Usage &amp; Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="#logs" data-section="logs"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">📋</span>
                            <span class="min-w-0 flex-1 truncate">Integration Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="#security" data-section="security"
                           class="rail-item flex items-center gap-2.5 py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <span class="w-5 flex-none text-center" aria-hidden="true">🔒</span>
                            <span class="min-w-0 flex-1 truncate">Security</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Back to dashboard -->
            <div class="mt-6 border-t border-line pt-5">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 rounded-control border border-line bg-surface px-3 py-2 text-xs font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2">
                    ← Back to dashboard
                </a>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <!-- Tenants -->
        <section id="tenants-content" class="content-section">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Tenants</h2>
                <p class="mt-2 text-sm text-ink-soft">Create, brand and configure operator and client organisations across the estate.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">Manage all tenant organizations in the system. This section will display a live list of operators and clients.</p>
                <div class="mt-4">
                    <button class="inline-flex rounded-control bg-teachhq px-4 py-2 text-sm font-semibold text-on-brand transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2" disabled>
                        + Create Tenant (coming soon)
                    </button>
                </div>
            </article>
        </section>

        <!-- Global Courses -->
        <section id="courses-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Global Course Catalogue</h2>
                <p class="mt-2 text-sm text-ink-soft">Publish system courses that cascade to every tenant.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">Manage system-wide courses that are available across all tenants.</p>
                <div class="mt-4">
                    <button class="inline-flex rounded-control bg-teachhq px-4 py-2 text-sm font-semibold text-on-brand transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2" disabled>
                        + Publish Course (coming soon)
                    </button>
                </div>
            </article>
        </section>

        <!-- Platform Settings -->
        <section id="settings-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Platform Settings</h2>
                <p class="mt-2 text-sm text-ink-soft">Platform-wide branding and configuration, held at the owner level.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">Configure platform-wide settings including branding, authentication, and system preferences.</p>
            </article>
        </section>

        <!-- AI Integration -->
        <section id="ai-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">AI Integration</h2>
                <p class="mt-2 text-sm text-ink-soft">The Claude (Anthropic) integration is configured once here, at the platform-owner level.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">Manage Claude AI integration settings, API keys, and usage quotas for the entire platform.</p>
            </article>
        </section>

        <!-- Platform Users -->
        <section id="users-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Platform Users</h2>
                <p class="mt-2 text-sm text-ink-soft">View and manage all users across the platform.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">See all users across all tenants, manage roles, and handle platform-wide user administration.</p>
            </article>
        </section>

        <!-- Usage & Analytics -->
        <section id="analytics-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Usage &amp; Analytics</h2>
                <p class="mt-2 text-sm text-ink-soft">Platform-wide usage statistics and insights.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">View platform-wide analytics including user engagement, course completions, and system performance metrics.</p>
            </article>
        </section>

        <!-- Integration Logs -->
        <section id="logs-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Integration Logs</h2>
                <p class="mt-2 text-sm text-ink-soft">Track all AI integration activity.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">Monitor Claude AI API calls, responses, and system integration events across the platform.</p>
            </article>
        </section>

        <!-- Security -->
        <section id="security-content" class="content-section hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-black text-slatecard">Security</h2>
                <p class="mt-2 text-sm text-ink-soft">Platform security and audit management.</p>
            </div>
            <article class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <p class="text-ink-muted">Access audit logs, manage roles and permissions, and monitor platform security events.</p>
            </article>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuLinks = document.querySelectorAll('[data-section]');
    const contentSections = document.querySelectorAll('.content-section');
    const activeClasses = ['font-semibold', 'text-teachhq-dark'];

    function setActive(link) {
        menuLinks.forEach(l => {
            l.classList.remove(...activeClasses);
            l.classList.add('text-slatecard');
        });
        link.classList.remove('text-slatecard');
        link.classList.add(...activeClasses);
    }

    menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const sectionName = this.getAttribute('data-section');

            contentSections.forEach(section => section.classList.add('hidden'));
            const selectedSection = document.getElementById(sectionName + '-content');
            if (selectedSection) {
                selectedSection.classList.remove('hidden');
            }

            setActive(this);
        });
    });

    if (menuLinks.length > 0) {
        setActive(menuLinks[0]);
    }
});
</script>
@endsection
