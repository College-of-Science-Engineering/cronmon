<?php

namespace App\Services;

class ImportPreview
{
    public function __construct(
        public array $usersToCreate,
        public array $usersToUpdate,
        public array $teamsToCreate,
        public array $teamsToUpdate,
        public array $tasksToCreate,
        public array $tasksToUpdate,
        public array $warnings,
    ) {}
}
