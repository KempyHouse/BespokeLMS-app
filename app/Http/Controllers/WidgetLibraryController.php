<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsDashboards;
use App\Support\Supabase\Contracts\WritesAuditLog;
use App\Support\Supabase\Contracts\WritesDashboards;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform → Widget Library — the platform-owner console for the dashboard
 * widget catalogue.
 *
 * This is where widgets are managed: which roles may see each one (the
 * role-based access that governs every user's Add-widget picker), a widget's
 * status, and its default size. Reads/writes go through the service-role
 * dashboards client; the owner-only boundary is the platform.owner middleware,
 * and saves additionally require a recent re-auth (platform.sudo).
 */
final class WidgetLibraryController extends Controller
{
    /** The roles a widget's visibility can be granted to, in display order. */
    private const ROLES = [
        'bespokelms_owner' => 'Platform Owner',
        'lms_operator_admin' => 'Operator Admin',
        'client_admin' => 'Client Admin',
        'team_manager' => 'Team Manager',
        'learner' => 'Learner',
    ];

    public function index(Request $request, ReadsDashboards $dashboards): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $error = null;
        try {
            $widgets = array_map($this->shape(...), $dashboards->registryAll());
        } catch (SupabaseAuthException $e) {
            report($e);
            $widgets = [];
            $error = 'The widget library could not be loaded right now. Please try again shortly.';
        }

        return view('platform.widgets.index', [
            'user' => $user,
            'widgets' => $widgets,
            'roleLabels' => self::ROLES,
            'error' => $error,
        ]);
    }

    public function show(Request $request, ReadsDashboards $dashboards, string $widget): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $row = $dashboards->widgetByKey($widget);
        } catch (SupabaseAuthException $e) {
            report($e);
            abort(503, 'The widget could not be loaded right now. Please try again shortly.');
        }

        if ($row === null) {
            abort(404);
        }

        return view('platform.widgets.show', [
            'user' => $user,
            'widget' => $this->shape($row),
            'roleLabels' => self::ROLES,
        ]);
    }

    public function update(
        Request $request,
        ReadsDashboards $reads,
        WritesDashboards $writes,
        WritesAuditLog $audit,
        string $widget
    ): RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $row = $reads->widgetByKey($widget);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.widgets')
                ->with('error', 'The widget could not be loaded right now. Please try again shortly.');
        }

        if ($row === null) {
            abort(404);
        }

        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'in:'.implode(',', array_keys(self::ROLES))],
            'status' => ['required', 'string', 'in:active,draft,retired'],
            'default_size' => ['required', 'string', 'in:s,m,l'],
        ]);

        // Preserve the configured role order; drop anything unexpected.
        $roles = array_values(array_intersect(array_keys(self::ROLES), $validated['roles'] ?? []));

        $sizes = is_array($row['sizes'] ?? null) ? array_map('strval', $row['sizes']) : ['s', 'm', 'l'];
        $defaultSize = in_array($validated['default_size'], $sizes, true)
            ? $validated['default_size']
            : (string) ($row['default_size'] ?? 'm');

        $id = (string) ($row['id'] ?? '');

        try {
            $writes->setWidgetRoles($id, $roles);
            $writes->updateWidget($id, [
                'status' => $validated['status'],
                'default_size' => $defaultSize,
            ]);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.widgets')
                ->with('error', 'The widget could not be saved right now. Please try again shortly.');
        }

        $audit->record(
            action: 'dashboard_widget.updated',
            actorId: $user->profileId,
            organizationId: $user->organizationId,
            entity: 'dashboard_widget',
            entityId: $id,
            meta: ['roles' => count($roles), 'status' => $validated['status'], 'default_size' => $defaultSize],
        );

        return redirect()->route('platform.widgets')
            ->with('status', (string) ($row['name'] ?? 'Widget').' updated.');
    }

    /**
     * Shape a raw registry row (with embedded visibility) into a view model.
     *
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function shape(array $row): array
    {
        $roles = [];
        foreach ((array) ($row['dashboard_widget_visibility'] ?? []) as $v) {
            if (is_array($v) && isset($v['role'])) {
                $roles[] = (string) $v['role'];
            }
        }

        $sizes = is_array($row['sizes'] ?? null) ? array_values(array_map('strval', $row['sizes'])) : ['s', 'm', 'l'];

        return [
            'id' => (string) ($row['id'] ?? ''),
            'key' => (string) ($row['key'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'category' => (string) ($row['category'] ?? 'General'),
            'icon' => (string) ($row['icon'] ?? ''),
            'sizes' => $sizes !== [] ? $sizes : ['s', 'm', 'l'],
            'default_size' => (string) ($row['default_size'] ?? 'm'),
            'supports_comparison' => (bool) ($row['supports_comparison'] ?? false),
            'status' => (string) ($row['status'] ?? 'active'),
            'roles' => $roles,
        ];
    }
}
