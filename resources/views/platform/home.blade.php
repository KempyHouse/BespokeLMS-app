@extends('layouts.app')

@section('title', 'Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none rail-sticky">
        {{-- Workspace switcher (shared component) --}}
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <!-- Platform Workspace -->
            <nav class="mb-6">
                <div class="mb-2.5 text-xs font-bold uppercase tracking-wider text-teachhq">Platform Workspace</div>
                <ul>
                    <li>
                        <a href="#tenants" data-section="tenants"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M16 10h.01M8 10h.01M12 14h.01M16 14h.01M8 14h.01"/></svg>
                            <span class="min-w-0 flex-1 truncate">Tenants</span>
                        </a>
                    </li>
                    <li>
                        <a href="#courses" data-section="courses"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
                            <span class="min-w-0 flex-1 truncate">Global Courses</span>
                        </a>
                    </li>
                    <li>
                        <a href="#settings" data-section="settings"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span class="min-w-0 flex-1 truncate">Platform Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('platform.ai') }}"
                           class="rail-item flex items-center gap-2.5 py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
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
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span class="min-w-0 flex-1 truncate">Platform Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="#analytics" data-section="analytics"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                            <span class="min-w-0 flex-1 truncate">Usage &amp; Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="#logs" data-section="logs"
                           class="rail-item flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                            <span class="min-w-0 flex-1 truncate">Integration Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="#security" data-section="security"
                           class="rail-item flex items-center gap-2.5 py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none">
                            <svg class="nav-ico h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                            <span class="min-w-0 flex-1 truncate">Security</span>
                        </a>
                    </li>
                </ul>
            </nav>

        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <!-- Tenants -->
        <section id="tenants-content" class="content-section">
            <div class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <h1 class="text-2xl font-black text-slatecard">Tenants</h1>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:flex-none">
                        @if (! empty($tenants))
                            <x-tenant-selector :tenants="$tenants" label="Configure a tenant" />
                        @endif
                        <button type="button" disabled
                                class="inline-flex items-center justify-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text opacity-60 focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                            <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                            Create Tenant <span class="text-micro font-normal opacity-80">(coming soon)</span>
                        </button>
                    </div>
                </div>
                <p class="mt-2 max-w-3xl text-sm text-ink-soft">Every operator and client organisation across the estate. Select a tenant to configure its white-label instance.</p>
            </div>

            @unless ($estateError)
                {{-- Estate summary --}}
                <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @php
                        $chips = [
                            ['label' => 'Tenants', 'value' => $summary['tenants'] ?? 0],
                            ['label' => 'Operators', 'value' => $summary['operators'] ?? 0],
                            ['label' => 'Client orgs', 'value' => $summary['clients'] ?? 0],
                            ['label' => 'Users', 'value' => $summary['users'] ?? 0],
                        ];
                    @endphp
                    @foreach ($chips as $chip)
                        <div class="rounded-panel border border-line bg-surface px-4 py-3 shadow-quiet">
                            <div class="text-2xl font-black tabular-nums text-slatecard">{{ $chip['value'] }}</div>
                            <div class="text-mini font-semibold uppercase tracking-wide text-ink-soft">{{ $chip['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endunless

            @php
                $tenantColumns = [
                    ['key' => 'name', 'label' => 'Tenant', 'type' => 'text'],
                    ['key' => 'type', 'label' => 'Type', 'type' => 'text'],
                    ['key' => 'model', 'label' => 'Model', 'type' => 'text', 'hide' => 'sm'],
                    ['key' => 'parent', 'label' => 'Parent or Served By', 'type' => 'text', 'hide' => 'md'],
                    ['key' => 'users', 'label' => 'Users', 'type' => 'num', 'align' => 'end'],
                    ['key' => 'clients', 'label' => 'Clients', 'type' => 'num', 'align' => 'end', 'hide' => 'lg'],
                    ['key' => 'added', 'label' => 'Added', 'type' => 'date', 'hide' => 'lg'],
                ];

                $tenantFilters = [
                    ['key' => 'type', 'label' => 'Type', 'options' => [
                        ['value' => 'operator', 'label' => 'Operators'],
                        ['value' => 'client', 'label' => 'Clients'],
                    ]],
                    ['key' => 'model', 'label' => 'Model', 'options' => $modelOptions ?? []],
                ];

                $bulkActions = [
                    ['label' => 'Export', 'disabled' => true, 'note' => 'soon'],
                    ['label' => 'Archive', 'disabled' => true, 'note' => 'soon'],
                ];

                $tenantRows = [];
                foreach ($tenants ?? [] as $t) {
                    $tenantRows[] = [
                        'id' => $t['id'],
                        'href' => route('platform.tenants.show', $t['id']),
                        'search' => implode(' ', array_filter([
                            $t['name'], $t['slug'], $t['type_label'], $t['model_label'],
                            $t['parent_name'], $t['location'],
                        ])),
                        'filters' => ['type' => $t['type'], 'model' => $t['model_value']],
                        'cells' => [
                            'name' => ['type' => 'text', 'value' => $t['name'], 'sub' => '/'.$t['slug'], 'sort' => $t['name']],
                            'type' => ['type' => 'badge', 'value' => $t['type_label'], 'tone' => $t['type'] === 'operator' ? 'brand' : 'soft', 'sort' => $t['type_label']],
                            'model' => ['type' => 'badge', 'value' => $t['model_label'], 'tone' => 'neutral', 'sort' => $t['model_label']],
                            'parent' => ['type' => 'muted', 'value' => $t['parent_name'] ?? '—', 'sort' => $t['parent_name'] ?? ''],
                            'users' => ['type' => 'strong', 'value' => $t['user_count'], 'sort' => $t['user_count']],
                            'clients' => ['type' => 'strong', 'value' => $t['type'] === 'operator' ? $t['client_count'] : '—', 'sort' => $t['client_count']],
                            'added' => ['type' => 'muted', 'value' => $t['created_label'], 'sort' => $t['created_sort']],
                        ],
                        'actions' => [
                            ['label' => 'Configure tenant', 'href' => route('platform.tenants.show', $t['id'])],
                            ['label' => 'View as tenant', 'disabled' => true, 'note' => 'soon'],
                        ],
                    ];
                }
            @endphp

            <x-data-table
                id="tenants"
                :columns="$tenantColumns"
                :rows="$tenantRows"
                :filters="$tenantFilters"
                :search="$q ?? ''"
                search-placeholder="Search tenants by name, slug or location"
                count-noun="tenant"
                selectable
                :bulk-actions="$bulkActions"
                :row-actions="true"
                :per-page="25"
                :error="$estateError"
                empty="No tenant organisations found yet." />
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
