<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsEmailIntegrations;
use App\Support\Supabase\Contracts\WritesEmailIntegrations;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

/**
 * Platform-owner email transport console.
 *
 * Email providers are configured once here, at the owner level (rows with a
 * NULL organization_id), and inherited by every tenant — the enabled row is the
 * platform transport, so swapping Resend for another provider is a matter of
 * enabling a different card. The area is owner-gated by the "platform.owner"
 * route middleware and independently by Supabase RLS.
 *
 * The API secret is encrypted with the application key (Laravel Crypt) before it
 * is persisted to `api_key_cipher`, and is never read back into the UI — the
 * page only ever knows whether a secret is set. Per-tenant sender identities
 * ("aliases") are configured separately, on each tenant's admin console.
 */
final class EmailIntegrationController extends Controller
{
    /**
     * Static, presentation-only metadata per provider (help copy, key hint,
     * whether a host/endpoint is needed, docs). The provider *rows* are the
     * source of truth for state; this only decorates them.
     *
     * @var array<string,array<string,mixed>>
     */
    private const PROVIDERS = [
        'resend' => [
            'tagline' => 'API-first email for transactional and product mail.',
            'key_hint' => 're_…',
            'secret_label' => 'API key',
            'needs_host' => false,
            'needs_region' => false,
            'docs' => 'https://resend.com/api-keys',
        ],
        'postmark' => [
            'tagline' => 'High-deliverability transactional email.',
            'key_hint' => 'Server API token',
            'secret_label' => 'Server token',
            'needs_host' => false,
            'needs_region' => false,
            'docs' => 'https://account.postmarkapp.com/servers',
        ],
        'ses' => [
            'tagline' => 'Amazon SES — send from your own AWS account.',
            'key_hint' => 'AWS secret access key',
            'secret_label' => 'Secret access key',
            'needs_host' => false,
            'needs_region' => true,
            'docs' => 'https://docs.aws.amazon.com/ses/',
        ],
        'smtp' => [
            'tagline' => 'Any SMTP relay — host, port and credentials.',
            'key_hint' => 'SMTP password',
            'secret_label' => 'SMTP password',
            'needs_host' => true,
            'needs_region' => false,
            'docs' => null,
        ],
        'custom' => [
            'tagline' => 'A self-hosted or bespoke sending endpoint.',
            'key_hint' => 'Bearer token',
            'secret_label' => 'Secret / token',
            'needs_host' => true,
            'needs_region' => false,
            'docs' => null,
        ],
    ];

    public function index(Request $request, ReadsEmailIntegrations $reads): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $error = null;
        $cards = [];

        try {
            $rows = $reads->all();
        } catch (SupabaseAuthException $e) {
            report($e);
            $rows = [];
            $error = 'The email integrations could not be loaded right now. Please try again shortly.';
        }

        foreach ($rows as $row) {
            $provider = (string) ($row['provider'] ?? '');
            $meta = self::PROVIDERS[$provider] ?? ['tagline' => '', 'key_hint' => 'Secret', 'secret_label' => 'Secret', 'needs_host' => false, 'needs_region' => false, 'docs' => null];
            $options = is_array($row['options'] ?? null) ? $row['options'] : [];

            $cards[] = [
                'id' => (string) ($row['id'] ?? ''),
                'provider' => $provider,
                'display_name' => (string) ($row['display_name'] ?? $provider),
                'is_enabled' => (bool) ($row['is_enabled'] ?? false),
                'has_key' => (bool) ($row['has_key'] ?? false),
                'from_address' => (string) ($row['from_address'] ?? ''),
                'from_name' => (string) ($row['from_name'] ?? ''),
                'reply_to' => (string) ($row['reply_to'] ?? ''),
                'base_url' => (string) ($row['base_url'] ?? ''),
                'status' => (string) ($row['status'] ?? 'unconfigured'),
                'sending_domain' => (string) ($options['sending_domain'] ?? ''),
                'region' => (string) ($options['region'] ?? ''),
                'smtp_port' => $options['smtp_port'] ?? null,
                'smtp_username' => (string) ($options['smtp_username'] ?? ''),
                'meta' => $meta,
            ];
        }

        return view('platform.email.index', [
            'user' => $user,
            'cards' => $cards,
            'error' => $error,
        ]);
    }

    public function update(
        Request $request,
        ReadsEmailIntegrations $reads,
        WritesEmailIntegrations $writes,
        string $integration
    ): RedirectResponse {
        // Confirm the id is an owner-level integration before writing.
        try {
            $rows = $reads->all();
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.email')->with('emailError', 'The email integrations could not be loaded right now. Please try again shortly.');
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
            'from_address' => ['nullable', 'email', 'max:200'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'reply_to' => ['nullable', 'email', 'max:200'],
            'base_url' => ['nullable', 'string', 'max:300'],
            'api_key' => ['nullable', 'string', 'max:400'],
            'remove_key' => ['sometimes', 'boolean'],
            'sending_domain' => ['nullable', 'string', 'max:200'],
            'region' => ['nullable', 'string', 'max:60'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:200'],
        ]);

        // Merge sending options into the existing options bag (preserve anything
        // set out of band). Empty values are cleared so the bag stays tidy.
        $options = is_array($current['options'] ?? null) ? $current['options'] : [];
        foreach (['sending_domain', 'region', 'smtp_username'] as $key) {
            $value = trim((string) ($validated[$key] ?? ''));
            if ($value !== '') {
                $options[$key] = $value;
            } else {
                unset($options[$key]);
            }
        }
        if (($validated['smtp_port'] ?? null) !== null) {
            $options['smtp_port'] = (int) $validated['smtp_port'];
        } else {
            unset($options['smtp_port']);
        }

        $attrs = [
            'is_enabled' => $request->boolean('is_enabled'),
            'from_address' => ($validated['from_address'] ?? '') !== '' ? $validated['from_address'] : null,
            'from_name' => ($validated['from_name'] ?? '') !== '' ? $validated['from_name'] : null,
            'reply_to' => ($validated['reply_to'] ?? '') !== '' ? $validated['reply_to'] : null,
            'base_url' => ($validated['base_url'] ?? '') !== '' ? $validated['base_url'] : null,
            'options' => $options,
            'updated_at' => now()->toIso8601String(),
        ];

        // Secret handling: a submitted secret is encrypted server-side; an
        // explicit remove clears it; otherwise the stored secret is untouched.
        $hasKey = (bool) ($current['has_key'] ?? false);
        if ($request->boolean('remove_key')) {
            $attrs['api_key_cipher'] = null;
            $hasKey = false;
        } elseif (($validated['api_key'] ?? '') !== '') {
            $attrs['api_key_cipher'] = Crypt::encryptString((string) $validated['api_key']);
            $hasKey = true;
        }

        // Status reflects configuration: a transport needs a secret to be usable.
        if (! $hasKey) {
            $attrs['status'] = 'unconfigured';
        } else {
            $attrs['status'] = $attrs['is_enabled'] ? 'connected' : 'disabled';
        }

        try {
            $writes->update($integration, $attrs);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.email')->with('emailError', 'That integration could not be saved right now. Please try again shortly.');
        }

        return redirect()->route('platform.email')->with('status', trim((string) ($current['display_name'] ?? 'Integration')).' saved.');
    }
}
