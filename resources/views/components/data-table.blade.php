{{--
 | Reusable data table — the single, consistent table UI/UX for the whole
 | platform. Fully data-driven: pass :columns and :rows and it renders the
 | toolbar (search + filter selects + live count), a responsive sortable table,
 | clickable rows, and empty / error states. Styling is 100% design-token
 | classes (no hardcoded values), so tenant brand kits reskin it automatically.
 |
 | Behaviour (search, filter, column sort, row navigation) is progressive
 | enhancement: the table is fully readable and each row is reachable via its
 | primary-cell link without JavaScript. The behaviour script is emitted once
 | per response no matter how many tables a page contains.
 |
 | @param string       $id       Unique instance id.
 | @param array        $columns  [ ['key','label','sortable'=>true,'type'=>'text|num|date','align'=>'start|end','hide'=>null|'sm'|'md'|'lg'] ]
 | @param array        $rows     [ ['href'=>?,'search'=>'','filters'=>['k'=>'v'],'cells'=>['colkey'=>scalar|['type'=>'badge|text|muted|strong|stack','value'=>...,'sub'=>...,'tone'=>...,'sort'=>...]]] ]
 | @param array        $filters  Toolbar selects: [ ['key','label','options'=>[['value','label']]] ]
 | @param bool|string  $search   Enable search; a string sets the initial query.
 | @param string       $searchPlaceholder
 | @param string       $countNoun / $countNounPlural
 | @param string       $empty    Text when there are no rows at all.
 | @param string|null  $error    When set, an error panel is shown instead of the table.
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
])

@php
    $nounPlural = $countNounPlural ?? ($countNoun.'s');
    $searchEnabled = $search !== false;
    $searchValue = is_string($search) ? $search : '';

    // Pre-compute shared column classes so <th> and <td> always match.
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

