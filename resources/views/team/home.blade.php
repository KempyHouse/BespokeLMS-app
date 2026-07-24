@extends('layouts.app')

@section('title', 'Team Dashboard')

@section('content')
@php
    // Placement lookup + display order from the saved layout.
    $placedOrder = [];
    $placedMeta = [];
    foreach ($layout as $i => $p) {
        $placedOrder[$p['key']] = $i;
        $placedMeta[$p['key']] = $p;
    }
    $teamFlip = array_flip($teamKeys ?? []);

    // Column footprint per size (mobile is always one column). Kept in sync with
    // the SPAN map in the controller script below and the safelist span.
    $sizeSpan = [
        's' => 'col-span-1 sm:col-span-1 lg:col-span-1',
        'm' => 'col-span-1 sm:col-span-2 lg:col-span-2',
        'l' => 'col-span-1 sm:col-span-2 lg:col-span-2',
    ];

    $sizeName = ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'];
    $hasAnyPlaced = count($layout) > 0;

    // Group the addable widgets by category for the picker.
    $byCategory = [];
    foreach ($widgets as $key => $w) {
        $byCategory[$w['category']][$key] = $w;
    }
@endphp

<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <x-team-nav active="team-dashboard" />

    <main class="min-w-0 flex-1" data-dashboard data-save-url="{{ route('team.dashboard.save') }}">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">Team workspace</p>
                <h1 class="mt-1 text-2xl font-black text-slatecard">Team Dashboard</h1>
                <p class="mt-2 max-w-xl text-sm text-ink-soft">Monitor your team's training and performance. Add the widgets you care about, size them, and arrange them however works for you — your layout is saved automatically.</p>
            </div>
            <div class="flex flex-none flex-col items-end gap-1.5">
                <div class="flex items-center gap-2">
                    <button type="button" data-add-widget
                            class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
                        <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        Add widget
                    </button>
                    <button type="button" data-edit-toggle aria-pressed="false"
                            class="inline-flex items-center gap-1.5 rounded-control border border-line bg-surface px-4 py-2 text-sm font-semibold text-slatecard transition hover:border-ink-faint focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
                        <svg class="h-icon w-icon text-ink-soft" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span data-edit-label>Customise</span>
                    </button>
                </div>
                <p data-save-status role="status" aria-live="polite" class="h-4 text-mini text-ink-faint"></p>
            </div>
        </div>

        @if ($registryError)
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ $registryError }}</p>
            </div>
        @endif

        {{-- Empty dashboard state --}}
        <div data-empty-state @class(['rounded-panel border border-dashed border-line bg-surface p-10 text-center', 'hidden' => $hasAnyPlaced])>
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teachhq-soft text-teachhq" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            </span>
            <h2 class="mt-3 text-lg font-black text-slatecard">Your dashboard is empty</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-ink-soft">Add widgets to track your team's performance at a glance. Choose from the {{ count($widgets) }} available to you and arrange them your way.</p>
            <button type="button" data-add-widget
                    class="mt-4 inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1">
                <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Add your first widget
            </button>
        </div>

        {{-- Safelist: span/state classes applied by the controller script (so the
             Tailwind build includes them even though they are added at runtime). --}}
        <span class="hidden col-span-1 sm:col-span-1 sm:col-span-2 lg:col-span-1 lg:col-span-2 ring-2 ring-teachhq/30 opacity-50" aria-hidden="true"></span>

        {{-- Widget grid. Every widget the user may add is rendered once (at all its
             sizes); placed ones are shown in saved order, the rest stay hidden and
             appear in the Add-widget picker. --}}
        <div data-grid @class(['grid grid-cols-1 items-start gap-4 sm:grid-cols-2 lg:grid-cols-4', 'hidden' => ! $hasAnyPlaced])>
            @foreach ($widgets as $key => $w)
                @continue($w['component'] === '')
                @php
                    $placed = isset($placedMeta[$key]);
                    $size = $placed ? $placedMeta[$key]['size'] : $w['default_size'];
                    $cmp = $placed ? ($placedMeta[$key]['settings']['comparison'] ?? $w['comparison_default']) : $w['comparison_default'];
                    $order = $placed ? $placedOrder[$key] : 999;
                    $whasData = isset($teamFlip[$key]) ? $hasData : true;
                    $sizes = $w['sizes'] ?: ['s', 'm', 'l'];
                @endphp
                <article @class([
                    'rounded-panel border border-line bg-paper p-5 transition',
                    $sizeSpan[$size],
                    'order-' . $order,
                    'hidden' => ! $placed,
                    'opacity-50 ring-2 ring-teachhq/30' => $placed && ! isset($_GET['no-edit']),
                ])
                         data-widget-key="{{ $key }}"
                         data-widget-sizes="{{ implode(',', $sizes) }}"
                         data-sizes-allowed="{{ implode(',', $sizes) }}">

                    {{-- Editing UI: placeholders for size/position controls (shown when customizing). --}}
                    @if ($placed)
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="text-xs font-bold uppercase tracking-wider text-ink-soft">{{ $w['category'] }}</p>
                                <h3 class="text-sm font-bold text-slatecard">{{ $w['name'] }}</h3>
                            </div>
                            <button type="button" data-widget-remove aria-label="Remove widget"
                                    class="flex-none rounded-lg p-1 text-ink-soft transition hover:bg-paper hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6l-12 12M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="mb-3 border-t border-line pt-3">
                            <p class="mb-1 text-nano font-semibold text-ink-faint">Size</p>
                            <div class="flex gap-1">
                                @foreach ($sizes as $s)
                                    <button type="button"
                                            data-size-btn="{{ $s }}"
                                            @class(['rounded-lg border border-line bg-paper px-2 py-1 text-mini font-semibold transition',
                                                'border-teachhq bg-teachhq text-on-brand' => $s === $size,
                                                'text-ink-faint hover:text-slatecard' => $s !== $size])>
                                        {{ $sizeName[$s] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Widget render. Every widget component gets:
                         - active (whether it's placed/visible)
                         - data (from the metrics map or empty object)
                         - hasData (whether real data exists for this metric)
                         - comparison (the selected comparison mode, if any)
                         - comparisons (options for this metric, if any)
                         - label (the widget name)
                    --}}
                    @includeUnless($w['component'] === '',
                        'components.widgets.' . $w['component'],
                        [
                            'active' => $placed,
                            'data' => $metrics[$key] ?? [],
                            'hasData' => $whasData,
                            'comparison' => $cmp,
                            'comparisons' => $w['comparison_options'],
                            'label' => $w['name'],
                        ])
                </article>
            @endforeach
        </div>

        {{-- Add-widget picker (modally + in-silo). Filled in by the script below
             with copies of the non-placed widgets. --}}
        <div data-add-modal role="dialog" aria-labelledby="add-modal-title" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
            <button type="button" data-add-modal-close class="fixed inset-0 bg-black/50"></button>
            <div class="rounded-panel border border-line bg-paper p-6 shadow-xl">
                <h2 id="add-modal-title" class="text-xl font-black text-slatecard">Add a widget</h2>
                <p class="mt-1 text-sm text-ink-soft">Choose from {{ count($widgets) }} available widgets to monitor your team's performance.</p>
                <form class="mt-4" data-add-form>
                    @if (empty($byCategory))
                        <p class="text-sm text-ink-faint">No widgets available for your role.</p>
                    @else
                        @foreach ($byCategory as $category => $widgetsInCategory)
                            <fieldset class="mb-4">
                                <legend class="mb-2 text-xs font-bold uppercase tracking-wider text-teachhq">{{ $category }}</legend>
                                <div class="space-y-2">
                                    @foreach ($widgetsInCategory as $w)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line bg-paper p-3 transition hover:border-teachhq hover:bg-paper">
                                            <input type="radio" name="widget" value="{{ $w['key'] }}"
                                                   class="mt-1 accent-teachhq" />
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-slatecard">{{ $w['name'] }}</p>
                                                <p class="text-sm text-ink-soft">{{ $w['description'] }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    @endif

                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="flex-1 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover">Add widget</button>
                        <button type="button" data-add-modal-close class="flex-1 rounded-control border border-line px-4 py-2 text-sm font-semibold text-slatecard transition hover:border-ink-faint">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script type="module">
    /* Dashboard widget configuration UI — shared by My and Team dashboards.
       Builds the add-widget picker, handles size/position/delete, persists changes
       to the save endpoint (data-save-url on the main element). */

    // This script is intentionally identical to the one in My Dashboard and should
    // be kept in sync. Future refactor: move to a shared module or Stimulus controller.

    const SPAN_MAP = {
        s: "col-span-1 sm:col-span-1 lg:col-span-1",
        m: "col-span-1 sm:col-span-2 lg:col-span-2",
        l: "col-span-1 sm:col-span-2 lg:col-span-2",
    };

    const dashboard = document.querySelector("[data-dashboard]");
    if (!dashboard) process.exit(0);

    const saveUrl = dashboard.getAttribute("data-save-url");
    const editToggle = dashboard.querySelector("[data-edit-toggle]");
    const addWidgetBtns = dashboard.querySelectorAll("[data-add-widget]");
    const addModal = dashboard.querySelector("[data-add-modal]");
    const addForm = dashboard.querySelector("[data-add-form]");
    const addCloseButtons = dashboard.querySelectorAll("[data-add-modal-close]");
    const saveStatus = dashboard.querySelector("[data-save-status]");
    const grid = dashboard.querySelector("[data-grid]");
    const emptyState = dashboard.querySelector("[data-empty-state]");

    // Edit mode toggle.
    editToggle?.addEventListener("click", (e) => {
        const pressed = editToggle.getAttribute("aria-pressed") === "true";
        editToggle.setAttribute("aria-pressed", String(!pressed));
        editToggle.querySelector("[data-edit-label]").textContent = pressed ? "Customise" : "Done";
        dashboard.classList.toggle("edit-mode", !pressed);
    });

    // Add widget button.
    addWidgetBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            addModal?.classList.remove("hidden");
            addModal?.classList.add("flex");
        });
    });

    // Add modal close.
    addCloseButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            addModal?.classList.add("hidden");
            addModal?.classList.remove("flex");
        });
    });

    // Add widget form submission.
    addForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const selected = new FormData(addForm).get("widget");
        if (!selected) return;

        const widgets = grid?.querySelectorAll("[data-widget-key]") || [];
        const layout = Array.from(widgets)
            .filter((w) => w.classList.contains("order-") === false || w.getAttribute("data-widget-key"))
            .map((w) => {
                const key = w.getAttribute("data-widget-key");
                const size = "m"; // Default size for new widgets.
                return { key, size, settings: {} };
            });

        layout.push({ key: selected, size: "m", settings: {} });

        await saveLayout(layout);
        addModal?.classList.add("hidden");
        addModal?.classList.remove("flex");
        location.reload();
    });

    // Widget size buttons.
    dashboard.addEventListener("click", async (e) => {
        if (e.target.dataset.sizeBtn) {
            const article = e.target.closest("[data-widget-key]");
            if (!article) return;

            const newSize = e.target.dataset.sizeBtn;
            const oldSpan = article.className.match(/col-span-\d\s*sm:col-span-\d\s*lg:col-span-\d/)?.[0] || "";
            const newSpan = SPAN_MAP[newSize];

            article.className = article.className.replace(oldSpan, newSpan);

            // Update all size buttons.
            article.querySelectorAll("[data-size-btn]").forEach((btn) => {
                btn.classList.toggle("border-teachhq", btn.dataset.sizeBtn === newSize);
                btn.classList.toggle("bg-teachhq", btn.dataset.sizeBtn === newSize);
                btn.classList.toggle("text-on-brand", btn.dataset.sizeBtn === newSize);
                btn.classList.toggle("text-ink-faint", btn.dataset.sizeBtn !== newSize);
            });

            await persistLayout();
        }

        if (e.target.closest("[data-widget-remove]")) {
            const article = e.target.closest("[data-widget-key]");
            if (!article) return;
            article.remove();
            await persistLayout();
        }
    });

    async function persistLayout() {
        const widgets = grid?.querySelectorAll("[data-widget-key]") || [];
        const layout = Array.from(widgets).map((w) => {
            const key = w.getAttribute("data-widget-key");
            const spanClass = Array.from(w.classList).find((c) => c.includes("lg:col-span"));
            let size = "m";
            if (spanClass?.includes("lg:col-span-1")) size = "s";
            else if (spanClass?.includes("lg:col-span-2")) size = "m";

            return { key, size, settings: { comparison: null } };
        });

        const resp = await fetch(saveUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')?.content || "",
            },
            body: JSON.stringify({ layout }),
        });

        const ok = resp.ok;
        if (saveStatus) {
            saveStatus.textContent = ok ? "Saved" : "Could not save";
            saveStatus.style.color = ok ? "var(--color-teachhq)" : "var(--color-rag-red)";
            setTimeout(() => {
                saveStatus.textContent = "";
            }, 2000);
        }
    }
</script>
@endsection
