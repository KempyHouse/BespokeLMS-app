{{--
 | Reusable data table + shell — the single, consistent table UI/UX for the
 | whole platform. Every table uses this component; only the column/field
 | definitions and the per-row data vary. It provides, as standard:
 |   • toolbar: search + filter selects + live filtered count
 |   • responsive, sortable columns (cells never wrap — overflow scrolls)
 |   • clickable rows (primary cell is a real link for no-JS / keyboard)
 |   • multi-select checkboxes + a bulk-action bar
 |   • a three-dot row-actions menu
 |   • client-side pagination
 |   • empty / error states
 | Styling is 100% design-token classes so tenant brand kits reskin it. The
 | behaviour script is emitted once per response regardless of table count.
 |
 | @param string       $id
 | @param array        $columns   [ ['key','label','sortable'=>true,'type'=>'text|num|date','align'=>'start|end','hide'=>null|'sm'|'md'|'lg'] ]
 | @param array        $rows      [ ['id'=>?,'href'=>?,'search'=>'','filters'=>[k=>v],'cells'=>[colkey=>scalar|['type'=>'badge|text|muted|strong|stack','value'=>...,'sub'=>...,'tone'=>...,'sort'=>...]],'actions'=>[['label','href'?,'disabled'?,'danger'?,'note'?]]] ]
 | @param array        $filters
 | @param bool|string  $search
 | @param string       $searchPlaceholder
 | @param string       $countNoun / $countNounPlural
 | @param string       $empty
 | @param string|null  $error
 | @param bool         $selectable   Leading checkbox column + bulk-action bar.
 | @param array        $bulkActions  [ ['label','key'?,'disabled'?,'danger'?,'note'?] ]
 | @param bool         $rowActions   Trailing three-dot actions menu column.
 | @param int          $perPage      Client-side page size (0 = no pagination).
--}}
@props([
    'id',
    'columns' => [],
    'rows' => [],
    'filters' => [],
    'search' => false,
    'searchPlaceholder' => 'Search…',
    'countNoun' => 'row',
    'countNounPlural' => null,
    'empty' => 'Nothing to show yet.',
    'error' => null,
    'selectable' => false,
    'bulkActions' => [],
    'rowActions' => false,
    'perPage' => 25,
])

@php
    $nounPlural = $countNounPlural ?? ($countNoun.'s');
    $searchEnabled = $search !== false;
    $searchValue = is_string($search) ? $search : '';

    $colClass = [];
    foreach ($columns as $i => $col) {
        $align = ($col['align'] ?? 'start') === 'end' ? 'text-right' : 'text-left';
        $hide = match ($col['hide'] ?? null) {
            'sm' => 'hidden sm:table-cell',
            'md' => 'hidden md:table-cell',
            'lg' => 'hidden lg:table-cell',
            default => '',
        };
        $colClass[$i] = trim($align.' '.$hide);
    }

    $toneClass = [
        'brand' => 'bg-teachhq-soft text-teachhq-dark',
        'neutral' => 'border border-line bg-surface text-ink-muted',
        'green' => 'bg-rag-green-soft text-rag-green',
        'amber' => 'bg-rag-amber-soft text-rag-amber',
        'red' => 'bg-rag-red-soft text-rag-red',
        'soft' => 'bg-line-soft text-ink-soft',
    ];
@endphp

