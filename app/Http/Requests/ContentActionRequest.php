<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a single content-builder action (add / rename / delete / move a
 * module, lesson or slide). Authorisation is handled by the `platform.owner`
 * route middleware, so this request authorises any caller that reaches it.
 */
final class ContentActionRequest extends FormRequest
{
    public const ACTIONS = [
        'add_module', 'rename_module', 'delete_module', 'move_module',
        'add_lesson', 'rename_lesson', 'delete_lesson', 'move_lesson',
        'add_slide', 'rename_slide', 'delete_slide', 'move_slide',
    ];

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
            'action'     => ['required', 'in:'.implode(',', self::ACTIONS)],
            'id'         => ['nullable', 'uuid'],
            'parent_id'  => ['nullable', 'uuid'],
            'title'      => ['nullable', 'string', 'max:300'],
            'slide_type' => ['nullable', 'in:image_text,video,document'],
            'direction'  => ['nullable', 'in:up,down'],
        ];
    }

    public function action(): string
    {
        return (string) $this->validated()['action'];
    }

    public function id(): ?string
    {
        $v = $this->validated()['id'] ?? null;

        return ($v !== null && $v !== '') ? (string) $v : null;
    }

    public function parentId(): ?string
    {
        $v = $this->validated()['parent_id'] ?? null;

        return ($v !== null && $v !== '') ? (string) $v : null;
    }

    public function title(): string
    {
        return trim((string) ($this->validated()['title'] ?? ''));
    }

    public function slideType(): string
    {
        $v = (string) ($this->validated()['slide_type'] ?? '');

        return $v !== '' ? $v : 'image_text';
    }

    public function direction(): string
    {
        return (string) ($this->validated()['direction'] ?? 'up');
    }
}
