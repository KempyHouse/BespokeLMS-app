{{--
 | Delta chip(s) — the "▲ 4 vs last week" comparison badge.
 |
 | Renders one chip per available comparison period; only the selected one is
 | visible. Switching the period (on the dashboard) just toggles which chip
 | shows, so no figure is ever recomputed on the client. Tone is decided here
 | from the sign and whether an increase is good for this metric.
 |
 | @param array        $deltas    map period-key => int (e.g. ['wow'=>-2,'yoy'=>17])
 | @param array        $options   [ ['key'=>'wow','label'=>'vs last week'], ... ]
 | @param string|null  $selected  currently-visible period key
 | @param string       $goodWhen  'up' | 'down' — is an increase good?
 | @param string       $suffix    appended to the number, e.g. '%'
--}}
@props([
    'deltas' => [],
    'options' => [],
    'selected' => null,
    'goodWhen' => 'up',
    'suffix' => '',
])

@php
    $selected = $selected ?? ($options[0]['key'] ?? null);

    $toneClass = static function (int $v, string $goodWhen): string {
        if ($v === 0) {
            return 'bg-line-soft text-ink-soft';
        }
        $good = $goodWhen === 'up' ? $v > 0 : $v < 0;

        return $good ? 'bg-rag-green-soft text-rag-green' : 'bg-rag-red-soft text-rag-red';
    };
@endphp

<span class="inline-flex" data-widget-delta>
    @foreach ($options as $opt)
        @php
            $v = (int) ($deltas[$opt['key']] ?? 0);
            $arrow = $v > 0 ? '↑' : ($v < 0 ? '↓' : '→');
            $isSel = $opt['key'] === $selected;
        @endphp
        <span data-delta-period="{{ $opt['key'] }}"
              class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-nano font-bold tabular-nums {{ $toneClass($v, $goodWhen) }} {{ $isSel ? '' : 'hidden' }}">
            <span aria-hidden="true">{{ $arrow }}</span>{{ abs($v) }}{{ $suffix }}
            <span class="font-semibold opacity-80">{{ $opt['label'] }}</span>
        </span>
    @endforeach
</span>
