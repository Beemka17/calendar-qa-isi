<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'event_group_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('event_groups', 'id')->where(fn ($q) => $q->where('is_active', 1)),
            ],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'required', 'date', 'after_or_equal:start_at'],

            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pic' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}