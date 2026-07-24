<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Support\Supabase\Contracts\ReadsDesignTokens;
use Throwable;

/**
 * Renders an outbound template (subject + body) into a complete, inline-styled
 * HTML email wrapped in a brand-tokened shell.
 *
 * Brand colours come from the Supabase design tokens — the same source that
 * feeds the app's `$brandTokensCss` (platform defaults merged with a tenant's
 * published brand kit). Any failure resolving tokens falls back to the platform
 * defaults so an email is always produced. Placeholders of the form
 * `{{ variable }}` are substituted from the supplied values.
 */
final class BrandedEmailRenderer
{
    private const DEFAULT_PRIMARY = '#009de1';

    public function __construct(private readonly ReadsDesignTokens $tokens)
    {
    }

    /**
     * @param  array<string,string>  $vars
     * @return array{0:string,1:string}  [subject, html]
     */
    public function render(string $subjectTemplate, string $bodyTemplate, array $vars, ?string $organizationId = null): array
    {
        $primary = $this->primaryColor($organizationId);

        $vars = array_merge([
            'app_name' => 'BespokeLMS',
            'brand_primary' => $primary,
        ], $vars);

        $subject = $this->substitute($subjectTemplate, $vars);
        $body = $this->substitute($bodyTemplate, $vars);
        $html = $this->wrap($body, $primary, (string) $vars['app_name']);

        return [$subject, $html];
    }

    /**
     * @param  array<string,string>  $vars
     */
    private function substitute(string $template, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', static function (array $m) use ($vars): string {
            return array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : $m[0];
        }, $template) ?? $template;
    }

    private function wrap(string $bodyHtml, string $primary, string $appName): string
    {
        $name = e($appName);
        $year = date('Y');

        return '<!doctype html>'
            .'<html lang="en"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>'.$name.'</title></head>'
            .'<body style="margin:0;padding:0;background:#f1f5f9;">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">'
            .'<tr><td align="center">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">'
            .'<tr><td style="background:'.$primary.';padding:20px 32px;">'
            .'<span style="font-size:18px;font-weight:800;color:#ffffff;letter-spacing:-0.01em;">'.$name.'</span>'
            .'</td></tr>'
            .'<tr><td style="padding:32px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">'
            .$bodyHtml
            .'</td></tr>'
            .'<tr><td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;">'
            .'<p style="margin:0;font-size:12px;line-height:1.5;color:#94a3b8;">Sent by '.$name.' &middot; &copy; '.$year.'</p>'
            .'</td></tr>'
            .'</table></td></tr></table></body></html>';
    }

    private function primaryColor(?string $organizationId): string
    {
        $primary = self::DEFAULT_PRIMARY;

        try {
            $values = [];
            foreach ($this->tokens->tokens() as $token) {
                if (isset($token['key'])) {
                    $values[(string) $token['key']] = (string) ($token['default_value'] ?? '');
                }
            }

            if ($organizationId !== null && $organizationId !== '') {
                foreach ($this->tokens->overrideRowsForOrg($organizationId) as $override) {
                    if (isset($override['token_key'])) {
                        $values[(string) $override['token_key']] = (string) ($override['value'] ?? '');
                    }
                }
            }

            $resolved = $values['color-button-primary'] ?? $values['color-brand-primary'] ?? '';
            if ($resolved !== '') {
                $primary = $resolved;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $primary;
    }
}
