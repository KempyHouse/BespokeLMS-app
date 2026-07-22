@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div class="rounded-panel border border-brand-line bg-brand-surface/70 p-6 shadow-panel backdrop-blur-sm sm:p-8">
        <h1 class="text-2xl font-black tracking-tight">Sign in</h1>
        <p class="mt-2 text-sm text-brand-ink-soft">Access your {{ config('app.name') }} workspace.</p>

        @if (session('status'))
            <div class="mt-4 rounded-control border border-brand-line bg-brand-bg/50 px-4 py-3 text-sm text-brand-ink-soft" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold">Email address</label>
                <input id="email" name="email" type="email" inputmode="email" autocomplete="username"
                       value="{{ old('email') }}" required autofocus
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="mt-2 block w-full rounded-control border border-brand-line bg-brand-bg px-4 py-3 text-brand-ink placeholder:text-brand-ink-faint focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-focus">
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-rag-red" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-accent hover:underline">Forgot password?</a>
                </div>
                <div class="relative mt-2">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                           class="block w-full rounded-control border border-brand-line bg-brand-bg px-4 py-3 pr-16 text-brand-ink focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-focus">
                    <button type="button" data-toggle-password="password" aria-pressed="false" aria-label="Show password"
                            class="absolute inset-y-0 right-0 my-1 mr-1 flex items-center rounded-control px-3 text-sm font-semibold text-brand-ink-soft hover:text-brand-ink focus:outline-none focus:ring-2 focus:ring-brand-focus">
                        <span data-toggle-text>Show</span>
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="mt-2 text-sm text-rag-red" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-control bg-brand-accent px-5 py-3 font-bold text-brand-on-accent shadow-cta transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-brand-focus focus:ring-offset-2 focus:ring-offset-brand-bg">
                Sign in
            </button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-brand-ink-faint">
        Accounts are provisioned by your administrator.
    </p>
@endsection
