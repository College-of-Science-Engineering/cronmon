<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxJsonSize implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected int $maxBytes
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $fail('The :attribute must be valid JSON.');

            return;
        }

        if (strlen($json) > $this->maxBytes) {
            $fail("The :attribute field must not exceed {$this->maxBytes} bytes.");
        }
    }
}
