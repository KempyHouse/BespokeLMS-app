<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyWorkspaceController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\TeamWorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes (visitors who are not signed in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    // Landing page for the Supabase recovery / magic-link email.
    Route::get('reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
});

/*
|--------------------------------------------------------------------------
| Authenticated application — the protected app ("only users can view")
|--------------------------------------------------------------------------
| Every application route belongs inside this group. Guests are redirected
| to the sign-in screen by the "auth" middleware.
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Rebuilt workspace shells — blank scaffolds being migrated off the
    // frozen prototype. Any authenticated user may load the shell; Supabase
    // RLS governs whatever tenant data the pages eventually read.
    Route::get('my', MyWorkspaceController::class)->name('my.home');
    Route::get('team', TeamWorkspaceController::class)->name('team.home');

    // Per-user theme preference (light / dark / system). Applied on the client
    // immediately; persisted here to the profile.
    Route::post('preferences/theme', [PreferencesController::class, 'updateTheme'])->name('preferences.theme');

    /*
    | Platform-owner-only area. The "platform.owner" middleware returns 404 to
    | anyone who is not the BespokeLMS platform owner, so the area is neither
    | reachable nor disclosed to tenant users. Database access is independently
    | enforced by Supabase RLS (is_platform_owner()).
    */
    Route::middleware('platform.owner')
        ->prefix('platform')
        ->name('platform.')
        ->group(function (): void {
            Route::get('/', [PlatformController::class, 'index'])->name('home');

            // Per-tenant admin console (configuration hub). {tenant} is an
            // organisation UUID; the controller 404s an unknown id.
            Route::get('tenants/{tenant}', [PlatformController::class, 'show'])->name('tenants.show');

            // Save a tenant's brand kit (themeable design-token overrides).
            Route::put('tenants/{tenant}/branding', [PlatformController::class, 'updateBranding'])
                ->name('tenants.branding.update');
        });
});
