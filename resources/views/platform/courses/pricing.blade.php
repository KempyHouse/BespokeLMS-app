@extends('layouts.app')

@section('title', 'Pricing · Global Courses')

@php
    $c = $course;
    $p = $pricing ?? [];

    // Derive the form's current values, preferring old() on validation bounce.
    $type = old('pricing_type', $p['pricing_type'] ?? 'free');

    $pricePennies = $p['price_pennies'] ?? null;
    $priceMajor = old('price', $pricePennies !== null ? number_format($pricePennies / 100, 2, '.', '') : '');

    $currency = old('currency', $p['currency'] ?? 'GBP');
    $creditCost = old('credit_cost', $p['credit_cost'] ?? '');
    $inSub = (bool) old('included_in_subscription', $p['included_in_subscription'] ?? false);

    // Retry (attempts to pass): null = inherit, -1 = unlimited, N = limited.
    $retryRaw = $p['assessment_retry_limit'] ?? null;
    $retryMode = old('retry_mode', $retryRaw === null ? 'inherit' : ((int) $retryRaw === -1 ? 'unlimited' : 'limited'));
    $retryLimit = old('retry_limit', ($retryRaw !== null && (int) $retryRaw >= 0) ? (int) $retryRaw : '');

    // Retake after pass: null = inherit, else the enum value.
    $retakeRaw = $p['retake_after_pass'] ?? null;
    $retakeMode = old('retake_mode', $retakeRaw === null ? 'inherit' : (string) $retakeRaw);
    $retakeLimit = old('retake_limit', $p['retake_limit'] ?? '');

    // Access revoked on pass (PAYG): null = inherit, true = yes, false = no.
    $revokeRaw = $p['access_revoked_on_pass'] ?? null;
    $revokeMode = old('revoke_mode', $revokeRaw === null ? 'inherit' : ($revokeRaw ? 'yes' : 'no'));

    $fieldCls = 'mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary';
    $labelCls = 'block text-sm font-semibold text-slatecard';

    // Human labels for the effective-policy preview.
    $limitLabel = function ($n) {
        if ($n === null) return '—';
        return ((int) $n === -1) ? 'Unlimited' : (string) (int) $n;
    };
    $retakeLabel = fn ($v) => $v === null ? '—' : ucfirst((string) $v);
