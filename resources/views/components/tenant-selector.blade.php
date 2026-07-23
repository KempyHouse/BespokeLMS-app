{{--
 | Tenant selector — a quick-jump control for moving between tenants' admin
 | consoles. Rendered as a fully-themed <details> dropdown (a native <select>
 | cannot style its opened option list, which falls back to OS chrome). Each
 | option is a real link to a tenant's configuration hub, so it works without
 | JS; a little script just closes it on outside-click / Escape.
 |
 | The table on the platform home is the no-JS accessible path to every tenant;
 | this control is a keyboard-operable enhancement on top.
 |
 | @param array   $tenants  Flat tenant list (each with id, name, type_label).
 | @param ?string $current  The id of the tenant currently open, if any.
 | @param string  $label    Placeholder / accessible label.
 | @param string  $id       Element id.
--}}
@props([
    'tenants' => [],
    'current' => null,
    'label' => 'Select a tenant',
    'id' => 'tenant-selector',
])

@php
    $currentLabel = null;
    foreach ($tenants as $t) {
        if ($current !== null && $current === ($t['id'] ?? null)) {
            $currentLabel = $t['name'].' — '.$t['type_label'];
        }
    }
@endphp

<details class="group relative w-full sm:w-64" data-dropdown data-tenant-selector>
    <summary id="{{ $id }}" aria-label="{{ $label }}"
             class="flex cursor-pointer items-center justify-between gap-2 rounded-control border border-line bg-surface py-2 pl-3 pr-3 text-sm font-medium text-slatecard transition hover:border-ink-faint focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
        <span class="min-w-0 flex-1 truncate {{ $currentLabel ? 'text-slatecard' : 'text-ink-faint' }}">{{ $currentLabel ?? ($label.'…') }}</span>
        <svg class="select-chevron h-4 w-4 flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </summary>

    <div class="absolute right-0 z-40 mt-1 max-h-80 w-full overflow-y-auto rounded-panel border border-line bg-surface p-1 shadow-panel sm:w-72" role="menu">
        @forelse ($tenants as $t)
            <a href="{{ route('platform.tenants.show', $t['id']) }}" role="menuitem"
               class="flex items-center justify-between gap-2 rounded-control px-2.5 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq {{ $current === ($t['id'] ?? null) ? 'bg-teachhq-soft font-semibold text-teachhq-dark' : 'text-slatecard hover:bg-paper' }}">
                <span class="min-w-0 flex-1 truncate">{{ $t['name'] }}</span>
                <span class="flex-none text-mini text-ink-soft">{{ $t['type_label'] }}</span>
            </a>
        @empty
            <p class="px-2.5 py-2 text-sm text-ink-soft">No tenants available.</p>
        @endforelse
    </div>
</details>

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var dropdowns = Array.prototype.slice.call(document.querySelectorAll('details[data-tenant-selector]'));
        if (!dropdowns.length) return;

        // Close on outside click, and close others when one opens.
        document.addEventListener('click', function (e) {
            dropdowns.forEach(function (d) {
                if (d.open && !d.contains(e.target)) d.removeAttribute('open');
            });
        });
        dropdowns.forEach(function (d) {
            d.addEventListener('toggle', function () {
                if (!d.open) return;
                dropdowns.forEach(function (o) { if (o !== d) o.removeAttribute('open'); });
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') dropdowns.forEach(function (d) { d.removeAttribute('open'); });
        });
    });
    </script>
    @endpush
@endonce
