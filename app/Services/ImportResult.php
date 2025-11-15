<?php

namespace App\Services;

class ImportResult
{
    public function __construct(
        public bool $success,
        public int $usersCreated,
        public int $usersUpdated,
        public int $teamsCreated,
        public int $teamsUpdated,
        public int $tasksCreated,
        public int $tasksUpdated,
        public array $errors = [],
    ) {}
}
