<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the course pricing & retake/retry editor (migration 007,
 * `course_pricing`). Authorisation is handled by the `platform.owner` route
 * middleware, so this request authorises any caller that reaches it.
 *
 * The retake/retry fields are entered as an inherit-or-override pair per row:
 * a `*_mode` selector ("inherit" | "unlimited" | "none" | "limited") plus, for
 * "limited", an integer count. {@see pricingFields()} maps these to the stored
 * columns using the migration's convention (null = inherit, -1 = unlimited).
 */
final class UpdateCoursePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'pricing_type'             => ['required', 'in:free,one_off,credits,included_in_subscription,pay_as_you_go'],
            'price'                    => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'currency'                 => ['required', 'string', 'size:3'],
            'credit_cost'              => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'included_in_subscription' => ['nullable', 'boolean'],

            // Attempts to PASS the assessment: inherit | unlimited | limited(N).
            'retry_mode'               => ['required', 'in:inherit,unlimited,limited'],
            'retry_limit'              => ['nullable', 'integer', 'min:0', 'max:1000', 'required_if:retry_mode,limited'],

            // Retakes AFTER a pass: inherit | none | unlimited | limited(N).
            'retake_mode'              => ['required', 'in:inherit,none,unlimited,limited'],
            'retake_limit'             => ['nullable', 'integer', 'min:0', 'max:1000', 'required_if:retake_mode,limited'],

            // PAYG: does access close once the learner passes? inherit | yes | no.
            'revoke_mode'              => ['required', 'in:inherit,yes,no'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'included_in_subscription' => $this->boolean('included_in_subscription'),
            'currency' => strtoupper(trim((string) $this->input('currency', 'GBP'))),
        ]);
    }

    /**
     * The validated input shaped into the `course_pricing` column set. Money is
     * captured in major units on the form and stored as integer pennies. The
     * retake/retry columns follow the migration convention: null = inherit the
     * pricing_defaults value, -1 = unlimited, N = an explicit limit.
     *
     * @return array<string,mixed>
     */
    public function pricingFields(): array
    {
        $v = $this->validated();
        $type = (string) $v['pricing_type'];

        // Money only applies to one_off / pay_as_you_go; credits only to credits.
        $price = $v['price'] ?? null;
        $pricePennies = ($price !== null && $price !== '' && in_array($type, ['one_off', 'pay_as_you_go'], true))
            ? (int) round(((float) $price) * 100)
            : null;

        $creditCost = ($type === 'credits') ? ($v['credit_cost'] ?? null) : null;

        return [
            'pricing_type'             => $type,
            'price_pennies'            => $pricePennies,
            'currency'                 => (string) $v['currency'],
            'credit_cost'              => $creditCost,
            'included_in_subscription' => $type === 'included_in_subscription'
                ? true
                : (bool) ($v['included_in_subscription'] ?? false),

            'assessment_retry_limit'   => $this->modeToLimit(
                (string) $v['retry_mode'],
                $v['retry_limit'] ?? null,
            ),
            'retake_after_pass'        => $this->retakeMode((string) $v['retake_mode']),
            'retake_limit'             => ((string) $v['retake_mode'] === 'limited')
                ? (int) ($v['retake_limit'] ?? 0)
                : null,
            'access_revoked_on_pass'   => $this->revokeMode((string) $v['revoke_mode']),
        ];
    }

    /**
     * inherit => null, unlimited => -1, limited => the entered integer.
     */
    private function modeToLimit(string $mode, mixed $limit): ?int
    {
        return match ($mode) {
            'unlimited' => -1,
            'limited'   => (int) $limit,
            default     => null, // inherit
        };
    }

    /**
     * The retake_after_pass enum column: inherit stays null, the rest map 1:1.
     */
    private function retakeMode(string $mode): ?string
    {
        return match ($mode) {
            'none'      => 'none',
            'unlimited' => 'unlimited',
            'limited'   => 'limited',
            default     => null, // inherit
        };
    }

    /**
     * access_revoked_on_pass: inherit stays null, yes/no map to the boolean.
     */
    private function revokeMode(string $mode): ?bool
    {
        return match ($mode) {
            'yes'   => true,
            'no'    => false,
            default => null, // inherit
        };
    }
}
