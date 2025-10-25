<?php

namespace App\Http\Requests;

use App\Rules\MaxJsonSize;
use Illuminate\Foundation\Http\FormRequest;

class RecordPingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => [
                'nullable',
                'array',
                new MaxJsonSize(2048),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawData = $this->input('data');

        if (is_string($rawData)) {
            $decoded = json_decode($rawData, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['data' => $decoded]);

                return;
            }
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data.array' => 'The data payload must be valid JSON.',
        ];
    }
}
