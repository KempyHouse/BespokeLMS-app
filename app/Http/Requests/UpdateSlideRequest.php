<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and shapes a single slide's typed payload + completion rule
 * (migration 003 `slides.payload` / `slides.completion_rule`, both jsonb).
 * Authorisation is handled by the `platform.owner` route middleware, so this
 * request authorises any caller that reaches it.
 *
 * The slide's `type` is fixed at creation and passed through as a hidden field
 * so the request only keeps the payload keys that belong to that type.
 */
final class UpdateSlideRequest extends FormRequest
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
            'slide_type'   => ['required', 'in:image_text,video,document'],
            'title'        => ['nullable', 'string', 'max:300'],
            'is_required'  => ['nullable', 'boolean'],

            // image_text
            'image_url'    => ['nullable', 'url', 'max:2048'],
            'image_alt'    => ['nullable', 'string', 'max:500'],
            'body'         => ['nullable', 'string', 'max:20000'],

            // video
            'video_url'    => ['nullable', 'url', 'max:2048'],
            'poster_url'   => ['nullable', 'url', 'max:2048'],
            'transcript'   => ['nullable', 'string', 'max:50000'],

            // document
            'document_url'   => ['nullable', 'url', 'max:2048'],
            'allow_download' => ['nullable', 'boolean'],

            // completion
            'min_view_seconds' => ['nullable', 'integer', 'min:0', 'max:36000'],
            'video_watch_pct'  => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_required'    => $this->boolean('is_required'),
            'allow_download' => $this->boolean('allow_download'),
        ]);
    }

    public function slideType(): string
    {
        return (string) $this->validated()['slide_type'];
    }

    /**
     * The `slides` columns to write.
     *
     * @return array<string,mixed>
     */
    public function slideFields(): array
    {
        $v = $this->validated();

        return [
            'title'           => ($v['title'] ?? '') !== '' ? (string) $v['title'] : null,
            'is_required'     => (bool) ($v['is_required'] ?? false),
            'payload'         => $this->payload(),
            'completion_rule' => $this->completionRule(),
        ];
    }

    /**
     * Type-specific payload, dropping empty values.
     *
     * @return array<string,mixed>
     */
    private function payload(): array
    {
        $v = $this->validated();
        $type = $this->slideType();

        $keysByType = [
            'image_text' => ['image_url', 'image_alt', 'body'],
            'video'      => ['video_url', 'poster_url', 'transcript'],
            'document'   => ['document_url', 'body'],
        ];

        $out = [];
        foreach ($keysByType[$type] ?? [] as $k) {
            $val = $v[$k] ?? null;
            if (is_string($val)) {
                $val = trim($val);
            }
            if ($val !== null && $val !== '') {
                $out[$k] = $val;
            }
        }

        // document download flag is a real boolean, kept explicitly.
        if ($type === 'document') {
            $out['allow_download'] = (bool) ($v['allow_download'] ?? false);
        }

        return $out;
    }

    /**
     * Type-appropriate completion rule, or empty when unset.
     *
     * @return array<string,mixed>
     */
    private function completionRule(): array
    {
        $v = $this->validated();

        if ($this->slideType() === 'video') {
            $pct = $v['video_watch_pct'] ?? null;

            return ($pct !== null && $pct !== '') ? ['video_watch_pct' => (int) $pct] : [];
        }

        $secs = $v['min_view_seconds'] ?? null;

        return ($secs !== null && $secs !== '') ? ['min_view_seconds' => (int) $secs] : [];
    }
}
