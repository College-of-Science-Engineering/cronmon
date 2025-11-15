<?php

namespace App\Services;

class ValidationResult
{
    public function __construct(
        public bool $valid,
        public array $errors = [],
    ) {}
}
