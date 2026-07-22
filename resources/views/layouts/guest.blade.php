<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    @vite($assets ?? ['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-bg text-brand-ink">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-control focus:bg-brand-accent focus:px-4 focus:py-2 focus:font-semibold focus:text-brand-on-accent">
        Skip to content
    </a>

    <div class="brand-glow" aria-hidden="true"></div>

    <div class="relative z-10 flex min-h-screen flex-col">
        <header class="mx-auto flex w-full max-w-5xl items-center px-5 py-6 sm:px-8">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-3" aria-label="{{ config('app.name') }} home">
                <span class="brand-mark" aria-hidden="true">B</span>
                <span class="text-xl font-black tracking-tight">Bespoke<span class="text-brand-accent">LMS</span></span>
            </a>
        </header>

        <main id="main" class="flex flex-1 items-center justify-center px-5 py-8 sm:px-8">
            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </main>

        <footer class="mx-auto w-full max-w-5xl px-5 py-6 text-sm text-brand-ink-faint sm:px-8">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