<div class="dt" data-datatable data-count-noun="{{ $countNoun }}" data-count-noun-plural="{{ $nounPlural }}" data-dt-perpage="{{ (int) $perPage }}">
    @if ($error)
        <div role="alert" class="flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
            <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <p>{{ $error }}</p>
        </div>
    @else
        {{-- Toolbar. The bulk-action bar overlays this row at the same height when
             rows are selected, so selecting does not shift the page down. --}}
        <div class="relative mb-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            @if ($searchEnabled)
                <div class="relative min-w-0 flex-1 sm:max-w-xs">
                    <label for="{{ $id }}-search" class="sr-only">{{ $searchPlaceholder }}</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 -mt-2 h-icon w-icon text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input id="{{ $id }}-search" type="search" data-dt-search value="{{ $searchValue }}"
                           placeholder="{{ $searchPlaceholder }}" autocomplete="off"
                           class="w-full rounded-control border border-line bg-surface py-2 pl-9 pr-3 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-teachhq">
                </div>
            @endif

            @foreach ($filters as $filter)
                <div class="relative min-w-0">
                    <label for="{{ $id }}-f-{{ $filter['key'] }}" class="sr-only">{{ $filter['label'] }}</label>
                    <select id="{{ $id }}-f-{{ $filter['key'] }}" data-dt-filter="{{ $filter['key'] }}"
                            class="w-full appearance-none rounded-control border border-line bg-surface py-2 pl-3 pr-10 text-sm font-medium text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">
                        <option value="">{{ $filter['label'] }}: All</option>
                        @foreach ($filter['options'] as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    <svg class="select-chevron pointer-events-none absolute right-3 top-1/2 -mt-2 h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            @endforeach

            </div>
            @if ($selectable)
                <div data-dt-bulkbar class="absolute inset-0 z-10 hidden items-center justify-between gap-3 rounded-control border border-teachhq/30 bg-teachhq-soft px-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-teachhq-dark"><span data-dt-bulkcount>0</span> selected</span>
                        <button type="button" data-dt-clear class="text-mini font-semibold text-ink-soft underline-offset-2 transition hover:text-slatecard hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Clear</button>
                    </div>
                    @if (! empty($bulkActions))
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @foreach ($bulkActions as $ba)
                                @if (! empty($ba['disabled']))
                                    <button type="button" disabled
                                            class="inline-flex items-center gap-1.5 rounded-control border border-line bg-surface px-3 py-1.5 text-mini font-semibold text-ink-soft opacity-70">
                                        {{ $ba['label'] }}@isset($ba['note']) <span class="font-normal">({{ $ba['note'] }})</span>@endisset
                                    </button>
                                @else
                                    <button type="button" data-dt-bulk="{{ $ba['key'] ?? $ba['label'] }}"
                                            class="inline-flex items-center gap-1.5 rounded-control px-3 py-1.5 text-mini font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-1 {{ ! empty($ba['danger']) ? 'border border-rag-red/40 bg-surface text-rag-red hover:bg-rag-red-soft' : 'bg-button-primary text-button-primary-text hover:bg-button-primary-hover' }}">
                                        {{ $ba['label'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if (count($rows) === 0)
            <div class="rounded-panel border border-dashed border-line bg-surface p-8 text-center text-sm text-ink-soft">
                {{ $empty }}
            </div>
        @else
            <div class="overflow-x-auto rounded-panel border border-line bg-surface shadow-panel">
                <table class="w-full border-collapse text-left">
                    <caption class="sr-only">{{ $countNoun }} list</caption>
                    <thead>
                        <tr class="bg-paper">
                            @if ($selectable)
                                <th scope="col" class="w-px whitespace-nowrap px-3 py-3">
                                    <input type="checkbox" data-dt-selectall aria-label="Select all rows on this page"
                                           class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                                </th>
                            @endif
                            @foreach ($columns as $i => $col)
                                @php $sortable = $col['sortable'] ?? true; @endphp
                                <th scope="col"
                                    class="{{ $colClass[$i] }} whitespace-nowrap px-4 py-3 text-mini font-bold uppercase tracking-wide text-ink-soft"
                                    @if ($sortable) data-dt-col="{{ $col['key'] }}" data-dt-type="{{ $col['type'] ?? 'text' }}" aria-sort="none" @endif>
                                    @if ($sortable)
                                        <button type="button" data-dt-sort="{{ $col['key'] }}"
                                                class="inline-flex items-center gap-1 rounded transition hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq {{ ($col['align'] ?? 'start') === 'end' ? 'flex-row-reverse' : '' }}">
                                            <span>{{ $col['label'] }}</span>
                                            <span data-dt-ind aria-hidden="true" class="text-ink-faint"></span>
                                        </button>
                                    @else
                                        {{ $col['label'] }}
                                    @endif
                                </th>
                            @endforeach
                            @if ($rowActions)
                                <th scope="col" class="w-px px-2"><span class="sr-only">Actions</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody data-dt-body>
                        @foreach ($rows as $row)
                            @php
                                $href = $row['href'] ?? null;
                                $rowId = $row['id'] ?? $loop->index;
                                $sortAttrs = [];
                                foreach ($columns as $col) {
                                    $cell = $row['cells'][$col['key']] ?? '';
                                    $sv = is_array($cell) ? ($cell['sort'] ?? ($cell['value'] ?? '')) : $cell;
                                    $sortAttrs[$col['key']] = is_scalar($sv) ? (string) $sv : '';
                                }
                                $acts = $row['actions'] ?? [];
                            @endphp
                            <tr class="dt-row border-t border-line align-middle {{ $href ? 'cursor-pointer transition hover:bg-paper focus-within:bg-paper' : '' }}"
                                data-search="{{ \Illuminate\Support\Str::lower((string) ($row['search'] ?? '')) }}"
                                @if ($href) data-href="{{ $href }}" @endif
                                @foreach (($row['filters'] ?? []) as $fk => $fv) data-filter-{{ $fk }}="{{ $fv }}" @endforeach
                                @foreach ($sortAttrs as $sk => $sv) data-sort-{{ $sk }}="{{ $sv }}" @endforeach>
                                @if ($selectable)
                                    <td class="w-px whitespace-nowrap px-3 py-3" data-dt-nonav>
                                        <input type="checkbox" data-dt-select value="{{ $rowId }}" aria-label="Select row"
                                               class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                                    </td>
                                @endif
                                @foreach ($columns as $i => $col)
                                    @php
                                        $cell = $row['cells'][$col['key']] ?? '';
                                        $isPrimary = $loop->first;
                                        if (! is_array($cell)) {
                                            $cell = ['type' => 'text', 'value' => (string) $cell];
                                        }
                                        $type = $cell['type'] ?? 'text';
                                    @endphp
                                    <td class="{{ $colClass[$i] }} whitespace-nowrap px-4 py-3 text-sm text-slatecard">
                                        @if ($isPrimary && $href)
                                            <a href="{{ $href }}"
                                               class="group inline-flex flex-col rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                                                <span class="font-semibold text-slatecard group-hover:text-teachhq-dark">{{ $cell['value'] ?? '' }}</span>
                                                @isset($cell['sub'])
                                                    <span class="text-mini text-ink-soft">{{ $cell['sub'] }}</span>
                                                @endisset
                                            </a>
                                        @elseif ($type === 'badge')
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-micro font-bold {{ $toneClass[$cell['tone'] ?? 'neutral'] ?? $toneClass['neutral'] }}">
                                                {{ $cell['value'] ?? '' }}
                                            </span>
                                        @elseif ($type === 'stack')
                                            <span class="flex flex-col">
                                                <span class="font-medium text-slatecard">{{ $cell['value'] ?? '' }}</span>
                                                @isset($cell['sub'])
                                                    <span class="text-mini text-ink-soft">{{ $cell['sub'] }}</span>
                                                @endisset
                                            </span>
                                        @elseif ($type === 'muted')
                                            <span class="text-ink-soft">{{ $cell['value'] ?? '—' }}</span>
                                        @elseif ($type === 'strong')
                                            <span class="font-semibold tabular-nums text-slatecard">{{ $cell['value'] ?? '' }}</span>
                                        @else
                                            {{ $cell['value'] ?? '' }}
                                        @endif
                                    </td>
                                @endforeach
                                @if ($rowActions)
                                    <td class="w-px px-2 py-3 text-right" data-dt-nonav>
                                        @if (! empty($acts))
                                            <div class="relative inline-block text-left">
                                                <button type="button" data-dt-actions-toggle aria-haspopup="menu" aria-expanded="false"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-control text-ink-faint transition hover:bg-line hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                                                    <span class="sr-only">Row actions</span>
                                                    <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                                                </button>
                                                <div data-dt-actions-menu role="menu" class="fixed z-50 hidden w-48 rounded-panel border border-line bg-surface p-1 shadow-panel">
                                                    @foreach ($acts as $act)
                                                        @if (! empty($act['disabled']))
                                                            <span class="flex cursor-not-allowed items-center gap-2 rounded-lg px-2.5 py-2 text-sm text-ink-faint" aria-disabled="true">
                                                                {{ $act['label'] }}@isset($act['note']) <span class="text-micro">({{ $act['note'] }})</span>@endisset
                                                            </span>
                                                        @else
                                                            <a href="{{ $act['href'] ?? '#' }}" role="menuitem"
                                                               class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq {{ ! empty($act['danger']) ? 'text-rag-red hover:bg-rag-red-soft' : 'text-slatecard hover:bg-paper' }}">
                                                                {{ $act['label'] }}
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <nav data-dt-pager class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
                <p class="text-mini text-ink-soft" data-dt-range aria-live="polite"></p>
                <div class="flex items-center gap-1">
                    <button type="button" data-dt-prev
                            class="inline-flex h-8 items-center gap-1 rounded-control border border-line bg-surface px-3 text-mini font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq disabled:pointer-events-none disabled:opacity-40">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Prev
                    </button>
                    <span class="px-2 text-mini font-medium text-ink-soft" data-dt-pageinfo></span>
                    <button type="button" data-dt-next
                            class="inline-flex h-8 items-center gap-1 rounded-control border border-line bg-surface px-3 text-mini font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq disabled:pointer-events-none disabled:opacity-40">
                        Next
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </nav>

            <div data-dt-empty class="mt-3 hidden rounded-panel border border-dashed border-line bg-surface p-6 text-center text-sm text-ink-soft">
                No {{ $nounPlural }} match your search and filters.
            </div>
        @endif
    @endif
</div>

@once
    @push('scripts')
    <script>
    (function () {
        function initDataTable(root) {
            var body = root.querySelector('[data-dt-body]');
            if (!body) return;
            var rows = Array.prototype.slice.call(body.querySelectorAll('.dt-row'));
            var searchInput = root.querySelector('[data-dt-search]');
            var filters = Array.prototype.slice.call(root.querySelectorAll('[data-dt-filter]'));
            var emptyEl = root.querySelector('[data-dt-empty]');
            var sortButtons = Array.prototype.slice.call(root.querySelectorAll('[data-dt-sort]'));
            var pagerEl = root.querySelector('[data-dt-pager]');
            var rangeEl = root.querySelector('[data-dt-range]');
            var pageInfoEl = root.querySelector('[data-dt-pageinfo]');
            var prevBtn = root.querySelector('[data-dt-prev]');
            var nextBtn = root.querySelector('[data-dt-next]');
            var nounS = root.getAttribute('data-count-noun') || 'row';
            var nounP = root.getAttribute('data-count-noun-plural') || (nounS + 's');
            var perPage = parseInt(root.getAttribute('data-dt-perpage'), 10) || 0;
            var total = rows.length;
            var sortKey = null, sortDir = 1, page = 1;

            function typeFor(key) {
                var th = root.querySelector('[data-dt-col="' + key + '"]');
                return th ? (th.getAttribute('data-dt-type') || 'text') : 'text';
            }
            function isVisible(row) { return row && !row.classList.contains('hidden'); }

            function matches(row) {
                if (searchInput && searchInput.value.trim()) {
                    var q = searchInput.value.trim().toLowerCase();
                    if ((row.getAttribute('data-search') || '').indexOf(q) === -1) return false;
                }
                for (var i = 0; i < filters.length; i++) {
                    var v = filters[i].value;
                    if (!v) continue;
                    var key = filters[i].getAttribute('data-dt-filter');
                    if ((row.getAttribute('data-filter-' + key) || '') !== v) return false;
                }
                return true;
            }

            function apply() {
                var filtered = rows.filter(matches);
                if (sortKey) {
                    var type = typeFor(sortKey);
                    filtered.sort(function (a, b) {
                        var av = a.getAttribute('data-sort-' + sortKey) || '';
                        var bv = b.getAttribute('data-sort-' + sortKey) || '';
                        var cmp;
                        if (type === 'num') { cmp = (parseFloat(av) || 0) - (parseFloat(bv) || 0); }
                        else if (type === 'date') { cmp = (av < bv ? -1 : (av > bv ? 1 : 0)); }
                        else { cmp = av.localeCompare(bv, undefined, { sensitivity: 'base', numeric: true }); }
                        return cmp * sortDir;
                    });
                }

                var pageCount = perPage > 0 ? Math.max(1, Math.ceil(filtered.length / perPage)) : 1;
                if (page > pageCount) page = pageCount;
                if (page < 1) page = 1;
                var start = perPage > 0 ? (page - 1) * perPage : 0;
                var end = perPage > 0 ? Math.min(start + perPage, filtered.length) : filtered.length;
                var pageRows = filtered.slice(start, end);

                rows.forEach(function (r) { r.classList.add('hidden'); });
                pageRows.forEach(function (r) { r.classList.remove('hidden'); body.appendChild(r); });

                if (emptyEl) { emptyEl.classList.toggle('hidden', filtered.length !== 0); }
                if (rangeEl) { rangeEl.textContent = filtered.length ? ('Showing ' + (start + 1) + '–' + end + ' of ' + filtered.length + ' ' + (filtered.length === 1 ? nounS : nounP)) : ''; }
                if (pageInfoEl) { pageInfoEl.textContent = 'Page ' + page + ' of ' + pageCount; }
                if (prevBtn) prevBtn.disabled = page <= 1;
                if (nextBtn) nextBtn.disabled = page >= pageCount;
                if (pagerEl) pagerEl.classList.toggle('hidden', filtered.length === 0);
                syncSelectAll();
            }

            sortButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var key = btn.getAttribute('data-dt-sort');
                    if (sortKey === key) { sortDir = -sortDir; } else { sortKey = key; sortDir = 1; }
                    page = 1;
                    root.querySelectorAll('[data-dt-col]').forEach(function (th) {
                        var active = th.getAttribute('data-dt-col') === key;
                        th.setAttribute('aria-sort', active ? (sortDir === 1 ? 'ascending' : 'descending') : 'none');
                        var ind = th.querySelector('[data-dt-ind]');
                        if (ind) ind.textContent = active ? (sortDir === 1 ? '▲' : '▼') : '';
                    });
                    apply();
                });
            });

            if (searchInput) searchInput.addEventListener('input', function () { page = 1; apply(); });
            filters.forEach(function (s) { s.addEventListener('change', function () { page = 1; apply(); }); });
            if (prevBtn) prevBtn.addEventListener('click', function () { if (page > 1) { page--; apply(); } });
            if (nextBtn) nextBtn.addEventListener('click', function () { page++; apply(); });

            /* Row navigation (ignore controls and no-nav cells). */
            body.addEventListener('click', function (e) {
                if (e.target.closest('a, button, input, select, label, [data-dt-nonav]')) return;
                var row = e.target.closest('.dt-row');
                if (!row || !row.getAttribute('data-href')) return;
                if (window.getSelection && String(window.getSelection())) return;
                window.location.href = row.getAttribute('data-href');
            });

            /* Selection + bulk bar. */
            var selectAll = root.querySelector('[data-dt-selectall]');
            var bulkBar = root.querySelector('[data-dt-bulkbar]');
            var bulkCount = root.querySelector('[data-dt-bulkcount]');
            var clearBtn = root.querySelector('[data-dt-clear]');
            function rowChecks() { return Array.prototype.slice.call(body.querySelectorAll('[data-dt-select]')); }
            function checkedRows() { return rowChecks().filter(function (c) { return c.checked; }); }
            function paintRow(check) {
                var tr = check.closest('.dt-row');
                if (tr) tr.classList.toggle('bg-teachhq-soft', check.checked);
            }
            function syncBulk() {
                var n = checkedRows().length;
                if (bulkCount) bulkCount.textContent = n;
                if (bulkBar) { bulkBar.classList.toggle('hidden', n === 0); bulkBar.classList.toggle('flex', n > 0); }
            }
            function syncSelectAll() {
                if (!selectAll) return;
                var vis = rowChecks().filter(function (c) { return isVisible(c.closest('.dt-row')); });
                var on = vis.filter(function (c) { return c.checked; }).length;
                selectAll.checked = vis.length > 0 && on === vis.length;
                selectAll.indeterminate = on > 0 && on < vis.length;
            }
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    rowChecks().forEach(function (c) {
                        if (isVisible(c.closest('.dt-row'))) { c.checked = selectAll.checked; paintRow(c); }
                    });
                    syncBulk();
                });
            }
            rowChecks().forEach(function (c) {
                c.addEventListener('change', function () { paintRow(c); syncBulk(); syncSelectAll(); });
            });
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    rowChecks().forEach(function (c) { c.checked = false; paintRow(c); });
                    syncBulk(); syncSelectAll();
                });
            }

            /* Row-actions dropdown (fixed-positioned to escape the scroll clip). */
            var openMenu = null;
            function closeMenu() {
                if (openMenu) { openMenu.menu.classList.add('hidden'); openMenu.btn.setAttribute('aria-expanded', 'false'); openMenu = null; }
            }
            root.querySelectorAll('[data-dt-actions-toggle]').forEach(function (btn) {
                var menu = btn.parentNode.querySelector('[data-dt-actions-menu]');
                if (!menu) return;
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var wasOpen = openMenu && openMenu.menu === menu;
                    closeMenu();
                    if (wasOpen) return;
                    menu.classList.remove('hidden');
                    var r = btn.getBoundingClientRect();
                    var mw = menu.offsetWidth, mh = menu.offsetHeight;
                    var left = Math.max(8, r.right - mw);
                    var top = r.bottom + 4;
                    if (top + mh > window.innerHeight - 8) { top = Math.max(8, r.top - mh - 4); }
                    menu.style.left = left + 'px';
                    menu.style.top = top + 'px';
                    btn.setAttribute('aria-expanded', 'true');
                    openMenu = { menu: menu, btn: btn };
                });
            });
            document.addEventListener('click', function (e) {
                if (openMenu && !openMenu.menu.contains(e.target) && !openMenu.btn.contains(e.target)) closeMenu();
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });
            window.addEventListener('scroll', function () { closeMenu(); }, true);
            window.addEventListener('resize', function () { closeMenu(); });

            apply();
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-datatable]').forEach(initDataTable);
        });
    })();
    </script>
    @endpush
@endonce
