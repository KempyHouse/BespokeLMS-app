<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-slatecard">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-control focus:bg-teachhq focus:px-4 focus:py-2 focus:font-semibold focus:text-white">
        Skip to content
    </a>

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2.5" aria-label="{{ config('app.name') }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teachhq text-base font-black text-white" aria-hidden="true">B</span>
                <span class="text-lg font-black text-slatecard">Bespoke<span class="text-teachhq">LMS</span></span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="rounded-control border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2">
                    Sign out
                </button>
            </form>
        </div>
    </header>

    <main id="main" class="mx-auto max-w-6xl px-5 py-8 sm:px-8">
        @yield('content')
    </main>
</body>
</html>
