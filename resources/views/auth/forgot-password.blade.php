@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <div class="rounded-panel border border-brand-line bg-brand-surface/70 p-6 shadow-panel backdrop-blur-sm sm:p-8">
        <h1 class="text-2xl font-black tracking-tight">Reset your password</h1>
        <p class="mt-2 text-sm text-brand-ink-soft">Enter your email and we'll send a secure link to set a new password.</p>

        @if (session('status'))
            <div class="mt-4 rounded-control border border-brand-line bg-brand-bg/50 px-4 py-3 text-sm text-brand-ink-soft" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" novalidate>
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

            <button type="submit"
                    class="w-full rounded-control bg-brand-accent px-5 py-3 font-bold text-brand-on-accent shadow-cta transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-brand-focus focus:ring-offset-2 focus:ring-offset-brand-bg">
                Email me a reset link
            </button>
        </form>

        <p class="mt-6 text-sm text-brand-ink-soft">
            <a href="{{ route('login') }}" class="font-medium text-brand-accent hover:underline">&larr; Back to sign in</a>
        </p>
    </div>
@endsection
