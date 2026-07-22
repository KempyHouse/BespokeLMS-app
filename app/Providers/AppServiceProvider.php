<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\SupabaseUserProvider;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Contracts\ReadsProfiles;
use App\Support\Supabase\SupabaseAuth;
use App\Support\Supabase\SupabaseProfiles;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Auth;
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
    }

    public function boot(): void
    {
        // Register the Supabase-backed user provider for the session guard.
        Auth::provider('supabase', function (Application $app, array $config): SupabaseUserProvider {
            return new SupabaseUserProvider($app->make('session.store'));
        });
    }
}
