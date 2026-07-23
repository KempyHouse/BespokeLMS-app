<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\ReadsDesignTokens;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a brand-kit save from the tenant configuration hub.
 *
 * Only tokens flagged `themeable` in the design-token contract may be set, and
 * each value is validated against its token type (colour → hex; dimension /
 * radius → a number with a CSS unit). The `inherit` map marks tokens to clear
 * back to the platform default.
 */
final class UpdateTenantBrandingRequest extends FormRequest
{
    /** @var array<string,string> token_key => type, memoised */
    private array $themeable;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof SupabaseUser && $user->isPlatformOwner();
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $rules = [
            'tokens' => ['sometimes', 'array'],
            'inherit' => ['sometimes', 'array'],
        ];

        foreach ($this->themeableTokens() as $key => $type) {
            $rules['tokens.'.$key] = $type === 'color'
                ? ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/']
                : ['nullable', 'string', 'regex:/^[0-9]+(\.[0-9]+)?(px|rem|em|%)$/'];
        }

        return $rules;
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'tokens.*.regex' => 'That value is not a valid colour (e.g. #009de1) or size (e.g. 0.85rem).',
        ];
    }

    /**
     * The themeable token contract as token_key => type. Best-effort: an
     * unreachable contract yields no per-token rules (the controller then has
     * nothing valid to persist).
     *
     * @return array<string,string>
     */
    public function themeableTokens(): array
    {
        if (isset($this->themeable)) {
            return $this->themeable;
        }

        $this->themeable = [];

        try {
            $tokens = app(ReadsDesignTokens::class)->tokens();
        } catch (SupabaseAuthException) {
            return $this->themeable;
        }

        foreach ($tokens as $token) {
            if (! empty($token['themeable']) && isset($token['key'])) {
                $this->themeable[(string) $token['key']] = (string) ($token['type'] ?? 'other');
            }
        }

        return $this->themeable;
    }
}
