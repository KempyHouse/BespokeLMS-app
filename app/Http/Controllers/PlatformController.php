<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Http\Requests\UpdateTenantBrandingRequest;
use App\Support\Supabase\Contracts\ReadsDesignTokens;
use App\Support\Supabase\Contracts\ReadsOrganizations;
use App\Support\Supabase\Contracts\ReadsTenantEmailAliases;
use App\Support\Supabase\Contracts\WritesAuditLog;
use App\Support\Supabase\Contracts\WritesBrandKits;
use App\Support\Supabase\Contracts\WritesTenantEmailAliases;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use App\Support\Theme\ThemeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The BespokeLMS platform-owner console.
 *
 * Access is gated twice: the "platform.owner" route middleware (404s non-owners)
 * and Supabase RLS on every table the console reads with a user token. The
 * cross-tenant estate view is read with the service-role key (see
 * {@see ReadsOrganizations}); the route middleware is its authorisation boundary.
 *
 * Every tenant, and everything shown about it, is loaded from Supabase — the
 * platform is fully database-driven so new white-label tenants are pure
 * configuration, scaling to hundreds of organisations.
 */
final class PlatformController extends Controller
{
    /**
     * Tenants index — a flat, sortable/filterable table of every organisation
     * in the estate (operators and their client organisations).
     */
    public function index(Request $request, ReadsOrganizations $organizations): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $q = trim((string) $request->query('q', ''));

        try {
            $rows = $organizations->all();
        } catch (SupabaseAuthException $e) {
            report($e);

            return view('platform.home', [
                'user' => $user,
                'tenants' => null,
                'summary' => null,
                'modelOptions' => [],
                'estateError' => 'The tenant list could not be loaded right now. Please try again shortly.',
                'q' => $q,
            ]);
        }

        [$tenants, $summary, $modelOptions] = $this->buildTenants($rows);

