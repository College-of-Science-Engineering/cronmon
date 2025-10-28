<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduledTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'schedule_type' => ['required', Rule::in(['simple', 'cron'])],
            'schedule_value' => [
                'required',
                'string',
                Rule::when(
                    fn () => $this->input('schedule_type') === 'simple',
                    Rule::in(StoreScheduledTaskRequest::SIMPLE_SCHEDULE_PRESETS)
                ),
                Rule::when(
                    fn () => $this->input('schedule_type') === 'cron',
                    'max:255'
                ),
            ],
            'timezone' => ['required', 'string', 'timezone:all'],
            'grace_period_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
