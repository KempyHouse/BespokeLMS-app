@extends('layouts.app')

@section('title', $template['name'].' · System Emails · Platform')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <x-platform-nav active="outbound" />
    <x-outbound-nav active="system-emails" />

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <a href="{{ route('platform.outbound.system-emails') }}" class="inline-flex items-center gap-1 text-mini font-semibold text-teachhq hover:text-teachhq-dark">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                System emails
            </a>
            <h2 class="mt-2 text-2xl font-black text-slatecard">{{ $template['name'] }}</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">Set the subject and wording. Use the variables below as placeholders — they're filled with real values when the email is sent. The preview shows the brand-styled result.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
                Some values were not valid. Please check the highlighted fields and try again.
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Editor --}}
            <form method="POST" action="{{ route('platform.outbound.system-emails.update', ['key' => $template['key']]) }}"
                  class="flex flex-col rounded-panel border border-line bg-surface p-5 shadow-panel">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="tpl-name" class="block text-sm font-semibold text-slatecard">Template name</label>
                    <input type="text" id="tpl-name" name="name" value="{{ old('name', $template['name']) }}" maxlength="120"
                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary">
                </div>

                <div class="mb-4">
                    <label for="tpl-subject" class="block text-sm font-semibold text-slatecard">Subject</label>
                    <input type="text" id="tpl-subject" name="subject" value="{{ old('subject', $template['subject']) }}" maxlength="200"
                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary">
                </div>

                <div class="mb-4">
                    <label for="tpl-body" class="block text-sm font-semibold text-slatecard">Body (HTML)</label>
                    <textarea id="tpl-body" name="body_html" rows="14"
                              class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 font-mono text-xs leading-relaxed text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary">{{ old('body_html', $template['body_html']) }}</textarea>
                    <p class="mt-1.5 text-micro text-ink-faint">The platform wordmark, brand colour and footer are added automatically — write just the message body.</p>
                </div>

                @if (! empty($template['variables']))
                    <div class="mb-4 rounded-control border border-line bg-paper p-3">
                        <p class="text-micro font-semibold text-slatecard">Available variables</p>
                        <ul class="mt-1.5 space-y-1">
                            @foreach ((array) $template['variables'] as $v)
                                @php $ph = '{{ '.($v['key'] ?? '').' }}'; @endphp
                                <li class="text-micro text-ink-soft"><code class="rounded bg-line-soft px-1 py-0.5 font-mono text-slatecard">{{ $ph }}</code> &mdash; {{ $v['label'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-4">
                    <span class="text-micro text-ink-faint">@if (! empty($template['is_protected']))Platform original — your edits set the default every tenant inherits.@endif</span>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                        Save template
                    </button>
                </div>
            </form>

            {{-- Preview --}}
            <div class="flex flex-col rounded-panel border border-line bg-surface p-5 shadow-panel">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <h3 class="text-sm font-black text-slatecard">Preview</h3>
                    <span class="min-w-0 truncate text-micro text-ink-faint">Subject: {{ $previewSubject }}</span>
                </div>
                <iframe title="Email preview" srcdoc="{{ $previewHtml }}" class="h-[520px] w-full flex-1 rounded-control border border-line bg-white"></iframe>
                <p class="mt-2 text-micro text-ink-faint">Preview uses example values; real data is substituted at send time.</p>
            </div>
        </div>
    </main>
</div>
@endsection
