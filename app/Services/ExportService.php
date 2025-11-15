<?php

namespace App\Services;

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ExportService
{
    public function export(): array
    {
        $user = Auth::user();

        $users = User::all();
        $teams = Team::with(['users', 'user'])->get();
        $scheduledTasks = ScheduledTask::with(['team', 'creator'])->get();

        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'exported_by' => $user->username,
            'data' => [
                'users' => $users->map(function ($user) {
                    return [
                        'username' => $user->username,
                        'email' => $user->email,
                        'forenames' => $user->forenames,
                        'surname' => $user->surname,
                        'is_staff' => $user->is_staff,
                        'is_admin' => $user->is_admin,
                    ];
                })->values()->toArray(),
                'teams' => $teams->map(function ($team) {
                    return [
                        'name' => $team->name,
                        'slug' => $team->slug,
                        'is_personal' => $team->isPersonalTeam(),
                        'owner_username' => $team->user?->username,
                        'members' => $team->users->pluck('username')->toArray(),
                    ];
                })->values()->toArray(),
                'scheduled_tasks' => $scheduledTasks->map(function ($task) {
                    return [
                        'team_name' => $task->team->name,
                        'name' => $task->name,
                        'description' => $task->description,
                        'schedule_type' => $task->schedule_type,
                        'schedule_value' => $task->schedule_value,
                        'timezone' => $task->timezone,
                        'grace_period_minutes' => $task->grace_period_minutes,
                        'status' => $task->status,
                        'created_by_username' => $task->creator?->username,
                    ];
                })->values()->toArray(),
            ],
        ];

        return $exportData;
    }
}
