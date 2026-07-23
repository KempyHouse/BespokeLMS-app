<?php

declare(strict_types=1);

namespace App\Support\Supabase\Contracts;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

/**
 * Writes a user's dashboard layout and (platform-owner only) the widget
 * registry's status and per-role visibility.
 *
 * Layout writes are scoped to the owning profile id. Registry writes are guarded
 * upstream by the platform-owner middleware + step-up re-auth on the route; this
 * writer holds the service-role key and performs the persistence.
 */
interface WritesDashboards
{
    /**
     * Create or update the profile's single default dashboard with the given
     * placed-widget layout.
     *
     * @param  array<int,array<string,mixed>>  $layout  Placed widgets: {key,x,y,w,h,size,settings}.
     *
     * @throws SupabaseAuthException
     */
    public function saveLayout(string $profileId, array $layout): void;

    /**
     * Replace a widget's set of allowed roles (deletes then inserts).
     *
     * @param  array<int,string>  $roles  app_role values.
     *
     * @throws SupabaseAuthException
     */
    public function setWidgetRoles(string $widgetId, array $roles): void;

    /**
     * Update a widget's owner-configurable attributes (status, default_size).
     *
     * @param  array<string,mixed>  $attrs
     *
     * @throws SupabaseAuthException
     */
    public function updateWidget(string $widgetId, array $attrs): void;
}
