{{--
 | Tenant selector — a quick-jump control for moving between tenants' admin
 | consoles. Given the flat tenant list it renders a labelled <select> whose
 | options navigate to each tenant's configuration hub. Present on the platform
 | home (jump to configure) and on a tenant's own console (switch tenant).
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

<div class="relative">
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    <select id="{{ $id }}" data-tenant-selector
            class="w-full rounded-control border border-line bg-surface py-2 pl-3 pr-9 text-sm font-medium text-slatecard focus:outline-none focus:ring-2 focus:ring-teachhq sm:w-64">
        <option value="">{{ $label }}…</option>
        @foreach ($tenants as $t)
            <option value="{{ route('platform.tenants.show', $t['id']) }}" @selected($current === $t['id'])>
                {{ $t['name'] }} — {{ $t['type_label'] }}
            </option>
        @endforeach
    </select>
    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</div>

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-tenant-selector]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (sel.value) window.location.href = sel.value;
            });
        });
    });
    </script>
    @endpush
@endonce
