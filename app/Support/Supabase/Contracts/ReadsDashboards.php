<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Reads the dashboard widget library and a user's saved dashboard layout.
 *
 * The registry (dashboard_widgets + dashboard_widget_visibility) is the
 * platform-owned catalogue seeded by migration; a user only ever sees the
 * widgets their role is granted. Saved layouts (user_dashboards) are personal
 * and MUST be scoped to the given profile id in the query itself, since the
 * concrete implementation reads with the service-role key.
 */
interface ReadsDashboards
{
    /**
     * The active widgets a given role may place, ordered for display.
     *
     * @return array<int,array<string,mixed>>  Each: key,name,description,category,icon,
     *                                          component,sizes,default_size,size_map,
     *                                          supports_comparison,comparison_options,
     *                                          comparison_default,status,sort_order.
     *
     * @throws SupabaseAuthException  When the registry cannot be read at all.
     */
    public function registryForRole(string $role): array;

    /**
     * The full registry (every widget + the roles allowed to see it), for the
     * platform-owner admin console.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws SupabaseAuthException
     */
    public function registryAll(): array;

    /**
     * One widget by its stable key, with the roles allowed to see it, or null.
     *
     * @return array<string,mixed>|null
     */
    public function widgetByKey(string $key): ?array;

    /**
     * The signed-in user's saved default dashboard, or null when they have none
     * yet (the dashboard then renders its empty "add your first widget" state).
     * Degrades to null on a read failure — never throws — so the page still loads.
     *
     * @return array{id:string,name:string,layout:array<int,array<string,mixed>>}|null
     */
    public function layoutForProfile(?string $profileId): ?array;
}
