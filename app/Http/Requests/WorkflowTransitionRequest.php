<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a workflow transition request. The action must be one the current
 * state actually allows — that is checked in the controller against the
 * data-driven `workflow_transitions` rows; this only validates the shape.
 * Authorisation is handled by the `platform.owner` route middleware.
 */
final class WorkflowTransitionRequest extends FormRequest
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
            'action'  => ['required', 'string', 'max:60'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function action(): string
    {
        return (string) $this->validated()['action'];
    }

    public function comment(): ?string
    {
        $c = trim((string) ($this->validated()['comment'] ?? ''));

        return $c !== '' ? $c : null;
    }
}
