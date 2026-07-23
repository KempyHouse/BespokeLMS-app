<?php

declare(strict_types=1);

use App\Http\Controllers\AiIntegrationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseLibraryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailIntegrationController;
use App\Http\Controllers\MyWorkspaceController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ProfileController;
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
    // Learner Course Library — the browsable catalogue on the My workspace.
    Route::get('my/courses', [CourseLibraryController::class, 'index'])->name('my.courses');
    Route::get('my/courses/{course}', [CourseLibraryController::class, 'show'])->name('my.courses.show');
    Route::get('team', TeamWorkspaceController::class)->name('team.home');

    // Per-user theme preference (light / dark / system). Applied on the client
    // immediately; persisted here to the profile.
    Route::post('preferences/theme', [PreferencesController::class, 'updateTheme'])->name('preferences.theme');

    // A user's own profile: identity fields + avatar image.
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');

    /*
    | Platform-owner-only area. The "platform.owner" middleware returns 404 to
    | anyone who is not the BespokeLMS platform owner, so the area is neither
    | reachable nor disclosed to tenant users. Database access is independently
    | enforced by Supabase RLS (is_platform_owner()). Sensitive writes below add
    | "platform.sudo" — a recent password re-confirmation (step-up auth).
    */
    Route::middleware('platform.owner')
        ->prefix('platform')
        ->name('platform.')
        ->group(function (): void {
            Route::get('/', [PlatformController::class, 'index'])->name('home');

            // Step-up ("sudo mode") re-authentication screen for sensitive writes.
            Route::get('confirm', [ConfirmPasswordController::class, 'create'])->name('confirm');
            Route::post('confirm', [ConfirmPasswordController::class, 'store'])->name('confirm.store');

            // Global Courses console — the ecosystem-wide course catalogue
            // (platform-owned courses that cascade to tenants + operator courses).
            Route::get('courses', [CourseController::class, 'index'])->name('courses');
            // Course workspace — read-only drill-in for one course.
            Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');

            // Per-tenant admin console (configuration hub). {tenant} is an
            // organisation UUID; the controller 404s an unknown id.
            Route::get('tenants/{tenant}', [PlatformController::class, 'show'])->name('tenants.show');

            // Save a tenant's brand kit (themeable design-token overrides).
            Route::put('tenants/{tenant}/branding', [PlatformController::class, 'updateBranding'])
                ->middleware('platform.sudo')
                ->name('tenants.branding.update');

            // Save a tenant's email sender identity ("alias").
            Route::put('tenants/{tenant}/email-alias', [PlatformController::class, 'updateAlias'])
                ->middleware('platform.sudo')
                ->name('tenants.alias.update');

            // Owner-level AI & voice provider integrations (Claude, OpenAI,
            // ElevenLabs, …), configured once and inherited by every tenant.
            Route::get('ai', [AiIntegrationController::class, 'index'])->name('ai');
            Route::put('ai/{integration}', [AiIntegrationController::class, 'update'])
                ->middleware('platform.sudo')
                ->name('ai.update');

            // Owner-level email transport (Resend, Postmark, SES, SMTP, …),
            // configured once here and inherited by every tenant. Per-tenant
            // sender identities ("aliases") are set on each tenant's console.
            Route::get('email', [EmailIntegrationController::class, 'index'])->name('email');
            Route::put('email/{integration}', [EmailIntegrationController::class, 'update'])
                ->middleware('platform.sudo')
                ->name('email.update');
            Route::post('email/test', [EmailIntegrationController::class, 'test'])
                ->middleware('platform.sudo')
                ->name('email.test');
        });
});
