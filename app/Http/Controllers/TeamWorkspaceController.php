<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsDashboards;
use App\Support\Supabase\Contracts\ReadsWidgetData;
use App\Support\Supabase\Contracts\WritesDashboards;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use App\Support\Widgets\WidgetMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves the Team workspace — a per-team configurable dashboard.
 *
 * The team administrator places widgets from the platform library onto their team
 * dashboard and arranges them (size + order); the layout is saved to team_dashboards.
 * Which widgets a team may add is governed by role: the registry is read pre-filtered
 * to the signed-in user's role (dashboard_widget_visibility). Every widget is
 * powered by live data scoped to this team and shows an honest empty state until
 * data exists — nothing is fabricated.
 */
final class TeamWorkspaceController extends Controller
{
    /** Widget keys whose data is team-level (governed by the team's training data). */
    private const TEAM_KEYS = [
        'training_overdue', 'training_to_complete', 'training_completion_rate',
        'training_in_progress', 'training_completed', 'training_time',
    ];

    public function index(Request $request, ReadsDashboards $dashboards, ReadsWidgetData $data): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $registryError = null;
        try {
            $registry = $dashboards->registryForRole($user->role);
        } catch (SupabaseAuthException $e) {
            report($e);
            $registry = [];
            $registryError = 'The widget library could not be loaded right now. Please try again shortly.';
        }

        $widgets = $this->shapeRegistry($registry);

        // For now, use empty team metrics. This should be expanded to fetch actual
        // team-level data from the platform (aggregate training, team members, etc.)
        $team = [];
        $platform = $user->isPlatformOwner() ? WidgetMetrics::platform($data->platformOverview()) : [];

        return view('team.home', [
            'user' => $user,
            'widgets' => $widgets,
            'metrics' => $this->mapMetrics($team, $platform),
            'hasData' => (bool) ($team['has_data'] ?? false),
            'layout' => $this->shapeLayout($dashboards->layoutForTeam($user->tenantId), $widgets),
            'teamKeys' => self::TEAM_KEYS,
            'registryError' => $registryError,
        ]);
    }

    /**
     * Persist the team's dashboard layout. Keys are whitelisted against
     * the widgets the user's role may actually place (defence in depth beyond
     * RLS), and sizes are clamped to each widget's allowed set.
     */
    public function save(Request $request, ReadsDashboards $reads, WritesDashboards $writes): JsonResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $validated = $request->validate([
            'layout' => ['present', 'array', 'max:60'],
            'layout.*.key' => ['required', 'string', 'max:100'],
            'layout.*.size' => ['required', 'string', 'in:s,m,l'],
            'layout.*.settings' => ['sometimes', 'array'],
            'layout.*.settings.comparison' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        try {
            $allowed = $this->shapeRegistry($reads->registryForRole($user->role));
        } catch (SupabaseAuthException $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Could not verify the widget library.'], 503);
        }

        $seen = [];
        $clean = [];
        foreach ($validated['layout'] as $placement) {
            $key = (string) $placement['key'];
            if (! isset($allowed[$key]) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $sizes = $allowed[$key]['sizes'] ?: ['s', 'm', 'l'];
            $size = in_array($placement['size'], $sizes, true)
                ? (string) $placement['size']
                : (string) ($allowed[$key]['default_size'] ?? 'm');

            $entry = ['key' => $key, 'size' => $size];

            $comparison = $placement['settings']['comparison'] ?? null;
            if (is_string($comparison) && $comparison !== '') {
                $entry['settings'] = ['comparison' => $comparison];
            }

            $clean[] = $entry;
        }

        try {
            $writes->saveLayout((string) $user->tenantId, $clean, 'team');
        } catch (SupabaseAuthException $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'The dashboard could not be saved right now.'], 503);
        }

        return response()->json(['ok' => true, 'count' => count($clean)]);
    }

    /**
     * Shape the raw registry rows into a view-ready map keyed by widget key.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<string,array<string,mixed>>
     */
    private function shapeRegistry(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $sizes = is_array($row['sizes'] ?? null) ? array_values(array_map('strval', $row['sizes'])) : ['s', 'm', 'l'];
            $options = [];
            foreach ((array) ($row['comparison_options'] ?? []) as $opt) {
                if (is_array($opt) && isset($opt['key'], $opt['label'])) {
                    $options[] = ['key' => (string) $opt['key'], 'label' => (string) $opt['label']];
                }
            }

            $out[$key] = [
                'key' => $key,
                'name' => (string) ($row['name'] ?? $key),
                'description' => (string) ($row['description'] ?? ''),
                'category' => (string) ($row['category'] ?? 'General'),
                'icon' => (string) ($row['icon'] ?? ''),
                'component' => (string) ($row['component'] ?? ''),
                'sizes' => $sizes !== [] ? $sizes : ['s', 'm', 'l'],
                'default_size' => (string) ($row['default_size'] ?? 'm'),
                'supports_comparison' => (bool) ($row['supports_comparison'] ?? false),
                'comparison_options' => $options,
                'comparison_default' => $row['comparison_default'] !== null ? (string) $row['comparison_default'] : null,
            ];
        }

        return $out;
    }

    /**
     * Map computed metrics to widget keys.
     *
     * @param  array<string,mixed>  $team
     * @param  array<string,mixed>  $platform
     * @return array<string,array<string,mixed>>
     */
    private function mapMetrics(array $team, array $platform): array
    {
        return [
            'training_overdue' => $team['overdue'] ?? [],
            'training_to_complete' => $team['to_complete'] ?? [],
            'training_completion_rate' => $team['completion_rate'] ?? [],
            'training_in_progress' => $team['in_progress'] ?? [],
            'training_completed' => $team['completed'] ?? [],
            'training_time' => $team['training_time'] ?? [],
            'platform_tenant_estate' => $platform['tenant_estate'] ?? [],
            'platform_users' => $platform['platform_users'] ?? [],
            'platform_integration_health' => $platform['integration_health'] ?? [],
        ];
    }

    /**
     * Normalise the saved layout: keep only placements whose widget is available
     * to this user, and clamp each size to that widget's allowed set.
     *
     * @param  array{id:string,name:string,layout:array<int,array<string,mixed>>}|null  $saved
     * @param  array<string,array<string,mixed>>  $widgets
     * @return array<int,array<string,mixed>>
     */
    private function shapeLayout(?array $saved, array $widgets): array
    {
        if ($saved === null) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($saved['layout'] as $placement) {
            if (! is_array($placement)) {
                continue;
            }
            $key = (string) ($placement['key'] ?? '');
            if ($key === '' || ! isset($widgets[$key]) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $sizes = $widgets[$key]['sizes'] ?: ['s', 'm', 'l'];
            $size = in_array($placement['size'] ?? '', $sizes, true)
                ? (string) $placement['size']
                : (string) $widgets[$key]['default_size'];

            $comparison = $placement['settings']['comparison'] ?? $widgets[$key]['comparison_default'];

            $out[] = [
                'key' => $key,
                'size' => $size,
                'settings' => ['comparison' => $comparison !== null ? (string) $comparison : null],
            ];
        }

        return $out;
    }
}
