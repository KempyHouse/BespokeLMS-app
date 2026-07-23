<style>
    .platform-container { display: flex; gap: 1.5rem; }
    .platform-sidebar { width: 14rem; flex-shrink: 0; }
    .platform-sidebar nav { margin-bottom: 2rem; }
    .platform-sidebar h2 { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin-bottom: 0.75rem; }
    .platform-sidebar ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; }
    .platform-sidebar a { display: block; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: #3d515b; border-radius: 0.375rem; transition: background-color 0.2s; }
    .platform-sidebar a:hover { background-color: #f5f5f5; }
    .platform-sidebar a.active { background-color: #f5f5f5; font-weight: 600; }
    .platform-content { flex: 1; }
    .platform-content section { margin-bottom: 2rem; }
    .platform-content h2 { font-size: 1.5rem; font-weight: 900; color: #3d515b; margin-bottom: 0.5rem; }
    .platform-content p { font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem; }
    .platform-content article { border: 1px solid #e5e7eb; border-radius: 0.375rem; background-color: white; padding: 1.5rem; margin-top: 1.5rem; }
    .back-link { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
    .back-link a { display: inline-flex; border: 1px solid #e5e7eb; background-color: white; padding: 0.5rem 1rem; font-size: 0.75rem; font-weight: 600; color: #3d515b; border-radius: 0.375rem; transition: background-color 0.2s; text-decoration: none; }
    .back-link a:hover { background-color: #f5f5f5; }
</style>

@extends('layouts.app')

@section('title', 'Platform')

@section('content')
<div class=”flex gap-6”>
    <!-- Left Sidebar -->
    <aside class=”w-56 flex-shrink-0”>
        <!-- User Info -->
        <div class=”mb-8”>
            <p class=”text-sm font-semibold uppercase tracking-wide text-teachhq”>BespokeLMS · Platform</p>
            <h1 class=”mt-1 text-lg font-black text-slatecard”>Platform administration</h1>
            <p class=”mt-2 text-xs text-slate-500”>
                {{ $user->displayName() }} ({{ $user->roleLabel() }})
            </p>
        </div>

        <!-- Platform Workspace Section -->
        <nav class=”mb-8”>
            <h2 class=”mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400”>Platform Workspace</h2>
            <ul class=”space-y-2”>
                <li>
                    <a href=”#tenants”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”tenants”>
                        <span class=”inline-block mr-2”>🏢</span>Tenants
                    </a>
                </li>
                <li>
                    <a href=”#courses”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”courses”>
                        <span class=”inline-block mr-2”>📖</span>Global Courses
                    </a>
                </li>
                <li>
                    <a href=”#settings”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”settings”>
                        <span class=”inline-block mr-2”>⚙</span>Platform Settings
                    </a>
                </li>
                <li>
                    <a href=”#ai”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”ai”>
                        <span class=”inline-block mr-2”>⚡</span>AI Integration
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Monitor Section -->
        <nav>
            <h2 class=”mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400”>Monitor</h2>
            <ul class=”space-y-2”>
                <li>
                    <a href=”#users”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”users”>
                        <span class=”inline-block mr-2”>👤</span>Platform Users
                    </a>
                </li>
                <li>
                    <a href=”#analytics”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”analytics”>
                        <span class=”inline-block mr-2”>📈</span>Usage & Analytics
                    </a>
                </li>
                <li>
                    <a href=”#logs”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”logs”>
                        <span class=”inline-block mr-2”>📋</span>Integration Logs
                    </a>
                </li>
                <li>
                    <a href=”#security”
                       class=”block rounded-control px-3 py-2 text-sm font-medium text-slatecard transition hover:bg-paper focus:outline-none”
                       data-section=”security”>
                        <span class=”inline-block mr-2”>🔒</span>Security
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Back to Dashboard -->
        <div class=”mt-8 pt-6 border-t border-slate-200”>
            <a href=”{{ route('dashboard') }}”
               class=”inline-flex rounded-control border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2”>
                ← Back to dashboard
            </a>
        </div>
    </aside>

    <!-- Right Content Area -->
    <main class=”flex-1”>
        <!-- Tenants Section -->
        <section id=”tenants-content” class=”content-section mb-8”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Tenants</h2>
                <p class=”mt-2 text-sm text-slate-500”>Create, brand and configure operator and client organisations across the estate.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>Manage all tenant organizations in the system. This section will display a live list of operators and clients.</p>
                <div class=”mt-4”>
                    <button class=”inline-flex rounded-control bg-teachhq px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2” disabled>
                        + Create Tenant (coming soon)
                    </button>
                </div>
            </article>
        </section>

        <!-- Global Courses Section -->
        <section id=”courses-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Global Course Catalogue</h2>
                <p class=”mt-2 text-sm text-slate-500”>Publish system courses that cascade to every tenant.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>Manage system-wide courses that are available across all tenants.</p>
                <div class=”mt-4”>
                    <button class=”inline-flex rounded-control bg-teachhq px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2” disabled>
                        + Publish Course (coming soon)
                    </button>
                </div>
            </article>
        </section>

        <!-- Platform Settings Section -->
        <section id=”settings-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Platform Settings</h2>
                <p class=”mt-2 text-sm text-slate-500”>Platform-wide branding and configuration, held at the owner level.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>Configure platform-wide settings including branding, authentication, and system preferences.</p>
            </article>
        </section>

        <!-- AI Integration Section -->
        <section id=”ai-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>AI Integration</h2>
                <p class=”mt-2 text-sm text-slate-500”>The Claude (Anthropic) integration is configured once here, at the platform-owner level.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>Manage Claude AI integration settings, API keys, and usage quotas for the entire platform.</p>
            </article>
        </section>

        <!-- Platform Users Section -->
        <section id=”users-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Platform Users</h2>
                <p class=”mt-2 text-sm text-slate-500”>View and manage all users across the platform.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>See all users across all tenants, manage roles, and handle platform-wide user administration.</p>
            </article>
        </section>

        <!-- Usage & Analytics Section -->
        <section id=”analytics-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Usage & Analytics</h2>
                <p class=”mt-2 text-sm text-slate-500”>Platform-wide usage statistics and insights.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>View platform-wide analytics including user engagement, course completions, and system performance metrics.</p>
            </article>
        </section>

        <!-- Integration Logs Section -->
        <section id=”logs-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Integration Logs</h2>
                <p class=”mt-2 text-sm text-slate-500”>Track all AI integration activity.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>Monitor Claude AI API calls, responses, and system integration events across the platform.</p>
            </article>
        </section>

        <!-- Security Section -->
        <section id=”security-content” class=”content-section mb-8 hidden”>
            <div class=”mb-6”>
                <h2 class=”text-2xl font-black text-slatecard”>Security</h2>
                <p class=”mt-2 text-sm text-slate-500”>Platform security and audit management.</p>
            </div>
            <article class=”rounded-control border border-slate-200 bg-white p-6”>
                <p class=”text-slate-600”>Access audit logs, manage roles and permissions, and monitor platform security events.</p>
            </article>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuLinks = document.querySelectorAll('[data-section]');
    const contentSections = document.querySelectorAll('.content-section');

    menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const sectionName = this.getAttribute('data-section');

            // Hide all sections
            contentSections.forEach(section => section.classList.add('hidden'));

            // Show selected section
            const selectedSection = document.getElementById(sectionName + '-content');
            if (selectedSection) {
                selectedSection.classList.remove('hidden');
            }

            // Update active menu item styling
            menuLinks.forEach(l => l.classList.remove('bg-paper', 'font-semibold'));
            this.classList.add('bg-paper', 'font-semibold');
        });
    });

    // Set first menu item as active on page load
    if (menuLinks.length > 0) {
        menuLinks[0].classList.add('bg-paper', 'font-semibold');
    }
});
</script>
@endsection
