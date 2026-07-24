<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by platform.owner middleware in route
        return true;
    }

    /**
     * @return array<string,array<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'aims' => ['required', 'string', 'max:2000'],
            'aims_short' => ['nullable', 'string', 'max:500'],
            'objectives_short' => ['nullable', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:courses,slug,'.$this->route('course')],
            'action' => ['required', 'in:save_draft,publish'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required.',
            'description.required' => 'Course description is required.',
            'aims.required' => 'Course aims are required.',
            'action.required' => 'Action is required (save_draft or publish).',
            'action.in' => 'Invalid action.',
        ];
    }
}
