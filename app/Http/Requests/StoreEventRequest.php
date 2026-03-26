<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        //return $this->user()?->is_active === true;
        return $this->user() && (bool) $this->user()->is_active;
    }

    public function rules(): array
    {
        
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_group_id' => [
                'required',
                'integer',
                Rule::exists('event_groups', 'id')->where(fn ($q) => $q->where('is_active', 1)),
            ],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'pic' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'leave_type' => ['nullable', 'in:full,half'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['exists:teams,id'],
            'attendance' => ['nullable', 'string'],
        ];
    }
}