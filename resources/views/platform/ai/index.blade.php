@extends('layouts.app')

@section('title', 'AI Integration · Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <!-- Left rail -->
    <aside class="w-full lg:w-rail lg:flex-none rail-sticky">
        <x-workspace-switcher active="platform" />

        <div class="rounded-control bg-paper p-6">
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Owner configuration</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">AI Integration</h1>
            <p class="mt-2 text-caption text-ink-soft">Providers are configured once here and inherited by every tenant. API keys are encrypted before they are stored and are never shown again.</p>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <x-breadcrumb :items="[
            ['label' => 'Platform', 'href' => route('platform.home')],
            ['label' => 'AI Integration'],
        ]" />

        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Platform owner</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">AI &amp; voice providers</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">Connect the AI and voice services the platform uses. Usage figures are month-to-date ({{ $monthLabel }}).</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif
        @if (session('aiError'))
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ session('aiError') }}</p>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                Some values were not valid. Please check the highlighted fields and try again.
            </div>
        @endif

        @if ($error)
            <div class="rounded-panel border border-dashed border-line bg-surface p-8 text-center text-sm text-ink-soft">{{ $error }}</div>
        @else
            @php
                $sections = [
                    ['key' => 'text',  'title' => 'Language models', 'blurb' => 'Text generation for authoring, summarising and chat. One provider can be the platform default; others stay available as alternatives.'],
                    ['key' => 'voice', 'title' => 'Voice & audio',    'blurb' => 'Text-to-speech for on-the-fly voice-over generation across course content.'],
                ];
                $statusTone = [
                    'connected' => 'green',
                    'disabled' => 'soft',
                    'error' => 'red',
                    'unconfigured' => 'neutral',
                ];
                $toneClass = [
                    'green' => 'bg-rag-green-soft text-rag-green',
                    'soft' => 'bg-line-soft text-ink-soft',
                    'red' => 'bg-rag-red-soft text-rag-red',
                    'neutral' => 'border border-line bg-surface text-ink-muted',
                ];
            @endphp

            @foreach ($sections as $section)
                @php $cards = $groups[$section['key']] ?? []; @endphp
                @if (! empty($cards))
                    <section class="mb-8">
                        <div class="mb-3">
                            <h3 class="text-lg font-black text-slatecard">{{ $section['title'] }}</h3>
                            <p class="mt-1 max-w-2xl text-caption text-ink-soft">{{ $section['blurb'] }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                            @foreach ($cards as $card)
                                @php
                                    $tone = $statusTone[$card['status']] ?? 'neutral';
                                    $statusLabel = ucfirst($card['status']);
                                    $fid = 'ai-'.$card['id'];
                                @endphp
                                <form method="POST" action="{{ route('platform.ai.update', $card['id']) }}"
                                      class="flex flex-col rounded-panel border border-line bg-surface p-5 shadow-panel">
                                    @csrf
                                    @method('PUT')

                                    {{-- Header --}}
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="text-base font-black text-slatecard">{{ $card['display_name'] }}</h4>
                                            <p class="mt-0.5 text-mini text-ink-soft">{{ $card['meta']['tagline'] }}</p>
                                        </div>
                                        <span class="inline-flex flex-none items-center rounded-full px-2.5 py-0.5 text-micro font-bold {{ $toneClass[$tone] }}">{{ $statusLabel }}</span>
                                    </div>

                                    {{-- Enable --}}
                                    <label class="mt-4 flex items-center gap-2.5 text-sm font-medium text-slatecard">
                                        <input type="checkbox" name="is_enabled" value="1" @checked($card['is_enabled'])
                                               class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                                        Enabled for the platform
                                    </label>

                                    {{-- API key (write-only) --}}
                                    <div class="mt-4">
                                        <label for="{{ $fid }}-key" class="block text-sm font-semibold text-slatecard">API key</label>
                                        <input type="password" id="{{ $fid }}-key" name="api_key" autocomplete="off"
                                               placeholder="{{ $card['has_key'] ? '•••••••• (a key is set)' : $card['meta']['key_hint'] }}"
                                               class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                        <div class="mt-1.5 flex items-center justify-between gap-2">
                                            <p class="text-micro text-ink-faint">Stored encrypted server-side. Leave blank to keep the current key.</p>
                                            @if ($card['has_key'])
                                                <label class="flex flex-none items-center gap-1.5 text-micro font-medium text-rag-red">
                                                    <input type="checkbox" name="remove_key" value="1" class="h-3.5 w-3.5 rounded border-line text-rag-red focus:ring-rag-red">
                                                    Remove
                                                </label>
                                            @endif
                                        </div>
                                        @if ($card['meta']['docs'])
                                            <a href="{{ $card['meta']['docs'] }}" target="_blank" rel="noopener noreferrer"
                                               class="mt-1 inline-flex items-center gap-1 text-micro font-semibold text-teachhq hover:text-teachhq-dark">
                                                Where do I find this?
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                                            </a>
                                        @endif
                                    </div>

                                    {{-- Default model + base URL --}}
                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="{{ $fid }}-model" class="block text-sm font-semibold text-slatecard">{{ $section['key'] === 'voice' ? 'Default voice model' : 'Default model' }}</label>
                                            <input type="text" id="{{ $fid }}-model" name="default_model" list="{{ $fid }}-models"
                                                   value="{{ old('default_model', $card['default_model']) }}" autocomplete="off"
                                                   placeholder="{{ $card['meta']['models'][0] ?? 'Model name' }}"
                                                   class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                            @if (! empty($card['meta']['models']))
                                                <datalist id="{{ $fid }}-models">
                                                    @foreach ($card['meta']['models'] as $m)
                                                        <option value="{{ $m }}"></option>
                                                    @endforeach
                                                </datalist>
                                            @endif
                                        </div>
                                        @if (! empty($card['meta']['needs_base_url']))
                                            <div>
                                                <label for="{{ $fid }}-url" class="block text-sm font-semibold text-slatecard">Endpoint / base URL</label>
                                                <input type="url" id="{{ $fid }}-url" name="base_url"
                                                       value="{{ old('base_url', $card['base_url']) }}" placeholder="https://…"
                                                       class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Usage controls --}}
                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="{{ $fid }}-tok" class="block text-sm font-semibold text-slatecard">Monthly token cap</label>
                                            <input type="number" id="{{ $fid }}-tok" name="monthly_token_limit" min="0" step="1000"
                                                   value="{{ old('monthly_token_limit', $card['monthly_token_limit']) }}" placeholder="No limit"
                                                   class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                        </div>
                                        <div>
                                            <label for="{{ $fid }}-usd" class="block text-sm font-semibold text-slatecard">Monthly budget (USD)</label>
                                            <input type="number" id="{{ $fid }}-usd" name="monthly_budget_usd" min="0" step="1"
                                                   value="{{ old('monthly_budget_usd', $card['monthly_budget_usd']) }}" placeholder="No limit"
                                                   class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                        </div>
                                    </div>

                                    {{-- Usage this month --}}
                                    <dl class="mt-4 grid grid-cols-3 gap-3 rounded-control border border-line bg-paper p-3">
                                        <div>
                                            <dt class="text-micro font-semibold uppercase tracking-wide text-ink-faint">Calls</dt>
                                            <dd class="mt-0.5 text-sm font-black tabular-nums text-slatecard">{{ number_format((int) $card['usage']['calls']) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-micro font-semibold uppercase tracking-wide text-ink-faint">Tokens in</dt>
                                            <dd class="mt-0.5 text-sm font-black tabular-nums text-slatecard">{{ number_format((int) $card['usage']['tokens_in']) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-micro font-semibold uppercase tracking-wide text-ink-faint">Tokens out</dt>
                                            <dd class="mt-0.5 text-sm font-black tabular-nums text-slatecard">{{ number_format((int) $card['usage']['tokens_out']) }}</dd>
                                        </div>
                                    </dl>

                                    <div class="mt-auto flex items-center justify-end gap-3 border-t border-line pt-4">
                                        <span class="mr-auto text-micro text-ink-faint">{{ $card['has_key'] ? 'Key on file' : 'No key yet' }}</span>
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                                            Save {{ $card['display_name'] }}
                                        </button>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        @endif
    </main>
</div>
@endsection
