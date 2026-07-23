<?php

use App\Http\Middleware\EnsurePlatformOwner;
use App\Http\Middleware\RequireRecentReauth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Unauthenticated visitors are redirected to the sign-in screen;
        // already-authenticated users hitting a guest route go to the app.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/');

        // Route-middleware aliases for the platform-owner-only area.
        //  · platform.owner — the owner-only gate (404s everyone else)
        //  · platform.sudo  — step-up re-auth for sensitive owner writes
        $middleware->alias([
            'platform.owner' => EnsurePlatformOwner::class,
            'platform.sudo' => RequireRecentReauth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
