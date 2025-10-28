<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class SilenceTeamRequest extends FormRequest
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
            'silenced_until' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function silencedUntil(): ?Carbon
    {
        $value = $this->validated('silenced_until');

        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->setTimezone('UTC');
    }
}
