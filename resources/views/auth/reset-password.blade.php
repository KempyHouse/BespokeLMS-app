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

        {{--
            This form is completed entirely client-side (supabase-js updateUser),
            so it has no method/action and the password fields carry NO name
            attribute — the browser must never serialise the password into the URL.
            resources/js/reset-password.js reads the fields by id and always calls
            event.preventDefault().
        --}}
        <form id="reset-form" class="mt-6 space-y-5" novalidate>
            <div>
                <div class="flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold">New password</label>
                    <button type="button" id="generate-password"
                            class="rounded-control px-2 py-1 text-sm font-medium text-brand-accent hover:text-brand-accent-strong hover:underline focus:outline-none focus:ring-2 focus:ring-brand-focus">
                        Generate strong password
                    </button>
                </div>

                <div class="relative mt-2">
                    <input id="password" type="password" autocomplete="new-password" minlength="8" required
                           aria-describedby="password-hint"
                           class="block w-full rounded-control border border-brand-line bg-brand-bg px-4 py-3 pr-16 text-brand-ink focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-focus">
                    <button type="button" data-toggle-password="password" aria-pressed="false" aria-label="Show password"
                            class="absolute inset-y-0 right-0 my-1 mr-1 flex items-center rounded-control px-3 text-sm font-semibold text-brand-ink-soft hover:text-brand-ink focus:outline-none focus:ring-2 focus:ring-brand-focus">
                        <span data-toggle-text>Show</span>
                    </button>
                </div>

                {{-- Password strength indicator — populated by reset-password.js. --}}
                <div id="password-strength" class="mt-3" hidden>
                    <div class="flex gap-1" role="progressbar" aria-label="Password strength"
                         aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-valuetext="Too short">
                        <span data-strength-bar class="h-1.5 flex-1 rounded-control bg-brand-line transition-colors"></span>
                        <span data-strength-bar class="h-1.5 flex-1 rounded-control bg-brand-line transition-colors"></span>
                        <span data-strength-bar class="h-1.5 flex-1 rounded-control bg-brand-line transition-colors"></span>
                        <span data-strength-bar class="h-1.5 flex-1 rounded-control bg-brand-line transition-colors"></span>
                    </div>
                    <p data-strength-label class="mt-1 text-xs text-brand-ink-faint" aria-hidden="true"></p>
                </div>

                <p id="password-hint" class="mt-2 text-xs text-brand-ink-faint">Use at least 8 characters, or generate one above.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold">Confirm new password</label>
                <input id="password_confirmation" type="password" autocomplete="new-password" minlength="8" required
                       aria-describedby="password-match"
                       class="mt-2 block w-full rounded-control border border-brand-line bg-brand-bg px-4 py-3 text-brand-ink focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-focus">
                <p id="password-match" role="status" hidden class="mt-2 text-xs text-brand-ink-faint"></p>
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
