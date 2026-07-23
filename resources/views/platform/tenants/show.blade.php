@extends('layouts.app')

@section('title', $tenant['name'].' · Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-20 lg:self-start">
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <div class="mb-5 border-b border-line pb-5">
                <a href="{{ route('platform.home') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    All tenants
                </a>
                <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">{{ $tenant['type_label'] }} tenant</p>
                <h1 class="mt-1 text-xl font-black text-slatecard">{{ $tenant['name'] }}</h1>
            </div>

            <nav aria-label="Tenant configuration sections">
                <div class="mb-2.5 text-xs font-bold uppercase tracking-wider text-teachhq">Configuration</div>
                <ul>
                    @php
                        $sections = [
                            ['id' => 'overview', 'label' => 'Overview'],
                            ['id' => 'branding', 'label' => 'Branding & brand kit'],
                            ['id' => 'domain', 'label' => 'Domain & routing'],
                            ['id' => 'clients', 'label' => $tenant['is_operator'] ? 'Client organisations' : 'Parent operator'],
                            ['id' => 'users', 'label' => 'Users & roles'],
                            ['id' => 'courses', 'label' => 'Courses'],
                            ['id' => 'ai', 'label' => 'AI & integrations'],
                        ];
                    @endphp
                    @foreach ($sections as $s)
                        <li>
                            <a href="#{{ $s['id'] }}" data-spy-link="{{ $s['id'] }}"
                               class="spy-link flex items-center gap-2.5 border-b border-line py-2.5 text-sm font-medium text-slatecard transition hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2 {{ $loop->last ? 'border-b-0' : '' }}">
                                <span class="min-w-0 flex-1 truncate">{{ $s['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Tenant configuration</p>
                <p class="mt-2 max-w-xl text-sm text-ink-soft">Configure this tenant's white-label LMS instance. All values are read from Supabase.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:flex-none">
                <x-tenant-selector :tenants="$tenants" :current="$tenant['id']" label="Switch tenant" id="switch-tenant" />
                <button type="button" disabled
                        class="inline-flex items-center justify-center gap-1.5 rounded-control border border-teachhq bg-teachhq-soft px-4 py-2 text-sm font-semibold text-teachhq-dark opacity-70 focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2"
                        title="Sign-in-as impersonation is the next build">
                    <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    View as this tenant <span class="text-micro font-normal opacity-80">(coming next)</span>
                </button>
            </div>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif
        @if (session('brandingError'))
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ session('brandingError') }}</p>
            </div>
        @endif

        <div class="flex flex-col gap-5">
            <!-- Overview -->
            <section id="overview" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h2 class="text-lg font-black text-slatecard">Overview</h2>
                <p class="mt-1 text-caption text-ink-soft">The tenant's core record in the estate.</p>
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3">
                    @php
                        $facts = [
                            ['t' => 'Type', 'v' => $tenant['type_label']],
                            ['t' => 'Model', 'v' => $tenant['model_label']],
                            ['t' => 'Slug', 'v' => '/'.$tenant['slug']],
                            ['t' => 'Location', 'v' => $tenant['location'] ?? '—'],
                            ['t' => 'Users', 'v' => (string) $tenant['user_count']],
                            ['t' => 'Added', 'v' => $tenant['created_label']],
                        ];
                        if ($tenant['is_operator']) {
                            $facts[] = ['t' => 'Client orgs', 'v' => (string) $tenant['client_count']];
                            $facts[] = ['t' => 'Client layer', 'v' => $tenant['has_client_layer'] ? 'Yes' : 'No'];
                        }
                    @endphp
                    @foreach ($facts as $f)
                        <div>
                            <dt class="text-mini font-semibold uppercase tracking-wide text-ink-soft">{{ $f['t'] }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slatecard">{{ $f['v'] }}</dd>
                        </div>
                    @endforeach
                    @if ($tenant['parent'])
                        <div>
                            <dt class="text-mini font-semibold uppercase tracking-wide text-ink-soft">Parent</dt>
                            <dd class="mt-1 text-sm font-semibold">
                                @if ($tenant['parent']['is_platform'])
                                    <span class="text-slatecard">{{ $tenant['parent']['name'] }} (platform)</span>
                                @else
                                    <a href="{{ route('platform.tenants.show', $tenant['parent']['id']) }}" class="text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">{{ $tenant['parent']['name'] }}</a>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            <!-- Branding -->
            <section id="branding" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h2 class="text-lg font-black text-slatecard">Branding &amp; brand kit</h2>
                <p class="mt-1 text-caption text-ink-soft">Styling is driven by the Supabase design-token system. Set the themeable tokens below to reskin this tenant's instance; anything left inheriting uses the platform default.</p>

                @if ($branding === null)
                    <div class="mt-5 rounded-control border border-dashed border-line bg-paper p-5 text-sm text-ink-soft">
                        The brand kit could not be loaded right now. Please try again shortly.
                    </div>
                @elseif (empty($branding['fields']))
                    <div class="mt-5 rounded-control border border-dashed border-line bg-paper p-5 text-sm text-ink-soft">
                        No themeable tokens are defined in the design system yet.
                    </div>
                @else
                    @if ($errors->any())
                        <div role="alert" class="mt-5 rounded-control border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                            Some values were not valid. Colours must look like <span class="font-mono">#009de1</span> and sizes like <span class="font-mono">0.85rem</span>.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('platform.tenants.branding.update', $tenant['id']) }}" class="mt-5" data-brandkit-form>
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($branding['fields'] as $field)
                                @php
                                    $isColor = $field['type'] === 'color';
                                    $eff = $field['effective'];
                                    // Native colour inputs need #rrggbb; expand #rgb.
                                    $colorVal = $eff;
                                    if ($isColor && preg_match('/^#([0-9a-fA-F]{3})$/', $eff, $m)) {
                                        $colorVal = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
                                    }
                                    $inheriting = old('inherit.'.$field['key'], $field['inheriting'] ? '1' : null) !== null;
                                @endphp
                                <div class="rounded-control border border-line bg-paper p-4" data-brandkit-field>
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <label for="bk-{{ $field['key'] }}" class="block text-sm font-semibold text-slatecard">{{ $field['label'] }}</label>
                                            <span class="mt-0.5 block font-mono text-micro text-ink-faint">{{ $field['css_var'] }}</span>
                                        </div>
                                        <label class="flex flex-none items-center gap-1.5 text-mini font-medium text-ink-soft">
                                            <input type="checkbox" name="inherit[{{ $field['key'] }}]" value="1" data-brandkit-inherit @checked($inheriting)
                                                   class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                                            Inherit default
                                        </label>
                                    </div>
                                    <div class="mt-3 flex items-center gap-3">
                                        @if ($isColor)
                                            <input type="color" id="bk-{{ $field['key'] }}" name="tokens[{{ $field['key'] }}]"
                                                   value="{{ old('tokens.'.$field['key'], $colorVal) }}" data-brandkit-input
                                                   class="h-9 w-12 flex-none cursor-pointer rounded-control border border-line bg-surface p-1">
                                            <span class="font-mono text-caption text-ink-soft">{{ old('tokens.'.$field['key'], $colorVal) }}</span>
                                        @else
                                            <input type="text" id="bk-{{ $field['key'] }}" name="tokens[{{ $field['key'] }}]"
                                                   value="{{ old('tokens.'.$field['key'], $field['current']) }}" placeholder="{{ $field['default'] }}" data-brandkit-input
                                                   class="w-full rounded-control border border-line bg-surface px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-teachhq">
                                        @endif
                                        <span class="ml-auto text-micro text-ink-faint">default {{ $field['default'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5 flex items-center gap-3">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                                Save brand kit
                            </button>
                            <span class="text-mini text-ink-soft">Saved values reskin this tenant immediately.</span>
                        </div>
                    </form>
                @endif
            </section>

            <!-- Domain & routing -->
            <section id="domain" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h2 class="text-lg font-black text-slatecard">Domain &amp; routing</h2>
                <p class="mt-1 text-caption text-ink-soft">How learners reach this tenant's instance.</p>
                <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @if ($tenant['is_operator'])
                        <div>
                            <dt class="text-mini font-semibold uppercase tracking-wide text-ink-soft">Branded subdomain</dt>
                            <dd class="mt-1 text-sm font-semibold text-slatecard">{{ $tenant['subdomain'] ?? '—' }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-mini font-semibold uppercase tracking-wide text-ink-soft">Workspace path</dt>
                        <dd class="mt-1 text-sm font-semibold text-slatecard">{{ $tenant['workspace_path'] ?? '—' }}</dd>
                    </div>
                    @if (! $tenant['is_operator'] && $tenant['parent'])
                        <div>
                            <dt class="text-mini font-semibold uppercase tracking-wide text-ink-soft">Served under</dt>
                            <dd class="mt-1 text-sm font-semibold text-slatecard">{{ $tenant['parent']['name'] }} (/{{ $tenant['parent']['slug'] }})</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-mini font-semibold uppercase tracking-wide text-ink-soft">Custom domain</dt>
                        <dd class="mt-1 text-sm text-ink-soft">Not configured</dd>
                    </div>
                </dl>
            </section>

            <!-- Clients / parent -->
            <section id="clients" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                @if ($tenant['is_operator'])
                    <h2 class="text-lg font-black text-slatecard">Client organisations</h2>
                    <p class="mt-1 text-caption text-ink-soft">Organisations this operator delivers to.</p>
                    @if (! $tenant['has_client_layer'])
                        <div class="mt-5 rounded-control border border-dashed border-line bg-paper p-5 text-sm text-ink-soft">
                            This tenant runs in-house training — no client layer.
                        </div>
                    @elseif (empty($tenant['clients']))
                        <div class="mt-5 rounded-control border border-dashed border-line bg-paper p-5 text-sm text-ink-soft">
                            No client organisations yet.
                        </div>
                    @else
                        <ul class="mt-5 divide-y divide-line">
                            @foreach ($tenant['clients'] as $client)
                                <li>
                                    <a href="{{ route('platform.tenants.show', $client['id']) }}"
                                       class="flex flex-wrap items-center justify-between gap-2 py-3 transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                                        <span class="min-w-0">
                                            <span class="block font-semibold text-slatecard">{{ $client['name'] }}</span>
                                            <span class="block text-mini text-ink-soft">{{ $client['model_label'] }}@if ($client['location']) &middot; {{ $client['location'] }}@endif</span>
                                        </span>
                                        <span class="text-mini font-semibold text-ink-soft">{{ $client['user_count'] }} {{ $client['user_count'] === 1 ? 'user' : 'users' }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <h2 class="text-lg font-black text-slatecard">Parent operator</h2>
                    <p class="mt-1 text-caption text-ink-soft">This client organisation is delivered by an operator tenant.</p>
                    @if ($tenant['parent'])
                        <a href="{{ route('platform.tenants.show', $tenant['parent']['id']) }}"
                           class="mt-5 inline-flex items-center gap-2 rounded-control border border-line bg-paper px-4 py-3 text-sm font-semibold text-slatecard transition hover:text-teachhq focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            {{ $tenant['parent']['name'] }}
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @else
                        <p class="mt-5 text-sm text-ink-soft">No parent operator on record.</p>
                    @endif
                @endif
            </section>

            <!-- Users & roles -->
            <section id="users" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h2 class="text-lg font-black text-slatecard">Users &amp; roles</h2>
                <p class="mt-1 text-caption text-ink-soft">People in this tenant.</p>
                <div class="mt-5 flex items-baseline gap-2">
                    <span class="text-3xl font-black tabular-nums text-slatecard">{{ $tenant['user_count'] }}</span>
                    <span class="text-sm text-ink-soft">{{ $tenant['user_count'] === 1 ? 'user' : 'users' }} in this organisation</span>
                </div>
                <p class="mt-3 text-caption text-ink-soft">Per-user administration (invites, roles, teams) is managed in the tenant's own Admin area under Supabase RLS, and will surface here as the impersonation and user-management views land.</p>
            </section>

            <!-- Courses -->
            <section id="courses" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h2 class="text-lg font-black text-slatecard">Courses</h2>
                <p class="mt-1 text-caption text-ink-soft">Course catalogue and visibility.</p>
                <div class="mt-5 rounded-control border border-dashed border-line bg-paper p-5 text-sm text-ink-soft">
                    Platform courses cascade to tenants from the Global Course Catalogue. Per-tenant course visibility and tenant-owned courses are on the roadmap.
                </div>
            </section>

            <!-- AI & integrations -->
            <section id="ai" class="scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h2 class="text-lg font-black text-slatecard">AI &amp; integrations</h2>
                <p class="mt-1 text-caption text-ink-soft">Claude (Anthropic) integration.</p>
                <div class="mt-5 rounded-control border border-dashed border-line bg-paper p-5 text-sm text-ink-soft">
                    The AI integration is configured once at the platform-owner level and inherited by every tenant — it is not configured per tenant.
                </div>
            </section>
        </div>
    </main>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-brandkit-field]').forEach(function (field) {
        var inherit = field.querySelector('[data-brandkit-inherit]');
        var input = field.querySelector('[data-brandkit-input]');
        if (!inherit || !input) return;
        var hex = (input.type === 'color') ? input.nextElementSibling : null;

        function sync() {
            input.disabled = inherit.checked;
            input.classList.toggle('opacity-50', inherit.checked);
        }
        inherit.addEventListener('change', sync);
        input.addEventListener('input', function () {
            if (inherit.checked) { inherit.checked = false; sync(); }
            if (hex) hex.textContent = input.value;
        });
        sync();
    });
});
</script>
@endpush

@push('scripts')
<script>
/* Scrollspy: highlight the configuration nav item whose section is in view. */
document.addEventListener('DOMContentLoaded', function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('[data-spy-link]'));
    if (!links.length || !('IntersectionObserver' in window)) return;
    var sections = links
        .map(function (l) { return document.getElementById(l.getAttribute('data-spy-link')); })
        .filter(Boolean);

    function setActive(id) {
        links.forEach(function (l) {
            var on = l.getAttribute('data-spy-link') === id;
            l.classList.toggle('text-teachhq', on);
            l.classList.toggle('font-semibold', on);
            l.classList.toggle('text-slatecard', !on);
            l.classList.toggle('font-medium', !on);
            if (on) { l.setAttribute('aria-current', 'true'); } else { l.removeAttribute('aria-current'); }
        });
    }

    var current = sections.length ? sections[0].id : null;
    var observer = new IntersectionObserver(function (entries) {
        var visible = entries.filter(function (e) { return e.isIntersecting; });
        if (visible.length) {
            visible.sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
            current = visible[0].target.id;
        }
        if (current) setActive(current);
    }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });

    sections.forEach(function (s) { observer.observe(s); });
    if (sections[0]) setActive(sections[0].id);
});
</script>
@endpush
@endsection
