<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Support\Mail\BrandedEmailRenderer;
use App\Support\Supabase\Contracts\ReadsOutboundTemplates;
use App\Support\Supabase\Contracts\WritesAuditLog;
use App\Support\Supabase\Contracts\WritesOutboundTemplates;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform → Outbound.
 *
 * The platform-owner communications hub. Phase 1 ships the navigation plus the
 * System Emails area, with the forgot-password template as the first editable,
 * brand-styled template. Other areas (transactional / marketing, SMS, WhatsApp,
 * social, notifications, score messages, logs) are scaffolded and marked
 * "Soon" in the sub-rail. Owner-gated by the "platform.owner" route middleware;
 * saves add the "platform.sudo" step-up.
 */
final class OutboundController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('platform.outbound.system-emails');
    }

    public function systemEmails(Request $request, ReadsOutboundTemplates $templates): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $error = null;
        $rows = [];
        try {
            foreach ($templates->platformAll() as $t) {
                if (($t['channel'] ?? null) === 'email' && ($t['category'] ?? null) === 'system') {
                    $rows[] = $t;
                }
            }
        } catch (SupabaseAuthException $e) {
            report($e);
            $error = 'The system email templates could not be loaded right now. Please try again shortly.';
        }

        return view('platform.outbound.system-emails.index', [
            'user' => $user,
            'templates' => $rows,
            'error' => $error,
        ]);
    }

    public function editSystemEmail(Request $request, ReadsOutboundTemplates $templates, BrandedEmailRenderer $renderer, string $key): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        try {
            $template = $templates->platformFind('email', $key);
        } catch (SupabaseAuthException $e) {
            report($e);
            abort(503, 'The template could not be loaded right now. Please try again shortly.');
        }

        if ($template === null || ($template['category'] ?? null) !== 'system') {
            abort(404);
        }

        [$previewSubject, $previewHtml] = $renderer->render(
            (string) ($template['subject'] ?? ''),
            (string) ($template['body_html'] ?? ''),
            $this->exampleVars($template),
            null,
        );

        return view('platform.outbound.system-emails.edit', [
            'user' => $user,
            'template' => $template,
            'previewSubject' => $previewSubject,
            'previewHtml' => $previewHtml,
        ]);
    }

    public function updateSystemEmail(Request $request, ReadsOutboundTemplates $templates, WritesAuditLog $audit, string $key): RedirectResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        // Resolve the writer from the container rather than method-injecting it:
        // SupabaseOutboundTemplates satisfies both the Reads and Writes contracts,
        // so Laravel's route-dependency resolver injects that shared instance only
        // once — a second same-instance parameter would be filled by the route
        // {key} (a string), the "$writes ... string given" TypeError. Resolving
        // here sidesteps that.
        $writes = app(WritesOutboundTemplates::class);

        try {
            $template = $templates->platformFind('email', $key);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.outbound.system-emails')
                ->with('error', 'The template could not be loaded right now. Please try again shortly.');
        }

        if ($template === null || ($template['category'] ?? null) !== 'system') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:200'],
            'body_html' => ['required', 'string', 'max:20000'],
        ]);

        try {
            $writes->update((string) ($template['id'] ?? ''), [
                'name' => $validated['name'],
                'subject' => $validated['subject'],
                'body_html' => $validated['body_html'],
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('platform.outbound.system-emails.edit', ['key' => $key])
                ->with('error', 'The template could not be saved right now. Please try again shortly.');
        }

        $audit->record(
            action: 'outbound_template.updated',
            actorId: $user->profileId,
            organizationId: $user->organizationId,
            entity: 'outbound_template',
            entityId: (string) ($template['id'] ?? ''),
            meta: ['key' => $key, 'channel' => 'email', 'category' => 'system'],
        );

        return redirect()->route('platform.outbound.system-emails.edit', ['key' => $key])
            ->with('status', (string) $validated['name'].' saved.');
    }

    /**
     * Example substitutions for the preview, taken from the template's declared
     * variables ({key, label, example}).
     *
     * @param  array<string,mixed>  $template
     * @return array<string,string>
     */
    private function exampleVars(array $template): array
    {
        $vars = [];
        foreach ((array) ($template['variables'] ?? []) as $v) {
            if (is_array($v) && isset($v['key'])) {
                $vars[(string) $v['key']] = (string) ($v['example'] ?? '');
            }
        }

        return $vars;
    }
}
