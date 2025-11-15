<?php

namespace App\Services;

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportService
{
    public function validate(array $data): ValidationResult
    {
        $errors = [];

        if (! isset($data['version'])) {
            $errors[] = 'Missing version field';
        } elseif ($data['version'] !== '1.0') {
            $errors[] = "Unsupported version: {$data['version']}";
        }

        if (! isset($data['data'])) {
            $errors[] = 'Missing data field';
        } else {
            if (! isset($data['data']['users']) || ! is_array($data['data']['users'])) {
                $errors[] = 'Missing or invalid users array';
            }

            if (! isset($data['data']['teams']) || ! is_array($data['data']['teams'])) {
                $errors[] = 'Missing or invalid teams array';
            }

            if (! isset($data['data']['scheduled_tasks']) || ! is_array($data['data']['scheduled_tasks'])) {
                $errors[] = 'Missing or invalid scheduled_tasks array';
            }
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
        );
    }

    public function preview(array $data): ImportPreview
    {
        $usersToCreate = [];
        $usersToUpdate = [];
        $teamsToCreate = [];
        $teamsToUpdate = [];
        $tasksToCreate = [];
        $tasksToUpdate = [];
        $warnings = [];

        $users = $data['data']['users'] ?? [];
        $teams = $data['data']['teams'] ?? [];
        $tasks = $data['data']['scheduled_tasks'] ?? [];

        foreach ($users as $userData) {
            $existingUser = User::where('username', $userData['username'])->first();
            if ($existingUser) {
                $usersToUpdate[] = $userData['username'];
            } else {
                $usersToCreate[] = $userData['username'];
            }
        }

        foreach ($teams as $teamData) {
            if ($teamData['is_personal']) {
                $user = User::where('username', $teamData['owner_username'])->first();
                if (! $user) {
                    $warnings[] = "Personal team owner '{$teamData['owner_username']}' not found - will skip";

                    continue;
                }

                $existingTeam = Team::where('user_id', $user->id)->first();
                if ($existingTeam) {
                    $teamsToUpdate[] = $teamData['name'];
                } else {
                    $teamsToCreate[] = $teamData['name'];
                }
            } else {
                $existingTeam = Team::where('name', $teamData['name'])
                    ->whereNull('user_id')
                    ->first();

                if ($existingTeam) {
                    $teamsToUpdate[] = $teamData['name'];
                } else {
                    $teamsToCreate[] = $teamData['name'];
                }
            }

            foreach ($teamData['members'] ?? [] as $username) {
                $user = User::where('username', $username)->first();
                if (! $user) {
                    $warnings[] = "User '{$username}' not found for team '{$teamData['name']}' - will skip";
                }
            }
        }

        foreach ($tasks as $taskData) {
            $teamExists = collect($teams)->firstWhere('name', $taskData['team_name']);
            if (! $teamExists) {
                $warnings[] = "Task '{$taskData['name']}' references unknown team '{$taskData['team_name']}' - will skip";

                continue;
            }

            $team = Team::where('name', $taskData['team_name'])->first();
            if ($team) {
                $existingTask = ScheduledTask::where('team_id', $team->id)
                    ->where('name', $taskData['name'])
                    ->first();

                if ($existingTask) {
                    $tasksToUpdate[] = "{$taskData['team_name']} / {$taskData['name']}";
                } else {
                    $tasksToCreate[] = "{$taskData['team_name']} / {$taskData['name']}";
                }
            } else {
                $tasksToCreate[] = "{$taskData['team_name']} / {$taskData['name']}";
            }
        }

        return new ImportPreview(
            usersToCreate: $usersToCreate,
            usersToUpdate: $usersToUpdate,
            teamsToCreate: $teamsToCreate,
            teamsToUpdate: $teamsToUpdate,
            tasksToCreate: $tasksToCreate,
            tasksToUpdate: $tasksToUpdate,
            warnings: $warnings,
        );
    }

    public function execute(array $data): ImportResult
    {
        $usersCreated = 0;
        $usersUpdated = 0;
        $teamsCreated = 0;
        $teamsUpdated = 0;
        $tasksCreated = 0;
        $tasksUpdated = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($data, &$usersCreated, &$usersUpdated, &$teamsCreated, &$teamsUpdated, &$tasksCreated, &$tasksUpdated, &$errors) {
                foreach ($data['data']['users'] as $userData) {
                    $result = $this->importUser($userData);

                    if ($result['created']) {
                        $usersCreated++;
                    } else {
                        $usersUpdated++;
                    }
                }

                $teamMap = [];

                foreach ($data['data']['teams'] as $teamData) {
                    $result = $this->importTeam($teamData);

                    if ($result['team']) {
                        $teamMap[$teamData['name']] = $result['team'];

                        if ($result['created']) {
                            $teamsCreated++;
                        } else {
                            $teamsUpdated++;
                        }
                    }
                }

                foreach ($data['data']['scheduled_tasks'] as $taskData) {
                    if (! isset($teamMap[$taskData['team_name']])) {
                        $errors[] = "Skipped task '{$taskData['name']}' - team '{$taskData['team_name']}' not found";

                        continue;
                    }

                    $result = $this->importTask($taskData, $teamMap[$taskData['team_name']]);

                    if ($result['created']) {
                        $tasksCreated++;
                    } else {
                        $tasksUpdated++;
                    }
                }
            });

            return new ImportResult(
                success: true,
                usersCreated: $usersCreated,
                usersUpdated: $usersUpdated,
                teamsCreated: $teamsCreated,
                teamsUpdated: $teamsUpdated,
                tasksCreated: $tasksCreated,
                tasksUpdated: $tasksUpdated,
                errors: $errors,
            );
        } catch (\Exception $e) {
            return new ImportResult(
                success: false,
                usersCreated: 0,
                usersUpdated: 0,
                teamsCreated: 0,
                teamsUpdated: 0,
                tasksCreated: 0,
                tasksUpdated: 0,
                errors: [$e->getMessage()],
            );
        }
    }

    protected function importUser(array $userData): array
    {
        $user = User::where('username', $userData['username'])->first();

        $userAttributes = [
            'username' => $userData['username'],
            'email' => $userData['email'],
            'forenames' => $userData['forenames'],
            'surname' => $userData['surname'],
            'is_staff' => $userData['is_staff'],
            'is_admin' => $userData['is_admin'],
            'password' => bcrypt(Str::random(32)), // Random password, they'll use SSO
        ];

        if (! $user) {
            $user = User::create($userAttributes);

            return ['created' => true];
        }

        $user->update($userAttributes);

        return ['created' => false];
    }

    protected function importTeam(array $teamData): array
    {
        if ($teamData['is_personal']) {
            $user = User::where('username', $teamData['owner_username'])->first();

            if (! $user) {
                return ['team' => null, 'created' => false];
            }

            $team = Team::where('user_id', $user->id)->first();

            if (! $team) {
                $team = Team::create([
                    'name' => $teamData['name'],
                    'slug' => $this->generateUniqueSlug($teamData['slug']),
                    'user_id' => $user->id,
                ]);

                $team->users()->attach($user);

                return ['team' => $team, 'created' => true];
            }

            $this->syncTeamMembers($team, $teamData['members']);

            return ['team' => $team, 'created' => false];
        }

        $team = Team::where('name', $teamData['name'])
            ->whereNull('user_id')
            ->first();

        if (! $team) {
            $team = Team::create([
                'name' => $teamData['name'],
                'slug' => $this->generateUniqueSlug($teamData['slug']),
                'user_id' => null,
            ]);

            $this->syncTeamMembers($team, $teamData['members']);

            return ['team' => $team, 'created' => true];
        }

        $this->syncTeamMembers($team, $teamData['members']);

        return ['team' => $team, 'created' => false];
    }

    protected function importTask(array $taskData, Team $team): array
    {
        $createdByUser = null;
        if (isset($taskData['created_by_username'])) {
            $createdByUser = User::where('username', $taskData['created_by_username'])->first();
        }

        $task = ScheduledTask::where('team_id', $team->id)
            ->where('name', $taskData['name'])
            ->first();

        $taskAttributes = [
            'team_id' => $team->id,
            'name' => $taskData['name'],
            'description' => $taskData['description'],
            'schedule_type' => $taskData['schedule_type'],
            'schedule_value' => $taskData['schedule_value'],
            'timezone' => $taskData['timezone'],
            'grace_period_minutes' => $taskData['grace_period_minutes'],
            'status' => 'pending',
            'created_by' => $createdByUser?->id,
            'last_checked_in_at' => null,
            'next_expected_at' => null,
        ];

        if (! $task) {
            $taskAttributes['unique_check_in_token'] = (string) Str::uuid();
            $task = ScheduledTask::create($taskAttributes);

            return ['created' => true];
        }

        $taskAttributes['unique_check_in_token'] = (string) Str::uuid();
        $task->update($taskAttributes);

        return ['created' => false];
    }

    protected function syncTeamMembers(Team $team, array $usernames): void
    {
        $users = User::whereIn('username', $usernames)->get();
        $team->users()->sync($users->pluck('id'));
    }

    protected function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (Team::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
