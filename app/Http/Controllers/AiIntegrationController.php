<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsAiIntegrations;
use App\Support\Supabase\Contracts\WritesAiIntegrations;
use App\Support\Supabase\Contracts\WritesAuditLog;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

/**
 * Platform-owner AI integrations console.
 *
 * AI providers are configured once here, at the owner level (rows with a NULL
 * organization_id), and inherited by every tenant. The area is owner-gated by
 * the "platform.owner" route middleware and independently by Supabase RLS; the
 * sensitive writes are additionally protected by step-up re-auth ("platform.sudo").
 *
 * API keys are encrypted with the application key (Laravel Crypt) before they
 * are persisted to `api_key_cipher`, and are never read back into the UI — the
 * page only ever knows whether a key is set. Every change is written to the
 * audit trail.
 */
final class AiIntegrationController extends Controller
{
    /**
     * Static, presentation-only metadata per provider (capability, help copy,
     * suggested models, key hint, docs). The provider *rows* are the source of
     * truth for state; this only decorates them.
     *
     * @var array<string,array<string,mixed>>
     */
    private const PROVIDERS = [
        'anthropic' => [
            'capability' => 'text',
            'tagline' => 'Claude models for course authoring, summarising and chat.',
            'models' => ['claude-opus-4-8', 'claude-sonnet-4-5', 'claude-haiku-4-5'],
            'key_hint' => 'sk-ant-…',
            'needs_base_url' => false,
            'docs' => 'https://console.anthropic.com/',
        ],
        'openai' => [
            'capability' => 'text',
            'tagline' => 'GPT models as an alternative text provider.',
            'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4.1'],
            'key_hint' => 'sk-…',
            'needs_base_url' => false,
            'docs' => 'https://platform.openai.com/api-keys',
        ],
        'azure_openai' => [
            'capability' => 'text',
            'tagline' => 'OpenAI models hosted in your own Azure tenancy.',
            'models' => [],
            'key_hint' => 'Azure API key',
            'needs_base_url' => true,
            'docs' => 'https://learn.microsoft.com/azure/ai-services/openai/',
        ],
        'custom' => [
            'capability' => 'text',
            'tagline' => 'Any OpenAI-compatible endpoint you self-host.',
            'models' => [],
            'key_hint' => 'Bearer token',
            'needs_base_url' => true,
            'docs' => null,
        ],
        'elevenlabs' => [
            'capability' => 'voice',
            'tagline' => 'On-the-fly voice-over generation for course content.',
            'models' => ['eleven_multilingual_v2', 'eleven_turbo_v2_5', 'eleven_flash_v2_5'],
            'key_hint' => 'xi-api-key',
            'needs_base_url' => false,
            'docs' => 'https://elevenlabs.io/app/settings/api-keys',
        ],
    ];

    public function index(Request $request, ReadsAiIntegrations $reads): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $error = null;
        $groups = ['text' => [], 'voice' => []];

        try {
            $since = now()->startOfMonth()->toIso8601String();
            $rows = $reads->all();
            $usage = $reads->usageSince($since);
        } catch (SupabaseAuthException $e) {
            report($e);
            $rows = [];
            $usage = [];
            $error = 'The AI integrations could not be loaded right now. Please try again shortly.';
        }

        foreach ($rows as $row) {
            $provider = (string) ($row['provider'] ?? '');
            $meta = self::PROVIDERS[$provider] ?? ['capability' => 'text', 'tagline' => '', 'models' => [], 'key_hint' => 'API key', 'needs_base_url' => false, 'docs' => null];
            $id = (string) ($row['id'] ?? '');
            $options = is_array($row['options'] ?? null) ? $row['options'] : [];
            $capability = (string) ($options['capability'] ?? $meta['capability']);

            $card = [
                'id' => $id,
                'provider' => $provider,
                'display_name' => (string) ($row['display_name'] ?? $provider),
                'is_enabled' => (bool) ($row['is_enabled'] ?? false),
                'has_key' => (bool) ($row['has_key'] ?? false),
                'default_model' => (string) ($row['default_model'] ?? ''),
                'base_url' => (string) ($row['base_url'] ?? ''),
                'status' => (string) ($row['status'] ?? 'unconfigured'),
                'monthly_token_limit' => $options['monthly_token_limit'] ?? null,
                'monthly_budget_usd' => $options['monthly_budget_usd'] ?? null,
                'meta' => $meta,
                'usage' => $usage[$id] ?? ['calls' => 0, 'tokens_in' => 0, 'tokens_out' => 0],
            ];

            $bucket = $capability === 'voice' ? 'voice' : 'text';
            $groups[$bucket][] = $card;
        }

        return view('platform.ai.index', [
            'user' => $user,
            'groups' => $groups,
            'error' => $error,
            'monthLabel' => now()->format('F Y'),
        ]);
    }

    public function update(
        Request $request,
        ReadsAiIntegrations $reads,
        WritesAuditLog $audit,
        string $integration
    ): RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        // Resolve the writer from the container rather than method-injecting it:
        // SupabaseAiIntegrations satisfies both the Reads and Writes contracts, so
        // Laravel's route-dependency resolver injects that shared instance only once —
        // a second same-instance parameter would otherwise be filled by the route id
        // (a string), which is the "$writes ... string given" TypeError. Resolving here
        // sidesteps that.
        $writes = app(WritesAiIntegrations::class);

        // Confirm the id is an owner-level integration before writing.
        try {
            $rows = $reads->all();
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.ai')->with('aiError', 'The AI integrations could not be loaded right now. Please try again shortly.');
        }

        $current = null;
        foreach ($rows as $row) {
            if ((string) ($row['id'] ?? '') === $integration) {
                $current = $row;
                break;
            }
        }
        if ($current === null) {
            abort(404);
        }

        $validated = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'default_model' => ['nullable', 'string', 'max:120'],
            'base_url' => ['nullable', 'url', 'max:300'],
            'api_key' => ['nullable', 'string', 'max:400'],
            'remove_key' => ['sometimes', 'boolean'],
            'monthly_token_limit' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'monthly_budget_usd' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        // Merge usage controls into the existing options (preserve capability etc).
        $options = is_array($current['options'] ?? null) ? $current['options'] : [];
        $options['monthly_token_limit'] = ($validated['monthly_token_limit'] ?? null) !== null ? (int) $validated['monthly_token_limit'] : null;
        $options['monthly_budget_usd'] = ($validated['monthly_budget_usd'] ?? null) !== null ? (float) $validated['monthly_budget_usd'] : null;

        $attrs = [
            'is_enabled' => $request->boolean('is_enabled'),
            'default_model' => ($validated['default_model'] ?? '') !== '' ? $validated['default_model'] : null,
            'base_url' => ($validated['base_url'] ?? '') !== '' ? $validated['base_url'] : null,
            'options' => $options,
        ];

        // Key handling: a submitted key is encrypted server-side; an explicit
        // remove clears it; otherwise the stored key is left untouched.
        $hasKey = (bool) ($current['has_key'] ?? false);
        $keyChanged = false;
        $keyRemoved = false;
        if ($request->boolean('remove_key')) {
            $attrs['api_key_cipher'] = null;
            $hasKey = false;
            $keyRemoved = true;
        } elseif (($validated['api_key'] ?? '') !== '') {
            $attrs['api_key_cipher'] = Crypt::encryptString((string) $validated['api_key']);
            $hasKey = true;
            $keyChanged = true;
        }

        // Status reflects configuration: needs a key to be "connected".
        if (! $hasKey) {
            $attrs['status'] = 'unconfigured';
        } else {
            $attrs['status'] = $attrs['is_enabled'] ? 'connected' : 'disabled';
        }

        try {
            $writes->update($integration, $attrs);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.ai')->with('aiError', 'That integration could not be saved right now. Please try again shortly.');
        }

        $audit->record(
            action: 'ai_integration.updated',
            actorId: $user->profileId,
            organizationId: $user->organizationId,
            entity: 'ai_integration',
            entityId: $integration,
            meta: [
                'provider' => (string) ($current['provider'] ?? ''),
                'is_enabled' => $attrs['is_enabled'],
                'status' => $attrs['status'],
                'key_changed' => $keyChanged,
                'key_removed' => $keyRemoved,
            ],
        );

        return redirect()->route('platform.ai')->with('status', trim((string) ($current['display_name'] ?? 'Integration')).' saved.');
    }
}
