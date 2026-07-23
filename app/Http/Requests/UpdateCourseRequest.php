<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Validates the course "details" editor (catalogue / media / SEO / certification).
 * Authorisation is handled by the `platform.owner` route middleware, so this
 * request authorises any caller that reaches it.
 */
final class UpdateCourseRequest extends FormRequest
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
            'title'                 => ['required', 'string', 'max:300'],
            'slug'                  => ['required', 'string', 'max:300', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'catalog_status'        => ['required', 'in:published,coming_soon,retired'],
            'content_type'          => ['required', 'in:native,scorm,mixed'],
            'category_id'           => ['nullable', 'uuid'],
            'hero_image_alt'        => ['nullable', 'string', 'max:300'],
            'trailer_url'           => ['nullable', 'url', 'max:2048'],
            'duration_min'          => ['nullable', 'integer', 'min:0', 'max:100000'],
            'cpd_points'            => ['nullable', 'numeric', 'min:0'],
            'cpd_body'              => ['nullable', 'string', 'max:200'],
            'meta_title'            => ['nullable', 'string', 'max:200'],
            'meta_description'      => ['nullable', 'string', 'max:400'],
            'meta_keywords'         => ['nullable', 'string', 'max:400'],
            'issues_certificate'    => ['nullable', 'boolean'],
            'certificate_validity_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'auto_reassign_on_expiry'     => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalise checkboxes to real booleans and slugify if left blank.
        $this->merge([
            'issues_certificate'      => $this->boolean('issues_certificate'),
            'auto_reassign_on_expiry' => $this->boolean('auto_reassign_on_expiry'),
            'slug' => trim((string) $this->input('slug')) !== ''
                ? Str::slug((string) $this->input('slug'))
                : Str::slug((string) $this->input('title')),
        ]);
    }

    /**
     * The validated fields shaped into the `courses` column set. The
     * certificate validity is entered in months and stored as an interval.
     *
     * @return array<string,mixed>
     */
    public function courseFields(): array
    {
        $v = $this->validated();

        $fields = [
            'title'                   => $v['title'],
            'slug'                    => $v['slug'],
            'catalog_status'          => $v['catalog_status'],
            'content_type'            => $v['content_type'],
            'category_id'             => $v['category_id'] ?? null,
            'hero_image_alt'          => $v['hero_image_alt'] ?? null,
            'trailer_url'             => $v['trailer_url'] ?? null,
            'duration_min'            => $v['duration_min'] ?? null,
            'cpd_points'              => $v['cpd_points'] ?? null,
            'cpd_body'                => $v['cpd_body'] ?? null,
            'meta_title'              => $v['meta_title'] ?? null,
            'meta_description'        => $v['meta_description'] ?? null,
            'meta_keywords'           => $v['meta_keywords'] ?? null,
            'issues_certificate'      => (bool) ($v['issues_certificate'] ?? false),
            'auto_reassign_on_expiry' => (bool) ($v['auto_reassign_on_expiry'] ?? false),
        ];

        $months = $v['certificate_validity_months'] ?? null;
        $fields['certificate_validity'] = ($months !== null && $months !== '')
            ? ((int) $months).' months'
            : null;

        return $fields;
    }
}
