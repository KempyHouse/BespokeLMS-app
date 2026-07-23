<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\SupabaseUser;
use App\Auth\SupabaseUserProvider;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Contracts\ReadsDesignTokens;
use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Contracts\ReadsProfiles;
use App\Support\Supabase\SupabaseAuth;
use App\Support\Supabase\Contracts\WritesBrandKits;
use App\Support\Supabase\Contracts\WritesProfiles;
use App\Support\Supabase\SupabaseBrandKits;
use App\Support\Supabase\SupabaseDesignTokens;
use App\Support\Supabase\SupabaseOrganizations;
use App\Support\Supabase\SupabaseProfiles;
use App\Support\Supabase\SupabaseProfilesWriter;
use App\Support\Theme\ThemeResolver;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthenticatesWithSupabase::class, function (Application $app): SupabaseAuth {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseAuth(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['anon_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(ReadsProfiles::class, function (Application $app): SupabaseProfiles {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseProfiles(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['anon_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(ReadsOrganizations::class, function (Application $app): SupabaseOrganizations {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseOrganizations(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(ReadsDesignTokens::class, function (Application $app): SupabaseDesignTokens {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseDesignTokens(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(WritesBrandKits::class, function (Application $app): SupabaseBrandKits {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseBrandKits(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(ThemeResolver::class, function (Application $app): ThemeResolver {
            return new ThemeResolver(
                $app->make(ReadsDesignTokens::class),
                $app->make(Cache::class),
            );
        });

        $this->app->singleton(WritesProfiles::class, function (Application $app): SupabaseProfilesWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseProfilesWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
    }

    public function boot(): void
    {
        // Register the Supabase-backed user provider for the session guard.
        Auth::provider('supabase', function (Application $app, array $config): SupabaseUserProvider {
            return new SupabaseUserProvider($app->make('session.store'));
        });

        // Authorisation gate for the platform-owner tier — the app-layer mirror
        // of the database's is_platform_owner() predicate. Use in controllers
        // (Gate::authorize / $this->authorize) or Blade (@can('administer-platform')).
        Gate::define('administer-platform', static function (SupabaseUser $user): bool {
            return $user->isPlatformOwner();
        });

        // Resolve the Supabase design tokens into CSS variables for the shared
        // layout. The tenant's published brand kit reskins the token-driven
        // components; any failure yields an empty string (compiled theme holds).
        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $organizationId = $user instanceof SupabaseUser ? $user->organizationId : null;

            /** @var ThemeResolver $resolver */
            $resolver = $this->app->make(ThemeResolver::class);

            $view->with('brandTokensCss', $resolver->cssFor($organizationId));
        });
    }
}
