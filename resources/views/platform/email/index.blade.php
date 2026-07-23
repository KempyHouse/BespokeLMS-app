@extends('layouts.app')

@section('title', 'Email Integration · Platform')

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
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Owner configuration</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Email Integration</h1>
            <p class="mt-2 text-caption text-ink-soft">The email transport is configured once here and inherited by every tenant. The enabled provider is the platform default; secrets are encrypted before they are stored and are never shown again.</p>
        </div>
    </aside>

    <!-- Main content -->
    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Platform owner</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">Email delivery</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">Connect the service that sends the platform's email. Enable one provider as the default transport; the others stay available so you can <span class="text-teachhq">switch provider without code changes</span>. Tenants send on this transport as their own sender identity, set per tenant.</p>
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-3 rounded-panel border border-line bg-surface p-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slatecard">Send a test</p>
                <p class="text-caption text-ink-soft">Sends a test message to your own address ({{ $user->email }}) on the enabled transport.</p>
            </div>
            <form method="POST" action="{{ route('platform.email.test') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-control border border-line bg-paper px-4 py-2 text-sm font-semibold text-slatecard transition hover:text-teachhq focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                    Send test email
                </button>
            </form>
        </div>
        <p class="mb-6 -mt-3 text-micro text-ink-faint">Saving a provider or sending a test asks you to confirm your password first.</p>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif
        @if (session('emailError'))
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ session('emailError') }}</p>
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

            <section class="mb-8">
                <div class="mb-3">
                    <h3 class="text-lg font-black text-slatecard">Transport providers</h3>
                    <p class="mt-1 max-w-2xl text-caption text-ink-soft">One provider is the platform default; the others stay available as alternatives. Enable a different card and add its key to switch provider.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    @foreach ($cards as $card)
                        @php
                            $tone = $statusTone[$card['status']] ?? 'neutral';
                            $statusLabel = ucfirst($card['status']);
                            $fid = 'em-'.$card['id'];
                        @endphp
                        <form method="POST" action="{{ route('platform.email.update', $card['id']) }}"
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
                                Use as the platform transport
                            </label>

                            {{-- Secret (write-only) --}}
                            <div class="mt-4">
                                <label for="{{ $fid }}-key" class="block text-sm font-semibold text-slatecard">{{ $card['meta']['secret_label'] }}</label>
                                <input type="password" id="{{ $fid }}-key" name="api_key" autocomplete="off"
                                       placeholder="{{ $card['has_key'] ? '•••••••• (a secret is set)' : $card['meta']['key_hint'] }}"
                                       class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                <div class="mt-1.5 flex items-center justify-between gap-2">
                                    <p class="text-micro text-ink-faint">Stored encrypted server-side. Leave blank to keep the current secret.</p>
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

                            {{-- Default sender identity --}}
                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="{{ $fid }}-fname" class="block text-sm font-semibold text-slatecard">Default "from" name</label>
                                    <input type="text" id="{{ $fid }}-fname" name="from_name"
                                           value="{{ old('from_name', $card['from_name']) }}" autocomplete="off" placeholder="BespokeLMS"
                                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                </div>
                                <div>
                                    <label for="{{ $fid }}-faddr" class="block text-sm font-semibold text-slatecard">Default "from" address</label>
                                    <input type="email" id="{{ $fid }}-faddr" name="from_address"
                                           value="{{ old('from_address', $card['from_address']) }}" autocomplete="off" placeholder="no-reply@bespokelms.com"
                                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="{{ $fid }}-reply" class="block text-sm font-semibold text-slatecard">Reply-to <span class="font-normal text-ink-faint">(optional)</span></label>
                                    <input type="email" id="{{ $fid }}-reply" name="reply_to"
                                           value="{{ old('reply_to', $card['reply_to']) }}" autocomplete="off" placeholder="support@bespokelms.com"
                                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                </div>
                                <div>
                                    <label for="{{ $fid }}-domain" class="block text-sm font-semibold text-slatecard">Sending domain <span class="font-normal text-ink-faint">(optional)</span></label>
                                    <input type="text" id="{{ $fid }}-domain" name="sending_domain"
                                           value="{{ old('sending_domain', $card['sending_domain']) }}" autocomplete="off" placeholder="mail.bespokelms.com"
                                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                </div>
                            </div>

                            {{-- Provider-specific connection --}}
                            @if (! empty($card['meta']['needs_host']))
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <label for="{{ $fid }}-host" class="block text-sm font-semibold text-slatecard">Host / endpoint</label>
                                        <input type="text" id="{{ $fid }}-host" name="base_url"
                                               value="{{ old('base_url', $card['base_url']) }}" placeholder="smtp.example.com"
                                               class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                    </div>
                                    <div>
                                        <label for="{{ $fid }}-port" class="block text-sm font-semibold text-slatecard">Port</label>
                                        <input type="number" id="{{ $fid }}-port" name="smtp_port" min="1" max="65535"
                                               value="{{ old('smtp_port', $card['smtp_port']) }}" placeholder="587"
                                               class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="{{ $fid }}-user" class="block text-sm font-semibold text-slatecard">Username <span class="font-normal text-ink-faint">(optional)</span></label>
                                        <input type="text" id="{{ $fid }}-user" name="smtp_username" autocomplete="off"
                                               value="{{ old('smtp_username', $card['smtp_username']) }}" placeholder="apikey"
                                               class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                    </div>
                                </div>
                            @elseif (! empty($card['meta']['needs_region']))
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="{{ $fid }}-akid" class="block text-sm font-semibold text-slatecard">Access key ID</label>
                                        <input type="text" id="{{ $fid }}-akid" name="smtp_username" autocomplete="off"
                                               value="{{ old('smtp_username', $card['smtp_username']) }}" placeholder="AKIA…"
                                               class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                    </div>
                                    <div>
                                        <label for="{{ $fid }}-region" class="block text-sm font-semibold text-slatecard">AWS region</label>
                                        <input type="text" id="{{ $fid }}-region" name="region" autocomplete="off"
                                               value="{{ old('region', $card['region']) }}" placeholder="eu-west-1"
                                               class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                                    </div>
                                    <p class="sm:col-span-2 text-micro text-ink-faint">The access key ID is public; the secret access key goes in the field above and is stored encrypted.</p>
                                </div>
                            @endif

                            <div class="mt-4 flex items-center justify-end gap-3 border-t border-line pt-4">
                                <span class="mr-auto text-micro text-ink-faint">{{ $card['has_key'] ? 'Secret on file' : 'No secret yet' }}</span>
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                                    Save {{ $card['display_name'] }}
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>

            <div class="rounded-panel border border-dashed border-line bg-surface p-5 text-sm text-ink-soft">
                <p class="font-semibold text-slatecard">Per-tenant sender identities</p>
                <p class="mt-1 max-w-2xl">Tenants send on the platform transport above, but as their own "from" name, address and verified domain. Those aliases are managed on each tenant's admin console rather than here, so a tenant can only ever change its own identity.</p>
            </div>
        @endif
    </main>
</div>
@endsection
