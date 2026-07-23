<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\ReadsDashboards;
use App\Support\Supabase\Contracts\WritesDashboards;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Reads/writes the dashboard widget library and per-user layouts through
 * PostgREST using the server-side service-role key.
 *
 * The registry read is the platform catalogue (readable to every authenticated
 * user by RLS); the role filter is applied in the query so a caller only sees
 * the widgets their role may place. Layout rows are scoped to the profile id in
 * the query. Registry writes are reached only from the platform-owner console.
 */
final class SupabaseDashboards implements ReadsDashboards, WritesDashboards
{
    /** Registry columns the dashboard + admin need. */
    private const WIDGET_SELECT = 'key,name,description,category,icon,component,sizes,default_size,size_map,supports_comparison,comparison_options,comparison_default,status,sort_order';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function registryForRole(string $role): array
    {
        $this->assertConfigured();

        try {
            $response = $this->request()->get('/rest/v1/dashboard_widgets', [
                'select' => self::WIDGET_SELECT.',dashboard_widget_visibility!inner(role)',
                'status' => 'eq.active',
                'dashboard_widget_visibility.role' => 'eq.'.$role,
                'order' => 'sort_order.asc',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase widget registry lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    public function registryAll(): array
    {
        $this->assertConfigured();

        try {
            $response = $this->request()->get('/rest/v1/dashboard_widgets', [
                'select' => self::WIDGET_SELECT.',is_platform,dashboard_widget_visibility(role)',
                'order' => 'sort_order.asc',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase widget registry lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
    }

    public function widgetByKey(string $key): ?array
    {
        $this->assertConfigured();

        try {
            $response = $this->request()->get('/rest/v1/dashboard_widgets', [
                'select' => 'id,'.self::WIDGET_SELECT.',is_platform,dashboard_widget_visibility(role)',
                'key' => 'eq.'.$key,
                'limit' => '1',
            ]);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Supabase widget lookup failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows[0] ?? null;
    }

    public function layoutForProfile(?string $profileId): ?array
    {
        if ($profileId === null || $profileId === '' || $this->serviceRoleKey === '') {
            return null;
        }

        try {
            $response = $this->request()->get('/rest/v1/user_dashboards', [
                'select' => 'id,name,layout',
                'profile_id' => 'eq.'.$profileId,
                'is_default' => 'is.true',
                'limit' => '1',
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];
        $row = $rows[0] ?? null;
        if ($row === null) {
            return null;
        }

        $layout = $row['layout'] ?? [];
        if (! is_array($layout)) {
            $layout = [];
        }

        return [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? 'My Dashboard'),
            'layout' => array_values($layout),
        ];
    }

    public function saveLayout(string $profileId, array $layout): void
    {
        $this->assertConfigured();

        $existingId = $this->layoutForProfile($profileId)['id'] ?? null;
        $payloadLayout = array_values($layout);

        try {
            if ($existingId !== null && $existingId !== '') {
                $response = $this->request()
                    ->withHeaders(['Prefer' => 'return=minimal'])
                    ->patch('/rest/v1/user_dashboards?id=eq.'.$existingId, [
                        'layout' => $payloadLayout,
                    ]);
            } else {
                $response = $this->request()
                    ->withHeaders(['Prefer' => 'return=minimal'])
                    ->post('/rest/v1/user_dashboards', [
                        'profile_id' => $profileId,
                        'name' => 'My Dashboard',
                        'is_default' => true,
                        'layout' => $payloadLayout,
                    ]);
            }
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Saving the dashboard failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    public function setWidgetRoles(string $widgetId, array $roles): void
    {
        $this->assertConfigured();

        // Replace the visibility set: clear existing rows, then insert the chosen
        // roles. De-duplicated so a repeated role never violates the PK.
        try {
            $delete = $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->delete('/rest/v1/dashboard_widget_visibility?widget_id=eq.'.$widgetId);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $delete->successful()) {
            throw new SupabaseAuthException(
                "Updating widget visibility failed (HTTP {$delete->status()}).",
                $delete->status(),
            );
        }

        $unique = array_values(array_unique(array_filter($roles, static fn ($r): bool => is_string($r) && $r !== '')));
        if ($unique === []) {
            return;
        }

        $insert = array_map(
            static fn (string $role): array => ['widget_id' => $widgetId, 'role' => $role],
            $unique,
        );

        try {
            $response = $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->post('/rest/v1/dashboard_widget_visibility', $insert);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Saving widget visibility failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    public function updateWidget(string $widgetId, array $attrs): void
    {
        $this->assertConfigured();

        if ($attrs === []) {
            return;
        }

        try {
            $response = $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->patch('/rest/v1/dashboard_widgets?id=eq.'.$widgetId, $attrs);
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase data service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new SupabaseAuthException(
                "Updating the widget failed (HTTP {$response->status()}).",
                $response->status(),
            );
        }
    }

    private function assertConfigured(): void
    {
        if ($this->serviceRoleKey === '') {
            throw new SupabaseAuthException(
                'The Supabase service-role key is not configured, so dashboards cannot be loaded.',
            );
        }
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['apikey' => $this->serviceRoleKey])
            ->withToken($this->serviceRoleKey);
    }
}
