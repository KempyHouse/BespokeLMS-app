@extends('layouts.app')

@section('title', 'Widget Library · Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none rail-sticky">
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.home') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Platform overview
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Dashboards</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Widget Library</h1>
            <p class="mt-2 text-caption text-ink-soft">The catalogue of widgets users can place on their dashboards. Control which roles see each widget, its status, and its default size.</p>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Platform owner</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">Widget Library</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">Every dashboard widget across the platform. Open a widget to set which roles can add it, whether it is active, and the size it starts at.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @php
            $roleKeys = array_keys($roleLabels);
            $statusLabels = ['active' => 'Active', 'draft' => 'Draft', 'retired' => 'Retired'];
            $statusTone = ['active' => 'green', 'draft' => 'amber', 'retired' => 'soft'];

            $widgetColumns = [
                ['key' => 'name', 'label' => 'Widget', 'type' => 'text'],
                ['key' => 'category', 'label' => 'Category', 'type' => 'text'],
                ['key' => 'sizes', 'label' => 'Sizes', 'type' => 'text', 'hide' => 'md'],
                ['key' => 'comparison', 'label' => 'Comparison', 'type' => 'text', 'hide' => 'lg'],
                ['key' => 'roles', 'label' => 'Visible to', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
            ];

            $widgetFilters = [
                ['key' => 'category', 'label' => 'Category', 'options' => collect($widgets)->pluck('category')->unique()->values()->map(fn ($c) => ['value' => $c, 'label' => $c])->all()],
                ['key' => 'status', 'label' => 'Status', 'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'retired', 'label' => 'Retired'],
                ]],
            ];

            $widgetRows = [];
            foreach ($widgets as $w) {
                $roleCount = count($w['roles']);
                $rolesLabel = match (true) {
                    $roleCount === 0 => 'No one',
                    $roleCount === count($roleKeys) => 'All roles',
                    $w['roles'] === ['bespokelms_owner'] => 'Owner only',
                    default => $roleCount.' roles',
                };
                $sizesLabel = implode(' · ', array_map('strtoupper', $w['sizes']));

                $widgetRows[] = [
                    'id' => $w['key'],
                    'href' => route('platform.widgets.show', $w['key']),
                    'search' => implode(' ', array_filter([$w['name'], $w['key'], $w['category']])),
                    'filters' => ['category' => $w['category'], 'status' => $w['status']],
                    'cells' => [
                        'name' => ['type' => 'text', 'value' => $w['name'], 'sub' => $w['key'], 'sort' => $w['name']],
                        'category' => ['type' => 'badge', 'value' => $w['category'], 'tone' => 'neutral', 'sort' => $w['category']],
                        'sizes' => ['type' => 'muted', 'value' => $sizesLabel, 'sort' => $sizesLabel],
                        'comparison' => ['type' => 'muted', 'value' => $w['supports_comparison'] ? 'Yes' : '—', 'sort' => $w['supports_comparison'] ? '1' : '0'],
                        'roles' => ['type' => 'text', 'value' => $rolesLabel, 'sort' => (string) $roleCount],
                        'status' => ['type' => 'badge', 'value' => $statusLabels[$w['status']] ?? $w['status'], 'tone' => $statusTone[$w['status']] ?? 'soft', 'sort' => $w['status']],
                    ],
                    'actions' => [
                        ['label' => 'Edit widget', 'href' => route('platform.widgets.show', $w['key'])],
                    ],
                ];
            }
        @endphp

        <x-data-table
            id="widgets"
            :columns="$widgetColumns"
            :rows="$widgetRows"
            :filters="$widgetFilters"
            :search="''"
            search-placeholder="Search widgets by name or category"
            count-noun="widget"
            :row-actions="true"
            :per-page="25"
            :error="$error"
            empty="No widgets in the library yet." />

        <p class="mt-4 text-mini text-ink-faint">Widget definitions are seeded and platform-owned. Editing here changes who can add each widget and how it behaves for every user across the estate.</p>
    </main>
</div>
@endsection
