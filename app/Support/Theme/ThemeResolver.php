<?php

declare(strict_types=1);

namespace App\Support\Theme;

use App\Support\Supabase\Contracts\ReadsDesignTokens;
use Illuminate\Contracts\Cache\Repository as Cache;
use Throwable;

/**
 * Resolves the effective design tokens for a request into a CSS custom-property
 * declaration string, ready to inject as `:root { … }`.
 *
 * Resolution = the platform default value of every token (from `design_tokens`)
 * merged with the current tenant's published brand-kit overrides (themeable
 * tokens only). The result is emitted as CSS variables so the same token-driven
 * components reskin per tenant. Supabase is therefore the source of truth for
 * styling values at runtime; the compiled Tailwind `@theme` block provides the
 * token NAMES/utilities and a safe fallback if the database is unreachable.
 *
 * Every path is defensive: any failure yields an empty string, in which case no
 * override is injected and the compiled theme defaults render unchanged.
 */
final class ThemeResolver
{
    private const CACHE_TOKENS = 'design_tokens.contract.v2';
    private const CACHE_TOKENS_TTL = 3600; // 1 hour
    private const CACHE_OVERRIDES_TTL = 600; // 10 minutes

    public function __construct(
        private readonly ReadsDesignTokens $tokens,
        private readonly Cache $cache,
    ) {
    }

    /**
     * Build the `:root` declaration body (e.g. "--color-teachhq:#009de1;…") for
     * the given organisation, or an empty string on any problem.
     */
    /**
     * Resolve the effective tokens into light and dark CSS declaration strings,
     * ready to inject as `:root{…}` and `[data-theme='dark']{…}`.
     *
     * Light = platform defaults merged with the tenant's brand-kit overrides.
     * Dark  = the token's dark_value, EXCEPT a tenant's themeable brand override
     * carries into dark too, so a tenant's brand colour stays consistent in both.
     *
     * @return array{light:string,dark:string}
     */
    public function resolve(?string $organizationId): array
    {
        try {
            $contract = $this->cache->remember(
                self::CACHE_TOKENS,
                self::CACHE_TOKENS_TTL,
                fn (): array => $this->tokens->tokens(),
            );

            if ($contract === []) {
                return ['light' => '', 'dark' => ''];
            }

            $overrides = $organizationId !== null && $organizationId !== ''
                ? $this->cache->remember(
                    'brand_kit.overrides.'.$organizationId.'.v1',
                    self::CACHE_OVERRIDES_TTL,
                    fn (): array => $this->tokens->overrideRowsForOrg($organizationId),
                )
                : [];

            $overrideByKey = [];
            foreach ($overrides as $row) {
                $k = (string) ($row['token_key'] ?? '');
                $v = $this->sanitiseValue((string) ($row['value'] ?? ''));
                if ($k !== '' && $v !== '') {
                    $overrideByKey[$k] = $v;
                }
            }

            // Pass 1: each token's own ("self") value = tenant override, else the
            // platform default (light) / dark_value (dark), plus its metadata.
            $meta = [];
            $selfLight = [];
            $selfDark = [];
            foreach ($contract as $token) {
                $key = (string) ($token['key'] ?? '');
                $cssVar = (string) ($token['css_var'] ?? '');
                if ($key === '' || ! $this->isSafeVar($cssVar)) {
                    continue;
                }
                $themeable = (bool) ($token['themeable'] ?? false);
                $default = $this->sanitiseValue((string) ($token['default_value'] ?? ''));
                $darkValue = $this->sanitiseValue((string) ($token['dark_value'] ?? ''));
                $hasOverride = $themeable && isset($overrideByKey[$key]);
                $tenant = $hasOverride ? $overrideByKey[$key] : null;

                $meta[$key] = [
                    'cssVar' => $cssVar,
                    'inherits' => (string) ($token['inherits_from'] ?? ''),
                    'override' => $hasOverride,
                ];
                $selfLight[$key] = $tenant ?? $default;
                $selfDark[$key] = $tenant ?? ($darkValue !== '' ? $darkValue : null);
            }

            // Pass 2: for a token with no override of its own, walk up the
            // inherits_from chain to the first ancestor that is overridden or does
            // not inherit, and adopt that ancestor's value (so e.g. the primary
            // button follows the brand colour unless it is set explicitly).
            $resolveKey = static function (string $key, array $self) use ($meta) {
                $seen = [];
                $cur = $key;
                for ($i = 0; $i < 8 && isset($meta[$cur]) && ! isset($seen[$cur]); $i++) {
                    $seen[$cur] = true;
                    $m = $meta[$cur];
                    if ($m['override'] || $m['inherits'] === '' || ! isset($meta[$m['inherits']])) {
                        break;
                    }
                    $cur = $m['inherits'];
                }

                return $self[$cur] ?? null;
            };

            $light = [];
            $dark = [];
            foreach ($meta as $key => $m) {
                $lightVal = $resolveKey($key, $selfLight);
                if (is_string($lightVal) && $lightVal !== '') {
                    $light[$m['cssVar']] = $lightVal;
                }
                $darkVal = $resolveKey($key, $selfDark);
                if (is_string($darkVal) && $darkVal !== '') {
                    $dark[$m['cssVar']] = $darkVal;
                }
            }

            return [
                'light' => $this->declarations($light),
                'dark' => $this->declarations($dark),
            ];
        } catch (Throwable $e) {
            report($e);

            return ['light' => '', 'dark' => ''];
        }
    }

    /**
     * @param  array<string,string>  $map
     */
    private function declarations(array $map): string
    {
        $out = '';
        foreach ($map as $cssVar => $value) {
            if ($value !== '') {
                $out .= $cssVar.':'.$value.';';
            }
        }

        return $out;
    }

    /**
     * Forget the cached brand-kit overrides for an organisation so the next
     * request re-resolves them. Call after saving a tenant's brand kit.
     */
    public function flushOrg(string $organizationId): void
    {
        if ($organizationId !== '') {
            $this->cache->forget('brand_kit.overrides.'.$organizationId.'.v1');
        }
    }

    /**
     * A CSS custom-property name must look like `--foo-bar`.
     */
    private function isSafeVar(string $var): bool
    {
        return preg_match('/^--[a-z0-9-]+$/i', $var) === 1;
    }

    /**
     * Strip characters that could break out of the `<style>` / declaration
     * context. Token values are owner-controlled, but this is defence in depth.
     */
    private function sanitiseValue(string $value): string
    {
        $value = str_replace(['<', '>', '{', '}', ';', '\\', '@'], '', $value);
        // Collapse whitespace/newlines to single spaces.
        $value = (string) preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}