@endphp

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-20 lg:self-start">
        <x-workspace-switcher active="platform" />
        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.courses.show', $c['id']) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Back to course
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Course editor</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Pricing &amp; retakes</h1>
            <p class="mt-2 text-caption text-ink-soft">How the course is charged, and how many attempts and retakes a learner gets. Leave a retake control on <em>Inherit</em> to follow the platform default for the chosen mechanism.</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">{{ $c['title'] ?? 'Course' }} — pricing</h2>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">{{ session('status') }}</div>
        @endif
        @if (session('editorError'))
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">{{ session('editorError') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">Some values were not valid — check the highlighted fields.</div>
        @endif

        {{-- Effective policy preview --}}
        @if (!empty($effective))
            <section class="mb-6 rounded-panel border border-line bg-paper p-5">
                <h3 class="text-sm font-black uppercase tracking-wide text-ink-soft">Effective policy right now</h3>
                <p class="mt-1 text-caption text-ink-soft">What a learner actually gets — course overrides resolved against the platform defaults.</p>
                <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                    <div><dt class="text-micro uppercase tracking-wide text-ink-faint">Attempts to pass</dt><dd class="font-semibold text-slatecard">{{ $limitLabel($effective['assessment_retry_limit'] ?? null) }}</dd></div>
                    <div><dt class="text-micro uppercase tracking-wide text-ink-faint">Retakes after pass</dt><dd class="font-semibold text-slatecard">{{ $retakeLabel($effective['retake_after_pass'] ?? null) }}</dd></div>
                    <div><dt class="text-micro uppercase tracking-wide text-ink-faint">Retake limit</dt><dd class="font-semibold text-slatecard">{{ $limitLabel($effective['retake_limit'] ?? null) }}</dd></div>
                    <div><dt class="text-micro uppercase tracking-wide text-ink-faint">Access after pass</dt><dd class="font-semibold text-slatecard">{{ ($effective['access_revoked_on_pass'] ?? false) ? 'Revoked' : 'Retained' }}</dd></div>
                </dl>
            </section>
        @endif

        <form method="POST" action="{{ route('platform.courses.pricing.update', $c['id']) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Mechanism --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Mechanism</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="pricing_type" class="{{ $labelCls }}">Pricing mechanism</label>
                        <select id="pricing_type" name="pricing_type" class="{{ $fieldCls }}">
                            @foreach ($typeOptions as $opt)
                                <option value="{{ $opt['value'] }}" @selected($type === $opt['value'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="currency" class="{{ $labelCls }}">Currency</label>
                        <input id="currency" name="currency" type="text" maxlength="3" value="{{ $currency }}" class="{{ $fieldCls }} uppercase" placeholder="GBP">
                    </div>
                    <div>
                        <label for="price" class="{{ $labelCls }}">Price</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" value="{{ $priceMajor }}" class="{{ $fieldCls }}" placeholder="e.g. 49.00">
                        <p class="mt-1 text-micro text-ink-faint">For one-off and pay-as-you-go. In major units (e.g. pounds).</p>
                    </div>
                    <div>
                        <label for="credit_cost" class="{{ $labelCls }}">Credit cost</label>
                        <input id="credit_cost" name="credit_cost" type="number" min="0" value="{{ $creditCost }}" class="{{ $fieldCls }}" placeholder="e.g. 3">
                        <p class="mt-1 text-micro text-ink-faint">For the credits mechanism.</p>
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-2.5">
                        <input id="included_in_subscription" name="included_in_subscription" type="checkbox" value="1" @checked($inSub) class="h-4 w-4 rounded border-line text-teachhq focus:ring-teachhq">
                        <label for="included_in_subscription" class="text-sm font-medium text-slatecard">Also available to subscribers at no extra charge</label>
                    </div>
                </div>
            </section>

            {{-- Attempts & retakes --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Attempts &amp; retakes</h3>
                <p class="mt-1 text-caption text-ink-soft">Override the platform default for this course, or leave on <em>Inherit</em> to follow it.</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="retry_mode" class="{{ $labelCls }}">Attempts to pass the assessment</label>
                        <select id="retry_mode" name="retry_mode" class="{{ $fieldCls }}">
                            <option value="inherit" @selected($retryMode === 'inherit')>Inherit platform default</option>
                            <option value="unlimited" @selected($retryMode === 'unlimited')>Unlimited</option>
                            <option value="limited" @selected($retryMode === 'limited')>Limited to…</option>
                        </select>
                    </div>
                    <div>
                        <label for="retry_limit" class="{{ $labelCls }}">Attempt limit</label>
                        <input id="retry_limit" name="retry_limit" type="number" min="0" value="{{ $retryLimit }}" class="{{ $fieldCls }}" placeholder="used when limited">
                    </div>
                    <div>
                        <label for="retake_mode" class="{{ $labelCls }}">Retakes after passing</label>
                        <select id="retake_mode" name="retake_mode" class="{{ $fieldCls }}">
                            <option value="inherit" @selected($retakeMode === 'inherit')>Inherit platform default</option>
                            <option value="none" @selected($retakeMode === 'none')>Not allowed</option>
                            <option value="unlimited" @selected($retakeMode === 'unlimited')>Unlimited</option>
                            <option value="limited" @selected($retakeMode === 'limited')>Limited to…</option>
                        </select>
                    </div>
                    <div>
                        <label for="retake_limit" class="{{ $labelCls }}">Retake limit</label>
                        <input id="retake_limit" name="retake_limit" type="number" min="0" value="{{ $retakeLimit }}" class="{{ $fieldCls }}" placeholder="used when limited">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="revoke_mode" class="{{ $labelCls }}">Access once the learner has passed</label>
                        <select id="revoke_mode" name="revoke_mode" class="{{ $fieldCls }}">
                            <option value="inherit" @selected($revokeMode === 'inherit')>Inherit platform default</option>
                            <option value="no" @selected($revokeMode === 'no')>Keep access to the course</option>
                            <option value="yes" @selected($revokeMode === 'yes')>Close the course (pay-as-you-go)</option>
                        </select>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('platform.courses.show', $c['id']) }}" class="rounded-control px-4 py-2 text-sm font-semibold text-ink-soft transition hover:text-slatecard">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-5 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">Save pricing</button>
            </div>
        </form>
    </main>
</div>
@endsection
