@php($assets = ['resources/css/app.css', 'resources/js/app.js', 'resources/js/reset-password.js'])

@extends('layouts.guest')

@section('title', 'Set a new password')

@section('content')
    <div class="rounded-panel border border-brand-line bg-brand-surface/70 p-6 shadow-panel backdrop-blur-sm sm:p-8">
        <h1 class="text-2xl font-black tracking-tight">Set a new password</h1>
        <p class="mt-2 text-sm text-brand-ink-soft">Choose a strong password for your {{ config('app.name') }} account.</p>

        {{-- Public config for supabase-js (project URL + publishable key are safe to expose). --}}
        <div id="reset-config"
             data-url="{{ config('services.supabase.url') }}"
             data-key="{{ config('services.supabase.anon_key') }}"
             data-login="{{ route('login') }}"></div>

        <p id="reset-status" role="status" hidden
           class="mt-4 rounded-control border border-brand-line bg-brand-bg/50 px-4 py-3 text-sm text-brand-ink-soft"></p>

        <form id="reset-form" class="mt-6 space-y-5" novalidate>
            <div>
                <label for="password" class="block text-sm font-semibold">New password</label>
                <div class="relative mt-2">
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required
                           class="block w-full rounded-control border border-brand-line bg-brand-bg px-4 py-3 pr-16 text-brand-ink focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-focus">
                    <button type="button" data-toggle-password="password" aria-pressed="false" aria-label="Show password"
                            class="absolute inset-y-0 right-0 my-1 mr-1 flex items-center rounded-control px-3 text-sm font-semibold text-brand-ink-soft hover:text-brand-ink focus:outline-none focus:ring-2 focus:ring-brand-focus">
                        <span data-toggle-text>Show</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-brand-ink-faint">Use at least 8 characters.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required
                       class="mt-2 block w-full rounded-control border border-brand-line bg-brand-bg px-4 py-3 text-brand-ink focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-focus">
            </div>

            <button id="reset-submit" type="submit"
                    class="w-full rounded-control bg-brand-accent px-5 py-3 font-bold text-brand-on-accent shadow-cta transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-brand-focus focus:ring-offset-2 focus:ring-offset-brand-bg">
                Update password
            </button>
        </form>

        <p class="mt-6 text-sm text-brand-ink-soft">
            <a href="{{ route('login') }}" class="font-medium text-brand-accent hover:underline">&larr; Back to sign in</a>
        </p>
    </div>
@endsection
