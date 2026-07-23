@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
@php
    // Placement lookup + display order from the saved layout.
    $placedOrder = [];
    $placedMeta = [];
    foreach ($layout as $i => $p) {
        $placedOrder[$p['key']] = $i;
        $placedMeta[$p['key']] = $p;
    }
    $personalFlip = array_flip($personalKeys ?? []);

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
    <x-my-nav active="my-learning" />

    <main class="min-w-0 flex-1" data-dashboard data-save-url="{{ route('my.dashboard.save') }}">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-teachhq">My workspace</p>
                <h1 class="mt-1 text-2xl font-black text-slatecard">My Dashboard</h1>
                <p class="mt-2 max-w-xl text-sm text-ink-soft">Build your own view of your training. Add the widgets you care about, size them, and arrange them however works for you — your layout is saved automatically.</p>
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
            <p class="mx-auto mt-1 max-w-md text-sm text-ink-soft">Add widgets to track your training at a glance. Choose from the {{ count($widgets) }} available to you and arrange them your way.</p>
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
                    $whasData = isset($personalFlip[$key]) ? $hasData : true;
                    $sizes = $w['sizes'] ?: ['s', 'm', 'l'];
                @endphp
                <div data-cell data-widget-key="{{ $key }}" data-size="{{ $size }}"
                     data-placed="{{ $placed ? '1' : '0' }}" data-comparison="{{ $cmp ?? '' }}"
                     style="order: {{ $order }};" tabindex="-1"
                     @class([$sizeSpan[$size] ?? $sizeSpan['m'], 'group relative self-start', 'hidden' => ! $placed])
                     role="group" aria-label="{{ $w['name'] }} widget">
                    {{-- Edit toolbar (shown only in Customise mode) --}}
                    <div data-toolbar class="mb-2 hidden flex-wrap items-center gap-1 rounded-control border border-line bg-paper p-1">
                        <span data-drag-handle class="flex-none cursor-grab px-1 text-ink-faint" aria-hidden="true" title="Drag to reorder">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.4"/><circle cx="15" cy="6" r="1.4"/><circle cx="9" cy="12" r="1.4"/><circle cx="15" cy="12" r="1.4"/><circle cx="9" cy="18" r="1.4"/><circle cx="15" cy="18" r="1.4"/></svg>
                        </span>
                        <button type="button" data-move="-1" aria-label="Move {{ $w['name'] }} earlier"
                                class="rounded p-1 text-ink-soft transition hover:bg-surface hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button type="button" data-move="1" aria-label="Move {{ $w['name'] }} later"
                                class="rounded p-1 text-ink-soft transition hover:bg-surface hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                        <span class="mx-0.5 inline-flex overflow-hidden rounded-control border border-line" role="group" aria-label="Widget size">
                            @foreach (['s', 'm', 'l'] as $sz)
                                @if (in_array($sz, $sizes, true))
                                    <button type="button" data-set-size="{{ $sz }}" aria-pressed="{{ $sz === $size ? 'true' : 'false' }}"
                                            aria-label="{{ $sizeName[$sz] }} size for {{ $w['name'] }}"
                                            class="size-btn px-2 py-0.5 text-nano font-bold text-ink-soft transition hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq aria-pressed:bg-teachhq aria-pressed:text-on-brand">{{ strtoupper($sz) }}</button>
                                @endif
                            @endforeach
                        </span>
                        <button type="button" data-remove aria-label="Remove {{ $w['name'] }}"
                                class="ml-auto rounded p-1 text-ink-soft transition hover:bg-rag-red-soft hover:text-rag-red focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    {{-- Size variants — one is visible; resizing toggles between them. --}}
                    @foreach (['s', 'm', 'l'] as $sz)
                        @if (in_array($sz, $sizes, true))
                            <div data-variant="{{ $sz }}" @class(['h-full', 'hidden' => $sz !== $size])>
                                <x-dynamic-component :component="$w['component']" :widget="$w" :metric="$metrics[$key] ?? []" :size="$sz" :comparison="$cmp" :has-data="$whasData" />
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Add-widget picker --}}
        <div data-picker class="fixed inset-0 z-50 hidden items-end justify-center bg-scrim p-0 sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-label="Add a widget">
            <div class="flex max-h-full w-full max-w-2xl flex-col overflow-hidden rounded-t-panel bg-surface shadow-panel sm:rounded-panel">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <div>
                        <h2 class="text-base font-black text-slatecard">Add a widget</h2>
                        <p class="text-mini text-ink-soft">Choose from the widgets available to you.</p>
                    </div>
                    <button type="button" data-picker-close aria-label="Close"
                            class="rounded-control p-1.5 text-ink-soft transition hover:bg-paper hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-5">
                    @foreach ($byCategory as $category => $items)
                        <div @class(['mt-5' => ! $loop->first])>
                            <h3 class="mb-2 text-nano font-bold uppercase tracking-wider text-ink-faint">{{ $category }}</h3>
                            <ul class="space-y-2">
                                @foreach ($items as $key => $w)
                                    <li data-picker-entry="{{ $key }}" data-placed="{{ isset($placedMeta[$key]) ? '1' : '0' }}"
                                        class="flex items-center gap-3 rounded-control border border-line bg-surface p-3">
                                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-control bg-teachhq-soft text-teachhq" aria-hidden="true">
                                            <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $w['icon'] !!}</svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-semibold text-slatecard">{{ $w['name'] }}</div>
                                            <div class="truncate text-mini text-ink-soft">{{ $w['description'] }}</div>
                                        </div>
                                        <button type="button" data-picker-add {{ isset($placedMeta[$key]) ? 'disabled' : '' }}
                                                class="flex-none rounded-control border border-line bg-surface px-3 py-1.5 text-mini font-semibold text-teachhq transition hover:bg-teachhq-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq disabled:cursor-not-allowed disabled:text-ink-faint disabled:hover:bg-surface">{{ isset($placedMeta[$key]) ? 'Added' : 'Add' }}</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                    <p data-picker-empty class="hidden py-6 text-center text-sm text-ink-soft">Every available widget is already on your dashboard.</p>
                </div>
            </div>
        </div>
    </main>
