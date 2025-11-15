<?php

use App\Models\ScheduledTask;
use App\Models\Team;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('exports all teams and tasks for authenticated user', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Test Team']);
    $user->teams()->attach($team);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Test Task',
        'created_by' => $user->id,
    ]);

    Auth::login($user);

    $exportService = new ExportService;
    $data = $exportService->export();

    expect($data)->toHaveKey('version', '1.0')
        ->and($data)->toHaveKey('exported_by', $user->username)
        ->and($data)->toHaveKey('exported_at')
        ->and($data)->toHaveKey('data')
        ->and($data['data'])->toHaveKey('teams')
        ->and($data['data'])->toHaveKey('scheduled_tasks')
        ->and($data['data']['teams'])->toHaveCount(2) // Personal team + test team
        ->and($data['data']['scheduled_tasks'])->toHaveCount(1)
        ->and($data['data']['scheduled_tasks'][0]['name'])->toBe('Test Task')
        ->and($data['data']['scheduled_tasks'][0]['team_name'])->toBe('Test Team');
});

it('includes personal team in export', function () {
    $user = User::factory()->create(['username' => 'billy']);
    Auth::login($user);

    $exportService = new ExportService;
    $data = $exportService->export();

    $personalTeam = collect($data['data']['teams'])->firstWhere('is_personal', true);

    expect($personalTeam)->not->toBeNull()
        ->and($personalTeam['name'])->toBe('billy')
        ->and($personalTeam['owner_username'])->toBe('billy')
        ->and($personalTeam['members'])->toContain('billy');
});

it('exports team with multiple members', function () {
    $user1 = User::factory()->create(['username' => 'user1']);
    $user2 = User::factory()->create(['username' => 'user2']);

    $team = Team::factory()->create(['name' => 'Shared Team']);
    $team->users()->attach([$user1->id, $user2->id]);

    Auth::login($user1);

    $exportService = new ExportService;
    $data = $exportService->export();

    $sharedTeam = collect($data['data']['teams'])->firstWhere('name', 'Shared Team');

    expect($sharedTeam)->not->toBeNull()
        ->and($sharedTeam['is_personal'])->toBeFalse()
        ->and($sharedTeam['members'])->toContain('user1')
        ->and($sharedTeam['members'])->toContain('user2');
});

it('exports task with all relevant fields', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    $task = ScheduledTask::factory()->create([
        'team_id' => $team->id,
        'name' => 'Backup Database',
        'description' => 'Daily backup',
        'schedule_type' => 'cron',
        'schedule_value' => '0 3 * * *',
        'timezone' => 'America/New_York',
        'grace_period_minutes' => 15,
        'status' => 'ok',
        'created_by' => $user->id,
    ]);

    Auth::login($user);

    $exportService = new ExportService;
    $data = $exportService->export();

    $exportedTask = $data['data']['scheduled_tasks'][0];

    expect($exportedTask['name'])->toBe('Backup Database')
        ->and($exportedTask['description'])->toBe('Daily backup')
        ->and($exportedTask['schedule_type'])->toBe('cron')
        ->and($exportedTask['schedule_value'])->toBe('0 3 * * *')
        ->and($exportedTask['timezone'])->toBe('America/New_York')
        ->and($exportedTask['grace_period_minutes'])->toBe(15)
        ->and($exportedTask['status'])->toBe('ok')
        ->and($exportedTask['created_by_username'])->toBe($user->username);
});

it('can download export via livewire component', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    ScheduledTask::factory()->count(3)->create(['team_id' => $team->id]);

    actingAs($user)
        ->get(route('import-export.export'))
        ->assertSuccessful()
        ->assertSeeLivewire(\App\Livewire\ImportExport\Export::class);
});