<div class="dt" data-datatable data-count-noun="{{ $countNoun }}" data-count-noun-plural="{{ $nounPlural }}">
    @if ($error)
        <div role="alert"
             class="flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
            <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <p>{{ $error }}</p>
        </div>
    @else
        {{-- Toolbar --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            @if ($searchEnabled)
                <div class="relative min-w-0 flex-1 sm:max-w-xs">
                    <label for="{{ $id }}-search" class="sr-only">{{ $searchPlaceholder }}</label>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-icon w-icon -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input id="{{ $id }}-search" type="search" data-dt-search value="{{ $searchValue }}"
                           placeholder="{{ $searchPlaceholder }}" autocomplete="off"
                           class="w-full rounded-control border border-line bg-surface py-2 pl-9 pr-3 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-teachhq">
                </div>
            @endif

            @foreach ($filters as $filter)
                <div class="min-w-0">
                    <label for="{{ $id }}-f-{{ $filter['key'] }}" class="sr-only">{{ $filter['label'] }}</label>
                    <select id="{{ $id }}-f-{{ $filter['key'] }}" data-dt-filter="{{ $filter['key'] }}"
                            class="w-full rounded-control border border-line bg-surface py-2 pl-3 pr-8 text-sm font-medium text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq">
                        <option value="">{{ $filter['label'] }}: All</option>
                        @foreach ($filter['options'] as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <p class="text-mini text-ink-soft sm:ml-auto" data-dt-count aria-live="polite">
                {{ count($rows) }} {{ count($rows) === 1 ? $countNoun : $nounPlural }}
            </p>
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
                            @foreach ($columns as $i => $col)
                                @php $sortable = $col['sortable'] ?? true; @endphp
                                <th scope="col"
                                    class="{{ $colClass[$i] }} px-4 py-3 text-mini font-bold uppercase tracking-wide text-ink-soft"
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
                        </tr>
                    </thead>
                    <tbody data-dt-body>
                        @foreach ($rows as $row)
                            @php
                                $href = $row['href'] ?? null;
                                $sortAttrs = [];
                                foreach ($columns as $col) {
                                    $cell = $row['cells'][$col['key']] ?? '';
                                    if (is_array($cell)) {
                                        $sv = $cell['sort'] ?? ($cell['value'] ?? '');
                                    } else {
                                        $sv = $cell;
                                    }
                                    $sortAttrs[$col['key']] = is_scalar($sv) ? (string) $sv : '';
                                }
                            @endphp
                            <tr class="dt-row border-t border-line align-middle {{ $href ? 'cursor-pointer transition hover:bg-paper focus-within:bg-paper' : '' }}"
                                data-search="{{ \Illuminate\Support\Str::lower((string) ($row['search'] ?? '')) }}"
                                @if ($href) data-href="{{ $href }}" @endif
                                @foreach (($row['filters'] ?? []) as $fk => $fv) data-filter-{{ $fk }}="{{ $fv }}" @endforeach
                                @foreach ($sortAttrs as $sk => $sv) data-sort-{{ $sk }}="{{ $sv }}" @endforeach>
                                @foreach ($columns as $i => $col)
                                    @php
                                        $cell = $row['cells'][$col['key']] ?? '';
                                        $isPrimary = $loop->first;
                                        if (! is_array($cell)) {
                                            $cell = ['type' => 'text', 'value' => (string) $cell];
                                        }
                                        $type = $cell['type'] ?? 'text';
                                    @endphp
                                    <td class="{{ $colClass[$i] }} px-4 py-3 text-sm text-slatecard">
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

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
            var countEl = root.querySelector('[data-dt-count]');
            var emptyEl = root.querySelector('[data-dt-empty]');
            var sortButtons = Array.prototype.slice.call(root.querySelectorAll('[data-dt-sort]'));
            var nounS = root.getAttribute('data-count-noun') || 'row';
            var nounP = root.getAttribute('data-count-noun-plural') || (nounS + 's');
            var total = rows.length;
            var sortKey = null, sortDir = 1;

            function typeFor(key) {
                var th = root.querySelector('[data-dt-col="' + key + '"]');
                return th ? (th.getAttribute('data-dt-type') || 'text') : 'text';
            }

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
                var visible = rows.filter(matches);
                if (sortKey) {
                    var type = typeFor(sortKey);
                    visible.sort(function (a, b) {
                        var av = a.getAttribute('data-sort-' + sortKey) || '';
                        var bv = b.getAttribute('data-sort-' + sortKey) || '';
                        var cmp;
                        if (type === 'num') {
                            cmp = (parseFloat(av) || 0) - (parseFloat(bv) || 0);
                        } else if (type === 'date') {
                            cmp = (av < bv ? -1 : (av > bv ? 1 : 0));
                        } else {
                            cmp = av.localeCompare(bv, undefined, { sensitivity: 'base', numeric: true });
                        }
                        return cmp * sortDir;
                    });
                }
                rows.forEach(function (r) { r.classList.add('hidden'); });
                visible.forEach(function (r) { r.classList.remove('hidden'); body.appendChild(r); });
                if (countEl) {
                    countEl.textContent = 'Showing ' + visible.length + ' of ' + total + ' ' + (total === 1 ? nounS : nounP);
                }
                if (emptyEl) { emptyEl.classList.toggle('hidden', visible.length !== 0); }
            }

            sortButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var key = btn.getAttribute('data-dt-sort');
                    if (sortKey === key) { sortDir = -sortDir; } else { sortKey = key; sortDir = 1; }
                    root.querySelectorAll('[data-dt-col]').forEach(function (th) {
                        var active = th.getAttribute('data-dt-col') === key;
                        th.setAttribute('aria-sort', active ? (sortDir === 1 ? 'ascending' : 'descending') : 'none');
                        var ind = th.querySelector('[data-dt-ind]');
                        if (ind) ind.textContent = active ? (sortDir === 1 ? '▲' : '▼') : '';
                    });
                    apply();
                });
            });

            if (searchInput) searchInput.addEventListener('input', apply);
            filters.forEach(function (s) { s.addEventListener('change', apply); });

            body.addEventListener('click', function (e) {
                if (e.target.closest('a, button, input, select, label')) return;
                var row = e.target.closest('.dt-row');
                if (!row || !row.getAttribute('data-href')) return;
                if (window.getSelection && String(window.getSelection())) return;
                window.location.href = row.getAttribute('data-href');
            });

            apply();
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-datatable]').forEach(initDataTable);
        });
    })();
    </script>
    @endpush
@endonce
