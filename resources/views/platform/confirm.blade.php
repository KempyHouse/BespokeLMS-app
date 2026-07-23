@extends('layouts.app')

@section('title', 'Confirm your password · Platform')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-panel border border-line bg-surface p-6 shadow-panel">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-control bg-paper text-teachhq">
                <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <div class="min-w-0">
                <h1 class="text-lg font-black text-slatecard">Confirm it's you</h1>
                <p class="mt-1 text-caption text-ink-soft">This is a secure area. Please re-enter your password to change provider keys, switch a provider, or update branding. You won't be asked again for a little while.</p>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-3 text-sm text-rag-red">
                {{ $errors->first('password') }}
            </div>
        @endif

        <form method="POST" action="{{ route('platform.confirm.store') }}" class="mt-5">
            @csrf
            <label for="confirm-password" class="block text-sm font-semibold text-slatecard">Password</label>
            <input type="password" id="confirm-password" name="password" autocomplete="current-password" autofocus required
                   class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">

            <div class="mt-5 flex items-center justify-end gap-3">
                <a href="{{ route('platform.home') }}"
                   class="inline-flex items-center rounded-control px-4 py-2 text-sm font-semibold text-ink-soft transition hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