</div>

@push('scripts')
    <script>
        (function () {
            'use strict';

            var root = document.querySelector('[data-dashboard]');
            if (!root) { return; }

            var grid = root.querySelector('[data-grid]');
            var picker = root.querySelector('[data-picker]');
            var emptyState = root.querySelector('[data-empty-state]');
            var editToggle = root.querySelector('[data-edit-toggle]');
            var statusEl = root.querySelector('[data-save-status]');
            var saveUrl = root.getAttribute('data-save-url');
            var editing = false;
            var dragKey = null;
            var saveTimer = null;

            var SPAN_ALL = ['col-span-1', 'sm:col-span-1', 'sm:col-span-2', 'lg:col-span-1', 'lg:col-span-2'];
            var SPAN = {
                s: ['col-span-1', 'sm:col-span-1', 'lg:col-span-1'],
                m: ['col-span-1', 'sm:col-span-2', 'lg:col-span-2'],
                l: ['col-span-1', 'sm:col-span-2', 'lg:col-span-2']
            };

            function esc(s) {
                if (window.CSS && CSS.escape) { return CSS.escape(s); }
                return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
            }
            function cells() { return Array.prototype.slice.call(grid.querySelectorAll('[data-cell]')); }
            function cellByKey(key) { return grid.querySelector('[data-cell][data-widget-key="' + esc(key) + '"]'); }
            function orderOf(c) { return parseInt(c.style.order || '0', 10) || 0; }
            function placedCells() {
                return cells().filter(function (c) { return c.getAttribute('data-placed') === '1'; })
                    .sort(function (a, b) { return orderOf(a) - orderOf(b); });
            }
            function normalise() { placedCells().forEach(function (c, i) { c.style.order = String(i); }); }

            function setStatus(state) {
                if (!statusEl) { return; }
                var map = { saving: 'Saving…', saved: 'All changes saved', error: 'Could not save — try again' };
                statusEl.textContent = map[state] || '';
            }
            function scheduleSave() {
                setStatus('saving');
                if (saveTimer) { clearTimeout(saveTimer); }
                saveTimer = setTimeout(save, 500);
            }
            function save() {
                var layout = placedCells().map(function (c) {
                    var entry = { key: c.getAttribute('data-widget-key'), size: c.getAttribute('data-size') };
                    var cmp = c.getAttribute('data-comparison');
                    if (cmp) { entry.settings = { comparison: cmp }; }
                    return entry;
                });
                var meta = document.querySelector('meta[name=csrf-token]');
                fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': meta ? meta.getAttribute('content') : ''
                    },
                    body: JSON.stringify({ layout: layout })
                }).then(function (r) { setStatus(r.ok ? 'saved' : 'error'); })
                    .catch(function () { setStatus('error'); });
            }

            function refreshEmpty() {
                var any = placedCells().length > 0;
                if (emptyState) { emptyState.classList.toggle('hidden', any); }
                if (grid) { grid.classList.toggle('hidden', !any); }
            }
            function refreshPicker() {
                if (!picker) { return; }
                var entries = picker.querySelectorAll('[data-picker-entry]');
                var remaining = 0;
                Array.prototype.forEach.call(entries, function (en) {
                    var cell = cellByKey(en.getAttribute('data-picker-entry'));
                    var placed = !!cell && cell.getAttribute('data-placed') === '1';
                    if (!placed) { remaining++; }
                    en.setAttribute('data-placed', placed ? '1' : '0');
                    var btn = en.querySelector('[data-picker-add]');
                    if (btn) { btn.disabled = placed; btn.textContent = placed ? 'Added' : 'Add'; }
                });
                var note = picker.querySelector('[data-picker-empty]');
                if (note) { note.classList.toggle('hidden', remaining > 0); }
            }

            function setSize(cell, size) {
                if (!SPAN[size]) { return; }
                cell.setAttribute('data-size', size);
                SPAN_ALL.forEach(function (c) { cell.classList.remove(c); });
                SPAN[size].forEach(function (c) { cell.classList.add(c); });
                Array.prototype.forEach.call(cell.querySelectorAll('[data-variant]'), function (v) {
                    v.classList.toggle('hidden', v.getAttribute('data-variant') !== size);
                });
                Array.prototype.forEach.call(cell.querySelectorAll('[data-set-size]'), function (b) {
                    b.setAttribute('aria-pressed', b.getAttribute('data-set-size') === size ? 'true' : 'false');
                });
                scheduleSave();
            }

            function addWidget(key) {
                var cell = cellByKey(key);
                if (!cell) { return; }
                cell.setAttribute('data-placed', '1');
                cell.classList.remove('hidden');
                cell.style.order = String(placedCells().length);
                normalise();
                refreshEmpty();
                refreshPicker();
                scheduleSave();
            }
            function removeWidget(cell) {
                cell.setAttribute('data-placed', '0');
                cell.classList.add('hidden');
                normalise();
                refreshEmpty();
                refreshPicker();
                scheduleSave();
            }

            function move(cell, dir) {
                var list = placedCells();
                var i = list.indexOf(cell);
                var j = i + dir;
                if (i < 0 || j < 0 || j >= list.length) { return; }
                var a = orderOf(cell), b = orderOf(list[j]);
                cell.style.order = String(b);
                list[j].style.order = String(a);
                scheduleSave();
                cell.focus();
            }

            function setComparison(cell, period) {
                cell.setAttribute('data-comparison', period || '');
                Array.prototype.forEach.call(cell.querySelectorAll('[data-widget-compare]'), function (sel) {
                    if (sel.value !== period) { sel.value = period; }
                });
                Array.prototype.forEach.call(cell.querySelectorAll('[data-widget-delta]'), function (grp) {
                    Array.prototype.forEach.call(grp.querySelectorAll('[data-delta-period]'), function (span) {
                        span.classList.toggle('hidden', span.getAttribute('data-delta-period') !== period);
                    });
                });
                scheduleSave();
            }

            function setEditing(on) {
                editing = on;
                cells().forEach(function (c) {
                    var tb = c.querySelector('[data-toolbar]');
                    if (tb) { tb.classList.toggle('hidden', !on); }
                    c.classList.toggle('ring-2', on);
                    c.classList.toggle('ring-teachhq/30', on);
                    c.setAttribute('draggable', on ? 'true' : 'false');
                    c.setAttribute('tabindex', on ? '0' : '-1');
                });
                if (editToggle) {
                    editToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
                    var lbl = editToggle.querySelector('[data-edit-label]');
                    if (lbl) { lbl.textContent = on ? 'Done' : 'Customise'; }
                }
            }

            function openPicker() {
                if (!picker) { return; }
                refreshPicker();
                picker.classList.remove('hidden');
                picker.classList.add('flex');
                var f = picker.querySelector('[data-picker-add]:not([disabled])');
                if (f) { f.focus(); }
            }
            function closePicker() {
                if (!picker) { return; }
                picker.classList.add('hidden');
                picker.classList.remove('flex');
            }

            // Delegated interactions.
            root.addEventListener('click', function (e) {
                var t = e.target;
                if (t.closest('[data-add-widget]')) { openPicker(); return; }
                if (t.closest('[data-picker-close]')) { closePicker(); return; }
                var add = t.closest('[data-picker-add]');
                if (add) {
                    var en = add.closest('[data-picker-entry]');
                    if (en) { addWidget(en.getAttribute('data-picker-entry')); }
                    return;
                }
                if (t.closest('[data-edit-toggle]')) { setEditing(!editing); return; }
                var cell = t.closest('[data-cell]');
                if (!cell) { return; }
                var sz = t.closest('[data-set-size]');
                if (sz) { setSize(cell, sz.getAttribute('data-set-size')); return; }
                var mv = t.closest('[data-move]');
                if (mv) { move(cell, parseInt(mv.getAttribute('data-move'), 10)); return; }
                if (t.closest('[data-remove]')) { removeWidget(cell); return; }
            });

            root.addEventListener('change', function (e) {
                var sel = e.target.closest ? e.target.closest('[data-widget-compare]') : null;
                if (!sel) { return; }
                var cell = sel.closest('[data-cell]');
                if (cell) { setComparison(cell, sel.value); }
            });

            grid.addEventListener('keydown', function (e) {
                if (!editing) { return; }
                var cell = e.target.closest ? e.target.closest('[data-cell]') : null;
                if (!cell || cell !== e.target) { return; }
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); move(cell, -1); }
                else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); move(cell, 1); }
            });

            grid.addEventListener('dragstart', function (e) {
                var cell = e.target.closest ? e.target.closest('[data-cell]') : null;
                if (!editing || !cell) { if (e.preventDefault) { e.preventDefault(); } return; }
                dragKey = cell.getAttribute('data-widget-key');
                cell.classList.add('opacity-50');
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    try { e.dataTransfer.setData('text/plain', dragKey); } catch (x) { /* IE guard */ }
                }
            });
            grid.addEventListener('dragend', function () {
                dragKey = null;
                cells().forEach(function (c) { c.classList.remove('opacity-50'); });
            });
            grid.addEventListener('dragover', function (e) { if (editing && dragKey) { e.preventDefault(); } });
            grid.addEventListener('drop', function (e) {
                if (!editing || !dragKey) { return; }
                e.preventDefault();
                var target = e.target.closest ? e.target.closest('[data-cell]') : null;
                var dragged = cellByKey(dragKey);
                if (!dragged || !target || target === dragged || target.getAttribute('data-placed') !== '1') { return; }
                var list = placedCells();
                var from = list.indexOf(dragged), to = list.indexOf(target);
                if (from < 0 || to < 0) { return; }
                list.splice(from, 1);
                list.splice(to, 0, dragged);
                list.forEach(function (c, i) { c.style.order = String(i); });
                scheduleSave();
            });

            if (picker) {
                picker.addEventListener('click', function (e) { if (e.target === picker) { closePicker(); } });
            }
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closePicker(); } });

            normalise();
            refreshEmpty();
            refreshPicker();
        })();
    </script>
@endpush
@endsection
