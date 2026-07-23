<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\SupabaseUser;
use App\Auth\SupabaseUserProvider;
use App\Support\Mail\EmailLogWriter;
use App\Support\Mail\EmailTransportResolver;
use App\Support\Mail\TenantMailer;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Contracts\ReadsAiIntegrations;
use App\Support\Supabase\Contracts\ReadsCourses;
use App\Support\Supabase\Contracts\ReadsDesignTokens;
use App\Support\Supabase\Contracts\ReadsEmailIntegrations;
use App\Support\Supabase\Contracts\ReadsLearnerCatalogue;
use App\Support\Supabase\Contracts\WritesCoursePricing;
use App\Support\Supabase\Contracts\WritesCourseAvailability;
use App\Support\Supabase\Contracts\WritesCourseContent;
use App\Support\Supabase\Contracts\WritesCourseVersion;
use App\Support\Supabase\Contracts\WritesCourses;
use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Contracts\ReadsProfiles;
use App\Support\Supabase\Contracts\ReadsTenantEmailAliases;
use App\Support\Supabase\SupabaseAuth;
use App\Support\Supabase\Contracts\WritesAiIntegrations;
use App\Support\Supabase\Contracts\WritesAuditLog;
use App\Support\Supabase\Contracts\WritesBrandKits;
use App\Support\Supabase\Contracts\WritesEmailIntegrations;
use App\Support\Supabase\Contracts\WritesProfiles;
use App\Support\Supabase\Contracts\WritesTenantEmailAliases;
use App\Support\Supabase\SupabaseAiIntegrations;
use App\Support\Supabase\SupabaseAuditLog;
use App\Support\Supabase\SupabaseBrandKits;
use App\Support\Supabase\SupabaseCourses;
use App\Support\Supabase\SupabaseDesignTokens;
use App\Support\Supabase\SupabaseEmailIntegrations;
use App\Support\Supabase\SupabaseLearnerCatalogue;
use App\Support\Supabase\SupabaseCoursePricingWriter;
use App\Support\Supabase\SupabaseCourseAvailabilityWriter;
use App\Support\Supabase\SupabaseCourseContentWriter;
use App\Support\Supabase\SupabaseCourseVersionWriter;
use App\Support\Supabase\SupabaseCoursesWriter;
use App\Support\Supabase\SupabaseOrganizations;
use App\Support\Supabase\SupabaseProfiles;
use App\Support\Supabase\SupabaseProfilesWriter;
use App\Support\Supabase\SupabaseTenantEmailAliases;
use App\Support\Supabase\Contracts\ReadsDashboards;
use App\Support\Supabase\Contracts\ReadsWidgetData;
use App\Support\Supabase\Contracts\WritesDashboards;
use App\Support\Supabase\SupabaseDashboards;
use App\Support\Supabase\SupabaseWidgetData;
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

        $this->app->singleton(ReadsCourses::class, function (Application $app): SupabaseCourses {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseCourses(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(WritesCourses::class, function (Application $app): SupabaseCoursesWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseCoursesWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(WritesCoursePricing::class, function (Application $app): SupabaseCoursePricingWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseCoursePricingWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(WritesCourseAvailability::class, function (Application $app): SupabaseCourseAvailabilityWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseCourseAvailabilityWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(WritesCourseContent::class, function (Application $app): SupabaseCourseContentWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseCourseContentWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(WritesCourseVersion::class, function (Application $app): SupabaseCourseVersionWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseCourseVersionWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->singleton(ReadsLearnerCatalogue::class, function (Application $app): SupabaseLearnerCatalogue {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseLearnerCatalogue(
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

        // AI integrations (owner-level). One service-role client implements both
        // the read and write contracts.
        $this->app->singleton(SupabaseAiIntegrations::class, function (Application $app): SupabaseAiIntegrations {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseAiIntegrations(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
        $this->app->bind(ReadsAiIntegrations::class, SupabaseAiIntegrations::class);
        $this->app->bind(WritesAiIntegrations::class, SupabaseAiIntegrations::class);

        // Email transport integrations (owner-level). One service-role client
        // implements both the read and write contracts, mirroring the AI slice.
        $this->app->singleton(SupabaseEmailIntegrations::class, function (Application $app): SupabaseEmailIntegrations {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseEmailIntegrations(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
        $this->app->bind(ReadsEmailIntegrations::class, SupabaseEmailIntegrations::class);
        $this->app->bind(WritesEmailIntegrations::class, SupabaseEmailIntegrations::class);

        // Audit trail writer (service-role; best-effort append-only logging).
        $this->app->singleton(WritesAuditLog::class, function (Application $app): SupabaseAuditLog {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseAuditLog(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });

        // Per-tenant email aliases (service-role). One client implements both
        // the read and write contracts.
        $this->app->singleton(SupabaseTenantEmailAliases::class, function (Application $app): SupabaseTenantEmailAliases {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseTenantEmailAliases(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
        $this->app->bind(ReadsTenantEmailAliases::class, SupabaseTenantEmailAliases::class);
        $this->app->bind(WritesTenantEmailAliases::class, SupabaseTenantEmailAliases::class);

        // Email runtime: the transport resolver + delivery-log writer feed the
        // TenantMailer, which sends on the enabled transport as the tenant alias.
        $this->app->singleton(EmailTransportResolver::class, function (Application $app): EmailTransportResolver {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new EmailTransportResolver(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
        $this->app->singleton(EmailLogWriter::class, function (Application $app): EmailLogWriter {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new EmailLogWriter(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
        $this->app->singleton(TenantMailer::class, function (Application $app): TenantMailer {
            return new TenantMailer(
                $app->make(EmailTransportResolver::class),
                $app->make(EmailLogWriter::class),
                $app['config'],
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

        // Dashboard widget library + per-user dashboards (service-role). One
        // client implements both the registry read and the layout/registry
        // writes; a second reads the raw rows the widgets are computed from.
        $this->app->singleton(SupabaseDashboards::class, function (Application $app): SupabaseDashboards {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseDashboards(
                $app->make(HttpFactory::class),
                (string) ($config['url'] ?? ''),
                (string) ($config['service_role_key'] ?? ''),
                (int) ($config['timeout'] ?? 10),
            );
        });
        $this->app->bind(ReadsDashboards::class, SupabaseDashboards::class);
        $this->app->bind(WritesDashboards::class, SupabaseDashboards::class);

        $this->app->singleton(ReadsWidgetData::class, function (Application $app): SupabaseWidgetData {
            /** @var array<string,mixed> $config */
            $config = $app['config']->get('services.supabase', []);

            return new SupabaseWidgetData(
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
            $tokens = $resolver->resolve($organizationId);

            $view->with('brandTokensCss', $tokens['light']);
            $view->with('brandTokensDarkCss', $tokens['dark']);
        });
    }
}