        return view('platform.home', [
            'user' => $user,
            'tenants' => $tenants,
            'summary' => $summary,
            'modelOptions' => $modelOptions,
            'estateError' => null,
            'q' => $q,
        ]);
    }

    /**
     * Per-tenant admin console — the configuration hub for one organisation's
     * white-label instance.
     */
    public function show(
        Request $request,
        ReadsOrganizations $organizations,
        ReadsDesignTokens $designTokens,
        WritesBrandKits $brandKits,
        ReadsTenantEmailAliases $aliases,
        string $tenant
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $rows = $organizations->all();
        } catch (SupabaseAuthException $e) {
            report($e);
            abort(503, 'The tenant could not be loaded right now. Please try again shortly.');
        }

        $byId = [];
        foreach ($rows as $row) {
            $byId[(string) ($row['id'] ?? '')] = $row;
        }

        $row = $byId[$tenant] ?? null;
        if ($row === null) {
            abort(404);
        }

        [$tenants] = $this->buildTenants($rows);

        return view('platform.tenants.show', [
            'user' => $user,
            'tenant' => $this->buildTenantDetail($row, $byId, $rows),
            'tenants' => $tenants,
            'branding' => $this->buildBranding($tenant, $designTokens, $brandKits),
            'emailAlias' => $this->buildAlias($tenant, $aliases),
        ]);
    }

    /**
     * Persist a tenant's brand kit — the themeable design-token overrides that
     * reskin its instance — then flush the tenant's resolved-theme cache.
     */
    public function updateBranding(
        UpdateTenantBrandingRequest $request,
        ReadsOrganizations $organizations,
        WritesBrandKits $brandKits,
        ThemeResolver $theme,
        WritesAuditLog $audit,
        string $tenant
    ): RedirectResponse {
        try {
            $rows = $organizations->all();
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.tenants.show', $tenant)
                ->withFragment('branding')
                ->with('brandingError', 'The tenant could not be loaded right now. Please try again shortly.');
        }

        $org = null;
        foreach ($rows as $row) {
            if ((string) ($row['id'] ?? '') === $tenant) {
                $org = $row;
                break;
            }
        }
        if ($org === null) {
            abort(404);
        }

        $themeable = $request->themeableTokens();
        $values = (array) $request->validated('tokens', []);
        $inherit = (array) $request->input('inherit', []);

        $upserts = [];
        $deletes = [];
        foreach (array_keys($themeable) as $key) {
            if (array_key_exists($key, $inherit)) {
                $deletes[] = $key;
            } elseif (isset($values[$key]) && $values[$key] !== '') {
                $upserts[$key] = (string) $values[$key];
            }
        }

        try {
            $kitId = $brandKits->ensurePublishedDefaultKitId($tenant, (string) ($org['name'] ?? 'Tenant'));
            $brandKits->save($kitId, $upserts, $deletes);
            $theme->flushOrg($tenant);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.tenants.show', $tenant)
                ->withFragment('branding')
                ->with('brandingError', 'The brand kit could not be saved right now. Please try again shortly.');
        }

        /** @var SupabaseUser $user */
        $user = $request->user();
        $audit->record(
            action: 'tenant_branding.updated',
            actorId: $user->profileId,
            organizationId: $tenant,
            entity: 'tenant_branding',
            entityId: $tenant,
            meta: ['tokens_changed' => count($upserts), 'tokens_reset' => count($deletes)],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->withFragment('branding')
            ->with('status', 'Brand kit saved. This tenant\'s instance now uses the updated tokens.');
    }

    /**
     * Save a tenant's email sender identity ("alias"): the from-name/address,
     * reply-to and sending domain it sends as on the shared platform transport.
     */
    public function updateAlias(
        Request $request,
        ReadsOrganizations $organizations,
        WritesTenantEmailAliases $aliases,
        WritesAuditLog $audit,
        string $tenant
    ): RedirectResponse {
        try {
            $rows = $organizations->all();
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.tenants.show', $tenant)
                ->withFragment('email')
                ->with('aliasError', 'The tenant could not be loaded right now. Please try again shortly.');
        }

        $org = null;
        foreach ($rows as $row) {
            if ((string) ($row['id'] ?? '') === $tenant) {
                $org = $row;
                break;
            }
        }
        if ($org === null) {
            abort(404);
        }

        $validated = $request->validate([
            'from_name' => ['nullable', 'string', 'max:120'],
            'from_address' => ['nullable', 'email', 'max:200'],
            'reply_to' => ['nullable', 'email', 'max:200'],
            'sending_domain' => ['nullable', 'string', 'max:200'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $attrs = [
            'from_name' => ($validated['from_name'] ?? '') !== '' ? $validated['from_name'] : null,
            'from_address' => ($validated['from_address'] ?? '') !== '' ? $validated['from_address'] : null,
            'reply_to' => ($validated['reply_to'] ?? '') !== '' ? $validated['reply_to'] : null,
            'sending_domain' => ($validated['sending_domain'] ?? '') !== '' ? $validated['sending_domain'] : null,
            'is_active' => $request->boolean('is_active'),
            'updated_at' => now()->toIso8601String(),
        ];

        try {
            $aliases->upsert($tenant, $attrs);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.tenants.show', $tenant)
                ->withFragment('email')
                ->with('aliasError', 'The email alias could not be saved right now. Please try again shortly.');
        }

        /** @var SupabaseUser $user */
        $user = $request->user();
        $audit->record(
            action: 'tenant_email_alias.updated',
            actorId: $user->profileId,
            organizationId: $tenant,
            entity: 'tenant_email_alias',
            entityId: $tenant,
            meta: ['is_active' => $attrs['is_active'], 'has_from' => $attrs['from_address'] !== null],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->withFragment('email')
            ->with('status', 'Email alias saved.');
    }

    /**
     * Build the email-alias editor model for a tenant: its current sender
     * identity, or empty defaults when it has none yet. Null on a read failure.
     *
     * @return array<string,mixed>|null
     */
    private function buildAlias(string $organizationId, ReadsTenantEmailAliases $aliases): ?array
    {
        try {
            $row = $aliases->forOrganization($organizationId);
        } catch (SupabaseAuthException $e) {
            report($e);

            return null;
        }

        return [
            'exists' => $row !== null,
            'from_name' => (string) ($row['from_name'] ?? ''),
            'from_address' => (string) ($row['from_address'] ?? ''),
            'reply_to' => (string) ($row['reply_to'] ?? ''),
            'sending_domain' => (string) ($row['sending_domain'] ?? ''),
            'is_active' => (bool) ($row['is_active'] ?? false),
            'is_verified' => (bool) ($row['is_verified'] ?? false),
        ];
    }

    /**
     * Build the brand-kit editor model for a tenant: every themeable token with
     * its platform default, the tenant's current override (if any), and whether
     * it is currently inheriting. Returns null if the token layer is unreachable.
     *
     * @return array<string,mixed>|null
     */
    private function buildBranding(string $organizationId, ReadsDesignTokens $designTokens, WritesBrandKits $brandKits): ?array
    {
        try {
            $tokens = $designTokens->tokens();
            $kitId = $brandKits->findPublishedDefaultKitId($organizationId);
            $overrides = $kitId !== null ? $brandKits->overrides($kitId) : [];
        } catch (SupabaseAuthException $e) {
            report($e);

            return null;
        }

        // Effective value per token (tenant override, else platform default) and
        // the inheritance map, so an inheriting token can adopt its source's value
        // for its "Default" state (e.g. the primary button follows the brand).
        $selfEffective = [];
        $inheritsFrom = [];
        $hasOwnOverride = [];
        foreach ($tokens as $t) {
            $k = (string) ($t['key'] ?? '');
            if ($k === '') {
                continue;
            }
            $ov = $overrides[$k] ?? null;
            $selfEffective[$k] = $ov ?? (string) ($t['default_value'] ?? '');
            $inheritsFrom[$k] = (string) ($t['inherits_from'] ?? '');
            $hasOwnOverride[$k] = $ov !== null;
        }
        $resolveFrom = static function (string $start) use ($selfEffective, $inheritsFrom, $hasOwnOverride): string {
            $seen = [];
            $cur = $start;
            for ($i = 0; $i < 8 && isset($selfEffective[$cur]) && ! isset($seen[$cur]); $i++) {
                $seen[$cur] = true;
                if (($hasOwnOverride[$cur] ?? false) || ($inheritsFrom[$cur] ?? '') === '' || ! isset($selfEffective[$inheritsFrom[$cur]])) {
                    break;
                }
                $cur = $inheritsFrom[$cur];
            }

            return $selfEffective[$cur] ?? '';
        };

        // The order the editor groups are presented in. Any themeable token
        // whose editor_group is not listed here (or is null) falls into "Other"
        // so nothing silently disappears from the editor.
        $groupOrder = ['Brand core', 'Surfaces', 'Buttons', 'Accent', 'Shape'];
        $grouped = [];

        $fields = [];
        foreach ($tokens as $token) {
            if (empty($token['themeable']) || ! isset($token['key'])) {
                continue;
            }
            $key = (string) $token['key'];
            $current = $overrides[$key] ?? null;
            $inh = (string) ($token['inherits_from'] ?? '');
            // The value shown in "Default" mode: the inherited source's effective
            // value when this token inherits, otherwise its own platform default.
            $default = ($inh !== '' && isset($selfEffective[$inh]))
                ? $resolveFrom($inh)
                : (string) ($token['default_value'] ?? '');
            $group = trim((string) ($token['editor_group'] ?? '')) ?: 'Other';
            $label = trim((string) ($token['label'] ?? '')) ?: trim((string) ($token['description'] ?? '')) ?: $key;

            $field = [
                'key' => $key,
                'css_var' => (string) ($token['css_var'] ?? ''),
                'type' => (string) ($token['type'] ?? 'other'),
                'label' => $label,
                'helper' => trim((string) ($token['helper'] ?? '')),
                'default' => $default,
                'current' => $current,
                'effective' => $current ?? $default,
                'inheriting' => $current === null,
                'inherits_from' => $inh,
            ];

            $fields[] = $field;
            $grouped[$group][] = $field;
        }

        // Emit groups in the defined order, appending any unlisted groups last.
        $groups = [];
        foreach ($groupOrder as $name) {
            if (! empty($grouped[$name])) {
                $groups[] = ['name' => $name, 'fields' => $grouped[$name]];
                unset($grouped[$name]);
            }
        }
        foreach ($grouped as $name => $groupFields) {
            $groups[] = ['name' => $name, 'fields' => $groupFields];
        }

        return [
            'has_overrides' => $overrides !== [],
            'fields' => $fields,
            'groups' => $groups,
        ];
    }

    /**
     * Shape the flat organisation rows into the table model, a summary, and the
     * distinct "model" options used by the table's filter.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array{0:array<int,array<string,mixed>>,1:array<string,int>,2:array<int,array{value:string,label:string}>}
     */
    private function buildTenants(array $rows): array
    {
        $nameById = [];
        $childCount = [];
        foreach ($rows as $row) {
            $nameById[(string) ($row['id'] ?? '')] = (string) ($row['name'] ?? '');
            $parentId = (string) ($row['parent_id'] ?? '');
            if ($parentId !== '') {
                $childCount[$parentId] = ($childCount[$parentId] ?? 0) + 1;
            }
        }

        $tenants = [];
        $operators = 0;
        $clients = 0;
        $userTotal = 0;
        $models = [];

        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');

            // The platform organisation itself is not a tenant in the table.
            if ($type === 'platform') {
                continue;
            }

            $id = (string) ($row['id'] ?? '');
            $users = (int) ($row['user_count'] ?? 0);
            $parentId = (string) ($row['parent_id'] ?? '');

            [$modelValue, $modelLabel] = $this->model($type, $row['operator_subtype'] ?? null, $row['subtype'] ?? null);
            if ($modelValue !== '') {
                $models[$modelValue] = $modelLabel;
            }

            if ($type === 'operator') {
                $operators++;
            } elseif ($type === 'client') {
                $clients++;
            }
            $userTotal += $users;

            $tenants[] = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'type' => $type,
                'type_label' => $type === 'operator' ? 'Operator' : 'Client',
                'model_value' => $modelValue,
                'model_label' => $modelLabel,
                'parent_id' => $parentId !== '' ? $parentId : null,
                'parent_name' => $parentId !== '' ? ($nameById[$parentId] ?? null) : null,
                'location' => $row['location'] !== null ? (string) $row['location'] : null,
                'user_count' => $users,
                'client_count' => (int) ($childCount[$id] ?? 0),
                'created_sort' => (string) ($row['created_at'] ?? ''),
                'created_label' => $this->formatDate($row['created_at'] ?? null),
            ];
        }

        usort($tenants, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        ksort($models);
        $modelOptions = [];
        foreach ($models as $value => $label) {
            $modelOptions[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return [
            $tenants,
            [
                'operators' => $operators,
                'clients' => $clients,
                'users' => $userTotal,
                'tenants' => count($tenants),
            ],
            $modelOptions,
        ];
    }

    /**
     * Build the detail model for a single tenant's configuration hub.
     *
     * @param  array<string,mixed>  $row
     * @param  array<string,array<string,mixed>>  $byId
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<string,mixed>
     */
    private function buildTenantDetail(array $row, array $byId, array $rows): array
    {
        $id = (string) ($row['id'] ?? '');
        $type = (string) ($row['type'] ?? '');
        [$modelValue, $modelLabel] = $this->model($type, $row['operator_subtype'] ?? null, $row['subtype'] ?? null);

        $parentId = (string) ($row['parent_id'] ?? '');
        $parent = null;
        if ($parentId !== '' && isset($byId[$parentId])) {
            $p = $byId[$parentId];
            $parent = [
                'id' => $parentId,
                'name' => (string) ($p['name'] ?? ''),
                'slug' => (string) ($p['slug'] ?? ''),
                'is_platform' => (string) ($p['type'] ?? '') === 'platform',
            ];
        }

        $children = [];
        foreach ($rows as $r) {
            if ((string) ($r['parent_id'] ?? '') !== $id) {
                continue;
            }
            [$cv, $cl] = $this->model((string) ($r['type'] ?? ''), $r['operator_subtype'] ?? null, $r['subtype'] ?? null);
            $children[] = [
                'id' => (string) ($r['id'] ?? ''),
                'name' => (string) ($r['name'] ?? ''),
                'model_label' => $cl,
                'location' => $r['location'] !== null ? (string) $r['location'] : null,
                'user_count' => (int) ($r['user_count'] ?? 0),
            ];
        }
        usort($children, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        $slug = (string) ($row['slug'] ?? '');
        $isOperator = $type === 'operator';
        $hasClientLayer = (bool) ($row['has_client_layer'] ?? false);

        return [
            'id' => $id,
            'name' => (string) ($row['name'] ?? ''),
            'type' => $type,
            'type_label' => $isOperator ? 'Operator' : 'Client',
            'model_value' => $modelValue,
            'model_label' => $modelLabel,
            'slug' => $slug,
            'location' => $row['location'] !== null ? (string) $row['location'] : null,
            'created_label' => $this->formatDate($row['created_at'] ?? null),
            'user_count' => (int) ($row['user_count'] ?? 0),
            'has_client_layer' => $hasClientLayer,
            'is_operator' => $isOperator,
            // Routing model (memory: operators on subdomains, clients path-based).
            'subdomain' => $isOperator && $slug !== '' ? $slug.'.bespokelms.com' : null,
            'workspace_path' => $slug !== '' ? '/'.$slug : null,
            'parent' => $parent,
            'clients' => $children,
            'client_count' => count($children),
            'brand_swatches' => $this->brandSwatches($row['brand_theme'] ?? null),
        ];
    }

    /**
     * Map an organisation's type + subtype to a filter value and display label.
     *
     * @return array{0:string,1:string}
     */
    private function model(string $type, mixed $operatorSubtype, mixed $subtype): array
    {
        $raw = $type === 'operator' ? (string) ($operatorSubtype ?? '') : (string) ($subtype ?? '');

        $label = match ($raw) {
            'reseller' => 'Reseller',
            'inhouse' => 'In-house',
            'own_brand' => 'Own brand',
            'school' => 'School',
            'trust' => 'Trust',
            'college' => 'College',
            'nursery' => 'Nursery',
            '' => '—',
            default => Str::of($raw)->replace('_', ' ')->title()->toString(),
        };

        return [$raw, $label];
    }

    /**
     * Extract hex-colour swatches from a tenant's brand_theme jsonb, whatever
     * its shape. Returns an empty array when no brand kit is configured.
     *
     * @return array<int,array{label:string,value:string}>
     */
    private function brandSwatches(mixed $brandTheme): array
    {
        if (! is_array($brandTheme)) {
            return [];
        }

        $swatches = [];
        $walk = function (array $node, string $prefix) use (&$walk, &$swatches): void {
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $walk($value, $prefix === '' ? (string) $key : $prefix.' '.$key);
                    continue;
                }
                if (is_string($value) && preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value) === 1) {
                    $label = trim($prefix === '' ? (string) $key : $prefix.' '.$key);
                    $swatches[] = [
                        'label' => Str::of($label)->replace(['_', '-'], ' ')->title()->toString(),
                        'value' => $value,
                    ];
                }
            }
        };
        $walk($brandTheme, '');

        return array_slice($swatches, 0, 24);
    }

    private function formatDate(mixed $raw): string
    {
        if (! is_string($raw) || $raw === '') {
            return '—';
        }

        try {
            return Carbon::parse($raw)->format('j M Y');
        } catch (\Throwable) {
            return substr($raw, 0, 10);
        }
    }
}
